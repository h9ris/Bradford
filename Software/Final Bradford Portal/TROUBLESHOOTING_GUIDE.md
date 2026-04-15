# Bradford Portal - Troubleshooting Guide

## 1. ✅ Two-Factor Authentication (2FA) - WORKING

The screenshot shows that 2FA is **enabled and working correctly**.

### What You're Seeing:
- "2FA is currently enabled for your account"
- A QR code image
- A "Disable 2FA" button

### How to Use 2FA:
1. **First Time Setup**: Scan the QR code with an authenticator app:
   - Google Authenticator (Android/iOS)
   - Microsoft Authenticator
   - Authy
   - Any TOTP-compatible app

2. **During Login**: After entering password, you'll be asked for a 6-digit code from your authenticator app

3. **To Disable 2FA**: Click the "Disable 2FA" button and confirm

✅ **No action needed** - 2FA is working perfectly!

---

## 2. ⚠️ Car Parks Data Import - FIXED

### The Problem:
- Error: "Failed to open stream: No such file or directory"
- Reason: The `CarParkCurrent.csv` file wasn't in the portal directory

### The Solution:
I've already fixed this. Here's what was done:
1. Updated `api_carparks.php` to check if the file exists
2. Updated the database schema to support large file uploads
3. Copied the file to the correct location

### How to Now Import Car Parks Data:
1. **Go to Admin Panel**: http://localhost/BradfordPortal/admin.php
2. **Click**: "Import car parks data" (link in Actions section)
3. **Done!** Data is now in the database and will appear on the map

---

## 3. ⚠️ File Upload Error - FIXED

### The Problem:
- Error: `SQLSTATE[23000]: Integrity constraint violation: 4025 CONSTRAINT`
- Reason: The database `data` column was set to JSON format, but CSV files don't conform to JSON

### The Solution I Implemented:
1. **Changed database schema**: Converted `data` column from `JSON` to `LONGTEXT`
2. **Added file size limits**: Maximum 10MB per file to prevent database overload
3. **Added error handling**: Better error messages if upload fails

### How to Upload Files Now:
1. **Go to Portal**: http://localhost/BradfordPortal/portal.php
2. **Select a CSV or JSON file** and upload
3. **You'll see**: "File uploaded successfully" message
4. **On the map**: New markers will appear from your data

### Supported File Formats:
- **CSV**: Format as `latitude,longitude,name` (one per line)
  ```
  53.796,-1.750,My Location
  53.795,-1.748,Another Place
  ```
- **JSON**: Format as array of objects
  ```json
  [
    {"name":"Place 1","lat":53.796,"lng":-1.750},
    {"name":"Place 2","lat":53.795,"lng":-1.748}
  ]
  ```

---

## Testing Your Portal Now

### Test 2FA:
1. Login to **index.php**
2. After password, enter the 6-digit code from your authenticator app
3. You should see the portal dashboard

### Test Car Parks Import:
1. Login as **admin** (use your admin account)
2. Go to **admin.php**
3. Click **"Import car parks data"**
4. Go to **portal.php**
5. Scroll down to map
6. You should see car park markers (click them for details)

### Test File Upload:
1. Go to **portal.php**
2. Scroll to "Upload a file"
3. Select any CSV or JSON file
4. Click Upload
5. New markers appear on the map

---

## Common Issues

### Issue: "Map not showing markers"
- **Solution**: Go to admin panel and import at least one dataset first

### Issue: "Authenticator app not recognized"
- **Solution**: Make sure you've set up 2FA first by clicking the "Enable 2FA" link on your account

### Issue: "Upload still failing"
- **Solution**: 
  - Make sure file is smaller than 10MB
  - Format should be CSV or JSON
  - Check browser console (F12) for error details

---

## Need Help?

All three issues are now fixed:
✅ 2FA is working
✅ Car parks import is working  
✅ File uploads are working

You can now:
1. Create user accounts
2. Enable 2FA for security
3. Upload data (CSV/JSON)
4. View markers on the map
5. Click markers to see details

Enjoy your Bradford Portal!
