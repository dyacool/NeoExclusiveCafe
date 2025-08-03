# Domain-Based Routing Setup for NeoCafe

This document explains how to set up domain-based routing for NeoCafe, where different domains automatically route to different parts of the application.

## Overview

The system automatically routes users based on the domain they're accessing:

- **`admin.neocafe.cafe`** → Admin login and backend
- **`neocafe.cafe`** → User frontend and customer area

## File Structure

```
NeoCafe/
├── index.php                    # Main router (handles root domain routing)
├── .htaccess                    # Apache URL rewriting rules
├── config/
│   └── domain-config.php        # Domain configuration
├── includes/
│   └── domain-utils.php         # Domain utility functions
├── backend/                     # Admin area
└── frontend/                    # User area
```

## Configuration

### 1. Domain Configuration (`config/domain-config.php`)

This file contains all domain-related settings for different environments:

```php
$domain_config = [
    'production' => [
        'admin_domain' => 'admin.neocafe.cafe',
        'main_domain' => 'neocafe.cafe',
        'admin_path' => '/backend/login/admin/admin-login.php',
        'user_path' => '/frontend/pages/home/user-dashboard.php'
    ],
    'development' => [
        'admin_domain' => 'admin.localhost',
        'main_domain' => 'localhost',
        // ... paths
    ]
];
```

### 2. Environment Detection

The system automatically detects the environment:
- **Production**: `neocafe.cafe` and `admin.neocafe.cafe`
- **Development**: `localhost` and `admin.localhost`
- **XAMPP**: `neocafe.local` and `admin.neocafe.local`

## Setup Instructions

### For Production (Live Server)

1. **DNS Configuration**:
   - Point `neocafe.cafe` to your server
   - Point `admin.neocafe.cafe` to the same server
   - Both domains should point to the same IP address

2. **Web Server Configuration**:
   - Ensure both domains are configured in your web server
   - Both should point to the same document root (`/NeoCafe`)

3. **SSL Certificates** (Recommended):
   - Get SSL certificates for both domains
   - Configure HTTPS redirects

### For Local Development (XAMPP)

1. **Edit hosts file** (`C:\Windows\System32\drivers\etc\hosts` on Windows):
   ```
   127.0.0.1 neocafe.local
   127.0.0.1 admin.neocafe.local
   ```

2. **XAMPP Virtual Hosts** (`C:\xampp\apache\conf\extra\httpd-vhosts.conf`):
   ```apache
   <VirtualHost *:80>
       DocumentRoot "C:/xampp/htdocs/NeoCafe"
       ServerName neocafe.local
       <Directory "C:/xampp/htdocs/NeoCafe">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>

   <VirtualHost *:80>
       DocumentRoot "C:/xampp/htdocs/NeoCafe"
       ServerName admin.neocafe.local
       <Directory "C:/xampp/htdocs/NeoCafe">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

3. **Restart Apache** after making changes

## How It Works

### 1. Root Domain Access

When someone visits the root of either domain:

- **`admin.neocafe.cafe/`** → Redirects to `/backend/login/admin/admin-login.php`
- **`neocafe.cafe/`** → Redirects to `/frontend/pages/home/user-dashboard.php`

### 2. Deep Links

Direct access to specific pages still works:
- **`admin.neocafe.cafe/backend/pages/homepage/admin-homepage.php`** → Works directly
- **`neocafe.cafe/frontend/pages/products/product-dashboard.php`** → Works directly

### 3. Authentication Flow

The system maintains separate authentication for admin and user areas:
- Admin users stay on `admin.neocafe.cafe`
- Regular users stay on `neocafe.cafe`
- Cross-domain access is prevented

## Usage in Code

### Including Domain Utilities

```php
require_once __DIR__ . '/includes/domain-utils.php';

// Check current domain
if (isAdminRequest()) {
    // Admin domain logic
} else {
    // User domain logic
}

// Get appropriate paths
$base_path = getBasePath();
$assets_path = getAssetsPath();
$login_path = getLoginPath();
```

### Domain Validation

```php
// Validate user is on correct domain for their role
validateDomainAccess($_SESSION['user_role']);

// Redirect to appropriate login
redirectToLogin();

// Redirect to appropriate dashboard
redirectToDashboard();
```

## Security Features

1. **Domain Isolation**: Admin and user areas are completely separated
2. **Role Validation**: Users can only access appropriate domains for their role
3. **Security Headers**: Added via .htaccess
4. **File Protection**: Sensitive files are protected from direct access

## Troubleshooting

### Common Issues

1. **Domain not redirecting**:
   - Check DNS settings
   - Verify virtual host configuration
   - Check .htaccess is enabled

2. **404 errors**:
   - Ensure document root is correct
   - Check file permissions
   - Verify Apache mod_rewrite is enabled

3. **SSL issues**:
   - Check SSL certificate configuration
   - Verify HTTPS redirects

### Testing

1. **Test admin domain**: Visit `admin.neocafe.cafe`
2. **Test user domain**: Visit `neocafe.cafe`
3. **Test deep links**: Try accessing specific pages directly
4. **Test authentication**: Login on both domains separately

## Customization

### Adding New Domains

Edit `config/domain-config.php`:

```php
'custom' => [
    'admin_domain' => 'admin.yourdomain.com',
    'main_domain' => 'yourdomain.com',
    'admin_path' => '/backend/login/admin/admin-login.php',
    'user_path' => '/frontend/pages/home/user-dashboard.php'
]
```

### Changing Redirect Paths

Modify the paths in `config/domain-config.php` to point to different pages.

### Adding Environment-Specific Logic

Use the `getEnvironment()` function to add environment-specific behavior. 