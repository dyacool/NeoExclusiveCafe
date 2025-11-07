# Requirements Document

## Introduction

This feature audits and migrates all PHP files in the NeoCafe application that use legacy session handling to the new centralized SessionManager. The goal is to ensure consistent session management across the entire codebase, eliminating bugs caused by direct session manipulation and inconsistent authentication checks.

## Glossary

- **SessionManager**: The centralized session management class located at `includes/session-manager.php`
- **Legacy Session Handling**: Direct use of `session_start()`, `$_SESSION` checks, or manual authentication logic
- **Authentication Check**: Code that verifies if a user or admin is logged in
- **Session Initialization**: Code that starts a PHP session
- **Migration**: The process of updating a file to use SessionManager instead of legacy session handling

## Requirements

### Requirement 1

**User Story:** As a developer, I want to identify all files using legacy session handling, so that I can systematically migrate them to SessionManager

#### Acceptance Criteria

1. THE system SHALL scan all PHP files in the codebase for legacy session patterns
2. THE system SHALL identify files using `session_start()` without SessionManager
3. THE system SHALL identify files using direct `$_SESSION['user_id']` or `$_SESSION['is_admin']` checks
4. THE system SHALL identify files using manual authentication logic instead of SessionManager methods
5. THE system SHALL generate a comprehensive list of files requiring migration

### Requirement 2

**User Story:** As a developer, I want to migrate authentication checks to use SessionManager methods, so that authentication is consistent across the application

#### Acceptance Criteria

1. THE system SHALL replace `isset($_SESSION['user_id'])` checks with `SessionManager::isUserLoggedIn()`
2. THE system SHALL replace `isset($_SESSION['is_admin'])` checks with `SessionManager::isAdminLoggedIn()`
3. THE system SHALL replace manual role checks with appropriate SessionManager methods
4. THE system SHALL ensure all authentication checks use the centralized API
5. THE system SHALL preserve the original logic and behavior of authentication checks

### Requirement 3

**User Story:** As a developer, I want to migrate session initialization to use proper session handling, so that sessions are started consistently

#### Acceptance Criteria

1. THE system SHALL replace direct `session_start()` calls with proper session status checks
2. THE system SHALL add SessionManager include statements where needed
3. THE system SHALL use `if (session_status() === PHP_SESSION_NONE) { session_start(); }` pattern
4. THE system SHALL ensure SessionManager is included before any session operations
5. THE system SHALL maintain backward compatibility with existing session data

### Requirement 4

**User Story:** As a developer, I want to migrate user data access to use SessionManager methods, so that user data retrieval is consistent

#### Acceptance Criteria

1. THE system SHALL replace direct `$_SESSION['user_id']` access with `SessionManager::getUserId()`
2. THE system SHALL replace direct `$_SESSION['user_username']` access with `SessionManager::getUsername()`
3. THE system SHALL replace direct `$_SESSION['user_firstname']` access with `SessionManager::getFirstName()`
4. THE system SHALL replace direct `$_SESSION['user_lastname']` access with `SessionManager::getLastName()`
5. THE system SHALL use `SessionManager::getUserData()` for retrieving multiple user fields

### Requirement 5

**User Story:** As a developer, I want to prioritize critical files for migration, so that high-impact areas are fixed first

#### Acceptance Criteria

1. THE system SHALL prioritize authentication files (login, logout, registration)
2. THE system SHALL prioritize payment and checkout files
3. THE system SHALL prioritize admin authentication files
4. THE system SHALL prioritize API endpoints that check authentication
5. THE system SHALL create a prioritized migration order based on file criticality

### Requirement 6

**User Story:** As a developer, I want to test migrated files, so that I can verify the migration didn't break functionality

#### Acceptance Criteria

1. THE system SHALL verify PHP syntax after each file migration
2. THE system SHALL check that SessionManager is properly included
3. THE system SHALL verify authentication logic still works correctly
4. THE system SHALL ensure no session-related errors are introduced
5. THE system SHALL maintain existing functionality and behavior

### Requirement 7

**User Story:** As a developer, I want to document the migration process, so that future developers understand the changes

#### Acceptance Criteria

1. THE system SHALL create a migration log listing all modified files
2. THE system SHALL document the specific changes made to each file
3. THE system SHALL note any files that couldn't be automatically migrated
4. THE system SHALL provide before/after examples of common patterns
5. THE system SHALL create a summary report of the migration results
