# Rider Subdomain Setup Guide

This guide explains how to configure the rider subdomain for both local development and production environments.

## Overview

The rider interface is accessible via:
- **Production**: `rider.neocafe.shop` or `rider.neocafe.cafe`
- **Local**: `rider.localhost` or `rider.neocafe.local`

## Local Development Setup (XAMPP)

### 1. Update Windows Hosts File

Add the rider subdomain to your hosts file:

**Location**: `C:\Windows\System32\drivers\etc\hosts`

**Add this line**:
```
127.0.0.1    rider.neocafe.local
127.0.0.1    rider.localhost
```

### 2. Update Apache VirtualHost Configuration

**Location**: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`

**Add this VirtualHost** (or update existing):

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/NeoCafe"
    ServerName rider.neocafe.local
    ServerAlias rider.localhost
    
    <Directory "C:/xampp/htdocs/NeoCafe">
        AllowOverride All
        Require all granted
        Options Indexes FollowSymLinks MultiViews
    </Directory>
    
    # Log files
    ErrorLog "logs/rider-neocafe-error.log"
    CustomLog "logs/rider-neocafe-access.log" combined
</VirtualHost>
```

### 3. Restart Apache

```bash
# Stop Apache
C:\xampp\apache\bin\httpd.exe -k stop

# Start Apache
C:\xampp\apache\bin\httpd.exe -k start
```

Or use XAMPP Control Panel to restart Apache.

### 4. Test Local Access

Visit: `http://rider.neocafe.local` or `http://rider.localhost`

Should redirect to: `/rider/orders.php`

## Production Setup

### 1. DNS Configuration

Add an A record or CNAME for the rider subdomain:

**Option A: A Record**
```
Type: A
Name: rider
Value: [Your server IP]
TTL: 3600
```

**Option B: CNAME**
```
Type: CNAME
Name: rider
Value: neocafe.shop
TTL: 3600
```

### 2. Apache/Nginx Configuration

**For Apache** (add to your VirtualHost config):

```apache
<VirtualHost *:80>
    DocumentRoot "/path/to/NeoCafe"
    ServerName rider.neocafe.shop
    ServerAlias rider.neocafe.cafe
    
    <Directory "/path/to/NeoCafe">
        AllowOverride All
        Require all granted
        Options Indexes FollowSymLinks MultiViews
    </Directory>
    
    ErrorLog "logs/rider-neocafe-error.log"
    CustomLog "logs/rider-neocafe-access.log" combined
</VirtualHost>

# SSL Configuration (recommended)
<VirtualHost *:443>
    DocumentRoot "/path/to/NeoCafe"
    ServerName rider.neocafe.shop
    ServerAlias rider.neocafe.cafe
    
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem
    
    <Directory "/path/to/NeoCafe">
        AllowOverride All
        Require all granted
        Options Indexes FollowSymLinks MultiViews
    </Directory>
</VirtualHost>
```

**For Nginx**:

```nginx
server {
    listen 80;
    server_name rider.neocafe.shop rider.neocafe.cafe;
    root /path/to/NeoCafe;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 3. SSL Certificate (Production)

Use Let's Encrypt for free SSL:

```bash
certbot --apache -d rider.neocafe.shop
# or
certbot --nginx -d rider.neocafe.shop
```

## How It Works

### Domain Routing Logic

The `index.php` file detects the domain and routes accordingly:

```php
if (isAdminDomain($current_domain)) {
    // Redirect to admin login
    redirect('/backend/login/admin/admin-login.php');
} elseif (isRiderDomain($current_domain)) {
    // Redirect to rider orders page
    redirect('/rider/orders.php');
} else {
    // Redirect to user dashboard
    redirect('/frontend/pages/home/user-dashboard.php');
}
```

### Supported Domains

**Admin:**
- `admin.neocafe.shop` (production)
- `admin.localhost` (local)
- `admin.neocafe.local` (XAMPP)

**Rider:**
- `rider.neocafe.shop` (production)
- `rider.neocafe.cafe` (production alternative)
- `rider.localhost` (local)
- `rider.neocafe.local` (XAMPP)

**Main/User:**
- `neocafe.shop` (production)
- `localhost` (local)
- `neocafe.local` (XAMPP)

## Testing

### Local Testing

1. **Test rider subdomain**:
   ```
   http://rider.neocafe.local
   ```
   Should redirect to rider orders page

2. **Test admin subdomain**:
   ```
   http://admin.neocafe.local
   ```
   Should redirect to admin login

3. **Test main domain**:
   ```
   http://neocafe.local
   ```
   Should redirect to user dashboard

### Production Testing

1. Wait for DNS propagation (up to 48 hours, usually faster)
2. Test: `https://rider.neocafe.shop`
3. Verify SSL certificate is valid
4. Check that routing works correctly

## Troubleshooting

### "This site can't be reached" (Local)

1. Check hosts file has the entry
2. Verify Apache is running
3. Check VirtualHost configuration
4. Restart Apache

### DNS Not Resolving (Production)

1. Check DNS records are correct
2. Wait for DNS propagation
3. Use `nslookup rider.neocafe.shop` to verify
4. Clear browser DNS cache

### Redirect Loop

1. Check .htaccess files
2. Verify domain-config.php paths are correct
3. Clear browser cache and cookies

### SSL Certificate Issues

1. Ensure certificate covers subdomain
2. Use wildcard certificate (*.neocafe.shop)
3. Or generate separate certificate for rider subdomain

## Security Considerations

1. **Rider Authentication**: Implement proper rider login system (currently uses admin auth)
2. **HTTPS**: Always use SSL in production
3. **Access Control**: Restrict rider interface to authorized personnel
4. **Session Security**: Use secure session cookies

## Next Steps

After setup:
1. Create rider login system (separate from admin)
2. Assign riders to specific orders
3. Implement rider tracking/management
4. Add rider dashboard with statistics

## Support

For issues:
- Check Apache error logs: `C:\xampp\apache\logs\error.log`
- Check rider-specific logs: `logs/rider-neocafe-error.log`
- Verify PHP errors: `C:\xampp\php\logs\php_error_log`
