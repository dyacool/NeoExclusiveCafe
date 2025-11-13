# Requirements Document

## Introduction

This feature implements clean URL routing for the NeoCafe website to improve user experience and SEO by removing technical path details and file extensions from URLs. Users will access pages through simplified, human-readable URLs instead of exposing the internal directory structure.

## Glossary

- **URL Rewriting System**: The Apache mod_rewrite module that processes incoming URLs and maps them to actual file paths
- **Clean URL**: A user-facing URL without file extensions or directory paths (e.g., `/user-dashboard` instead of `/frontend/pages/home/user-dashboard.php`)
- **Rewrite Rule**: An Apache directive that defines how to transform incoming URLs to internal file paths
- **Frontend Pages**: PHP files located in the `/frontend/pages/` directory structure that serve user-facing content

## Requirements

### Requirement 1

**User Story:** As a website visitor, I want to access pages using simple, clean URLs without seeing technical file paths, so that the website appears more professional and URLs are easier to remember and share.

#### Acceptance Criteria

1. WHEN a user navigates to `/user-dashboard`, THE URL Rewriting System SHALL serve the content from `/frontend/pages/home/user-dashboard.php`
2. WHEN a user navigates to any URL without a file extension, THE URL Rewriting System SHALL append `.php` extension and resolve to the corresponding file in the frontend pages directory
3. THE URL Rewriting System SHALL maintain the clean URL in the browser address bar without exposing the internal file path
4. WHEN a requested clean URL does not map to an existing file, THE URL Rewriting System SHALL return a 404 error response
5. THE URL Rewriting System SHALL preserve existing domain-based routing rules for admin, rider, and main domains

### Requirement 2

**User Story:** As a developer, I want the URL routing to automatically map common page names to their correct locations, so that I don't need to manually define rules for every single page.

#### Acceptance Criteria

1. THE URL Rewriting System SHALL check for files in the following order: `/frontend/pages/{page-name}/{page-name}.php`, then `/frontend/pages/{page-name}.php`
2. WHEN multiple potential file paths exist for a clean URL, THE URL Rewriting System SHALL use the first matching file based on the defined priority order
3. THE URL Rewriting System SHALL support nested page structures (e.g., `/products/view` mapping to `/frontend/pages/products/view.php`)
4. THE URL Rewriting System SHALL ignore requests for actual files and directories that exist (CSS, JS, images, API endpoints)

### Requirement 3

**User Story:** As a website administrator, I want the URL rewriting to work alongside existing security and performance configurations, so that implementing clean URLs doesn't compromise site security or functionality.

#### Acceptance Criteria

1. THE URL Rewriting System SHALL process clean URL rules after domain-based routing rules
2. THE URL Rewriting System SHALL not interfere with existing security headers, file blocking, compression, or caching configurations
3. THE URL Rewriting System SHALL preserve the existing force-www redirect for the main domain
4. THE URL Rewriting System SHALL allow direct access to static assets (CSS, JS, images) without rewriting
5. THE URL Rewriting System SHALL allow direct access to API endpoints without rewriting

### Requirement 4

**User Story:** As a developer, I want clear documentation of the URL routing patterns, so that I can understand how URLs are mapped and troubleshoot issues when they arise.

#### Acceptance Criteria

1. THE URL Rewriting System SHALL include inline comments explaining each rewrite rule's purpose
2. THE URL Rewriting System SHALL document the order of rule evaluation in comments
3. THE URL Rewriting System SHALL provide examples of URL transformations in comments
