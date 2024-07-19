# Groups Project

## Authors
- Thomas FILHOL
- Adam AMMAR

## Installation

### 1. Navigate to the project directory
```bash
cd custom-api-bar
```
### 2. Install libraries
```bash
composer install
```


### 3. Configure the database
All data are set in .env. Change just the database line to correspond to your database:

```bash
DATABASE_URL="mysql://user:password@your-localhost/databasename?charset=utf8mb4"
```

### 4. Create the database and migrate it
```bash
php bin/console doctrine:database:create
```

```bash
php bin/console make:migration
```

```bash
php bin/console doctrine:migrations:migrate
```

### Run
Start the Symfony development server

```bash
symfony serve
```

### Information pour Yoann COUALAN
Les routes normalment accessible uniquement par le ROLE_PATRON ont été faites de manière à être accéssible via les autres rôles mais en affichant uniquement les informations disponible pour eux (celle du ROLE_USER plutôt que de tous les ROLES que peut voir le patron)
