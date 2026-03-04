# Bradford Portal - Schools & Email Setup Guide

## ✅ Newly Implemented Features

### 1. **Schools Management System**
- **Database Tables**: `schools` and `school_performance`
- **Features**: Store school info, geocode addresses, track performance data

### 2. **School Geocoding**
- Automatic geocoding of school addresses using OpenStreetMap Nominatim
- Bradford city center fallback if address not found
- Adds latitude/longitude for map display

### 3. **Performance Data Import**
- Import KS4/KS5 performance metrics (Attainment 8, Progress 8, etc.)
- Links data to schools by URN
- Historical tracking by academic year

### 4. **Real Email Sending** 
- Registration confirmation emails
- Password reset emails with secure tokens
- PHPMailer integration with SMTP support
- Professional HTML + text templates

---

## 🚀 Quick Start

### Step 1: Create Database Tables
```sql
-- In phpMyAdmin, run: schools_schema.sql
-- Creates 'schools' and 'school_performance' tables
```

### Step 2: Import Schools
1. Login as admin
2. Go to **Admin Panel** → **Import Schools**
3. Upload `380_school_information.csv`
4. ✓ Schools imported (closed schools skipped)

### Step 3: Geocode Schools (Add Coordinates)
1. Go to **Admin Panel** → **🌍 Geocode Schools**
2. Upload `380_school_information.csv` again
3. ✓ Addresses geocoded, coordinates saved
4. Schools now appear on map with blue markers

### Step 4: Import Performance Data
1. Go to **Admin Panel** → **📊 Import Performance Data**
2. Upload any KS4 CSV file (e.g., `380_ks4revised.csv`)
3. ✓ Performance metrics linked to schools by URN
4. Data searchable and filterable in schools directory

### Step 5: Configure Email
1. Go to **Admin Panel** → **📧 Email Configuration**
2. Choose your setup:
   
   **For Development (Recommended):**
   ```bash
   # Install MailHog
   brew install mailhog
   
   # Run MailHog
   mailhog
   
   # View emails at http://localhost:8025
   ```
   
   **For Production:**
   Set environment variables:
   ```bash
   export MAIL_SMTP_HOST=smtp.gmail.com
   export MAIL_SMTP_PORT=587
   export MAIL_SMTP_USER=your@email.com
   export MAIL_SMTP_PASS=app_password
   export MAIL_SMTP_SECURE=tls
   export MAIL_FROM_EMAIL=noreply@yoursite.com
   ```

3. Test email sending in configuration page
4. ✓ Registration/password reset emails now sent automatically

---

## 📚 New Admin Features

| Feature | Location | Action |
|---------|----------|--------|
| Import Schools | Admin → Import Schools | Upload CSV with school info |
| Geocode Schools | Admin → 🌍 Geocode Schools | Add lat/lng coordinates |
| Performance Data | Admin → 📊 Import Performance Data | Upload KS4/KS5 metrics |
| Email Config | Admin → 📧 Email Configuration | Configure SMTP & test |
| Schools Directory | Portal → 📚 Schools Directory | Browse/filter schools |

---

## 🗺️ Map Display

The portal map now shows:
- **Blue Markers** = Imported schools (from `schools` table)
- **Colored Markers** = Custom assets by category
- **Click** on markers for details (name, type, address)
- **Filter** by category in manage assets

---

## 📧 Email Features

### Registration Email
- Sent automatically when user creates account
- Confirms account created successfully
- Includes login link

### Password Reset Email
- Sent when user requests reset
- Includes secure 15-minute token link
- HTML + plain text versions
- Professional template with Bradford branding

---

## 🔧 Troubleshooting

**Q: Geocoding is slow?**  
A: Each address takes ~300ms to query OpenStreetMap. 100 schools ≈ 30 seconds. This is normal and respectful to the service.

**Q: Emails not sending in development?**  
A: Install MailHog - captures all outgoing emails for testing without actual SMTP.

**Q: Emails sending but look plain?**  
A: Check email client supports HTML. All emails include plain text fallback.

**Q: Performance data import shows "school not found"?**  
A: Schools must be imported first with matching URNs.

---

## 📊 Database Schema

### schools table
```
id (PK), urn (UNIQUE), name, street, town, postcode, school_type, 
religious_character, gender, min_age, max_age, latitude, longitude, 
status, phone, email, website, headteacher, total_pupils, description
```

### school_performance table
```
id (PK), school_id (FK), urn, academic_year, ks2_progress, ks4_progress,
attainment_8, progress_8, ebacc_participation, ofsted_rating, ofsted_date
```

---

## ✨ Next Steps

1. **Populate with real data**: Import actual Bradford Council schools and performance data
2. **Add charts/analytics**: Display performance trends by school/town
3. **Parent dashboard**: Allow parents to search schools by postcode
4. **Notifications**: Email admins when new data imported
5. **API integration**: Sync live data from DataHub periodically

---

**Status**: All features implemented and tested. Ready for production use.
