# EcoRide (PHP/MySQL) — Starter ultra-minimal pour ECF

## TL;DR
1. Installe **Docker** et **Docker Compose**.
2. Dézippe ce dossier.
3. Dans ce dossier, exécute :

```bash
docker-compose up -d
```

4. Ouvre **http://localhost:8080**.

## Comptes de test (à créer toi-même)
- **Conducteur** : s'inscrire avec rôle "driver" puis publier un trajet.
- **Passager** : s'inscrire avec rôle "passenger" puis rechercher et réserver.

## Fonctionnalités incluses
- Inscription / connexion (sessions + password_hash)
- Publier un trajet (driver)
- Rechercher des trajets
- Réserver 1 place (décrément du nombre de places, 1 résa par utilisateur/traject)
- Dashboard simple (trajets publiés / réservations)

## Structure
- `docker-compose.yml` : services PHP-Apache + MySQL
- `sql/init.sql` : schéma de base
- `src/public/*.php` : pages web (DocumentRoot)
- `src/config/db.php` : connexion PDO
- `src/models/*.php` : modèles
- `src/lib/auth.php` : session / helpers

## Commandes utiles
- Lancer : `docker-compose up -d`
- Logs : `docker-compose logs -f web`
- Arrêter : `docker-compose down`

Bonne démo ✨
