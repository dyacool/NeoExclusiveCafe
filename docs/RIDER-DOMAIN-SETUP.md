# Rider Subdomain Setup Guide

This guide explains how to configure the rider subdomain for both local development and production environments.

## Domain Configuration

### Local Development
- **Domain**: `rider.neocafe.cafe:8080`
- **Main Site**: `neocafe.cafe:8080`
- **Admin**: `admin.neocafe.cafe:8080`

### Production
- **Domain**: `rider.neocafe.shop`
- **Main Site**: `neocafe.shop`
- **Admin**: `admin.neocafe.shop`

## Local Setup Instructions

### Step 1: Configure Windows Hosts File

1. Open Notepad as Administrator
2. Open the file: `C:\Windows\System32\drivers\etc\hosts`
3. Add these lines:

```
127.0.0.1    neocafe.cafe
127.0.0.1    rider.neocafe.cafe
127.0.0.1    admin.neocafe.cafe
```

4. Save the file

### Step 2: Configure Apache Virtual Hosts

If using XAMPP, edit `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
# Main domain
<VirtualHost *:8080>
    ServerName neocafe.cafe
    DocumentRoot "C:/xampp/htdocs/your-project-path"
    <Directory "C:/xampp/htdocs/your-project-path">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

# Rider subdomain
<VirtualHost *:8080>
    ServerName rider.neocafe.cafe
    DocumentRoot "C:/xampp/htdocs/your-project-path"
    <Directory "C:/xampp/htdocs/your-project-path">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

# Admin subdomain
<VirtualHost *:8080>
    ServerName admin.neocafe.cafe
    DocumentRoot "C:/xampp/htdocs/your-project-path"
    <Directory "C:/xampp/htdocs/your-project-path">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Step 3: Restart Apache

Restart Apache server from XAMPP Control Panel.

### Step 4: Test the Setup

Open your browser and test:

1. **Main site**: http://neocafe.cafe:8080
   - Should redirect to user dashboard
   
2. **Rider portal**: http://rider.neocafe.cafe:8080
   - Should redirect to `/rider/orders.php`
   
3. **Admin portal**: http://admin.neocafe.cafe:8080
   - Should redirect to admin login

## Production Setup Instructions

### Step 1: DNS Configuration

In your domain registrar (e.g., Namecheap, GoDaddy), add these DNS records:

```
Type    Host    Value               TTL
A       @       your-server-ip      Automatic
A       rider   your-server-ip      Automatic
A       admin   your-server-ip      Automatic
```

### Step 2: Apache/Nginx Configuration

#### For Apache:

```apache
<VirtualHost *:80>
    ServerName neocafe.shop
    ServerAlias www.neocafe.shop
    DocumentRoot /var/www/html/neocafe
    # ... SSL and other configs
</VirtualHost>

<VirtualHost *:80>
    ServerName rider.neocafe.shop
    DocumentRoot /var/www/html/neocafe
    # ... SSL and other configs
</VirtualHost>

<VirtualHost *:80>
    ServerName admin.neocafe.shop
    DocumentRoot /var/www/html/neocafe
    # ... SSL and other configs
</VirtualHost>
```

#### For Nginx:

```nginx
server {
    listen 80;
    server_name neocafe.shop www.neocafe.shop;
    root /var/www/html/neocafe;
    # ... SSL and other configs
}

server {
    listen 80;
    server_name rider.neocafe.shop;
    root /var/www/html/neocafe;
    # ... SSL and other configs
}

server {
    listen 80;
    server_name admin.neocafe.shop;
    root /var/www/html/neocafe;
    # ... SSL and other configs
}
```

### Step 3: SSL Certificates

Use Let's Encrypt to secure all subdomains:

```bash
certbot --apache -d neocafe.shop -d www.neocafe.shop -d rider.neocafe.shop -d admin.neocafe.shop
```

## How It Works

The routing system in `index.php` detects the domain and redirects accordingly:

1. **Domain Detection**: Checks `$_SERVER['HTTP_HOST']`
2. **Environment Detection**: Determines if local or production
3. **Routing Logic**:
   - `rider.neocafe.cafe:8080` or `rider.neocafe.shop` → `/rider/orders.php`
   - `admin.neocafe.cafe:8080` or `admin.neocafe.shop` → `/backend/login/admin/admin-login.php`
   - `neocafe.cafe:8080` or `neocafe.shop` → `/frontend/pages/home/user-dashboard.php`

## Troubleshooting

### Issue: Subdomain not working locally

**Solution**: 
- Verify hosts file entries
- Clear browser cache
- Restart Apache
- Check Apache error logs

### Issue: Infinite redirect loop

**Solution**:
- Check that the target files exist
- Verify session handling in target pages
- Check for conflicting redirects in `.htaccess`

### Issue: Port not included in redirect

**Solution**:
- The configuration handles ports automatically
- Ensure Apache is listening on port 8080 in `httpd.conf`

## Testing Checklist

- [ ] Local main domain loads user dashboard
- [ ] Local rider subdomain loads rider orders page
- [ ] Local admin subdomain loads admin login
- [ ] Production main domain works
- [ ] Production rider subdomain works
- [ ] Production admin subdomain works
- [ ] SSL certificates installed for all domains
- [ ] Redirects work correctly on all domains

## Security Notes

1. Ensure rider authentication is implemented in `/rider/orders.php`
2. Use HTTPS in production for all subdomains
3. Implement rate limiting for login attempts
4. Keep session cookies secure and httpOnly
