# Project Demo 51

This is a Laravel 11 project that serves as a powerful and feature-rich web application. It includes a variety of backend and frontend packages for building a modern, data-driven application.

## Features

This project comes with a wide array of features, thanks to the included packages:

*   **Backend:**
    *   [Laravel 11](https://laravel.com/): The latest version of the popular PHP framework.
    *   [Livewire](https://livewire.laravel.com/): For building dynamic interfaces.
    *   [Laravel DataTables](https://yajrabox.com/docs/laravel-datatables/master): For handling server-side DataTables.
    *   [Laravel Permission](https://spatie.be/docs/laravel-permission/v6/introduction): For managing user permissions and roles.
    *   [Laravel Backup](https://spatie.be/docs/laravel-backup/v9/introduction): For backing up the application and database.
    *   [Laravel Sanctum](https://laravel.com/docs/11.x/sanctum): For API authentication.
    *   [Laravel Socialite](https://laravel.com/docs/11.x/socialite): For OAuth authentication.
    *   [Barryvdh Laravel DomPDF](https://github.com/barryvdh/laravel-dompdf): For generating PDFs from HTML.
    *   And many more...

*   **Frontend:**
    *   [Vite](https://vitejs.dev/): For fast frontend development.
    *   [Bootstrap 5](https://getbootstrap.com/): The world's most popular front-end open source toolkit.
    *   [jQuery](https://jquery.com/): A fast, small, and feature-rich JavaScript library.
    *   [DataTables](https://datatables.net/): For creating advanced interaction controls for HTML tables.
    *   [Font Awesome](https://fontawesome.com/): For vector icons and social logos.
    *   [SweetAlert2](https://sweetalert2.github.io/): A beautiful, responsive, customizable, and accessible replacement for JavaScript's popup boxes.
    *   And many more...

## Installation

1.  **Clone the repository:**
    ```bash
    git clone <repository-url>
    ```

2.  **Install PHP dependencies:**
    ```bash
    composer install
    ```

3.  **Install JavaScript dependencies:**
    ```bash
    npm install
    ```

4.  **Create a copy of the `.env` file:**
    ```bash
    cp .env.example .env
    ```

5.  **Generate an application key:**
    ```bash
    php artisan key:generate
    ```

6.  **Configure your database credentials in the `.env` file.**

7.  **Run the database migrations:**
    ```bash
    php artisan migrate
    ```

8.  **Seed the database (optional):**
    ```bash
    php artisan db:seed
    ```

## Usage

To start the development server, run the following commands:

```bash
# Start the Vite development server
npm run dev

# Start the Laravel development server
php artisan serve
```

Then, open your browser and navigate to `http://localhost:8000`.