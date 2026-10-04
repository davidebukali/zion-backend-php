# Zion Backend (PHP)

Zion Backend is a clean, modular REST API built with **Laravel 13** and **PHP 8.3+**. It leverages a decoupled modular architecture to isolate domain concerns, ensuring scalability, high performance, and ease of maintenance.

---

## 🚀 Key Features

- **Modular Architecture**: Built using `nwidart/laravel-modules` to strictly isolate domain packages (e.g. `Auth`, `Posts`, `SocialGraph`, `Media`, `Comments`, `Interactions`, `Notifications`).
- **Standardized API Responses**: Employs a uniform API envelope via the `RespondsWithApi` trait and `ApiResponse` support layer (`success`, `message`, `data`, `meta`, `errors`).
- **Global Error Handling**: Integrated error pipeline in `bootstrap/app.php` that transforms validation, authentication, authorization, domain exceptions, and missing models into consistent JSON error responses.
- **Asynchronous Post Fan-out Engine**: High-throughput two-tier `Bus::batch()` processing that chunks followers and performs bulk `FeedItem::insertOrIgnore()` inserts into timeline feeds.
- **Dynamic Feed Backfill & Cleanup**: Automatically backfills existing posts upon follow request acceptance and removes unfollowed users' posts asynchronously via transaction-safe events.
- **Event-Driven In-App Notifications**: Real-time asynchronous notification listeners for user interactions (likes, shares, comments, follows, follow requests, reports) with built-in self-action suppression.
- **Dedicated Queue Isolation**: Redis-backed queue pools (`notifications`, `feed`, `media`, `default`) monitored and auto-scaled with **Laravel Horizon**.
- **Direct Cloud Media Uploads**: Secure Cloudflare R2 / AWS S3 direct presigned upload URLs with async media processing and attachment workflows.
- **Cursor Pagination**: Efficient cursor-based pagination for high-volume feeds (`GET /api/v1/feed`).

---

## 📦 Modules Overview

The application is organized into the following decoupled modules:

- **Auth**: User registration, authentication, credential management, and API token generation using Laravel Sanctum.
- **Posts**: Post creation, visibility settings (`public`, `followers`, `private`), post retrieval, updates, and soft deletion.
- **SocialGraph**: Follow/unfollow management, follow request handling (accept/reject), followers & following lists, timeline feed generation, async post fan-out, feed backfilling, and unfollow cleanup.
- **Media**: Cloudflare R2 / S3 presigned upload URL generation, upload confirmation, background media processing, media attachment to posts/comments, and orphan cleanup.
- **Comments**: Top-level comments and nested replies on posts, with comment creation, updates, listing, and deletion.
- **Interactions**: User engagement actions including liking/unliking posts and comments, post sharing (internal & external with analytics counters), bookmarking, and reporting.
- **Notifications**: In-app notification engine with queued listeners (`notifications` queue) handling `post_liked`, `comment_liked`, `post_shared`, `post_commented`, `user_followed`, `follow_requested`, `follow_request_accepted`, and `post_reported`. Includes REST API endpoints to fetch paginated notifications and mark individual or all notifications as read.

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

### Prerequisites

- **PHP**: ^8.3
- **Composer**: Dependency Manager for PHP
- **PostgreSQL** or **SQLite**
- **Redis**: For cache and queue management

### Installation Steps

1. **Clone the Repository** and navigate to the project directory:
   ```bash
   cd zion-backend-php
   ```

2. **Copy Environment Configurations**:
   ```bash
   cp .env.example .env
   ```

3. **Install Composer Dependencies**:
   ```bash
   composer install
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

## 🧪 Testing

Run the automated test suite across all modules:

```bash
php artisan test
```

To run tests for specific modules:

```bash
php artisan test Modules/Notifications
php artisan test Modules/SocialGraph
```
