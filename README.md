# Projet Cloud S5 — Backend PHP MVC minimal

Prérequis: Docker et Docker Compose installés.

Démarrer les services:

```bash
docker compose up --build
```

L'API sera disponible sur http://localhost:8000
La documentation Swagger sera visible sur http://localhost:8080

Base de données: MySQL (port 3306)

Structure minimale:
- `src/public` : point d'entrée
- `src/app/controllers` : controllers
- `src/app/core` : classes utilitaires (DB, Router)
- `openapi.yaml` : documentation OpenAPI