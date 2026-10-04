# Zion Backend (PHP)

Zion Backend is a clean, modular REST API built with **Laravel 13** and **PHP 8.3+**. It leverages a decoupled modular architecture to isolate domain concerns, ensuring scalability, high performance, and ease of maintenance.

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

To run tests for individual domain modules:

```bash
php artisan test Modules/Auth
php artisan test Modules/Posts
php artisan test Modules/SocialGraph
php artisan test Modules/Search
php artisan test Modules/Notifications
php artisan test Modules/Interactions
php artisan test Modules/Comments
php artisan test Modules/Media
```

