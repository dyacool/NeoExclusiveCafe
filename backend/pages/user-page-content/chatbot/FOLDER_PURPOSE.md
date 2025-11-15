# 📁 Chatbot Folder - OTP Security System

## Purpose
This folder contains the **OTP (One-Time Password) Security System** for accessing sensitive chatbot database settings.

## ✅ Files Structure

```
chatbot/
├── api/                              # API Endpoints for OTP
│   ├── get-database-preview.php     # Get current DB configuration
│   ├── send-otp.php                 # Send OTP to admin email
│   └── verify-otp.php               # Verify OTP code
│
├── migrations/                       # Database Setup
│   ├── create_otp_tables.sql        # SQL for OTP tables
│   └── run_migration.php            # Migration runner
│
├── cb-database-settings.php         # Protected settings page
├── chatbot-api.php                  # Chatbot API handler
├── chatbot-knowledge.css            # Styles (original)
├── chatbot-knowledge.php            # Knowledge UI (original)
│
└── Documentation/
    ├── OTP_IMPLEMENTATION_GUIDE.md  # Complete technical guide
    ├── SETUP_COMPLETE.md            # Setup summary
    ├── README.md                     # General readme
    └── DATABASE_INTEGRATION_SPEC.md # DB specs
```

## 🎯 What's NEW (OTP System)

### API Files (`api/`)
1. **get-database-preview.php** - Returns current database configuration
2. **send-otp.php** - Generates and sends 6-digit OTP via email
3. **verify-otp.php** - Validates OTP and grants access

### Migration Files (`migrations/`)
1. **create_otp_tables.sql** - Creates 3 tables:
   - `chatbot_otp` - Stores OTP codes
   - `chatbot_access_tokens` - Access management
   - `chatbot_database_settings` - DB configuration

2. **run_migration.php** - Web-based migration runner

### Documentation
- **OTP_IMPLEMENTATION_GUIDE.md** - Full technical documentation
- **SETUP_COMPLETE.md** - Implementation summary

## 📍 Files Moved BACK to Parent Directory

The following files are now in `user-page-content/` (parent folder):
- ✅ `cb-knowledge-settings.php` - Main knowledge base page (with OTP UI)
- ✅ `save-knowledge.php` - Save knowledge API
- ✅ `get-knowledge.php` - Get knowledge API
- ✅ `chatbot-knowledge.js` - Knowledge base JavaScript

## 📍 Files Moved BACK to admin-includes

- ✅ `chatbot.php` - Main chatbot logic (Cohere API)

## 🔗 Integration Points

### In `cb-knowledge-settings.php` (Parent Directory)
The OTP system is integrated via:
- Database preview section
- "Change Settings" button
- OTP modal (JavaScript)
- API calls to `chatbot/api/` endpoints

### API Path References
```javascript
// From cb-knowledge-settings.php
fetch('chatbot/api/get-database-preview.php')  // Get DB info
fetch('chatbot/api/send-otp.php')              // Send OTP
fetch('chatbot/api/verify-otp.php')            // Verify OTP
```

### Redirect After Verification
```javascript
window.location.href = 'chatbot/cb-database-settings.php';
```

## 🚀 How to Use

1. **Run Migration** (First time only):
   ```
   http://localhost/NeoCafe/backend/pages/user-page-content/chatbot/migrations/run_migration.php
   ```

2. **Access from Knowledge Base Page**:
   - Go to `cb-knowledge-settings.php`
   - See database preview
   - Click "Change Settings"
   - Complete OTP verification
   - Access database settings

## 🎨 What the Chatbot Folder Does

### Security Layer
- OTP generation and validation
- Email delivery
- Access token management
- Session security

### Database Management
- Store OTP codes
- Track verification status
- Manage access tokens
- Configure database settings

### Protected Access
- Only verified admins can access `cb-database-settings.php`
- Time-limited access (30 minutes)
- Audit trail of access attempts

## 🔒 Security Features

1. **Email Verification** - OTP sent to registered admin email
2. **Time Limits** - OTP expires in 5 minutes
3. **Single Use** - Each OTP can only be used once
4. **Session Tokens** - 30-minute access after verification
5. **Admin Auth** - All endpoints require admin login

## 📊 Database Tables Created

### chatbot_otp
Stores temporary OTP codes with expiration tracking

### chatbot_access_tokens  
Manages temporary access sessions for settings

### chatbot_database_settings
Stores chatbot database configuration

## 🔧 Maintenance

The `chatbot/` folder is self-contained for OTP functionality:
- All OTP-related code is here
- Migrations are included
- Documentation is complete
- APIs are isolated

## ✨ Clean Separation

**Chatbot Folder** = OTP Security System only
- New functionality
- Self-contained
- Isolated APIs
- Own migrations

**Parent Directory** = Main Application
- Knowledge base pages
- Existing functionality
- Original structure maintained

---
**Last Updated:** November 15, 2025  
**Purpose:** OTP Security for Database Settings Access
