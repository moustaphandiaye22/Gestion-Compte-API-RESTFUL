# Gestion Compte API RESTFUL

Une API REST complète pour la gestion des comptes bancaires développée avec Laravel 10. Cette API permet de gérer les comptes clients, les transactions, et inclut des fonctionnalités avancées comme le blocage automatique des comptes et l'archivage.

## 🚀 Fonctionnalités

- ✅ **Gestion des comptes bancaires** : Création, lecture, mise à jour et suppression des comptes
- ✅ **Gestion des clients** : Système complet de gestion des clients avec validation CNI et téléphone sénégalais
- ✅ **Transactions financières** : Suivi des dépôts, retraits et virements
- ✅ **Blocage automatique** : Système de blocage temporaire des comptes avec dates de début/fin
- ✅ **Archivage automatique** : Archivage programmé des comptes bloqués expirés
- ✅ **Notifications** : Envoi d'emails et SMS (Twilio) lors de la création de comptes
- ✅ **API RESTful** : Architecture REST complète avec ressources et collections
- ✅ **Documentation Swagger** : Documentation interactive de l'API
- ✅ **Authentification** : Laravel Sanctum pour la sécurité
- ✅ **Docker** : Conteneurisation complète pour le développement et le déploiement

## 🛠️ Technologies Utilisées

- **Framework** : Laravel 10.x
- **PHP** : 8.1+
- **Base de données** : PostgreSQL 15
- **Authentification** : Laravel Sanctum
- **Documentation** : Swagger/OpenAPI (darkaonline/swagger-lume)
- **SMS** : Twilio SDK
- **Conteneurisation** : Docker & Docker Compose
- **Tests** : PHPUnit & Pest
- **UUID** : ramsey/uuid pour les identifiants uniques

## 📋 Prérequis

- Docker & Docker Compose
- PHP 8.1 ou supérieur
- Composer
- Git

## 🚀 Installation & Configuration

### 1. Clonage du projet

```bash
git clone <repository-url>
cd Gestion-Compte-API-RESTFUL
```

### 2. Configuration Docker

Le projet utilise Docker pour l'environnement de développement. Un `docker-compose.yml` est fourni avec :

- **App Laravel** : Serveur PHP-FPM sur le port 8000
- **PostgreSQL** : Base de données sur le port 5432
- **Volumes** : Persistance des données PostgreSQL

### 3. Variables d'environnement

Copiez le fichier `.env.example` vers `.env` et configurez les variables suivantes :

```env
# Base de données
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=password

# Application
APP_NAME="Gestion Compte API"
APP_ENV=local
APP_KEY=base64:your-app-key
APP_DEBUG=true
APP_URL=http://localhost:8000

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:8000

# Mail (pour les notifications)
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com

# Twilio (pour les SMS)
TWILIO_SID=your-twilio-sid
TWILIO_TOKEN=your-twilio-token
TWILIO_PHONE_NUMBER=+1234567890

# Swagger
SWAGGER_LUME_CONST_HOST=http://localhost:8000
```

### 4. Lancement avec Docker

```bash
# Construction et lancement des conteneurs
docker-compose up -d --build

# Installation des dépendances PHP
docker-compose exec app composer install

# Génération de la clé d'application
docker-compose exec app php artisan key:generate

# Exécution des migrations
docker-compose exec app php artisan migrate

# (Optionnel) Exécution des seeders pour données de test
docker-compose exec app php artisan db:seed

# Génération de la documentation Swagger
docker-compose exec app php artisan swagger-lume:generate
```

### 5. Accès à l'application

- **API** : http://localhost:8000
- **Documentation Swagger** : http://localhost:8000/docs
- **Base de données** : localhost:5432 (accessible depuis l'extérieur si configuré)

## 📊 Modèle de Données

### Clients (`clients`)
- `id` : UUID (clé primaire)
- `prenom` : Prénom
- `nom` : Nom
- `cni` : Numéro CNI (13 chiffres)
- `telephone` : Téléphone sénégalais (+221XXXXXXXXX, 00221XXXXXXXXX, ou XXXXXXXXX)
- `email` : Email unique
- `adresse` : Adresse
- `created_at`, `updated_at` : Timestamps
- `deleted_at` : Soft delete

### Comptes (`comptes`)
- `id` : UUID (clé primaire)
- `numeroCompte` : Numéro unique généré automatiquement (CPT-YYYY-XXXXXX)
- `titulaire` : Nom complet du client
- `type` : Enum ('Epargne', 'Cheque')
- `devise` : Devise (défaut: 'FCFA')
- `dateCreation` : Date de création
- `statut` : Enum ('Actif', 'Bloque', 'Ferme', 'Supprime')
- `dateFermeture` : Date de fermeture (nullable)
- `date_debut_blocage` : Date début blocage (nullable)
- `date_fin_blocage` : Date fin blocage (nullable)
- `metadata` : JSON pour données supplémentaires
- `client_id` : UUID (clé étrangère vers clients)

### Transactions (`transactions`)
- `id` : UUID (clé primaire)
- `numeroCompte` : Numéro du compte
- `type` : Enum ('Depot', 'Retrait', 'Virement')
- `montant` : Decimal (15,2)
- `dateTransaction` : DateTime
- `description` : Description (nullable)
- `statut` : Enum ('En attente', 'Validee', 'Annulee', 'Archivee')
- `compte_id` : UUID (clé étrangère vers comptes)

## 🔐 Contraintes et Règles Métier

### Comptes
- **Solde initial minimum** : 10,000 FCFA
- **Types autorisés** : 'Cheque', 'Epargne'
- **Statuts** : 'Actif', 'Bloque', 'Ferme', 'Supprime'
- **Numéro de compte** : Format automatique CPT-YYYY-XXXXXX

### Clients
- **Téléphone sénégalais** : Format international ou local avec préfixes valides :
  - Préfixes autorisés : 30, 33, 70, 72, 75, 76, 77, 78
  - Formats acceptés : +221XXXXXXXXX, 00221XXXXXXXXX, ou XXXXXXXXX
  - Regex : `/^(?:\+221|00221)?(?:30|33|70|72|75|76|77|78)\d{7}$/`
- **CNI** : Numéro de Carte Nationale d'Identité :
  - Format : Exactement 13 chiffres numériques
  - Regex : `/^\d{13}$/`
- **Email** : Doit être unique dans le système

### Transactions
- **Montants** : Valeurs décimales positives
- **Statuts** : 'En attente', 'Validee', 'Annulee', 'Archivee'

### Blocage de comptes
- **Durée maximale** : Non spécifiée, mais gérée par dates
- **Archivage automatique** : Comptes bloqués expirés sont archivés à 01h00
- **Désarchivage automatique** : Comptes débloqués à 02h00

## 📡 API Endpoints

### Comptes (`/api/v1/comptes`)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/v1/comptes` | Liste des comptes avec filtrage et pagination |
| POST | `/api/v1/comptes` | Création d'un nouveau compte |
| GET | `/api/v1/comptes/{id}` | Détails d'un compte spécifique |
| PUT | `/api/v1/comptes/{id}` | Mise à jour d'un compte |
| DELETE | `/api/v1/comptes/{id}` | Suppression d'un compte |
| GET | `/api/v1/comptes-archives` | Liste des comptes archivés (cloud) |
| POST | `/api/v1/comptes/{id}/bloquer` | Blocage d'un compte |

### Paramètres de filtrage (GET /comptes)

- `type` : 'Cheque' ou 'Epargne'
- `statut` : 'Actif', 'Bloque', 'Ferme'
- `search` : Recherche par titulaire ou numéro
- `devise` : Filtrage par devise
- `date_from` : Date de création minimale
- `date_to` : Date de création maximale
- `solde_min` : Solde minimum
- `solde_max` : Solde maximum
- `sort` : Champ de tri
- `order` : 'asc' ou 'desc'
- `limit` : Nombre d'éléments par page (max 100)

## 📝 Exemples d'utilisation

### Création d'un compte

```bash
curl -X POST http://localhost:8000/api/v1/comptes \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "type": "Cheque",
    "soldeInitial": 50000,
    "devise": "FCFA",
      "client": {
        "titulaire": "Mamadou Diop",
        "nci": "1234567890123",
        "email": "mamadou.diop@example.com",
        "telephone": "+221771234567",
        "adresse": "Dakar, Sénégal"
      }
  }'
```

### Liste des comptes avec filtrage

```bash
curl -X GET "http://localhost:8000/api/v1/comptes?type=Cheque&statut=Actif&limit=10" \
  -H "Accept: application/json"
```

### Blocage d'un compte

```bash
curl -X POST http://localhost:8000/api/v1/comptes/{compte-id}/bloquer \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "date_debut": "2024-01-15",
    "date_fin": "2024-01-30",
    "motif": "Suspicion de fraude"
  }'
```

## 🔄 Tâches Programmées

Le système exécute automatiquement les tâches suivantes :

- **Archivage des comptes bloqués** : Tous les jours à 01h00
- **Désarchivage des comptes débloqués** : Tous les jours à 02h00

Ces tâches sont configurées dans `app/Console/Kernel.php`.

## 🧪 Tests

### Exécution des tests

```bash
# Tests unitaires et fonctionnels
docker-compose exec app php artisan test

# Tests avec couverture
docker-compose exec app php artisan test --coverage
```

### Tests disponibles

- `tests/Feature/CompteApiTest.php` : Tests API des comptes
- `tests/Feature/CompteModelTest.php` : Tests du modèle Compte
- `tests/Feature/TransactionModelTest.php` : Tests du modèle Transaction

## 📚 Documentation API

La documentation complète de l'API est disponible via Swagger UI :

- **URL** : http://localhost:8000/docs
- **JSON** : http://localhost:8000/docs-json

## 🔒 Sécurité

- **Authentification** : Laravel Sanctum pour les tokens API
- **Validation** : Règles strictes sur les données d'entrée
- **Soft Delete** : Suppression logique des clients
- **Logs** : Suivi des erreurs et activités importantes

## 🚀 Déploiement

### Production avec Docker

```bash
# Construction de l'image de production
docker build -t gestion-compte-api .

# Lancement en production
docker run -d \
  --name gestion-compte-api \
  -p 8000:8000 \
  --env-file .env.production \
  gestion-compte-api
```

### Variables d'environnement production

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
DB_HOST=your-production-db-host
# ... autres variables de production
```

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📝 License

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.


---

**Développé par  MOUSTAPHA NDIAYE**
