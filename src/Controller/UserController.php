<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/api/users', name: 'create_user', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Obtenez l'utilisateur actuellement connecté
        $currentUser = $this->getUser();

        // Vérifiez que l'utilisateur est connecté
        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Vérifiez si l'utilisateur connecté a le rôle ROLE_PATRON
        if (!in_array('ROLE_PATRON', $currentUser->getRoles(), true)) {
            // Si l'utilisateur n'est pas un patron, il peut seulement créer des utilisateurs avec ROLE_USER
            $roles = $data['roles'] ?? [];
            if (array_intersect($roles, ['ROLE_BARMAN', 'ROLE_SERVEUR'])) {
                return new JsonResponse(['error' => 'Only patrons can assign ROLE_BARMAN or ROLE_SERVEUR.'], JsonResponse::HTTP_FORBIDDEN);
            }
        }

        // Validation des rôles
        $roles = $data['roles'] ?? [];
        if (empty($roles) || !in_array('ROLE_USER', $roles, true)) {
            return new JsonResponse(['error' => 'Invalid roles. Users must have at least ROLE_USER.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Création de l'utilisateur
        $user = new User();
        $user->setUsername($data['username']);
        $user->setRoles($roles);
        $plainPassword = $data['plainPassword'];
        
        // Hachage du mot de passe
        if ($plainPassword) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // On utilise les groupes de sérialisation pour ne pas exposer le mot de passe
        return $this->json($user, JsonResponse::HTTP_CREATED, [], ['groups' => ['read']]);
    }

    #[Route('/api/users', name: 'list_users', methods: ['GET'])]
    public function listUsers(): JsonResponse
    {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        if (in_array('ROLE_PATRON', $currentUser->getRoles(), true)) {
            $users = $this->entityManager->getRepository(User::class)->findByRoles(['ROLE_BARMAN', 'ROLE_SERVEUR', 'ROLE_USER']
            );
        } else {
            $users = $this->entityManager->getRepository(User::class)->findByRole('ROLE_USER');
        }

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles()
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/user/{id}', name: 'find_user', methods: ['GET'])]
    public function FindUser(User $askedUser = null): JsonResponse
    {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }
        if ($askedUser === null) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        if (in_array('ROLE_PATRON', $currentUser->getRoles(), true)) {
            return new JsonResponse([
                'id' => $askedUser->getId(),
                'username' => $askedUser->getUsername(),
                'roles' => $askedUser->getRoles()
            ]);
        } elseif ($askedUser->getRoles() === ['ROLE_USER']) {
            return new JsonResponse([
                'id' => $askedUser->getId(),
                'username' => $askedUser->getUsername(),
                'roles' => $askedUser->getRoles()
            ]);
        }
        return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
    }


    #[Route('/api/users/{id}', name: 'delete_user', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        $currentUser = $this->getUser();

        if (!$currentUser || !in_array('ROLE_PATRON', $currentUser->getRoles(), true)) {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user || !in_array('ROLE_BARMAN', $user->getRoles(), true) && !in_array('ROLE_SERVEUR', $user->getRoles(), true)) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'User deleted successfully'], JsonResponse::HTTP_OK);
    }
}
