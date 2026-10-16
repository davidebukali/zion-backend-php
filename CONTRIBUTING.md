# Contributing to Zion Backend

Thank you for your interest in contributing to **Zion Backend**! We welcome contributions that align with our mission of creating a clean, high-performance, and modular social platform.

---

## 🛠️ Development Setup

The fastest way to get started is with Docker Compose:

```bash
# 1. Clone repository
git clone https://github.com/davidebukali/zion-backend-php.git
cd zion-backend-php

# 2. Setup environment
cp .env.docker.example .env

# 3. Start services
docker compose up -d --build

# 4. Generate application key
docker compose exec app php artisan key:generate

# 5. Run migrations
docker compose exec app php artisan migrate
```

The application will be available at `http://localhost`.

---

## 🌿 Branching Strategy

- Always create your feature branch from the latest `main` branch.
- Use descriptive branch names prefixed by change type:
  - `feature/user-bookmarks`
  - `fix/notification-query-n-plus-one`
  - `docs/update-endpoints-table`
  - `refactor/posts-visibility-service`

---

## 📝 Commit Conventions

We follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

- `feat:` A new feature or endpoint
- `fix:` A bug fix
- `docs:` Documentation-only changes
- `style:` Code style/formatting changes that do not affect code logic
- `refactor:` Code refactoring that neither fixes a bug nor adds a feature
- `test:` Adding or updating tests
- `chore:` Build process, dependency updates, or auxiliary tool changes

**Examples:**
```bash
git commit -m "feat(posts): add cursor pagination to user posts sub-resource"
git commit -m "fix(socialgraph): prevent duplicate feed items during backfill"
```

---

## 🎨 Code Style (Laravel Pint)

All PHP code must conform to Laravel code style standards. Run Pint before committing:

```bash
# Check code style without modifying files
docker compose exec app ./vendor/bin/pint --test

# Automatically fix code style
docker compose exec app ./vendor/bin/pint
```

---

## 🧪 Testing Guidelines

Every new feature or bug fix must be accompanied by comprehensive tests:

```bash
# Run the complete test suite
docker compose exec app php artisan test

# Run tests for a specific module
docker compose exec app php artisan test Modules/Posts
docker compose exec app php artisan test Modules/SocialGraph
```

---

## 📖 API Documentation Updates

If your changes modify API routes, controller docstrings, request validation rules, or response shapes, re-generate the **Scribe** documentation:

```bash
docker compose exec app php artisan scribe:generate
```

---

## 🚀 Submitting a Pull Request

1. Push your branch to your GitHub fork:
   ```bash
   git push origin feature/your-feature-name
   ```
2. Open a Pull Request targeting the `main` branch.
3. Fill out the provided Pull Request template completely.
4. Ensure all CI checks and automated tests pass.
5. Address any review comments or suggestions.
