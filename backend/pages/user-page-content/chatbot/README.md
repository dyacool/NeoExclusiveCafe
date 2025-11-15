# Chatbot System - NeoCafe

## Overview
This folder contains all chatbot-related components for the NeoCafe system. The chatbot uses Cohere AI API to provide intelligent responses based on a knowledge base managed by admins.

## File Structure

```
chatbot/
├── chatbot-api.php          # Main chatbot API endpoint (handles user messages)
├── chatbot-knowledge.php    # Admin UI for managing static knowledge base
├── chatbot-knowledge.css    # Styling for knowledge base UI
└── README.md               # This file
```

## Components

### 1. chatbot-api.php
**Purpose:** Main chatbot message handler that processes user queries via Cohere AI  
**Endpoint:** `/backend/pages/user-page-content/chatbot/chatbot-api.php`  
**Method:** POST  
**Parameters:** 
- `message` (string) - User's message

**Response Format:**
```json
{
    "response": "AI generated response",
    "success": true
}
```

**Key Features:**
- CafeChatbot class with Cohere integration
- Fetches knowledge base content from database
- Uses command-a-03-2025 model for natural language processing
- Returns JSON responses

**Dependencies:**
- `.env` file for COHERE_API_KEY
- `database.php` for knowledge base access
- `chatbot_knowledge` table in database

### 2. chatbot-knowledge.php
**Purpose:** Admin interface for managing static chatbot knowledge base  
**Access:** Admin authentication required  
**Features:**
- Rich text editor for knowledge input
- Live preview of chatbot responses
- Save/load knowledge base content
- Reset functionality

**Database Table:** `chatbot_knowledge`
- `id` (INT) - Primary key
- `content` (MEDIUMTEXT) - Static knowledge base content
- `updated_at` (TIMESTAMP) - Last update timestamp

### 3. cb-knowledge-settings.php (Parent Directory)
**Purpose:** Alternative admin UI with AJAX-based save/load  
**Location:** `backend/pages/user-page-content/cb-knowledge-settings.php`  
**Note:** Consider consolidating with chatbot-knowledge.php

## Integration Points

### Frontend Integration
**File:** `frontend/user-includes/user-header.php`
- Chatbot widget in user header
- Fetch API call to chatbot-api.php
- Real-time message handling with typing indicators

### Admin Navigation
**Access Path:** Admin Panel → Chatbot Knowledge Base
- Menu item in admin navbar
- Links to chatbot-knowledge.php

## Database Schema

```sql
CREATE TABLE IF NOT EXISTS chatbot_knowledge (
    id INT PRIMARY KEY AUTO_INCREMENT,
    content MEDIUMTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Future Enhancements

### Planned Features:
1. **Database Integration**
   - Add `dynamic_content` column for auto-fetched data
   - Implement ChatbotDataFetcher class
   - Create cb-database-settings.php for admin control

2. **Data Sources**
   - Best Sellers (Top 10 products from orders)
   - Product Catalog (In-stock items)
   - Categories (Active categories)

3. **Smart Updates**
   - Polling mechanism for automatic data refresh
   - Event-driven updates on order completion
   - Manual "Refresh Now" button for admins

4. **Intent Detection**
   - Detect user query intent (best sellers, product info, etc.)
   - Route queries to appropriate data sources
   - Structured response formatting

## API Configuration

**Cohere AI Settings:**
- Model: `command-a-03-2025`
- Temperature: 0.7
- Max Tokens: 500
- API Key: Stored in `.env` file

**Environment Variables:**
```env
COHERE_API_KEY=your_api_key_here
```

## Maintenance Notes

### Recent Changes:
- Moved from `backend/pages/admin-includes/chatbot.php` to current location
- Updated reference in `user-header.php` to new path
- Fixed AJAX handler positioning in cb-knowledge-settings.php
- Removed rogue session code from chatbot-api.php

### Known Issues:
- None currently

### Testing:
1. Test chatbot widget in frontend after path changes
2. Verify admin knowledge base save/load functionality
3. Check Cohere API responses with updated knowledge base

## Related Documentation
- `SESSION_MIGRATION_COMPLETE.md` - Session management updates
- `MIGRATION_FINAL_SUMMARY.md` - System migration history
- `.env.example` - Environment variable template
