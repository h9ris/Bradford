# Bradford Portal - Implementation Checklist

## ✅ Completed Features

### Core Authentication
- [x] User registration with email and password
- [x] User login with session management
- [x] Bcrypt password hashing for security
- [x] Password reset workflow with 15-minute token expiry
- [x] Activity logging (login, register, upload, etc.)
- [x] Database schema with encrypted fields

### User Interface
- [x] Login page with email/password form
- [x] Registration page with password confirmation
- [x] User dashboard with welcome message
- [x] File upload form (CSV/JSON)
- [x] **Manual data entry form** (latitude, longitude, name)
- [x] Map view using Google Maps API
- [x] Logout functionality
- [x] Admin dashboard access link (for admins)

### Admin Features
- [x] Admin dashboard showing all users
- [x] Ability to reset any user's password
- [x] Activity log viewer (see who logged in, uploaded, etc.)
- [x] API import script example
- [x] Access control (non-admins cannot access admin pages)

### Data Management
- [x] Database storage for users, uploads, activity logs
- [x] CSV file upload and parsing
- [x] JSON file upload and parsing
- [x] Single-point manual entry (no file needed)
- [x] Map marker display from uploaded data
- [x] Encryption helpers for sensitive data (user name is encrypted)
- [x] Asset/category tables and interaction tracking
- [x] Category management UI (admin)
- [x] Asset CRUD interface with categories
- [x] Map visualization of assets with colors
- [x] Interaction count per asset

### Styling & Accessibility
- [x] Bradford blue colour scheme (`#005ea5`)
- [x] Basic responsive layout
- [x] Form styling
- [x] Error/success message styling
- [x] Activity log table styling

---

## 🔄 In Progress / Partially Implemented

### Encryption
- [x] Database encryption helpers defined (`encrypt_data`, `decrypt_data`)
- [x] User name field encrypted on registration
- [ ] Email field encryption (optional)
- [ ] Optional: full-database encryption

### Email Notifications
- [x] Stub functions for registration email
- [x] Stub functions for password reset email
- [x] **ACTUAL EMAIL SENDING** (requires PHPMailer or similar)
  - [x] PHPMailer installed via Composer
  - [x] SMTP server configured (localhost:1025 for MailHog, configurable for production)
  - [x] `send_registration_email()` called after successful registration
  - [x] `send_reset_email()` called after password reset requested
  - [x] Professional HTML + text templates
  - [x] Email configuration & testing page

### Two-Factor Authentication (2FA)
- [x] TOTP helper functions fully implemented in `includes/totp.php`
- [x] **ACTUAL TOTP SETUP AND VERIFICATION** (COMPLETE)
  - [x] spomky-labs/otphp installed via Composer (v11.4)
  - [x] `two_factor_secret` column added to `users` table
  - [x] `setup_2fa.php` page for QR code setup + management
  - [x] 2FA verification page on login (2-step authentication)
  - [x] 2FA management link on dashboard (portal.php)
  - [x] Activity log records enable/disable events
  - [x] Supports all TOTP authenticator apps (Google Authenticator, Authy, etc.)
  - [x] Time-based verification with ±60 second tolerance window

---

## ❌ Not Yet Started

### Data Visualization
- [ ] Charts/graphs for uploaded data
- [ ] Data filtering and export
- [ ] Table views with sorting/pagination
- [ ] Heatmaps for location clusters

### API Integration
- [ ] Real API connection to Bradford Council data sources
- [ ] School locations data import
- [ ] Parks/outdoor spaces data import
- [ ] Live data refresh mechanism
- [ ] Error handling for failed API calls

### Advanced Features
- [ ] User profile page (edit name, preferences)
- [ ] Download uploaded data as CSV/JSON
- [ ] Data sharing between users
- [ ] Search/filter functionality
- [ ] Bulk data import from web interfaces
- [ ] Audit trails and data versioning

### Accessibility Enhancements
- [ ] ARIA labels on all form inputs
- [ ] Keyboard navigation testing
- [ ] Screen reader compatibility check
- [ ] High contrast mode option
- [ ] Font size adjustment

### Security & Deployment
- [ ] HTTPS enforcement
- [ ] Secure cookie flags (HttpOnly, Secure, SameSite)
- [ ] Rate limiting on login attempts
- [ ] CSRF token protection on forms
- [ ] SQL injection tests and remediation
- [ ] Production environment setup (not localhost)

---

## ✨ Newly Completed (March 4, 2026)

### Schools Management System
- [x] `schools` database table with 20+ fields
- [x] `school_performance` table for KS4/KS5 metrics
- [x] School import page (`import_schools.php`)
- [x] Schools directory with search/filtering (`schools.php`)
- [x] Town filter, school type filter, name search

### School Geocoding
- [x] Geocoding page (`geocode_schools.php`)
- [x] OpenStreetMap Nominatim integration
- [x] Automatic address-to-coordinates conversion
- [x] Bradford city center fallback
- [x] Batch geocoding (~30 seconds for 100 schools)

### Performance Data
- [x] Performance data import (`import_performance.php`)
- [x] KS4/KS5 metrics by URN linking
- [x] Academic year tracking
- [x] Attainment 8, Progress 8, OFSTED fields

### Map Integration
- [x] Schools display on portal map as **blue markers**
- [x] Custom assets remain with **category colors**
- [x] Info windows show school details
- [x] Clickable markers with full information

### Email System
- [x] PhpMailer integration (installed)
- [x] Registration confirmation emails
- [x] Password reset emails with secure tokens
- [x] Email configuration page (`email_config.php`)
- [x] MailHog support for development
- [x] Production SMTP configuration ready
- [x] Professional HTML + text templates

---

### To Add Email Sending:

```bash
# 1. Install PHPMailer
cd /Applications/XAMPP/xamppfiles/htdocs/BradfordPortal
composer require phpmailer/phpmailer

# 2. Update includes/mailer.php with SMTP credentials
# 3. Call send_registration_email() in register.php after register_user()
# 4. Call send_reset_email() in includes/auth.php after send_password_reset()
```

### To Add 2FA:

```bash
# 1. Install TOTP library
composer require spomky-labs/otphp

# 2. Add 2fa_secret column to users table:
# ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(255) DEFAULT NULL;

# 3. Create 2fa-setup.php page to generate secret and QR code
# 4. Create 2fa-verify.php to verify code on login
# 5. Update login_user() to check for and validate TOTP
```

### To Add Encryption:

```bash
# Email field is optional, but if needed:
# 1. In register_user(), add: $email = encrypt_data($email);
# 2. In login_user(), add: $email = decrypt_data($email);
# Note: Be careful with encrypted search queries (they won't work the same way)
```

---

## 📝 Files Overview

```
BradfordPortal./
├── index.php                    ← Login page (ready)
├── register.php                 ← Registration (+ name field) (ready)
├── portal.php                   ← User dashboard + map (ready)
├── forgot.php                   ← Password reset request (ready)
├── reset.php                    ← Password reset confirm (ready)
├── logout.php                   ← Session logout (ready)
├── upload.php                   ← File & manual entry upload (ready)
│
├── includes/
│   ├── auth.php                 ← Login/register/reset functions (ready)
│   ├── db.php                   ← Database connection + encryption (ready)
│   ├── mailer.php               ← Email stub (needs real config)
│   └── totp.php                 ← 2FA stub (needs library)
│
├── css/
│   ├── admin.php                ← Admin dashboard (ready)
│   ├── reset_user.php           ← Admin password reset (ready)
│   ├── activity_log.php         ← Admin activity log (ready)
│   └── style.css                ← Styling (ready)
│
├── js/
│   └── map.js                   ← Google Maps helper (ready)
│
├── api_fetch.php                ← API import example (ready)
├── schema.sql                   ← Database schema (ready)
└── README.md                    ← Setup docs (updated)
```

---

## 🎯 Next Priorities (Recommended Order)

1. **Email setup** (makes registration/password reset functional)
2. **Map marker testing** (add Google API key)
3. **2FA implementation** (adds security)
4. **Data visualization** (improves user experience)
5. **Real API integration** (pulls live Bradford data)
6. **Accessibility audit** (ensures compliance)

---

## 📧 Contact

For questions or to report issues:  
**Yunus.mayat@bradford.gov.uk**
