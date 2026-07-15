# Sonic Foundry Coding Standards

## Purpose

This document defines the coding standards for the Sonic Foundry platform.

These standards exist to keep the codebase consistent, readable, secure, testable, and maintainable as the platform grows.

All contributors should follow these standards unless a documented architectural decision explicitly establishes an exception.

---

# Core Principles

- Prefer clarity over cleverness.
- Prefer maintainability over brevity.
- Prefer explicit behavior over hidden behavior.
- Keep responsibilities narrow and well defined.
- Separate presentation, business logic, data access, and external services.
- Follow the architecture defined in `ARCHITECTURE.md`.
- Follow the product philosophy defined in `FOUNDRY_PRINCIPLES.md`.

---

# PHP Version

Sonic Foundry targets modern PHP.

Minimum supported version:

```text
PHP 8.1

New code may use:

strict typing
constructor property promotion
union types
nullable types
typed properties
enums
readonly properties where appropriate
named arguments where they improve clarity

Avoid language features that require a newer PHP version unless the minimum supported version is intentionally updated.

File Requirements

Every PHP file must begin with:

<?php
declare(strict_types=1);

There must be no whitespace, byte-order mark, comments, or output before <?php.

PHP source files must be saved as:

UTF-8 without BOM

Each PHP file should contain one primary class, interface, enum, or trait.

File names must match class names exactly.

Example:

src/User/UserRepository.php

contains:

final class UserRepository
Formatting Standard

Use PSR-12 formatting.

General rules:

Use four spaces for indentation.
Do not use tabs.
Place opening braces on the next line for classes and methods.
Use one blank line between methods.
Keep lines reasonably short, preferably under 100–120 characters.
Add trailing commas to multiline arrays and argument lists where valid.
Use spaces around operators.
Avoid multiple statements on one line.

Example:

final class UserRepository
{
    public function findById(int $id): ?User
    {
        // Implementation
    }
}
Namespaces and Autoloading

All application classes must use the SonicFoundry root namespace.

Namespaces must correspond to the directory structure.

Example:

src/User/UserRepository.php

must use:

namespace SonicFoundry\User;

Composer PSR-4 autoloading must be used.

Do not manually include application classes with repeated require_once statements.

Allowed:

use SonicFoundry\User\UserRepository;

Avoid:

require_once '../src/User/UserRepository.php';
Naming Conventions
Classes and Interfaces

Use PascalCase.

UserRepository
ProjectService
MusicGenerationProvider

Interfaces should describe capability or responsibility.

MusicGenerationProvider
PaymentGateway
ProjectRepositoryInterface

Avoid prefixing interfaces with I.

Prefer:

MusicGenerationProvider

Not:

IMusicGenerationProvider
Methods and Variables

Use camelCase.

findByEmail()
projectId
displayName
Constants

Use uppercase snake case.

MAX_LOGIN_ATTEMPTS
DEFAULT_PAGE_SIZE
Database Tables and Columns

Use lowercase snake case.

users
project_id
created_at
google_sub
Class Responsibilities

Each class should have one primary responsibility.

A class should answer one clear question.

Examples:

UserRepository

How are users stored and retrieved?

PromptBuilder

How is AI context assembled?

Auth

Is a user authenticated, and who is that user?

MusicGenerationService

How is an approved track sent for music generation?

Avoid classes that become general dumping grounds.

Do not create classes named:

Helper
Manager
Utility
Common
Misc

unless their responsibility is unusually clear and narrowly defined.

Repository Rule

Repositories are the only layer permitted to communicate directly with the database.

No SQL may appear in:

public pages
controllers
services
authentication classes
AI modules
templates
JavaScript
presentation code

All SQL must exist inside repository classes.

Example:

$projects = $projectRepository->findByUserId($userId);

Avoid:

$statement = $pdo->prepare(
    'SELECT * FROM projects WHERE user_id = ?'
);

outside a repository.

Repositories must:

use prepared statements
return domain objects or well-defined value objects
hide database schema details from callers
own persistence for one domain or aggregate
avoid presentation logic
avoid rendering HTML
Repository Ownership

Each repository owns one primary aggregate.

Examples:

UserRepository
ProjectRepository
ConversationRepository
TrackRepository
GenerationRepository

A repository should not absorb unrelated responsibilities.

Avoid:

UserRepository::findProjectTracks()

Prefer:

TrackRepository::findByProjectId()
Domain Objects

Domain objects represent meaningful concepts inside Sonic Foundry.

Examples:

User
Project
Track
Conversation
Message
Generation
Subscription

Repositories should return domain objects rather than raw database rows.

Preferred:

$user = $userRepository->findById($id);

echo $user->displayName();

Avoid:

$user = $userRepository->findById($id);

echo $user['display_name'];

Domain objects must not contain SQL or depend directly on PDO.

Public Pages and Screens

Files inside public/ are entry points and presentation screens.

They may:

receive request input
validate basic request shape
call application services
redirect
render templates
return JSON responses

They must not:

contain SQL
call external APIs directly
contain large business rules
perform complex project-state updates
assemble AI prompts
store secrets
duplicate authentication logic

Public pages should remain thin.

Example:

$projects = $projectService->listForUser($user);

require dirname(__DIR__) . '/templates/workspace.php';
Services

Services coordinate business operations.

Examples:

AuthService
ProjectService
WorkshopService
MusicGenerationService
BillingService

Services may:

coordinate repositories
enforce business rules
call provider interfaces
create domain objects
record usage
return application results

Services must not:

render HTML
read directly from $_POST or $_GET
contain raw SQL
expose API secrets
depend on browser-specific behavior
External APIs

External provider code must be isolated behind interfaces.

Examples:

OpenAIClient
MusicGenerationProvider
GoogleAuthenticator
StripeBillingProvider

Provider-specific request and response formats must not leak throughout the application.

Preferred:

$task = $musicGenerationService->generateTrack($track);

Avoid:

$musicApiResponse = curl_exec(...);

inside pages or domain classes.

API credentials must:

remain server-side
be loaded from environment configuration
never appear in JavaScript
never be committed to Git
never be written to ordinary logs
Error Handling

Use exceptions for exceptional failures.

Do not silently ignore errors.

Catch exceptions only when the caller can:

recover
add useful context
log the failure
return an appropriate response
redirect safely

Avoid:

try {
    // code
} catch (Throwable $error) {
}

Use meaningful exception messages.

Do not expose sensitive stack traces, SQL, credentials, or internal paths to production users.

Development mode may show detailed errors.

Production mode must log details and show a safe message.

Logging

Use centralized logging.

Logs should record:

authentication events
provider failures
background job failures
project-generation failures
billing events
security-relevant events
unexpected exceptions

Logs must not contain:

passwords
API keys
access tokens
session identifiers
full payment details
sensitive personal data
complete private prompt content unless explicitly required and protected

Log messages should contain useful context.

Preferred:

Music generation failed for project 42, track 7, provider task abc123.

Avoid:

Something went wrong.
Security

Security is a design requirement.

All code must follow these rules:

Use prepared SQL statements.
Escape HTML output with htmlspecialchars().
Validate all user input.
Use CSRF protection for state-changing requests.
Regenerate session IDs after login.
Destroy sessions fully on logout.
Use secure, HTTP-only, SameSite cookies.
Never trust client-provided user IDs.
Verify resource ownership server-side.
Store passwords only with password_hash().
Verify passwords with password_verify().
Keep all secrets in .env.
Never expose provider secrets in browser code.
Apply rate limits to expensive or sensitive endpoints.
Enforce authorization independently from authentication.
Input Validation

Validate data at system boundaries.

Examples:

form submissions
API requests
uploaded files
webhook payloads
provider callbacks
query parameters

Validation should confirm:

required values
expected types
maximum lengths
allowed formats
ownership
permitted state transitions

Do not rely solely on browser validation.

Output Escaping

Escape output at render time.

HTML:

<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>

JSON responses should use:

json_encode(
    $data,
    JSON_THROW_ON_ERROR
);

Do not pre-escape values before storing them in the database.

Store clean data.

Escape according to the output context.

Configuration

Configuration must not be scattered throughout the codebase.

Use environment variables for:

database credentials
API keys
application URLs
provider settings
environment mode
billing credentials
webhook secrets

Use dedicated configuration classes or files for application defaults.

Do not hardcode credentials, domains, or environment-specific paths.

Comments and Documentation

Write comments that explain why, not what.

Avoid:

// Increment count
$count++;

Prefer:

// Preserve the previous version before replacing the approved lyrics.

Public methods with non-obvious behavior should include PHPDoc.

Do not use comments to excuse confusing code.

Refactor unclear code instead.

Method Design

Methods should:

perform one coherent task
have clear names
use explicit parameter and return types
avoid excessive nesting
avoid hidden side effects
remain short enough to understand readily

Prefer early returns.

Example:

if (!$user) {
    return null;
}

Avoid deeply nested conditionals where possible.

Dependency Direction

Dependencies should point inward and downward.

Presentation
    ↓
Application Services
    ↓
Domain
    ↓
Repositories and Provider Interfaces
    ↓
Infrastructure

Lower-level classes must not depend on presentation code.

Repositories must not know about HTML.

Domain objects must not know about browser sessions.

External providers must not control project business rules.

Database Migrations and Schema Changes

Every database change must be documented and reproducible.

Do not modify production tables manually without recording the change.

Until a migration system is introduced, schema updates must be stored in versioned SQL files.

Example:

database/migrations/
    001_create_users.sql
    002_create_projects.sql
    003_create_conversations.sql

Schema changes should include:

forward migration
rollback guidance where practical
index considerations
foreign-key behavior
data-migration notes
Testing

New business logic should be designed for testing.

Priority areas include:

authentication
authorization
repositories
project ownership
usage metering
billing
provider integrations
project-state transitions
conversation persistence
generation versioning

Avoid designs that require a browser or live external API for every test.

External providers should be mockable through interfaces.

API Responses

API endpoints must return consistent JSON.

Example:

{
  "success": true,
  "data": {},
  "error": null
}

Failure example:

{
  "success": false,
  "data": null,
  "error": {
    "code": "PROJECT_NOT_FOUND",
    "message": "The requested project could not be found."
  }
}

Do not expose internal exception messages directly to public clients.

Git Standards

Commits should be small, coherent, and testable.

Commit messages should describe the completed change.

Preferred:

Add secure session management
Create user repository and domain model
Implement Google authentication callback

Avoid:

Updates
Fix stuff
More work

Before committing:

run the application
test the changed workflow
inspect git status
confirm .env is not staged
confirm secrets are absent
confirm generated files are ignored
review the diff
Forbidden Practices

The following are not permitted without an explicit architectural decision:

SQL outside repositories
API credentials in client-side code
business logic in public pages
direct provider calls from templates
raw database rows passed throughout the application
unvalidated ownership assumptions
disabled security checks for convenience
secrets committed to Git
generated assets overwritten without versioning
silent exception handling
giant general-purpose classes
duplicated authentication logic
duplicated project-state logic
Review Checklist

Before considering a feature complete, ask:

Does the code follow the architecture?
Is SQL contained within repositories?
Is business logic outside public pages?
Are inputs validated?
Is output escaped correctly?
Are permissions and ownership checked?
Are secrets protected?
Are failures handled and logged?
Is the code understandable without excessive explanation?
Does the feature support the journey from Story to Legacy?

# Final Standard

Code in Sonic Foundry should feel like the product itself:

Intentional.

Structured.

Durable.

Built to support meaningful creative work.