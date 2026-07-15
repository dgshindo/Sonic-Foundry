# Sonic Foundry Architecture

## Purpose

This document defines the architecture of the Sonic Foundry platform.

It is the technical blueprint for all development.

If implementation conflicts with this document, the architecture should be reviewed before code is changed.

---

# Core Philosophy

The application is service-oriented.

Presentation is separate from business logic.

Business logic is separate from data access.

AI is a service.

Music generation is a service.

The browser is only a client.

---

# High-Level Architecture

Browser

↓

Presentation Layer

↓

Application Layer

↓

Domain Services

↓

Repositories

↓

Database

↓

External Services

OpenAI

MusicAPI

Stripe

Google Identity

---

# Application Layers

Presentation

Responsible for:

- HTML
- CSS
- JavaScript
- User interaction

Contains:

public/

Never contains:

SQL

Business logic

AI prompts

---

Application

Coordinates requests.

Knows what should happen.

Does not know how it happens.

Contains:

Application

Controllers

Routing

---

Domain

Contains the business rules.

Projects

Albums

Tracks

Users

Conversation

Publishing

Marketing

AI

---

Repositories

Only layer allowed to communicate with MySQL.

No SQL exists outside repositories.

---

Infrastructure

External APIs.

OpenAI

MusicAPI

Google

Stripe

Filesystem

Email

---

# Folder Structure

src/

Application/

AI/

Auth/

Database/

Infrastructure/

Project/

User/

Support/

---

# Major Objects

Application

User

Project

Conversation

Album

Track

Lyrics

Artwork

Generation

Publishing

Marketing

---

# Project Hierarchy

User

↓

Projects

↓

Album

↓

Tracks

↓

Lyrics

↓

Generations

↓

Publishing

---

# AI Architecture

PromptBuilder

↓

Knowledge

↓

Templates

↓

Conversation

↓

OpenAI

↓

Response Parser

↓

Project Update

---

# User Journey

Visitor

↓

Authentication

↓

Dashboard

↓

Projects

↓

Workshop

↓

Album

↓

Music

↓

Publishing

↓

Legacy

---

# Rules

No SQL outside repositories.

No HTML inside services.

No business logic inside pages.

No API calls directly from presentation.

No global state.

One responsibility per class.

---

# Future Modules

Forge

Studio

Gallery

Publishing

Marketing

Analytics

Marketplace

---

# Guiding Principle

Every feature must help an artist move from

Story

↓

Emotion

↓

Identity

↓

Sound

↓

Legacy

## Repository Rule

Repositories are the ONLY layer permitted to communicate with the database.

No SQL may appear in:

- Pages
- Services
- Controllers
- AI modules
- Authentication
- UI code

Every database interaction must pass through a Repository.

Reason:

This centralizes all data access, makes schema changes manageable, simplifies testing, and keeps business logic independent of storage implementation.

## Repository Ownership

Each Repository owns exactly one aggregate.

UserRepository
    owns Users

ProjectRepository
    owns Projects

TrackRepository
    owns Tracks

ConversationRepository
    owns Conversations

## Direction of Dependencies

Presentation

↓

Application

↓

Domain

↓

Repositories

↓

Database

Dependencies only point downward.

Lower layers never know about higher layers.    