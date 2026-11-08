# Native PHP REST API Framework (No Dependencies)

A lightweight, zero-dependency, pure PHP 8+ REST API framework built with a strict MVC architecture. Specifically designed for seamless deployment to shared hosting environments such as **InfinityFree**, **cPanel**, Apache, and local XAMPP/WAMP setups.

---

## Key Features

- **Pure PHP 8+**: No Composer, no external packages, no build steps.
- **Strict MVC Architecture**: Clear separation of Models, Views/Responses, Controllers, and Core components.
- **Universal REST Router**: Full support for `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, and `OPTIONS`, with dynamic route parameter extraction (e.g., `/api/users/{id}`), 404 handling, and 405 Method Not Allowed handling.
- **Automatic Route Discovery**: Drop any `.php` file into `app/Routes/` and endpoints register automatically without touching `index.php`.
- **Universal File & Image Uploader**: Reusable `Uploader` engine with binary MIME verification (`finfo_file`), file size limits, secure random naming, and old-file cleanup.
- **CORS Middleware**: Built-in support for cross-origin requests and preflight `OPTIONS` requests.
- **Subfolder & Root Compatible**: Auto-detects base directories; runs equally well at `https://example.com/api/users` or `http://localhost/my-api/api/users`.
- **Production-Ready Security**: PDO prepared statements, input validation, strict JSON response format, safe exception handling, and script execution locks in upload folders.
- **Architecture Manual Included**: Standalone printable [NATIVE_PHP_REST_API_MANUAL.pdf](file:///c:/xampp/htdocs/my-api/NATIVE_PHP_REST_API_MANUAL.pdf).

---

## Project Structure

```text
my-api/
│
├── index.php                 # Single API Entry Point (Autoload, Route loading, Dispatch)
├── database.sql              # Database schema ready for phpMyAdmin import
├── README.md                 # Documentation & deployment guide
├── NATIVE_PHP_REST_API_MANUAL.pdf # Developer manual & architecture guide
│
├── app/
│   ├── Core/
│   │   ├── Autoloader.php    # Native PSR-4 compliant autoloader
│   │   ├── Database.php      # PDO database connection manager
│   │   ├── Request.php       # HTTP request abstraction (body, files, headers, params)
│   │   ├── Response.php      # Standardized JSON response emitter
│   │   ├── Model.php         # Base Model with prepared statement helpers
│   │   ├── Controller.php    # Base Controller with input validator & response helpers
│   │   ├── Router.php        # Universal REST router with route parameter resolution
│   │   └── Uploader.php      # Reusable file & image uploader with MIME security
│   │
│   ├── Controllers/
│   │   └── UserController.php # User CRUD & avatar upload controller
│   │
│   ├── Models/
│   │   └── User.php          # User database operations (PDO queries, zero HTTP logic)
│   │
│   ├── Middleware/
│   │   └── CorsMiddleware.php# CORS headers and preflight OPTIONS handling
│   │
│   └── Routes/
│       └── users.php         # User endpoint definitions (auto-loaded)
│
├── config/
│   ├── app.php               # Application & CORS settings
│   └── database.php          # PDO MySQL database credentials
│
├── public/
│   ├── .htaccess             # Public rewrite rules
│   └── test.html             # Browser-based interactive API test console
│
├── uploads/
│   ├── .htaccess             # Anti-execution security shield
│   └── avatars/              # Uploaded user avatar images
│
└── .htaccess                 # Apache rewrite engine & sensitive file protection
```
│   ├── app.php               # Application & CORS settings
│   └── database.php          # PDO MySQL database credentials
│
├── public/
│   ├── .htaccess             # Public rewrite rules
│   └── test.html             # Browser-based interactive API test console
│
└── .htaccess                 # Apache rewrite engine & sensitive file protection
```

---

## 1. Database Setup

### SQL Schema

Create a database named `my_api_db` (or your InfinityFree database name) and execute:

```sql
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Seed Data
INSERT INTO users (name, email) VALUES
('John Doe', 'john.doe@example.com'),
('Jane Smith', 'jane.smith@example.com')
ON DUPLICATE KEY UPDATE id=id;
```

A complete SQL file is included at [`database.sql`](file:///c:/xampp/htdocs/my-api/database.sql).

---

## 2. Shared Hosting Deployment (InfinityFree / cPanel)

### Uploading via cPanel File Manager or FTP

1. Log into your **cPanel** or **InfinityFree Client Area**.
2. Open **File Manager** (or connect via FileZilla / FTP).
3. Navigate to your web root (usually `htdocs/` on InfinityFree or `public_html/` on cPanel).
4. Upload all files and folders directly. You can upload the contents directly into `htdocs/` or into a subfolder like `htdocs/my-api/`.
5. Ensure hidden files like `.htaccess` are uploaded (in cPanel File Manager, check **"Show Hidden Files"** under Settings).

### Database Configuration on InfinityFree

1. In the InfinityFree Control Panel (vPanel), go to **MySQL Databases** and create a new database.
2. Note your credentials:
   - **MySQL Hostname**: e.g., `sqlXXX.infinityfree.com`
   - **MySQL Database Name**: e.g., `if0_12345678_my_api_db`
   - **MySQL Username**: e.g., `if0_12345678`
   - **MySQL Password**: your vPanel account password
3. Open [`config/database.php`](file:///c:/xampp/htdocs/my-api/config/database.php) and update the values:

```php
return [
    'driver'    => 'mysql',
    'host'      => 'sqlXXX.infinityfree.com',
    'port'      => 3306,
    'database'  => 'if0_12345678_my_api_db',
    'username'  => 'if0_12345678',
    'password'  => 'your_vpanel_password',
    'charset'   => 'utf8mb4',
    'options'   => [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
```

4. Go to **phpMyAdmin** in vPanel, select your database, and import [`database.sql`](file:///c:/xampp/htdocs/my-api/database.sql).

---

## 3. Standard JSON Response Format

All API responses follow a uniform contract:

### Success Response (`200 OK`, `201 Created`)
```json
{
    "success": true,
    "message": "User retrieved successfully",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john.doe@example.com",
        "created_at": "2026-11-08 14:00:00"
    }
}
```

### Error Response (`400`, `404`, `405`, `409`, `422`, `500`)
```json
{
    "success": false,
    "message": "User not found",
    "data": null
}
```

---

## 4. Users API Reference

| Method | Endpoint | Description | Status Code |
|---|---|---|---|
| `GET` | `/api/users` | List all users | `200 OK` |
| `GET` | `/api/users/{id}` | Get a single user by ID | `200 OK` / `404 Not Found` |
| `POST` | `/api/users` | Create a new user (`name`, `email`) | `201 Created` / `422 Unprocessable` / `409 Conflict` |
| `PUT` / `PATCH` | `/api/users/{id}` | Update existing user details | `200 OK` / `404 Not Found` / `409 Conflict` |
| `POST` | `/api/users/{id}/avatar` | Upload & update user profile avatar image | `200 OK` / `400 Bad Request` / `422 Unprocessable` |
| `DELETE` | `/api/users/{id}` | Delete a user by ID | `200 OK` / `404 Not Found` |

---

## 5. Frontend API Testing (JavaScript Fetch API)

External frontend applications (React, Vue, Vanilla JS, mobile apps) communicate with the API using standard fetch calls:

```javascript
const API_BASE_URL = 'http://localhost/my-api/api/users'; 
// Or 'https://yourdomain.infinityfreeapp.com/api/users'

// 1. GET All Users
async function getAllUsers() {
    try {
        const response = await fetch(API_BASE_URL);
        const result = await response.json();
        console.log('All Users:', result);
    } catch (error) {
        console.error('Error fetching users:', error);
    }
}

// 2. GET User by ID
async function getUserById(id) {
    try {
        const response = await fetch(`${API_BASE_URL}/${id}`);
        const result = await response.json();
        if (!response.ok) {
            console.error('Error:', result.message);
            return;
        }
        console.log('User Details:', result.data);
    } catch (error) {
        console.error('Network Error:', error);
    }
}

// 3. POST Create User
async function createUser(name, email) {
    try {
        const response = await fetch(API_BASE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ name, email })
        });
        const result = await response.json();
        if (response.status === 201) {
            console.log('Created User:', result.data);
        } else {
            console.error('Validation or Creation Error:', result.message, result.data);
        }
    } catch (error) {
        console.error('Network Error:', error);
    }
}

// 4. PUT Update User
async function updateUser(id, updatedFields) {
    try {
        const response = await fetch(`${API_BASE_URL}/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(updatedFields)
        });
        const result = await response.json();
        if (response.ok) {
            console.log('Updated User:', result.data);
        } else {
            console.error('Update Error:', result.message);
        }
    } catch (error) {
        console.error('Network Error:', error);
    }
}

// 5. DELETE User
async function deleteUser(id) {
    try {
        const response = await fetch(`${API_BASE_URL}/${id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json'
            }
        });
        const result = await response.json();
        if (response.ok) {
            console.log('User deleted successfully');
        } else {
            console.error('Delete Error:', result.message);
        }
    } catch (error) {
        console.error('Network Error:', error);
    }
}
```

---

## 6. How to Extend the Framework

Adding new resources is completely modular:

### Step 1: Create a Model in `app/Models/`
Create `app/Models/Product.php`:
```php
<?php

namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    public function all(): array
    {
        return $this->fetchAll("SELECT * FROM products ORDER BY id DESC");
    }
}
```

### Step 2: Create a Controller in `app/Controllers/`
Create `app/Controllers/ProductController.php`:
```php
<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(): void
    {
        $products = (new Product())->all();
        $this->success($products, 'Products retrieved successfully');
    }
}
```

### Step 3: Create a Route File in `app/Routes/`
Create `app/Routes/products.php`:
```php
<?php

use App\Controllers\ProductController;

/** @var \App\Core\Router $router */
$router->get('/api/products', [ProductController::class, 'index']);
```

**That's it!** The router will automatically detect `app/Routes/products.php` and register the `/api/products` endpoint immediately without modifying `index.php`.
#   U n n a m e d - E c o m e r c e - B a c k e n d  
 