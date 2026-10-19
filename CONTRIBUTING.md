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

## 🚀 Areas for Contribution
If you're looking to get involved, we're currently prioritizing the following areas:
- **[Setup Google Analytics]**: Set up tracking for user engagement and website traffic.
- **[Optimise website SEO]**: Enhance on-page SEO elements and improve the meta description for better search engine visibility.
- **[Setup RabbitMQ]**: Set up RabbitMQ for message queuing and event-driven architecture.
- **[CI CD]**: Set up CI/CD for automated testing and deployment.
- **[Setup AI marketing]**: AI marketing toolset - create AI-driven post suggestions, captions, and targeted content for user engagement. This is targeted to alternative platforms to draw an audience to our app.
- **[Build python APIs for content moderation]**: Implement robust AI-powered content moderation using Python to detect and filter inappropriate content, ensuring a safe user environment.
