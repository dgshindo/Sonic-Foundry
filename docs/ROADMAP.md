# Sonic Foundry Development Roadmap

## Purpose

This roadmap defines the planned evolution of Sonic Foundry from technical foundation to a complete creative album-production platform.

The roadmap is organized by capability, not by calendar date.

Each version should leave the application stable, testable, and usable before the next phase begins.

---

# Product Direction

Sonic Foundry guides artists through:

Story

↓

Emotion

↓

Identity

↓

Sound

↓

Legacy

The platform begins as a guided album-development workspace and evolves into an end-to-end environment for concept development, music generation, artwork, release preparation, and publishing support.

---

# Version 0.1 — Foundation

## Goal

Establish a secure, maintainable web application foundation.

## Features

- PHP application scaffold
- Composer and PSR-4 autoloading
- Environment configuration
- MySQL database connection
- Repository-based data access
- Logging
- Error handling
- Sonic Foundry visual identity
- Responsive public landing page
- Architecture documentation
- Foundry principles documentation
- Development roadmap

## Architectural Requirements

- No SQL outside repositories
- No business logic inside public pages
- No API credentials in client-side code
- No secrets committed to Git
- Presentation, services, repositories, and infrastructure remain separate

## Completion Criteria

- Application loads locally
- MySQL connection succeeds
- Composer autoloading works
- Repository structure is established
- Documentation reflects the intended architecture

---

# Version 0.2 — Authentication and Accounts

## Goal

Allow users to securely enter Sonic Foundry and maintain an account.

## Features

- User database schema
- Secure PHP sessions
- Google Identity Services login
- Optional email/password login
- Logout
- Protected routes
- User profile
- Avatar display
- Account status
- Session expiration
- CSRF protection
- Basic audit logging

## Completion Criteria

- User can sign in with Google
- Returning users are recognized by Google subject identifier
- Protected pages reject anonymous visitors
- Sessions survive normal page navigation
- Logout fully terminates the session
- User information is stored only through `UserRepository`

---

# Version 0.3 — Projects and Persistent Memory

## Goal

Make the Project the central unit of Sonic Foundry.

## Features

- Create album project
- Rename project
- Archive project
- Delete project
- Project dashboard
- Project ownership
- Project status
- Current workflow stage
- Conversation storage
- Message history
- Structured project state
- Resume project across sessions
- Autosave
- Revision history foundation

## Core Project Stages

- Story
- Emotion
- Identity
- Sound
- Legacy

## Completion Criteria

- User can create and reopen a project
- All conversation history persists in MySQL
- Project state survives logout and browser closure
- Users can access only their own projects
- All project SQL remains inside repositories

---

# Version 0.4 — Guided Album Workshop

## Goal

Recreate and improve the collaborative workflow proven in Sonic Forge Desktop.

## Features

- Streamed OpenAI conversation
- External prompt files
- PromptBuilder
- Stage-specific prompts
- Album concept development
- Focused creative questions
- Concept directions
- Album title exploration
- Narrative arc
- Emotional journey
- Master sonic identity
- Track-list workshop
- Track sequencing
- Track-by-track development
- Approval checkpoints
- Structured conversation summaries
- Token and cost tracking
- OpenAI usage limits
- Regeneration of individual sections

## AI Architecture

PromptBuilder

↓

System Prompt

↓

Stage Prompt

↓

Project Context

↓

Recent Conversation

↓

OpenAI

↓

Structured Response

↓

Project Update

## Completion Criteria

- User can workshop an album through natural conversation
- Responses stream progressively
- Project state updates separately from conversation prose
- Approved decisions are retained
- Forge does not advance without explicit approval
- Individual tracks can be revised without regenerating the entire album

---

# Version 0.5 — Music Generation

## Goal

Generate playable and downloadable music from approved Sonic Foundry tracks.

## Primary Integration

MusicAPI.ai

MusicAPI.ai currently provides authenticated REST endpoints for music generation, task-based retrieval, multiple generation models, and webhook support. The integration must remain isolated behind a provider interface so Sonic Foundry is not permanently coupled to one vendor. :contentReference[oaicite:0]{index=0}

## Features

Music-generation provider interface
- MusicAPI.ai provider
- Server-side API authentication
- Generate track from approved lyrics and style
- Instrumental generation
- Vocal-track generation
- Multiple generation versions
- Async task handling
- Polling and webhook support
- Generation status
- Error handling and retries
- Credit estimation
- Usage recording
- Audio preview
- Download generated tracks
- Approve or reject generation
- Regenerate track
- Preserve all versions
- Provider response logging
- Music-generation cost controls

## Suggested Service Boundary

```php
interface MusicGenerationProvider
{
    public function createTrack(
        MusicGenerationRequest $request
    ): MusicGenerationTask;

    public function getTaskStatus(
        string $providerTaskId
    ): MusicGenerationStatus;

    public function downloadResult(
        string $providerTaskId,
        string $destination
    ): GeneratedAudio;
}
```
## Data Model Additions
- music_generations
- generation_versions
- provider_tasks
- usage_records
- audio_assets

## Completion Criteria
- Approved track can be sent to MusicAPI.ai
- Task status is tracked asynchronously
- Completed audio can be previewed and downloaded
- Multiple generated versions are retained
- Failed jobs can be retried safely
- API key remains exclusively server-side
- Provider-specific code does not leak into project or UI layers

# Version 0.6 — Album Workspace
## Goal

Provide a complete visual workspace for managing the album as a body of work.

## Features
- Project explorer
- Album overview
- Track list and sequencing
- Drag-and-drop track order
- Track status
- Lyrics versions
- Style versions
- Audio versions
- Approval indicators
- Energy curve
- Narrative curve
- Recurring motif tracking
- Duplicate-language detection
- Album consistency review
- Completion progress

## Completion Criteria
- Entire album can be reviewed without searching conversation history
- User can move between tracks quickly
- Approved and draft elements are clearly distinguished
- Album-level pacing and cohesion are visible

# Version 0.7 — Artwork and Visual Identity

## Goal

Translate the approved sonic identity into a coherent visual identity.

# Features
- Album-cover prompt generation
- Track artwork prompts
- Visual identity profile
- Color palette
- Typography direction
- Recurring image motifs
- Image-generation provider interface
- Artwork versions
- Upload custom artwork
- Crop and export formats
- YouTube thumbnail concepts
- Social-media image formats

# Completion Criteria
- Album has an approved visual identity
- Artwork remains consistent with project story and sonic identity
- Multiple artwork versions can be retained and compared


# Version 0.8 — Release Packaging

## Goal

Prepare approved albums and tracks for publication.

# Features
- Album metadata
- Track metadata
- Credits
- Lyrics export
- Style archive
- Artwork export
- Audio-file organization
- File naming
- ZIP album package
- YouTube title and description
- YouTube Shorts packaging
- Social post copy
- Release notes
- Press-kit foundation
- Downloadable project archive

## Completion Criteria
- User can generate one organized release package
- Package contains approved audio, lyrics, artwork, metadata, and promotional material
- Export process is repeatable and versioned

# Version 0.9 — Billing and Subscription Controls

## Goal

Prepare Sonic Foundry for paid public use.

# Features
- Stripe integration
- Subscription plans
- Customer billing portal
- Monthly credits
- Usage metering
- OpenAI cost accounting
- MusicAPI cost accounting
- Credit purchases
- Plan limits
- Trial support
- Invoice history
- Cancellation
- Refund administration
- Admin usage dashboard
- Abuse prevention
- Rate limiting
- Completion Criteria
- Users can subscribe and manage billing
- Expensive API usage is metered
- Plan limits are enforced server-side
- Platform margins can be measured
- No plan offers uncontrolled unlimited generation

# Version 1.0 — Public Release

## Goal

Release a stable, secure, commercially usable Sonic Foundry platform.

# Required Capabilities
- Authentication
- User accounts
- Persistent album projects
- Guided Album Workshop
- Album workspace
- OpenAI integration
- MusicAPI.ai integration
- Audio generation and versioning
- Artwork workflow
- Release packaging
- Subscription billing
- Usage controls
- Responsive interface
- Accessibility baseline
- Security review
- Privacy policy
- Terms of service
- Support workflow
- Backup and recovery process
- Release Standard

## Version 1.0 should allow a subscriber to:

- Create an album project
- Develop it through guided conversation
- Approve the concept and track list
- Generate and revise lyrics
- Generate multiple music versions
- Approve final tracks
- Develop artwork
- Export a complete downloadable album package

## Post-1.0 Opportunities
- Collaboration
- Invite collaborators
- Project roles
- Comments
- Shared approvals
- Producer and artist workspaces
- Publishing
- Distribution-service integrations
- Release scheduling
- Metadata validation
- ISRC and UPC workflow support
- Marketing
- Campaign calendar
- Content scheduling
- Ad packages
- Audience personas
- Analytics-assisted recommendations
- Marketplace
- Prompt packs
- Sonic identities
- Album templates
- Visual identity packs
- Producer services
- Collaborator discovery
- Mobile
- Responsive project review
- Mobile approval workflow
- Audio review
- Conversation access
  
  ## Explicitly Out of Scope for Early Versions

The following should not delay the core workflow:

- Native mobile applications
- Social network features
- Public artist profiles
- Marketplace
- Automated distribution
- Advanced analytics
- Real-time multi-user editing
- Unlimited generations
- Support for every music-generation provider
- Large-scale enterprise administration
- Development Rules
- No SQL exists outside repositories.
- No business logic exists inside public pages.
- No external API is called directly from presentation code.
- All provider integrations use interfaces.
- Every expensive action is metered.
- Every AI-generated project update is reviewable.
- User approval is required before advancing major stages.
- Generated assets are versioned rather than overwritten.
- Secrets remain server-side.
- Accessibility and security are design requirements, not final-stage additions.
- Every feature must support the journey from Story to Legacy.

# Current Status

**Completed**
Version 0.1 foundation work has begun
- Repository created
- Public landing page created
- Visual identity established
- Composer configured
- MySQL connection verified
- Foundry Principles documented
- Architecture documented

# Next Milestone

Version 0.2 — Authentication and Accounts