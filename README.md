# Category Management (Laravel 10)

This is a RESTful API built with Laravel 10 for managing a hierarchical category system. It includes full CRUD operations, tree view retrieval, search, filtering, pagination, and caching for optimal performance.

## Features

-   Category CRUD (Create, Read, Update, Delete)
-   Parent-child category relationships
-   Tree view of categories
-   Search by name, ID, or parent name
-   Filter by parent ID or categories with children
-   Optional eager loading (`parent`, `children`)
-   Pagination and full list support
-   Caching with auto invalidation
-   Multi-delete support
-   Protection against circular parent references

## Tech Stack

-   **Laravel 10**
-   **MySQL**
-   **File Cache**
-   **PHP 8+**

---

## Installation

1. **Clone the repository:**

    ```bash
    git clone https://github.com/Abdulrahman-Abdelrazeq/category-management.git
    cd category-api
    ```

2. **Install dependencies:**

    ```bash
    composer install
    ```

3. **Copy `.env` and generate app key:**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4. **Configure environment variables:**

    Update `.env` with your database credentials and caching preferences.

5. **Run migrations:**

    ```bash
    php artisan migrate
    ```

6. **(Optional) Seed categories:**

    ```bash
    php artisan db:seed
    ```

7. **Start the server:**

    ```bash
    php artisan serve
    ```

---

## API Endpoints

| Method | Endpoint                       | Description                           |
| ------ | ------------------------------ | ------------------------------------- |
| GET    | `/api/categories`              | Get list with optional filters/search |
| POST   | `/api/categories`              | Create a new category                 |
| GET    | `/api/categories/{id}`         | Get specific category by ID           |
| PUT    | `/api/categories/{id}`         | Update category                       |
| DELETE | `/api/categories/{id}`         | Delete category                       |
| POST   | `/api/categories/delete-multi` | Delete multiple categories            |
| GET    | `/api/categories/tree`         | Get the full tree of categories       |

### Optional Query Parameters (for `/categories`):

-   `keyword`: Search by ID, name, or parent name
-   `parent_id`: Filter by specific parent ID
-   `parent_only`: Show only categories with children
-   `with`: Eager load relations (`parent`, `children`)
-   `list`: Return all results (no pagination)
-   `per_page`: Pagination size (default: 10)
-   `page`: Page number (default: 1)

---

## Caching

-   List endpoints (`index`, `tree`) are cached for 1 hour.
-   Cache is invalidated automatically on category creation, update, or deletion.
-   Uses Laravel's cache driver (configured via `.env`).

---

## Folder Structure

-   `app/Http/Controllers/Api/CategoryController.php` — Main logic for category handling
-   `app/Http/Requests/CategoryRequest.php` — Validation for store/update
-   `app/Http/Requests/DestroyMultiCategoryRequest.php` — Validation for multi-delete
-   `app/Http/Resources/CategoryResource.php` — API response formatting
-   `app/Services/CategoryService.php` — Business logic helper (e.g., circular dependency detection)
-   `app/Traits/Response.php` — Unified API response helper
-   `app/Traits/CacheKeyManager.php` — Key-based cache invalidation helper

---

## Author

Developed by Abdelrahman Ahmed Elsayed – Backend Laravel Developer  
For questions or support, please contact: [abdelrahman.ahmed.elazizy@gmail.com]
