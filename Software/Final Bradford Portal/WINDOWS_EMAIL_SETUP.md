# Bradford Portal - Email Setup for Windows XAMPP

## Step 1: Install Composer (if not already installed)

### Option A: Using Composer Installer (Recommended for Windows)
1. Download: https://getcomposer.org/Composer-Setup.exe
2. Run the installer
3. When asked for PHP, point it to: `C:\xampp\php\php.exe`
4. Complete the installation

### Option B: Verify Installation
Open PowerShell and run:
```powershell
composer --version
```

---

## Step 2: Install PHPMailer

Navigate to your project folder in PowerShell:
```powershell
cd C:\xampp\htdocs\BradfordPortal
composer require phpmailer/phpmailer
```

This creates:
- `composer.json` - package list
- `composer.lock` - locked versions
- `vendor/` folder - the actual libraries

---

## Step 3: Configure SMTP Settings

Choose ONE of the options below:

### **Option A: Gmail SMTP (Easy for Testing)**

1. Go to: https://myaccount.google.com/apppasswords
2. Create an **App Password** for "Mail" and "Windows"
3. Copy the 16-character password
4. Update `includes/mailer.php`:

```php
$mail->Host       = 'smtp.gmail.com';
$mail->Username   = 'your.email@gmail.com';    // Your Gmail address
$mail->Password   = 'xxxx xxxx xxxx xxxx';      // 16-char app password
```

**Port:** 587 (already configured)

---

### **Option B: Bradford Council Exchange Server**

If you have access to Bradford's own email server:

```php
$mail->Host       = 'exchange.bradford.gov.uk';  // Ask IT for server
$mail->Username   = 'your.email@bradford.gov.uk';
$mail->Password   = 'your_password';
$mail->Port       = 587;  // or 25, ask IT
```

---

### **Option C: Mailpit (Development - No External SMTP)**

Perfect for testing without real email credentials:

1. Download: https://github.com/axllent/mailpit/releases (download `.exe` for Windows)
2. Run `mailpit.exe` in a terminal
3. It starts on: `http://localhost:1025` (for sending) and `http://localhost:8025` (for viewing)
4. Configure in `includes/mailer.php`:

```php
$mail->Host       = 'localhost';
$mail->Port       = 1025;
$mail->SMTPAuth   = false;  // No auth needed for localhost
```

Then visit `http://localhost:8025` to see all emails sent during testing.

---

## Step 4: Update the Encryption Key

In `includes/db.php`, change:
```php
define('APP_ENCRYPT_KEY', 'replace-with-32-byte-key-here-make-it-secure');
```

To a proper 32-byte key. You can generate one in PowerShell:
```powershell
$bytes = [System.Security.Cryptography.RNGCryptoServiceProvider]::new()
$key = [byte[]]::new(32)
$bytes.GetBytes($key)
-join $key.ForEach({ '{0:x2}' -f $_ })
```

Copy the output and use it as your key.

---

## Step 5: Test Email Sending

1. Go to: `http://localhost/BradfordPortal/register.php`
2. Create a test account
3. Check your mail provider (Gmail inbox, or Mailpit at `http://localhost:8025`)
4. You should see the registration confirmation email

---

## Troubleshooting

### "Failed to send email"
- Check SMTP credentials are correct
- Confirm Port is accessible (87, 25, etc.)
- If using Gmail, make sure you created an **App Password** (not regular password)
- Check firewall isn't blocking SMTP port

### "SMTP Auth failed"
- Wrong password or username
- May need to enable "Less secure apps" in Gmail (old accounts)
- Try the Gmail App Password method above

### "Connection refused"
- SMTP server is down or unreachable
- Check hostname spelling
- Confirm port number with your email provider

---

## Configuration Summary

Once done:
- ✅ Registration emails sent automatically
- ✅ Password reset links sent to user email
- ✅ 15-minute token expiry enforced
- ✅ Activity logging captures all actions

Contact: Yunus.mayat@bradford.gov.uk
