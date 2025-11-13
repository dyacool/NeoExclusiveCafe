# Design Document: Clean URL Routing

## Overview

This design implements Apache mod_rewrite rules in the `.htaccess` file to transform user-friendly URLs (e.g., `/user-dashboard`) into internal file paths (e.g., `/frontend/pages/home/user-dashboard.php`). The solution uses a cascading approach that checks multiple potential file locations and preserves all existing functionality including domain routing, security, and performance optimizations.

## Architecture

### URL Resolution Flow

```
Incoming Request: /user-dashboard
    ↓
Domain Routing Check (existing rules)
    ↓
Static File Check (skip rewriting if exists)
    ↓
Clean URL Rewrite Rules
    ↓
Pattern 1: /frontend/pages/{page}/{page}.php
    ↓ (if not found)
Pattern 2: /frontend/pages/{page}.php
    ↓
Serve File or 404
```

### Rule Placement Strategy

The clean URL rules will be inserted after domain routing but before the final catch-all, ensuring:
1. Domain-based routing takes precedence
2. Existing files (CSS, JS, images, APIs) are not rewritten
3. Clean URLs are processed for non-existent paths
4. Security and performance rules remain unaffected

## Components and Interfaces

### 1. Static Resource Bypass

**Purpose:** Prevent rewriting of actual files and directories

**Implementation:**
```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
```

This ensures that if a file or directory actually exists at the requested path, Apache serves it directly without applying URL rewriting rules.

### 2. API Endpoint Protection

**Purpose:** Exclude API endpoints from clean URL rewriting

**Implementation:**
```apache
RewriteCond %{REQUEST_URI} !^/frontend/api/
RewriteCond %{REQUEST_URI} !^/backend/api/
```

API endpoints should maintain their explicit paths for clarity and to avoid conflicts with page routing.

### 3. Primary Page Pattern

**Purpose:** Map clean URLs to nested page structure (most common pattern)

**Pattern:** `/page-name` → `/frontend/pages/page-name/page-name.php`

**Implementation:**
```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{DOCUMENT_ROOT}/frontend/pages/$1/$1.php -f
RewriteRule ^([a-zA-Z0-9-]+)$ /frontend/pages/$1/$1.php [L]
```

**Examples:**
- `/user-dashboard` → `/frontend/pages/home/user-dashboard.php` (requires manual rule)
- `/bulk-form` → `/frontend/pages/bulk/bulk-form.php`
- `/product-list` → `/frontend/pages/products/product-list.php`

### 4. Flat Page Pattern

**Purpose:** Map clean URLs to flat page structure (fallback)

**Pattern:** `/page-name` → `/frontend/pages/page-name.php`

**Implementation:**
```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{DOCUMENT_ROOT}/frontend/pages/$1.php -f
RewriteRule ^([a-zA-Z0-9-]+)$ /frontend/pages/$1.php [L]
```

### 5. Nested Path Support

**Purpose:** Support multi-level URLs like `/products/view`

**Pattern:** `/category/page` → `/frontend/pages/category/page.php`

**Implementation:**
```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{DOCUMENT_ROOT}/frontend/pages/$1/$2.php -f
RewriteRule ^([a-zA-Z0-9-]+)/([a-zA-Z0-9-]+)$ /frontend/pages/$1/$2.php [L]
```

### 6. Special Case Mappings

**Purpose:** Handle pages where the URL name doesn't match the directory structure

**Examples:**
- `/user-dashboard` is in `/frontend/pages/home/` not `/frontend/pages/user-dashboard/`

**Implementation:**
```apache
# Special mappings for non-standard locations
RewriteRule ^user-dashboard$ /frontend/pages/home/user-dashboard.php [L]
```

## Data Models

No database changes required. This is purely a web server configuration change.

## Error Handling

### 404 Handling

When a clean URL doesn't map to any existing file:
1. Apache's default 404 handler will trigger
2. If a custom 404 page exists, it should be configured separately
3. The rewrite rules use the `[L]` flag to stop processing once a match is found

### Debugging

To troubleshoot URL rewriting issues:
1. Enable Apache rewrite logging (requires server access)
2. Check that mod_rewrite is enabled
3. Verify file permissions on target PHP files
4. Test rules in order from most specific to most general

## Testing Strategy

### Manual Testing Checklist

1. **Clean URL Access**
   - Navigate to `/user-dashboard` → should load user dashboard page
   - Navigate to `/bulk-form` → should load bulk order form
   - Navigate to `/product-list` → should load product list

2. **Static Assets**
   - Verify CSS files load: `/frontend/assets/css/style.css`
   - Verify JS files load: `/frontend/assets/js/script.js`
   - Verify images load: `/assets/login-background.png`

3. **API Endpoints**
   - Test frontend API: `/frontend/api/get-products.php`
   - Test backend API: `/backend/api/update-product.php`

4. **Domain Routing**
   - Test admin subdomain: `admin.neocafe.shop`
   - Test rider subdomain: `rider.neocafe.shop`
   - Test main domain: `neocafe.shop`

5. **404 Behavior**
   - Navigate to `/nonexistent-page` → should return 404
   - Verify error doesn't expose internal paths

6. **Nested Paths**
   - Test `/products/view` if such structure exists
   - Test `/account/settings` if such structure exists

### Browser Testing

Test in multiple browsers to ensure consistent behavior:
- Chrome/Edge
- Firefox
- Safari (if available)

### Performance Verification

- Verify page load times remain consistent
- Check that caching still works for static assets
- Confirm compression is still applied

## Implementation Notes

### Order of Rules

The order of rewrite rules is critical:
1. Domain-based routing (existing)
2. Static file bypass conditions
3. API endpoint exclusions
4. Special case mappings (most specific)
5. Nested path patterns
6. Primary page pattern
7. Flat page pattern (fallback)

### Regular Expression Patterns

- `[a-zA-Z0-9-]+` matches page names with letters, numbers, and hyphens
- This prevents matching special characters that could cause security issues
- Extend pattern if underscores or other characters are needed

### Backward Compatibility

- Old URLs with full paths will still work (e.g., `/frontend/pages/home/user-dashboard.php`)
- This ensures existing bookmarks and links don't break
- Consider adding redirects from old URLs to new clean URLs in a future enhancement

## Security Considerations

1. **Path Traversal Prevention:** The regex pattern `[a-zA-Z0-9-]+` prevents directory traversal attacks
2. **File Existence Check:** Rules only rewrite to files that actually exist
3. **Existing Security Headers:** All existing security configurations remain active
4. **Sensitive File Blocking:** Existing rules blocking .log, .sql, .env files remain in place

## Future Enhancements

1. **Automatic 301 Redirects:** Redirect old full-path URLs to clean URLs
2. **Query String Preservation:** Ensure query parameters are maintained during rewrites
3. **Custom 404 Page:** Create a branded 404 error page
4. **URL Canonicalization:** Implement trailing slash handling
