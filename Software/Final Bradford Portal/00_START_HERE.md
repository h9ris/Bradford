# 🚀 START HERE - Bradford Portal Windows Setup

## ⏱️ Total Time: ~15 minutes

Follow these steps IN ORDER. Each step is critical.

---

## STEP 1: Install Composer (5 min)

1. **Download:** https://getcomposer.org/Composer-Setup.exe
2. **Run the installer**
3. **When asked for PHP location,** enter: `C:\xampp\php\php.exe`
4. **Click Install**
5. **Verify** by opening PowerShell and typing:
   ```
   composer --version
   ```
   Should show something like: `Composer 2.x.x`

---

## STEP 2: Install PHPMailer (2 min)

1. **Open PowerShell**
2. **Navigate to project:**
   ```powershell
   cd C:\xampp\htdocs\BradfordPortal
   ```
3. **Run:**
   ```powershell
   composer install
   ```
4. **Wait** for it to complete. You'll see a `vendor/` folder created ✅

---

## STEP 3: Pick Your Email Provider (1 min)

Choose ONE:

### ✅ OPTION A: Gmail (Best for Testing)
- **Fastest to set up**
- **Works immediately**
- **Free**

Go to: https://myaccount.google.com/apppasswords
- Select: Mail + Windows
- Copy the 16-character password
- You'll need this in Step 4

### ✅ OPTION B: Office 365 (If you have it)
- **Ask Bradford IT for:** hostname, port, username
- **Use your regular password**

### ✅ OPTION C: Mailpit (Perfect for Development)
- **Download:** https://github.com/axllent/mailpit/releases
- **Get:** `mailpit_windows_amd64.exe`
- **Run it** in a terminal (it starts a local email server)
- **View emails at:** http://localhost:8025

---

## STEP 4: Configure Email (3 min)

**Open:** `C:\xampp\htdocs\BradfordPortal\includes\mailer.php`

**Find lines 30-35:**
```php
define('MAIL_HOST', 'smtp.gmail.com');           // CHANGE THIS
define('MAIL_PORT', 587);                        // CHANGE THIS  
define('MAIL_USERNAME', 'your@gmail.com');       // CHANGE THIS
define('MAIL_PASSWORD', 'your_app_password');    // CHANGE THIS
```

### If you chose Gmail:
```php
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'YOUR_EMAIL@gmail.com');        // Your Gmail address
define('MAIL_PASSWORD', 'xxxx xxxx xxxx xxxx');         // 16-char app password from Step 3
```

### If you chose Mailpit:
```php
define('MAIL_HOST', 'localhost');
define('MAIL_PORT', 1025);
define('MAIL_USERNAME', '');
define('MAIL_PASSWORD', '');
```

### If you chose Office 365:
```php
define('MAIL_HOST', 'smtp.office365.com');       // Ask IT
define('MAIL_PORT', 587);                         // Ask IT
define('MAIL_USERNAME', 'your@bradford.gov.uk');
define('MAIL_PASSWORD', 'your_password');
```

**Save the file** (Ctrl+S)

---

## STEP 5: Update Encryption Key (2 min)

**Open:** `C:\xampp\htdocs\BradfordPortal\includes\db.php`

**Find line 20:**
```php
define('APP_ENCRYPT_KEY', 'replace-with-32-byte-key-here...');
```

**Generate a new key in PowerShell:**
```powershell
$bytes = [System.Security.Cryptography.RNGCryptoServiceProvider]::new()
$key = [byte[]]::new(32)
$bytes.GetBytes($key)
$hexKey = -join $key.ForEach({ '{0:x2}' -f $_ })
Write-Host $hexKey
```

**Copy the output.** Replace line 20 with:
```php
define('APP_ENCRYPT_KEY', 'paste_your_key_here');
```

**Save the file**

---

## STEP 6: Test Email (2 min)

**Open browser:** http://localhost/BradfordPortal/test_email.php

You should see:
- Current email configuration
- A form to send test email

**Enter your real email address** (Gmail, Office 365, or any email that will receive it)

**Click:** "Send Test Email"

**Check your inbox.** You should see a test email! ✅

---

## STEP 7: Delete Test File (Security) (1 min)

**Open PowerShell:**
```powershell
Remove-Item C:\xampp\htdocs\BradfordPortal\test_email.php
```

(Don't leave test tools on production servers)

---

## ✅ YOU'RE DONE! 

Your portal now has **fully working email** for:
- ✅ Registration confirmations
- ✅ Password reset links (15-minute expiry)
- ✅ All notifications

---

## 🧪 Final Verification

Test these to confirm everything works:

### Test 1: Registration Email
1. Go to: http://localhost/BradfordPortal/register.php
2. Create account with name, email, password
3. **Check your inbox** - should receive confirmation ✅

### Test 2: Password Reset Email
1. Go to: http://localhost/BradfordPortal/forgot.php
2. Enter your email
3. **Check your inbox** - should receive reset link ✅
4. Click the link
5. Set new password
6. Login with new password ✅

### Test 3: Admin Dashboard
1. Login with your account
2. Click "Admin" link (appears because first user is auto-admin)
3. See all users
4. See activity log
5. See password reset options ✅

---

## ❌ If Something Doesn't Work

### Email Not Received
1. Check **spam/junk folder**
2. Run `test_email.php` again to verify config
3. Check SMTP credentials are correct
4. For Gmail: Did you use **App Password**? (not regular password)

### "PHPMailer not found"
1. Run `composer install` again
2. Verify `vendor/` folder exists

### Other Issues
- See `QUICKSTART.md` for full troubleshooting
- See `WINDOWS_EMAIL_SETUP.md` for detailed guide

---

## 📞 Questions?

Contact: **Yunus.mayat@bradford.gov.uk**

---

**Estimated Time: 15 minutes**  
**Difficulty: Easy ✅**  
**Status: Ready to Go 🚀**
