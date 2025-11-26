# 🏢 ERP Inventory Management

Système ERP de gestion d'inventaire développé avec Symfony 6+ et PostgreSQL.

## 🚀 Technologies

- Symfony 6+
- PostgreSQL 15
- Docker & Docker Compose
- JWT Authentication

## 📦 Installation
```bash
# Clone le projet
git clone https://github.com/TON_USERNAME/erp-inventory-management.git
cd erp-inventory-management

# Lance Docker
docker-compose up -d

# Installe les dépendances
composer install

# Configure l'environnement
cp .env .env.local
# Édite .env.local avec tes paramètres

# Crée la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## 🔧 Configuration

- PostgreSQL accessible sur `localhost:5432`
- Base de données : `erp_db`
- Utilisateur : `erp_user`

## 📅 Roadmap

- [x] Setup Symfony + Docker
- [x] Configuration PostgreSQL
- [x] Entité User
- [x] Authentification JWT
- [x] CRUD Produits
- [x] CRUD Inventaires
- [ ] Dashboard KPIs
- [ ] Exports Excel
