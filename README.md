# Bradford Portal

This is a simple PHP portal project for Bradford Council with login/registration, admin management, data upload, and Google Maps integration.

## Features implemented

- User registration and login with bcrypt password hashing
- Password reset workflow with email token stub
- Admin dashboard listing users
- File upload handling with storage in `uploads` table
- Activity logging
- Basic map view using Google Maps API
- Styling with Bradford colour palette and simple accessibility
- Database connection using PDO and basic encryption helpers
- Placeholder for 2FA and email notifications

## Setup

1. Install XAMPP or another PHP/MySQL stack and ensure it's running.
2. Create the database and tables by running `schema.sql`:
   ```sh
   mysql -u root < schema.sql
   ```
3. Update credentials in `includes/db.php` and set `APP_ENCRYPT_KEY` to a secure 32-byte value.
4. Replace `YOUR_API_KEY` in `css/portal.php` with a Google Maps API key.
5. Configure a mailer (e.g. PHPMailer) to send real emails in `includes/auth.php`.

## Usage

-- Open `index.php` in a browser to log in or register.
-- For administrators there is a separate login page (`admin_login.php`).
   Once logged in as an admin you will be redirected to `admin.php`, the
   Admin Control Panel.
   - Users can upload CSV/JSON files containing coordinate data. If the file contains `lat`/`lng` fields or the first two columns are numeric, markers are shown on the map.
   - A simple manual‑entry form on the dashboard allows adding a single latitude/longitude/name point without a file.

## Next steps

- Implement 2FA (e.g. TOTP via Google Authenticator). Hooks exist in `auth.php` where the check can be inserted.
- Add email sending for registration and password resets (TODO comments in `auth.php`).
- Encrypt sensitive database columns using application‑level or MySQL AES (helper functions are defined in `includes/db.php`). Currently the user `name` field is encrypted on registration and decrypted when loaded.
- Add API integration and data visualization features (example import script `api_fetch.php`).
- Improve accessibility (ARIA labels, keyboard navigation) and error messaging.

Contact: Yunus.mayat@bradford.gov.uk
