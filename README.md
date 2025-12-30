# 🚀 gitGerson/Filament Starter Kit

This is a **Filament v4 Starter Kit** for **Laravel 12**, designed to accelerate the development of Filament-powered applications.

## ⚙️ Setup

1️⃣ **Database Configuration**

By default, this starter kit uses **SQLite**. If you’re okay with this, you can skip this step. If you prefer **MySQL**, follow these steps:

- Update your database credentials in `.env`
- Run migrations: `php artisan migrate`
- (Optional) delete the existing database file: ```rm database/database.sqlite```

2️⃣ Create Filament Admin User
```bash
php artisan make:filament-user
```

3️⃣ Assign Super Admin Role
```bash
php artisan shield:super-admin --user=1 --panel=admin
```

4️⃣ Generate Permissions
```bash
php artisan shield:generate --all --ignore-existing-policies --panel=admin
```

## 🌟Panel Include 

- [Shield](https://filamentphp.com/plugins/bezhansalleh-shield) Access management to your Filament Panel's Resources, Pages & Widgets through spatie/laravel-permission.
- [Backgrounds](https://filamentphp.com/plugins/swisnl-backgrounds) Beautiful backgrounds for Filament auth pages.
- [Logger](https://filamentphp.com/plugins/z3d0x-logger) Extensible activity logger for filament that works out-of-the-box.
- [Nord Theme](https://filamentphp.com/plugins/andreia-bohner-nord-theme) Beautiful Nord theme with subdued palette
- [Breezy](https://filamentphp.com/plugins/jeffgreco-breezy) My Profile page.

> More will be added when the relevant plugins release support for v4

## 🧑‍💻Development Include

- [barryvdh/laravel-debugbar](https://github.com/barryvdh/laravel-debugbar) The most popular debugging tool for Laravel, providing detailed request and query insights.
- [larastan/larastan](https://github.com/larastan/larastan) A PHPStan extension for Laravel, configured at level 5 for robust static code analysis.

The `composer check` script runs **tests, PHPStan, and Pint** for code quality assurance:
```bash
composer check
```

## 📜 License

This project is open-source and licensed under the MIT License.