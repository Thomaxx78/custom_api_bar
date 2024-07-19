<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;

#[ApiResource()]
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ApiResource(
    operations: [
        new Get(
            security: "is_granted('ROLE_PATRON')",
            securityMessage: "Only patrons can see their orders"
        ),
        new GetCollection(
            security: "is_granted('ROLE_PATRON')",
            securityMessage: "Only patrons can see their orders"
        ),
        new Post(
            security: "is_granted('ROLE_SERVEUR')"
        ),
        new Put(
            security: "is_granted('ROLE_SERVEUR') or is_granted('ROLE_BARMAN')"
        ),
        new Delete(
            security: "is_granted('ROLE_PATRON')"
        ),
    ]
)]
#[ApiFilter(DateFilter::class, properties: ['createdDate'])]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Drink>
     */
    #[ORM\ManyToMany(targetEntity: Drink::class, inversedBy: 'orders')]
    private Collection $drinks;

    #[ORM\Column]
    private ?float $tableNumber = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?User $waiter = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?User $barman = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    public function __construct()
    {
        $this->drinks = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, Drink>
     */
    public function getDrinks(): Collection
    {
        return $this->drinks;
    }

    public function addDrink(Drink $drink): static
    {
        if (!$this->drinks->contains($drink)) {
            $this->drinks->add($drink);
        }

        return $this;
    }

    public function removeDrink(Drink $drink): static
    {
        $this->drinks->removeElement($drink);

        return $this;
    }

    public function getTableNumber(): ?float
    {
        return $this->tableNumber;
    }

    public function setTableNumber(float $tableNumber): static
    {
        $this->tableNumber = $tableNumber;

        return $this;
    }

    public function getWaiter(): ?User
    {
        return $this->waiter;
    }

    public function setWaiter(?User $waiter): static
    {
        $this->waiter = $waiter;

        return $this;
    }

    public function getBarman(): ?User
    {
        return $this->barman;
    }

    public function setBarman(?User $barman): static
    {
        $this->barman = $barman;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
