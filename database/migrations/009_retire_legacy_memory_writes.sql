/*
|--------------------------------------------------------------------------
| Sonic Foundry
| Migration 009 — Retire Legacy Pillar Memory Writes
|--------------------------------------------------------------------------
|
| memory_data is now the authoritative Creative Memory document.
|
| Story-specific columns remain temporarily for backward compatibility,
| but are made nullable so future pillars can persist their own schemas
| without populating Story fields.
|
*/

ALTER TABLE work_pillar_memory
    MODIFY COLUMN summary TEXT NULL,
    MODIFY COLUMN perspective VARCHAR(255) NULL,
    MODIFY COLUMN core_tension TEXT NULL,
    MODIFY COLUMN listener_takeaway TEXT NULL,
    MODIFY COLUMN themes JSON NULL,
    MODIFY COLUMN key_subjects JSON NULL;

ALTER TABLE work_pillar_memory_revisions
    MODIFY COLUMN summary TEXT NULL,
    MODIFY COLUMN perspective VARCHAR(255) NULL,
    MODIFY COLUMN core_tension TEXT NULL,
    MODIFY COLUMN listener_takeaway TEXT NULL,
    MODIFY COLUMN themes JSON NULL,
    MODIFY COLUMN key_subjects JSON NULL;