# Bradford Portal

A comprehensive web application for Bradford Council to manage and visualize geographical data. Developed by **Haris and Team** as a final year project.

## 🎯 Project Overview

The Bradford Portal is a secure, user-friendly web application that allows authorized users to upload, manage, and visualize location-based data for Bradford Council. Our team has implemented enterprise-grade features including user authentication, data sharing, audit trails, and production deployment capabilities.

## 👥 Development Team


## 🚀 Key Features

### Core Functionality
- **Secure Authentication**: User registration, login, and password reset with bcrypt hashing
- **Data Management**: Upload CSV/JSON files or manually enter location data
- **Interactive Maps**: Google Maps integration with marker clustering and heatmap visualization
- **Admin Dashboard**: User management, activity monitoring, and system administration

### Advanced Features
- **Data Sharing**: Share datasets between users with granular permissions
- **Audit Trails**: Complete version history and activity logging
- **Accessibility**: High contrast mode, font size adjustment, screen reader support
- **Bulk Import**: Web-based data import from CSV/JSON text
- **API Integration**: Live data refresh from external sources (car parks, schools, sports facilities)

### Security & Compliance
- **Rate Limiting**: Protection against brute force attacks
- **CSRF Protection**: Cross-site request forgery prevention
- **HTTPS Enforcement**: Automatic SSL redirection in production
- **SQL Injection Prevention**: PDO prepared statements throughout
- **Secure Cookies**: HttpOnly, Secure, and SameSite flags

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+ with PDO database abstraction
- **Database**: MySQL 5.7+ with encrypted sensitive fields
- **Frontend**: HTML5, CSS3, JavaScript with Google Maps API
- **Security**: bcrypt, AES encryption, PHPMailer, TOTP 2FA
- **Deployment**: Apache/Nginx with SSL, automated backups

## 📁 Project Structure

```
BradfordPortal/
├── Core Application
│   ├── index.php              # Login page
│   ├── portal.php             # Main dashboard with map
│   ├── profile.php            # User profile management
│   └── admin.php              # Administrative dashboard
│
├── Data Management
│   ├── table_view.php         # Data table with sorting/filtering
│   ├── share_data.php         # Data sharing interface
│   ├── data_history.php       # Audit trails and versioning
│   └── bulk_import.php        # Web-based data import
│
├── API Integration
│   ├── api_carparks.php       # Car park data import
│   ├── api_schools.php        # School locations import
│   ├── api_sports.php         # Sports facilities import
│   └── refresh_data.php       # Live data refresh
│
├── Security & Testing
│   ├── sql_injection_test.php # Security testing
│   └── PRODUCTION_SETUP.md    # Deployment guide
│
└── Configuration
    ├── includes/auth.php      # Authentication functions
    ├── includes/db.php        # Database connection
    ├── schema.sql             # Database schema
    └── css/style.css          # Application styling
```

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Google Maps API key

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/haris-team/bradford-portal.git
   cd bradford-portal
   ```

2. **Set up the database**
   ```bash
   mysql -u root -p < schema.sql
   ```

3. **Configure the application**
   - Update database credentials in `includes/db.php`
   - Add Google Maps API key to `portal.php`
   - Configure email settings in `includes/mailer.php`

4. **Start the development server**
   ```bash
   # Using PHP built-in server
   php -S localhost:8000
   ```

## 📊 Features Showcase

### Data Visualization
- Interactive maps with custom markers
- Heatmap overlay for data density
- Table views with sorting and pagination
- Export functionality (JSON/CSV)

### User Experience
- Responsive design for all devices
- Accessibility features (WCAG compliant)
- Real-time data updates
- Intuitive navigation

### Administration
- User management interface
- Activity monitoring dashboard
- System health checks
- Automated data imports

## 🔒 Security Features

Our application implements multiple layers of security:

- **Authentication**: Multi-factor authentication support
- **Authorization**: Role-based access control
- **Data Protection**: AES encryption for sensitive data
- **Network Security**: HTTPS enforcement and secure headers
- **Application Security**: Input validation and sanitization

## 📈 Performance & Scalability

- **Database Optimization**: Indexed queries and connection pooling
- **Caching**: Session and data caching mechanisms
- **CDN Ready**: Static asset optimization
- **Monitoring**: Error logging and performance metrics

## 🤝 Contributing

This project was developed by Haris and Team as part of our academic coursework. For questions or feedback:

- **Project Lead**: Haris
- **Team Repository**: https://github.com/haris-team/bradford-portal

## 📄 License

This project is developed for educational purposes as part of our university coursework.

---

**Developed with ❤️ by Haris and Team**
- Encrypt sensitive database columns using application‑level or MySQL AES (helper functions are defined in `includes/db.php`). Currently the user `name` field is encrypted on registration and decrypted when loaded.
- Add API integration and data visualization features (example import script `api_fetch.php`).
- Improve accessibility (ARIA labels, keyboard navigation) and error messaging.

Contact: Yunus.mayat@bradford.gov.uk
