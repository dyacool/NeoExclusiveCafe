# Requirements Document

## Introduction

This document outlines the requirements for implementing a centralized session management system for the NeoCafe application. Currently, session variable checks are inconsistent across frontend files, with some files checking `$_SESSION['admin_id']` when they should check `$_SESSION['is_admin']`, and various authentication patterns scattered throughout the codebase. This creates maintenance issues and potential security vulnerabilities. The solution will provide a unified API for session management that standardizes authentication checks, user data retrieval, and session state management across the entire application.

## Glossary

- **Session_Manager**: A centralized PHP class that handles all session-related operations including authentication checks, user data retrieval, and session state management
- **Frontend_Application**: The customer-facing portion of the application located in the frontend directory
- **Backend_Application**: The administrative portion of the application located in the backend directory
- **User_Session**: Session data for regular customers with role 'user'
- **Admin_Session**: Session data for administrators with role 'admin'
- **Authentication_State**: The current login status and role of the session
- **Session_Variables**: Data stored in PHP's $_SESSION superglobal including user_id, user_role, is_admin, etc.

## Requirements

### Requirement 1

**User Story:** As a developer, I want a single source of truth for session management, so that I can avoid inconsistent session checks across the codebase

#### Acceptance Criteria

1. THE Session_Manager SHALL provide a static method `isUserLoggedIn()` that returns true when a valid User_Session exists
2. THE Session_Manager SHALL provide a static method `isAdminLoggedIn()` that returns true when a valid Admin_Session exists
3. THE Session_Manager SHALL provide a static method `getUserId()` that returns the authenticated user's ID or null when no User_Session exists
4. THE Session_Manager SHALL provide a static method `getUserData()` that returns an associative array containing user_id, username, firstname, lastname, and role when a valid User_Session exists
5. THE Session_Manager SHALL provide a static method `getAdminData()` that returns an associative array containing admin_id, username, firstname, lastname, and role when a valid Admin_Session exists

### Requirement 2

**User Story:** As a developer, I want consistent authentication checks, so that security vulnerabilities from incorrect session variable usage are eliminated

#### Acceptance Criteria

1. THE Session_Manager SHALL validate User_Session by checking that `$_SESSION['user_id']` exists AND `$_SESSION['user_role']` equals 'user'
2. THE Session_Manager SHALL validate Admin_Session by checking that `$_SESSION['is_admin']` equals true AND `$_SESSION['admin_role']` equals 'admin'
3. THE Session_Manager SHALL NOT use `$_SESSION['admin_id']` for authentication checks in the Frontend_Application
4. WHEN authentication validation fails, THE Session_Manager SHALL return false or null values without throwing exceptions
5. THE Session_Manager SHALL ensure session is started before accessing Session_Variables

### Requirement 3

**User Story:** As a developer, I want easy-to-use helper methods for common session operations, so that I can write cleaner and more maintainable code

#### Acceptance Criteria

1. THE Session_Manager SHALL provide a static method `requireUserLogin($redirectUrl)` that redirects to the specified URL when no valid User_Session exists
2. THE Session_Manager SHALL provide a static method `requireAdminLogin($redirectUrl)` that redirects to the specified URL when no valid Admin_Session exists
3. THE Session_Manager SHALL provide a static method `isPreviewMode()` that returns true when neither User_Session nor Admin_Session exists
4. THE Session_Manager SHALL provide a static method `getRole()` that returns 'user', 'admin', or 'guest' based on the current Authentication_State
5. THE Session_Manager SHALL provide a static method `destroySession()` that clears all Session_Variables and destroys the session

### Requirement 4

**User Story:** As a developer, I want to refactor existing frontend files to use the Session_Manager, so that the codebase has consistent session handling

#### Acceptance Criteria

1. THE Frontend_Application SHALL replace all direct `$_SESSION['admin_id']` checks with `Session_Manager::isAdminLoggedIn()` calls
2. THE Frontend_Application SHALL replace all direct `$_SESSION['user_id']` authentication checks with `Session_Manager::isUserLoggedIn()` calls
3. THE Frontend_Application SHALL replace preview mode checks with `Session_Manager::isPreviewMode()` calls
4. THE Frontend_Application SHALL use `Session_Manager::getUserData()` instead of directly accessing multiple Session_Variables
5. THE Frontend_Application SHALL use `Session_Manager::requireUserLogin()` in protected pages instead of manual redirect logic

### Requirement 5

**User Story:** As a developer, I want the Session_Manager to be easily accessible throughout the application, so that I don't need complex include paths

#### Acceptance Criteria

1. THE Session_Manager SHALL be located in a shared includes directory accessible to both Frontend_Application and Backend_Application
2. THE Session_Manager SHALL be a single PHP file that can be included with a simple require_once statement
3. THE Session_Manager SHALL automatically start the session if not already started when any method is called
4. THE Session_Manager SHALL NOT depend on other custom classes or libraries
5. THE Session_Manager SHALL work with the existing session structure without requiring database schema changes

### Requirement 6

**User Story:** As a developer, I want comprehensive documentation for the Session_Manager, so that team members can easily understand and use it

#### Acceptance Criteria

1. THE Session_Manager SHALL include PHPDoc comments for all public methods describing parameters, return types, and usage
2. WHERE documentation is provided, THE Session_Manager SHALL include code examples demonstrating common usage patterns
3. THE Session_Manager SHALL include inline comments explaining the validation logic for User_Session and Admin_Session
4. WHERE the Session_Manager is implemented, a migration guide SHALL be provided showing before/after code examples
5. THE Session_Manager SHALL include a summary comment at the top of the file describing its purpose and basic usage
