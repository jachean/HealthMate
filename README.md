# HealthMate

Full-stack medical reporting app (work in progress).

## Tech stack

- Backend: Symfony 7, PHP 8.2, MySQL (currently MariaDB), JWT auth (lexik/jwt-authentication-bundle)
- Frontend: Vue 3 + Vite, Pinia, Vue Router

## Setup

### Backend

1. Install PHP 8.2+, Composer, Symfony CLI (optional).
2. Copy `.env` to `.env.local` and update:
    - `DATABASE_URL` (your local DB)
    - `JWT_PASSPHRASE` (same as when you generated the keys)
3. Generate JWT keys (if you don’t already have them):

   ```bash
   php bin/console lexik:jwt:generate-keypair
