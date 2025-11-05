# Event Queue Directory

This directory stores the realtime notification event queue files.

## Files

- `queue.json` - Main event queue file (auto-generated)
- `queue.json.lock` - Lock file for thread-safe operations (auto-generated)

## Permissions

Ensure this directory is writable by the web server user:

```bash
# Linux/Unix
chmod 755 backend/api/events/

# Windows
# Right-click folder → Properties → Security → Edit
# Give IUSR and IIS_IUSRS write permissions
```

## Maintenance

The queue automatically:
- Cleans up events older than 1 hour
- Manages file locking for concurrent access
- Initializes files on first use

No manual maintenance required.
