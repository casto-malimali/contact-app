# Contact App

A simple contact management application built with Laravel.

## Features

-   Contact management (Add, Edit, Delete)
-   User authentication
-   Search functionality
-   Contact categorization

## Requirements

-   PHP >= 8.1
-   Composer
-   MySQL/PostgreSQL
-   Node.js & NPM

## Installation

1. Clone the repository

```bash
git clone https://github.com/yourusername/contact-app.git
```

2. Install dependencies

```bash
composer install
npm install
```

3. Configure environment variables

```bash
cp .env.example .env
php artisan key:generate
```

4. Setup database and run migrations

```bash
php artisan migrate
```

5. Start the application

```bash
php artisan serve
npm run dev
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
