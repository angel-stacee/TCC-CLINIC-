# TCC Clinic Management System

A clinic management app with a React and TypeScript frontend and a Laravel API backend.

## Requirements

- PHP 8.2 or newer and Composer
- Node.js and npm
- MySQL (XAMPP works on Windows)
- A Gemini API key if you want to use the AI assistant

## First-time setup

### 1. Start MySQL and create a database

In XAMPP, start **MySQL**. In phpMyAdmin, create a database named `tcc_clinic` using `utf8mb4` encoding. If you use a different database name or MySQL credentials, use those values in the next step.

### 2. Configure the Laravel backend

From the project root, copy the example environment file and install PHP packages:

```powershell
Copy-Item backend/.env.example backend/.env
Set-Location backend
composer install
```

Edit `backend/.env` and set `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` to match your MySQL setup. XAMPP commonly uses `root` with a blank password by default.

Generate the Laravel app key and create the database tables:

```powershell
php artisan key:generate
php artisan migrate --seed
```

The seeder creates a local test account: `test@example.com` with password `password`. Change or remove that account before using real clinic data.

### 3. Configure the AI assistant (optional)

In `backend/.env`, set `GEMINI_API_KEY` to your own Gemini API key. Keep the key private and do not commit `.env` files. The model can be set with `GEMINI_MODEL`; it defaults to `gemini-2.5-flash`. Set `GEMINI_FALLBACK_MODEL` to a different supported model if you want it used after a temporary provider capacity failure; it defaults to `gemini-2.5-flash`.

### 4. Install frontend packages

Open a second terminal at the project root:

```powershell
Set-Location frontend
npm ci
```

## Run the app on a single domain (Frontend + Backend together)

Now that backend and frontend are combined into 1 folder, you only need to run:

```powershell
php artisan serve --port=8000
```

Open your browser at **<http://localhost:8000>**.
- Both the React frontend UI and the Laravel API are served together on the **SAME domain**.
- No need to run Vite separately on port 5173.

### Rebuilding Frontend Assets (When you make UI changes)

From the project root:
```powershell
npm run build
```

### Optional: Hot Reload Development Mode
```powershell
npm run dev
```

## Useful commands

Run these from the `frontend` directory:

```powershell
npm run typecheck
npm run lint
npm run build
```

Run these from the `backend` directory:

```powershell
php artisan migrate:status
php artisan test
```

`npm run build` runs the TypeScript check before creating the production frontend bundle.

## Troubleshooting

- **Database connection error:** Confirm MySQL is running and the `DB_*` values in `backend/.env` match your database.
- **Frontend cannot reach the API:** Keep `php artisan serve` running on port `8000`; the frontend API URL is configured in `frontend/src/services/api.ts`.
- **AI assistant unavailable:** Confirm `GEMINI_API_KEY` is set in `backend/.env`, then restart the Laravel server.
- **Missing packages:** Run `composer install` in `backend` and `npm ci` in `frontend`.

## Project layout

```text
backend/   Laravel API, database migrations, and seeders
frontend/  React, TypeScript, Vite, and Tailwind CSS app
```
# TCC-CLINIC
