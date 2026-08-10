# Zion Backend (PHP)

Zion Backend is a clean, modular REST API built with Laravel 13 and PHP 8.3+. It leverages a modular architecture to group features into decoupled packages, ensuring scalability and ease of maintenance.

---

## Key Features

- **Modular Architecture**: Built using `nwidart/laravel-modules` to isolate distinct logic modules (e.g. `Auth`, `Posts`).
- **Standardized API Responses**: Employs a unified response envelope via the `RespondsWithApi` trait and `ApiResponse` support classes.
- **Global Error Handling**: Integrated error handling inside `bootstrap/app.php` that transforms standard exceptions (e.g., validation errors, auth failures, resource not found) into uniform API error response envelopes.
- **Implicit Route Model Binding**: Utilizes native Laravel route model binding mapped to modular schemas.
- **Cursor Pagination**: Employs performant, cursor-based pagination for feeds.

---

## Technology Stack

- **Framework**: Laravel 13
- **Language**: PHP 8.3+
- **Authentication**: Laravel Sanctum (token-based)
- **Database**: SQLite (default configuration)
- **Modularity**: `nwidart/laravel-modules`

---

## API Documentation

All API endpoints, request structures, validation parameters, and response formats are automatically generated using **Scribe**.

### Viewing the Docs
- Local Environment: [http://localhost:8000/docs](http://localhost:8000/docs)
- Deployed Environments: `<app_url>/docs`

### Generating Documentation
Since the generated documentation assets (HTML, CSS, JS, Blade view) are ignored in `.gitignore`, you must compile them locally before viewing:

```bash
php artisan scribe:generate
```

---

## Setup & Running the Application

### Prerequisites

- **PHP**: ^8.3
- **Composer**: Dependency Manager for PHP
- **SQLite**: (Default local database)

### Installation Steps

1. **Clone the Repository** and navigate to the directory:
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

4. **Initialize Database**:
   Ensure you have a SQLite database file created (e.g. `database/database.sqlite`), then run:
   ```bash
   php artisan migrate
   ```

5. **Generate Application Key**:
   ```bash
   php artisan key:generate
   ```

6. **Start the Development Server**:
   ```bash
   php artisan serve
   ```
   The backend will be accessible locally at `http://127.0.0.1:8000`.

---

## Testing

The project uses PHPUnit for automated feature and unit tests. Run the test suite using:

```bash
php artisan test
```
