# Bradford Portal - Windows XAMPP Quick Start Guide

## ✅ What's Already Done

Your portal already has:
- ✅ User registration & login with encrypted passwords (bcrypt)
- ✅ Admin dashboard with user management
- ✅ Activity logging (who logged in, uploaded files, etc.)
- ✅ Google Maps integration for viewing data
- ✅ CSV/JSON file upload
- ✅ Manual data entry form
- ✅ Password reset with 15-minute token expiry
- ✅ Bradford color scheme (#005ea5)

## 🎯 Setup Steps (Do This Now)

### Step 0: Create the Database (2 minutes)

1. Ensure MySQL is running in XAMPP.
2. Run the schema script:
   ```powershell
   mysql -u root < C:\xampp\htdocs\BradfordPortal\schema.sql
   ```

> **Note:** The updated schema includes a `login_attempts` table used by the rate-limiting feature. If you imported the database before March 2026 and see a "table not found" error when logging in, add it manually with:
> ```sql
> CREATE TABLE IF NOT EXISTS login_attempts (
>     id INT AUTO_INCREMENT PRIMARY KEY,
>     ip_address VARCHAR(45) NOT NULL,
>     email VARCHAR(255),
>     attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
>     successful TINYINT(1) DEFAULT 0,
>     INDEX idx_ip_time (ip_address, attempt_time),
>     INDEX idx_email_time (email, attempt_time)
> ) ENGINE=InnoDB;
> ```
> Re-run the schema command if you prefer instead.

### Step 1: Install Composer (5 minutes)

**Method A: Installer (Easiest)**
1. Download: https://getcomposer.org/Composer-Setup.exe
2. Run the installer
3. When asked for PHP, enter: `C:\xampp\php\php.exe`
4. Click "Install"

**Method B: Verify Installation**
Open PowerShell and type:
```powershell
composer --version
```

### Step 2: Install Dependencies (2 minutes)

Open PowerShell and run:
```powershell
cd C:\xampp\htdocs\BradfordPortal
composer install
```

This installs PHPMailer and creates a `vendor/` folder.

### Step 2a: (Optional) Enable two-factor authentication
A simple TOTP system is already built in. Once you have an account, log in and visit:

```
http://localhost/BradfordPortal/twofactor_setup.php
```

You can enable/disable 2FA and scan the QR code with Google Authenticator or similar.

### Step 3: Configure Email (5 minutes)

After logging in you can also:
- visit `visualize.php` to see upload statistics
- visit `export.php` to download your data as JSON or CSV


### Step 3a: Add Google Maps API key (optional but needed for map display)
Open `portal.php` and replace `YOUR_API_KEY` with a valid key from Google Cloud. A warning will appear on the dashboard if the key is missing or left as the placeholder. Use a **restricted key** (HTTP referrer) to avoid quota issues.

(You can alternatively define it globally by adding to `includes/db.php` or another config file: `define('MAPS_API_KEY','your_key_here');`.)


Edit: `C:\xampp\htdocs\BradfordPortal\includes\mailer.php`

**Lines 30-35** - Change these four settings:

```php
define('MAIL_HOST', 'smtp.gmail.com');           // Your SMTP server
define('MAIL_PORT', 587);                        // 587 for TLS, 465 for SSL
define('MAIL_USERNAME', 'your@gmail.com');       // Your email
define('MAIL_PASSWORD', 'your_app_password');    // See below
```

**For Gmail (Recommended for Testing):**
1. Go to: https://myaccount.google.com/apppasswords
2. Select "Mail" and "Windows"
3. Copy the 16-character password
4. Paste into `MAIL_PASSWORD` above
5. That's it!

**For Bradford Exchange Server:**
Ask IT for:
- SMTP hostname
- Port (usually 587)
- Your username and password

**For Office 365:**
```php
define('MAIL_HOST', 'smtp.office365.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your@bradford.gov.uk');
define('MAIL_PASSWORD', 'your_password');
```

**For Local Testing (Mailpit):**
1. Download: https://github.com/axllent/mailpit/releases (get `mailpit_windows_amd64.exe`)
2. Run `mailpit.exe` in a terminal
3. Configure:
```php
define('MAIL_HOST', 'localhost');
define('MAIL_PORT', 1025);
```
4. View emails at: http://localhost:8025

### Step 4: Update Encryption Key (2 minutes)

Edit: `C:\xampp\htdocs\BradfordPortal\includes\db.php`

Line 20 - Change from:
```php
define('APP_ENCRYPT_KEY', 'replace-with-32-byte-key-here...');
```

Generate a secure key in PowerShell:
```powershell
$bytes = [System.Security.Cryptography.RNGCryptoServiceProvider]::new()
$key = [byte[]]::new(32)
$bytes.GetBytes($key)
$hexKey = -join $key.ForEach({ '{0:x2}' -f $_ })
Write-Host $hexKey
```

Copy the output and paste it as your key:
```php
define('APP_ENCRYPT_KEY', '<paste-here>');
```

### Step 5: Test Configuration (2 minutes)

1. Go to: http://localhost/BradfordPortal/test_email.php
2. If PHPMailer is installed, you'll see the config
3. Enter a test email address
4. Click "Send Test Email"
5. Check your inbox for the test email

### Step 6: Delete Test File (Security)

After testing, delete the test file:
```powershell
Remove-Item C:\xampp\htdocs\BradfordPortal\test_email.php
```

---

## 🚀 Now Your Portal Works!

### New User Registration:
- User goes to: http://localhost/BradfordPortal/register.php
- Fills in name, email, password
- **✅ Receives confirmation email automatically**

### Forgot Password:
- User goes to: http://localhost/BradfordPortal/forgot.php
- Enters email
- **✅ Receives reset link (valid 15 minutes) automatically**
- Clicks link, sets new password
- **✅ Password updated securely**

### Admin Functions:
- Login with admin account: http://localhost/BradfordPortal/admin_login.php
- View all registered users
- Reset any user's password
- View activity log (who did what, when)

---

## 📊 Dashboard Features

Once logged in, users can:
- ✅ Upload CSV/JSON files with asset data
- ✅ View assets on Google Maps
- ✅ Add single manual data points (lat/lng/name)
- ✅ See formatted data tables

---

## ⚙️ Troubleshooting

### "Failed to send email"
- Check SMTP settings are correct
- For Gmail, use **App Password**, not regular password
- Verify port 587 isn't blocked by firewall

### "PHPMailer not found"
Run: `composer install` again

### "Email not received"
- Check spam/junk folder
- If using Mailpit, check http://localhost:8025

### Still having issues?
Contact: Yunus.mayat@bradford.gov.uk

---

## 🔒 Security Checklist

- [ ] Changed `APP_ENCRYPT_KEY` in `includes/db.php`
- [ ] Configured SMTP settings
- [ ] Tested email with `test_email.php`
- [ ] Deleted `test_email.php` after testing
- [ ] Created test user account
- [ ] Verified registration email received
- [ ] Tested password reset flow

---

## 📋 Next Steps (Future Features)

Already built with stub code:
- 2FA (Two-Factor Authentication) - `includes/totp.php`
- API data import - `api_fetch.php`
- Database encryption helpers - fully in place

When you're ready:
1. **2FA Setup:** Install `spomky-labs/totp-lib` via Composer
2. **API Integration:** Update `api_fetch.php` with real data sources
3. **Visualization:** Consider Chart.js or Google Charts for dashboards

---

## 📁 File Structure

```
BradfordPortal/
├── index.php                 # Login page
├── register.php              # Registration (sends email)
├── forgot.php                # Password reset (sends email)
├── reset.php                 # Password reset form
├── portal.php                # User dashboard
├── admin.php                 # Admin dashboard
├── admin_login.php           # Admin login
├── upload.php                # File upload handler
├── includes/
│   ├── auth.php             # Authentication functions
│   ├── db.php               # Database connection
│   ├── mailer.php           # ⭐ Email function (CONFIGURED)
│   └── totp.php             # 2FA skeleton
├── css/style.css            # Bradford colors
├── js/map.js                # Google Maps code
├── composer.json            # PHP dependencies
└── WINDOWS_EMAIL_SETUP.md   # Detailed email guide
```

---

## 🎓 Testing Checklist

After setup, test these flows:

1. **Registration + Email**
   - Go to register.php
   - Create account with name, email, password
   - Check email for confirmation

2. **Login**
   - Go to index.php
   - Login with created credentials
   - See user dashboard

3. **Password Reset**
   - Go to forgot.php
   - Enter email
   - Check email for reset link
   - Click link and set new password
   - Login with new password

4. **Admin Dashboard**
   - Login as first user (auto-admin)
   - Go to admin.php
   - See users, activity log, reset options

5. **Data Upload**
   - Upload sample CSV: `lat,lng,name`
   - View markers on map

---

## 📞 Support

If anything doesn't work:
1. Check `WINDOWS_EMAIL_SETUP.md` for detailed instructions
2. Run `test_email.php` to diagnose email issues
3. Check XAMPP MySQL is running
4. Verify database created: `mysql -u root` then `SHOW DATABASES;`

Contact: **Yunus.mayat@bradford.gov.uk**

---

**Last updated:** March 2026 | **Status:** Ready for Testing ✅
