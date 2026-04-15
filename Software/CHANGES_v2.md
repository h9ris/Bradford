# Bradford Portal - Update v2 Changes
**Updated:** April 2026

## What Was Changed

### 1. Email Now Working ✅
- `includes/mailer.php` — Beautiful HTML email templates for both:
  - **Registration**: "Welcome to Bradford Council Portal" with blue header and sign-in button
  - **Password Reset**: Secure reset link with 15-minute expiry warning
- **To activate**: Edit the 4 constants at the top of `includes/mailer.php`:
  ```php
  define('MAIL_USERNAME', 'your@gmail.com');      // your Gmail
  define('MAIL_PASSWORD', 'your_app_password');   // Gmail App Password
  ```
  Gmail App Password: myaccount.google.com → Security → 2-Step Verification → App Passwords

### 2. 2FA Fixed ✅
- `includes/auth.php` — `login_user()` now returns `'2fa_required'` string when password is correct but 2FA code is wrong/missing
- `index.php` — Shows a proper error message specifically for 2FA failures; "Show 2FA field" link reveals code input
- Existing TOTP logic (`includes/totp.php`) was already correct — no changes needed

### 3. Name → First Name + Last Name ✅
- `register.php` — Two separate required fields (First Name, Last Name) replace the old optional Name field
- `includes/auth.php` — `register_user()` now accepts `$first_name`, `$last_name`; auto-migration adds DB columns
- `profile.php` — Two separate fields for editing
- `schema.sql` — Added `first_name` and `last_name` columns; `name` kept for backward compatibility
- **Migration**: Run `migrate_v2.sql` on existing databases

### 4. Bradford Schools Dataset ✅
- 15 real Bradford schools with coordinates, Ofsted ratings, addresses, and phone numbers
- Added to `schema.sql` and `migrate_v2.sql`
- Schools appear as **blue markers** on the portal map

### 5. Colour-Coded Map Pins ✅
- `portal.php` — Markers now colour-coded:
  - 🔵 **Blue** = Schools
  - 🟢 **Green** = Parks  
  - 🟡 **Amber** = Car Parks
  - 🔴 **Red** = Uploaded / Custom data
- Replaced Google Maps API with free **Leaflet.js** (no API key needed)
- Legend shown in sidebar

### 6. Modern UI with Bradford Branding ✅
- `css/style.css` — Full redesign:
  - Bradford blue (#005ea5) as primary colour throughout
  - Bradford skyline silhouette on login/register background
  - Sticky header with Bradford Council crest + navigation
  - Card-based layout for the dashboard
  - Sidebar with upload tools + legend + security status
  - Accessibility bar (high contrast, font sizes)
  - Responsive design (mobile-friendly)

## How to Deploy

1. **Replace** your project files with these updated versions
2. **Run** `migrate_v2.sql` in phpMyAdmin (or MySQL CLI) to update your existing DB
3. **Edit** `includes/mailer.php` lines 37-38 with your Gmail/SMTP credentials
4. Test registration → should receive welcome email
5. Test forgot password → should receive reset link

