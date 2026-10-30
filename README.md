# Zion Backend (PHP)

<p align="left">
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-%3E%3D%208.4-777bb4?logo=php&logoColor=white" alt="PHP Version"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="License: MIT"></a>
  <a href="docker-compose.yml"><img src="https://img.shields.io/badge/Docker-ready-2496ED?logo=docker&logoColor=white" alt="Docker Ready"></a>
  <a href="https://github.com/davidebukali/zion-backend-php/actions"><img src="https://github.com/davidebukali/zion-backend-php/actions/workflows/deploy.yml/badge.svg" alt="CI/CD"></a>
</p>

Zion Backend is a clean, modular REST API built with **Laravel 13** and **PHP 8.4+**. It leverages a decoupled modular architecture to isolate domain concerns, ensuring scalability, high performance, and ease of maintenance.

---

## 📑 Table of Contents

- [Key Features](#-key-features)
- [Modules Overview](#-modules-overview)
- [API Endpoints Summary](#-api-endpoints-summary)
  - [Authentication & Profiles](#authentication--profiles-modulesauth)
  - [Posts](#posts-modulesposts)
  - [Social Graph & Feeds](#social-graph--feeds-modulessocialgraph)
  - [Unified Search](#unified-search-modulessearch)
  - [Notifications](#notifications-modulesnotifications)
  - [Interactions & Comments](#interactions--comments-modulesinteractions-modulescomments)
  - [Media](#media-modulesmedia)
- [Technology Stack](#️-technology-stack)
- [Queue & Background Workers](#-queue-background-workers)
- [API Documentation](#-api-documentation)
- [Setup & Running the Application](#️-setup-running-the-application)
  - [Running with Docker (Recommended)](#-running-with-docker-recommended)
  - [Local (Non-Docker) Setup](#-local-non-docker-setup)
- [Automated Testing & CI/CD](#-automated-testing--cicd)
  - [GitHub Actions CI Pipeline](#-github-actions-ci-pipeline)
  - [Running Tests Locally](#-running-tests-locally)
  - [Production Deployment Pipeline](#-production-deployment-pipeline)
- [Contributing](#-contributing)
- [Security Policy](#-security-policy)
- [License & Acknowledgments](#-license-acknowledgments)

---

## 🚀 Key Features

- **Modular Architecture**: Built using `nwidart/laravel-modules` to strictly isolate domain packages (`Auth`, `Posts`, `SocialGraph`, `Media`, `Comments`, `Interactions`, `Notifications`, `Search`).
- **Standardized API Responses**: Employs a uniform API envelope via the `RespondsWithApi` trait and `ApiResponse` support layer (`success`, `message`, `data`, `meta`, `errors`).
- **Global Error Handling**: Integrated error pipeline in `bootstrap/app.php` that transforms validation, authentication, authorization, domain exceptions, and missing models into consistent JSON error responses.
- **User Profiles & Flexible Onboarding**: Automatic 1:1 profile generation upon user registration in an atomic transaction, supporting bio, avatar, cover image, website, location, and customizable unique usernames.
- **Unified & Tabbed Search**: Fast search API (`GET /api/v1/search`) supporting combined overview results (`type=all`) and dedicated tabbed queries (`type=users`, `type=posts`) with real-time post visibility scoping.
- **3-Tier Post Visibility Scoping**: Robust post access control enforcing `public`, `followers`, and `private` visibility rules across all feed, user profile, and search query endpoints.
- **Asynchronous Post Fan-out Engine**: High-throughput two-tier `Bus::batch()` processing that chunks followers and performs bulk `FeedItem::insertOrIgnore()` inserts into timeline feeds.
- **Dynamic Feed Backfill & Cleanup**: Automatically backfills existing posts upon follow request acceptance and removes unfollowed users' posts asynchronously via transaction-safe events.
- **Event-Driven In-App Notifications**: Real-time asynchronous notification listeners on a dedicated queue pool for user interactions (likes, shares, comments, follows, follow requests, reports) with built-in self-action suppression and mark-as-read APIs.
- **Dedicated Queue Isolation**: Redis-backed queue pools (`notifications`, `feed`, `media`, `default`) monitored and auto-scaled with **Laravel Horizon**.
- **Direct Cloud Media Uploads**: Secure Cloudflare R2 / AWS S3 direct presigned upload URLs with async media processing and attachment workflows.
- **Cursor Pagination**: Efficient cursor-based pagination with query string preservation (`withQueryString()`) for feeds, user posts, and activity streams.

---

## 📦 Modules Overview

The application is organized into the following decoupled modules:

- **Auth**: User registration, authentication, token management (Sanctum), and profile management (`Profile` model with bio, website, location, avatar/cover media relations, and username uniqueness).
- **Posts**: Post creation, visibility settings (`public`, `followers`, `private`), post detail retrieval, updates, soft deletion, and user post sub-resource (`GET /api/v1/users/{user}/posts`) with 3-tier visibility checks and cursor pagination.
- **SocialGraph**: Follow/unfollow workflows, follow request lifecycle (accept/reject), follower/following lists, timeline feed generation, async post fan-out, feed backfilling, and unfollow cleanup.
- **Media**: Cloudflare R2 / S3 presigned upload URL generation, upload confirmation, background media processing, polymorphic media attachments, and orphan cleanup.
- **Comments**: Top-level comments and nested threaded replies on posts, with comment creation, updates, listing, and deletion.
- **Interactions**: User engagement actions including liking/unliking posts and comments, post sharing (internal & external with analytics counters), bookmarking, and reporting.
- **Notifications**: In-app notification engine with queued listeners (`notifications` queue) handling `post_liked`, `comment_liked`, `post_shared`, `post_commented`, `user_followed`, `follow_requested`, `follow_request_accepted`, and `post_reported`. Includes REST API endpoints to fetch paginated notifications and mark individual or all notifications as read.
- **Search**: Unified search engine (`GET /api/v1/search`) supporting combined overview results (`type=all`) and tabbed searches (`type=users`, `type=posts`) with real-time post visibility scoping.

---

## 📡 API Endpoints Summary

### Authentication & Profiles (`Modules/Auth`)
| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/register` | Register a new user (automatically initializes profile) | No |
| `POST` | `/api/v1/login` | Authenticate user and issue API bearer token | No |
| `GET` | `/api/v1/me` | Fetch authenticated user details | Yes |
| `GET` | `/api/v1/users/{user}/profile` | View a user's public profile | Optional |
| `PUT\|PATCH`| `/api/v1/profile` | Update authenticated user profile fields | Yes |

### Posts (`Modules/Posts`)
| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/posts` | List posts | Optional |
| `POST` | `/api/v1/posts` | Create a new post | Yes |
| `GET` | `/api/v1/posts/{post}` | Get post details | Optional |
| `DELETE` | `/api/v1/posts/{post}` | Delete a post | Yes |
| `GET` | `/api/v1/users/{user}/posts` | List posts created by a specific user (cursor-paginated) | Optional |

### Social Graph & Feeds (`Modules/SocialGraph`)
| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/feed` | Fetch personalized timeline feed (cursor-paginated) | Yes |
| `POST` | `/api/v1/users/{user}/follow` | Follow a user or send a follow request | Yes |
| `DELETE` | `/api/v1/users/{user}/follow` | Unfollow a user or cancel a follow request | Yes |
| `POST` | `/api/v1/follows/{follow}/accept` | Accept an incoming follow request | Yes |
| `POST` | `/api/v1/follows/{follow}/reject` | Reject an incoming follow request | Yes |
| `GET` | `/api/v1/users/{user}/followers` | List user followers (cursor-paginated) | Optional |
| `GET` | `/api/v1/users/{user}/following` | List users followed by user (cursor-paginated) | Optional |

### Unified Search (`Modules/Search`)
| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/search?q={query}&type={all\|users\|posts}` | Unified or tabbed search across users & posts | Optional |

### Notifications (`Modules/Notifications`)
| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/notifications` | List user notifications (cursor-paginated) | Yes |
| `POST` | `/api/v1/notifications/{notification}/read`| Mark a specific notification as read | Yes |
| `POST` | `/api/v1/notifications/read-all` | Mark all unread notifications as read | Yes |

### Interactions & Comments (`Modules/Interactions`, `Modules/Comments`)
| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/posts/{post}/like` | Like a post | Yes |
| `DELETE` | `/api/v1/posts/{post}/like` | Unlike a post | Yes |
| `POST` | `/api/v1/posts/{post}/internal-share` | Share post internally | Yes |
| `POST` | `/api/v1/posts/{post}/external-share` | Track external post share | Yes |
| `DELETE` | `/api/v1/posts/{post}/share` | Unshare a post | Yes |
| `POST` | `/api/v1/posts/{post}/bookmark` | Bookmark a post | Yes |
| `DELETE` | `/api/v1/posts/{post}/bookmark` | Remove a bookmark | Yes |
| `GET` | `/api/v1/bookmarks` | List bookmarked posts | Yes |
| `POST` | `/api/v1/posts/{post}/report` | Report a post | Yes |
| `GET` | `/api/v1/posts/{post}/comments` | List comments on a post | Optional |
| `POST` | `/api/v1/posts/{post}/comments` | Add a comment to a post | Yes |
| `PATCH` | `/api/v1/comments/{comment}` | Update a comment | Yes |
| `DELETE` | `/api/v1/comments/{comment}` | Delete a comment | Yes |
| `GET` | `/api/v1/comments/{comment}/replies` | List replies to a comment | Optional |
| `POST` | `/api/v1/comments/{comment}/replies` | Reply to a comment | Yes |
| `POST` | `/api/v1/comments/{comment}/like` | Like a comment | Yes |
| `DELETE` | `/api/v1/comments/{comment}/like` | Unlike a comment | Yes |

### Media (`Modules/Media`)
| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/media/upload-url` | Generate direct presigned S3/R2 upload URL | Yes |
| `POST` | `/api/v1/media/{media}/confirm` | Confirm completed media upload | Yes |
| `GET` | `/api/v1/media/{media}` | View media metadata | Yes |
| `DELETE` | `/api/v1/media/{media}` | Delete uploaded media | Yes |

---

## 🛠️ Technology Stack

- **Framework**: Laravel 13
- **Language**: PHP 8.3+
- **Database**: PostgreSQL (Production/Testing) / SQLite
- **Queue & Cache**: Redis with **Laravel Horizon**
- **Object Storage**: Cloudflare R2 / AWS S3 (Flysystem S3 v3)
- **Authentication**: Laravel Sanctum (Bearer Token)
- **API Documentation**: Knuckles Scribe

---

## ⚡ Queue & Background Workers

Queue workers are grouped into specialized supervisor pools in `config/horizon.php` to ensure resource-intensive jobs never block real-time operations:

| Supervisor | Queues | Workload Characteristics |
| :--- | :--- | :--- |
| **`supervisor-general`** | `notifications`, `default` | Low-latency alerts, emails, general tasks (128MB RAM, auto-balanced) |
| **`supervisor-feed`** | `feed` | High-throughput post fan-out & feed backfill batches (256MB RAM, auto-scales up to 20 workers) |
| **`supervisor-media`** | `media` | Heavy image manipulation, video transcoding & cloud storage operations (512MB RAM, isolated) |

---

## 📖 API Documentation

All endpoints, request payloads, query parameters, and response schemas are generated using **Scribe**.

### Viewing the Docs
- **Local Environment**: [http://localhost:8000/docs](http://localhost:8000/docs)
- **Horizon Dashboard**: [http://localhost:8000/horizon](http://localhost:8000/horizon)

### Compiling Documentation
Generate the interactive API documentation assets:

```bash
php artisan scribe:generate
```

---

## ⚙️ Setup & Running the Application

### 🐳 Running with Docker (Recommended)

The easiest way to run the entire stack (PHP 8.4 FPM, Nginx, PostgreSQL 17, Redis, Laravel Horizon, Task Scheduler, and Vite HMR) is using Docker Compose.

#### 1. Setup Environment Configuration
Copy the Docker environment template:
```bash
cp .env.docker.example .env
```
*(Ensure `APP_KEY` is set or run `docker compose exec app php artisan key:generate`. Add your Cloudflare R2 credentials if you test media uploads).*

#### 2. Start the Stack
```bash
docker compose up -d --build
```

#### 3. Available Endpoints
- **Web Application / API**: [http://localhost](http://localhost)
- **Vite Dev Server (HMR)**: [http://localhost:5173](http://localhost:5173)
- **Horizon Dashboard**: [http://localhost/horizon](http://localhost/horizon)
- **API Documentation (Scribe)**: [http://localhost/docs](http://localhost/docs)

#### 4. Run Migrations (Manual)
```bash
docker compose exec app php artisan migrate
```

#### 5. Common Docker Commands
- **Run Artisan commands**:
  ```bash
  docker compose exec app php artisan <command>
  ```
- **Stream logs**:
  ```bash
  docker compose logs -f app
  docker compose logs -f horizon
  docker compose logs -f vite
  ```
- **Run test suite**:
  ```bash
  docker compose exec app php artisan test
  ```
- **Stop containers**:
  ```bash
  docker compose down
  ```

---

### 💻 Local (Non-Docker) Setup

#### Prerequisites
- **PHP**: ^8.3
- **Composer**: Dependency Manager for PHP
- **PostgreSQL** or **SQLite**
- **Redis**: For cache and queue management
- **Node.js**: For Vite asset bundling

#### Installation Steps
1. **Clone the Repository** and navigate to the project directory:
   ```bash
   cd zion-backend-php
   ```

2. **Copy Environment Configurations**:
   ```bash
   cp .env.example .env
   ```

3. **Install Composer & Node Dependencies**:
   ```bash
   composer install
   npm install
   ```

4. **Generate Application Key**:
   ```bash
   php artisan key:generate
   ```

5. **Run Database Migrations**:
   ```bash
   php artisan migrate
   ```

6. **Start the Development Server**:
   In Laravel 13, run the all-in-one development command:
   ```bash
   php artisan dev
   ```
   *This concurrently starts the HTTP server, queue listeners, and log streaming.*


---

## 🚀 Automated Testing & CI/CD

Tsiyon Backend uses a continuous integration and zero-downtime deployment pipeline powered by GitHub Actions ([`.github/workflows/deploy.yml`](.github/workflows/deploy.yml)) and a hardened server deployment script ([`docker/scripts/tsiyon-deploy`](docker/scripts/tsiyon-deploy)).

### 🧪 GitHub Actions CI Pipeline
On every **pull request** targeting `main` and on every **push** to `main`, GitHub Actions executes the automated test suite:

1. **100% Isolated Ephemeral Testing Database**:
   * Spins up disposable **PostgreSQL 17** (`services.postgres`) and **Redis 7** (`services.redis`) service containers in the GitHub runner.
   * Runs tests strictly against the ephemeral `zionweb_test` database with **zero connection to or risk for production data**.
2. **Modular Test Suite Discovery**:
   * Automatically discovers and runs all **180+ tests** across root `tests/` and domain modules (`Modules/*/tests/`).
3. **Optimized Caching**:
   * Caches Composer archives (`~/.composer/cache/files`) based on `composer.lock` and all `Modules/*/composer.json` manifests for rapid test runs.
4. **Deployment Gate**:
   * Deployment only runs on the `main` branch if all automated tests pass with 0 failures (`needs: test`).

```bash
# What GitHub Actions runs in CI:
composer install --prefer-dist --no-interaction --no-progress
php artisan test
```

### 💻 Running Tests Locally

Run the complete test suite across all modules:

```bash
docker compose exec app php artisan test
```

To run tests for a specific domain module:

```bash
docker compose exec app php artisan test Modules/Auth
docker compose exec app php artisan test Modules/Posts
docker compose exec app php artisan test Modules/SocialGraph
docker compose exec app php artisan test Modules/Search
docker compose exec app php artisan test Modules/Notifications
docker compose exec app php artisan test Modules/Interactions
docker compose exec app php artisan test Modules/Comments
docker compose exec app php artisan test Modules/Media
```

To run a specific test class:

```bash
docker compose exec app php artisan test --filter=MediaAttachmentTest
```

---

### 🚢 Production Deployment Pipeline

When tests pass on `main` (or upon manual `workflow_dispatch` trigger):

1. **Secure SSH Handshake**:
   * Runner connects to the production host via OpenSSH (custom port `22022`) using an ephemeral `chmod 600` key file cleaned up immediately on exit.
   * The host matches the restricted public key in `/home/deploy/.ssh/authorized_keys` and executes `/usr/local/sbin/tsiyon-deploy <commit-sha>`.
2. **Production Image Builds & Release Order**:
   * Acquires an exclusive lock (`flock`) on `/var/lock/tsiyon-deploy.lock`.
   * Enforces strict **tip-of-main** verification (`[ "$SHA" = "$TARGET_SHA" ]`).
   * Builds production container images (`zion/app:${SHA:0:12}` and `zion/web:${SHA:0:12}`).
   * Runs database migrations under `www-data` (`migrate --force`) to protect storage permissions.
   * Switches containers gracefully with zero downtime (`docker compose up -d`).
   * Clears and compiles configuration, route, and event caches (`php artisan optimize`).
   * Performs a local SNI health check via `https://tsiyonapp.com/up` directly resolving to `127.0.0.1:443`.
   * Automatically triggers a self-healing rollback to the previous commit if the health check fails.
   * Prunes Docker BuildKit cache and unused images to preserve VPS disk space.

### One-Time Server Setup
On your production host (`/src/tsiyon`):

1. **Symlink the Deploy Script**:
   ```bash
   sudo ln -sf /src/tsiyon/docker/scripts/tsiyon-deploy /usr/local/sbin/tsiyon-deploy
   sudo chmod 755 /src/tsiyon/docker/scripts/tsiyon-deploy
   ```

2. **Configure Sudoers Permission**:
   ```bash
   echo 'deploy ALL=(root) NOPASSWD: /usr/local/sbin/tsiyon-deploy' | sudo tee /etc/sudoers.d/deploy > /dev/null
   sudo chmod 0440 /etc/sudoers.d/deploy
   sudo visudo -c
   ```

3. **Install Restricted SSH Key**:
   In `/home/deploy/.ssh/authorized_keys`, add the GitHub Actions public key with forced-command restrictions:
   ```text
   restrict,command="sudo /usr/local/sbin/tsiyon-deploy \"$SSH_ORIGINAL_COMMAND\"" ssh-ed25519 <PUBLIC_KEY> github-actions-deploy
   ```

### Required GitHub Secrets & Variables
Set these under **Settings > Environments > production** (or **Settings > Secrets and variables > Actions**):

| Secret / Variable | Description | Example |
| :--- | :--- | :--- |
| `SSH_HOST` | Production server IP or hostname | `209.74.82.49` or `tsiyonapp.com` |
| `SSH_USER` | Deployment user on the server | `deploy` |
| `SSH_PORT` | Custom SSH port (defaults to `22022`) | `22022` |
| `SSH_PRIVATE_KEY` | Dedicated OpenSSH private key for GitHub Actions | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `SSH_KNOWN_HOSTS` | Output of `ssh-keyscan -p <PORT> -H -t ed25519,rsa <HOST>` | `\|1\|... ssh-ed25519 AAAAC...` |

---

## 🤝 Contributing

We welcome community contributions! Please read our [Contributing Guidelines](CONTRIBUTING.md) for details on our branching model, Conventional Commits specification, automated test suite, and pull request checklist.

---

## 🔒 Security Policy

If you discover a security vulnerability within Zion Backend, please review our [Security Policy](SECURITY.md) and report it privately via [GitHub Private Vulnerability Reporting](https://github.com/davidebukali/zion-backend-php/security/advisories/new). Please **do not** open public issues for security vulnerabilities.

---

## 📄 License & Acknowledgments

Zion Backend is open-source software licensed under the [MIT License](LICENSE). Copyright &copy; 2026 Tsiyon App.

Special thanks to the open-source projects powering this platform:
- [Laravel](https://laravel.com)
- [nwidart/laravel-modules](https://github.com/nWidart/laravel-modules)
- [Laravel Horizon](https://laravel.com/docs/horizon)
- [Knuckles Scribe](https://scribe.knuckles.wtf)


