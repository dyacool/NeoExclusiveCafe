# Domain Configuration Quick Reference

## 🌐 Domain URLs

### Local Development (Port 8080)
| Purpose | URL | Redirects To |
|---------|-----|--------------|
| Main Site | http://neocafe.cafe:8080 | User Dashboard |
| Rider Portal | http://rider.neocafe.cafe:8080 | Rider Orders Page |
| Admin Portal | http://admin.neocafe.cafe:8080 | Admin Login |

### Production
| Purpose | URL | Redirects To |
|---------|-----|--------------|
| Main Site | https://neocafe.shop | User Dashboard |
| Rider Portal | https://rider.neocafe.shop | Rider Orders Page |
| Admin Portal | https://admin.neocafe.shop | Admin Login |

## 🔧 Quick Setup (Windows)

### 1. Edit Hosts File
```
C:\Windows\System32\drivers\etc\hosts
```

Add:
```
127.0.0.1    neocafe.cafe
127.0.0.1    rider.neocafe.cafe
127.0.0.1    admin.neocafe.cafe
```

### 2. Test Setup
Visit: http://neocafe.cafe:8080/test-domain-routing.php

### 3. Verify Routing
- ✅ Each domain should show correct detection
- ✅ Redirects should point to correct paths
- ✅ Environment should be "development"

## 📁 Key Files

| File | Purpose |
|------|---------|
| `config/domain-config.php` | Domain configuration and routing logic |
| `index.php` | Main entry point with domain-based routing |
| `test-domain-routing.php` | Test script to verify domain detection |
| `docs/RIDER-DOMAIN-SETUP.md` | Complete setup guide |

## 🚀 Testing Checklist

- [ ] Hosts file configured
- [ ] Apache restarted
- [ ] Main domain loads
- [ ] Rider subdomain loads
- [ ] Admin subdomain loads
- [ ] Test script shows correct detection

## 🐛 Common Issues

**Issue**: Subdomain not working
- Clear browser cache
- Verify hosts file
- Restart Apache

**Issue**: Wrong redirect
- Check `test-domain-routing.php` output
- Verify environment detection

**Issue**: Port issues
- Ensure Apache listens on 8080
- Check virtual host configuration
