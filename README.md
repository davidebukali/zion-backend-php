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

## API Standard Response Formats

All APIs conform to consistent JSON envelopes.

### Success Envelope

Returned for successful requests (2xx status codes).

```json
{
  "success": true,
  "message": "Detailed success message",
  "data": {
    "key": "value"
  },
  "meta": null
}
```

*Note: For paginated responses, metadata including links and cursor tokens are embedded inside the `meta` key:*

```json
{
  "success": true,
  "message": "Success",
  "data": [ ... ],
  "meta": {
    "links": {
      "first": "...",
      "last": "...",
      "prev": "...",
      "next": "..."
    },
    "meta": {
      "path": "https://...",
      "per_page": 15,
      "next_cursor": "...",
      "prev_cursor": "..."
    }
  }
}
```

### Error Envelope

Returned for failed requests (4xx or 5xx status codes).

```json
{
  "success": false,
  "message": "Reason for the error",
  "errors": {
    "field_name": [
      "Validation or processing error details"
    ]
  }
}
```

---

## API Documentation

### Auth Module (Prefix: `/api/v1`)

| Method | Endpoint | Authentication | Description |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/register` | None | Register a new user |
| `POST` | `/api/v1/login` | None | Authenticate credentials and get Sanctum token |
| `GET` | `/api/v1/me` | Bearer Token | Retrieve currently authenticated user profile |

#### 1. Register User
- **URL**: `/api/v1/register`
- **Method**: `POST`
- **Request Body**:
  ```json
  {
    "name": "John Doe",
    "email": "john@example.com",
    "password": "securepassword"
  }
  ```
- **Validation Rules**:
  - `name`: Required, string, max:255
  - `email`: Required, string, email, max:255, unique in `users` table
  - `password`: Required, string, min:8

#### 2. Login User
- **URL**: `/api/v1/login`
- **Method**: `POST`
- **Request Body**:
  ```json
  {
    "email": "john@example.com",
    "password": "securepassword"
  }
  ```
- **Success Response**:
  ```json
  {
    "user": {
      "id": "01J4V8Q...",
      "name": "John Doe",
      "email": "john@example.com",
      "created_at": "2026-08-09T03:13:58.000000Z",
      "updated_at": "2026-08-09T03:13:58.000000Z"
    },
    "token": "1|sanctum_plain_text_token..."
  }
  ```

#### 3. Currently Authenticated User Profile
- **URL**: `/api/v1/me`
- **Method**: `GET`
- **Headers**:
  - `Authorization: Bearer <token>`
- **Success Response**:
  ```json
  {
    "id": "01J4V8Q...",
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2026-08-09T03:13:58.000000Z",
    "updated_at": "2026-08-09T03:13:58.000000Z"
  }
  ```

---

### Posts Module (Prefix: `/api/v1`)

*All Post endpoints require a valid Sanctum token.*

| Method | Endpoint | Authentication | Description |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/posts` | Bearer Token | Create a new post |
| `GET` | `/api/v1/posts` | Bearer Token | List posts feed (cursor paginated) |
| `GET` | `/api/v1/posts/{post}`| Bearer Token | Show a specific post's details |
| `DELETE`| `/api/v1/posts/{post}`| Bearer Token | Soft-delete a post (owner only) |

#### 1. Create a Post
- **URL**: `/api/v1/posts`
- **Method**: `POST`
- **Request Body**:
  ```json
  {
    "content": "This is a new post!",
    "visibility": "public"
  }
  ```
- **Validation Rules**:
  - `content`: Optional, string, max:5000
  - `visibility`: Optional, enum string (`public`, `private`, `followers`) - defaults to `public`

#### 2. List Feed Posts
- **URL**: `/api/v1/posts`
- **Method**: `GET`
- **Query Parameters**:
  - `per_page`: Optional integer (defaults to 15)
- **Response**: Returns a cursor-paginated list of posts, sorted by newest first, wrapped in the paginated success envelope.

#### 3. Get Post Details
- **URL**: `/api/v1/posts/{post}`
- **Method**: `GET`
- **Success Response**:
  ```json
  {
    "success": true,
    "message": "Success",
    "data": {
      "id": "01J4V8X...",
      "content": "This is a new post!",
      "visibility": "public",
      "created_at": "2026-08-09T03:13:58.000000Z",
      "updated_at": "2026-08-09T03:13:58.000000Z"
    },
    "meta": null
  }
  ```

#### 4. Delete Post
- **URL**: `/api/v1/posts/{post}`
- **Method**: `DELETE`
- **Success Response**:
  ```json
  {
    "success": true,
    "message": "Post deleted successfully",
    "data": null,
    "meta": null
  }
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
