# Bradford Portal

This is a simple PHP portal project for Bradford Council with login/registration, admin management, data upload, and Google Maps integration.

## Features implemented

- User registration and login with bcrypt password hashing
- Password reset workflow with email token stub
- Admin dashboard listing users
- File upload handling with storage in `uploads` table
- Activity logging
- Basic map view using Google Maps API, now enhanced with asset/category visualization and colored markers
- Asset management system: create/edit/delete assets, assign to categories, track interactions
- Category management interface (admin) with customizable colors/icons
- Styling with Bradford colour palette and simple accessibility
- Database connection using PDO and basic encryption helpers
- Two-factor authentication (TOTP) fully implemented with QR code setup and login verification
- Email notifications stubs remain; PHPMailer installed for future use

## Setup

1. Install XAMPP (or similar LAMP/AMP stack) and start Apache/MySQL.
2. Create the database and tables by importing `schema.sql`:
   ```sh
   mysql -u root < schema.sql
   ```
3. Update database credentials in `includes/db.php` and set `APP_ENCRYPT_KEY` to a secure 32‑byte string.
4. Obtain a Google Maps API key and replace `YOUR_API_KEY` in `js/map.js` (or `css/portal.php` if still there).  
   Optionally restrict the key to `localhost` for development.
5. (Optional) Configure SMTP settings in `includes/mailer.php` and `includes/auth.php` to enable real email sending.
6. Run Composer from the project root to install PHP dependencies:
   ```sh
   /Applications/XAMPP/bin/php composer.phar install
   ```
7. Ensure the `uploads/` directory exists and is writable by the web server.

After setup, point your browser to `http://localhost/BradfordPortal./` (or rename the folder and use `:BradfordPortal/`). You should see the login page.

## Usage

-- Open `index.php` in a browser to log in or register.
-- For administrators there is a separate login page (`admin_login.php`).
   Once logged in as an admin you will be redirected to `admin.php`, the
   Admin Control Panel.
   - Admins can manage user privileges, view activity logs, and manage asset categories through the new "Manage Asset Categories" link.
   - Users (including admins) can manage assets via `Manage Assets` on the dashboard, assigning them to categories and logging interactions.
   - Users can still upload CSV/JSON files containing coordinate data; these points will appear on the map alongside manually created assets.
   - A simple manual‑entry form on the dashboard allows adding a single latitude/longitude/name point without a file.
   - Two-factor authentication can be enabled/disabled via the dashboard link, protecting each account with TOTP codes.

## Next steps

- Implement 2FA (e.g. TOTP via Google Authenticator). Hooks exist in `auth.php` where the check can be inserted.
- Add email sending for registration and password resets (TODO comments in `auth.php`).
- Encrypt sensitive database columns using application‑level or MySQL AES (helper functions are defined in `includes/db.php`). Currently the user `name` field is encrypted on registration and decrypted when loaded.
- Add API integration and data visualization features (example import script `api_fetch.php`).
- Improve accessibility (ARIA labels, keyboard navigation) and error messaging.

Contact: Yunus.mayat@bradford.gov.uk
