/*
|--------------------------------------------------------------------------
| Sonic Foundry
| Migration 008 — Generalize Pillar Memory
|--------------------------------------------------------------------------
|
| Introduces a pillar-agnostic JSON document while preserving all existing
| Story-specific columns during the transition.
|
| The legacy columns remain in place for backward compatibility until the
| repository and domain layers have been migrated to memory_data.
|
*/

/*
|--------------------------------------------------------------------------
| Add generic memory document columns
|--------------------------------------------------------------------------
*/

ALTER TABLE work_pillar_memory
    ADD COLUMN memory_data JSON NULL
    AFTER pillar;

ALTER TABLE work_pillar_memory_revisions
    ADD COLUMN memory_data JSON NULL
    AFTER pillar;

/*
|--------------------------------------------------------------------------
| Backfill current Story memory
|--------------------------------------------------------------------------
|
| Each row already belongs to one pillar, so memory_data contains only that
| pillar's structured fields rather than nesting them beneath "story".
|
*/

UPDATE work_pillar_memory
SET memory_data = JSON_OBJECT(
    'schema_version', 1,

    'summary', summary,

    'perspective', perspective,

    'core_tension', core_tension,

    'listener_takeaway', listener_takeaway,

    'themes',
        COALESCE(
            themes,
            JSON_ARRAY()
        ),

    'key_subjects',
        COALESCE(
            key_subjects,
            JSON_ARRAY()
        )
)
WHERE memory_data IS NULL;

/*
|--------------------------------------------------------------------------
| Backfill revision history
|--------------------------------------------------------------------------
*/

UPDATE work_pillar_memory_revisions
SET memory_data = JSON_OBJECT(
    'schema_version', 1,

    'summary', summary,

    'perspective', perspective,

    'core_tension', core_tension,

    'listener_takeaway', listener_takeaway,

    'themes',
        COALESCE(
            themes,
            JSON_ARRAY()
        ),

    'key_subjects',
        COALESCE(
            key_subjects,
            JSON_ARRAY()
        )
)
WHERE memory_data IS NULL;

/*
|--------------------------------------------------------------------------
| Enforce generic document presence
|--------------------------------------------------------------------------
|
| Every current memory and every historical revision must now possess a
| structured memory document.
|
*/

ALTER TABLE work_pillar_memory
    MODIFY COLUMN memory_data JSON NOT NULL;

ALTER TABLE work_pillar_memory_revisions
    MODIFY COLUMN memory_data JSON NOT NULL;

/*
|--------------------------------------------------------------------------
| Optional lookup indexes
|--------------------------------------------------------------------------
|
| These indexes support future schema-version queries and diagnostics.
| Generated columns are used because JSON expressions cannot be indexed
| directly in a conventional MySQL index.
|
*/

ALTER TABLE work_pillar_memory
    ADD COLUMN memory_schema_version
        INT GENERATED ALWAYS AS (
            CAST(
                JSON_UNQUOTE(
                    JSON_EXTRACT(
                        memory_data,
                        '$.schema_version'
                    )
                )
                AS UNSIGNED
            )
        ) STORED
        AFTER memory_data;

ALTER TABLE work_pillar_memory_revisions
    ADD COLUMN memory_schema_version
        INT GENERATED ALWAYS AS (
            CAST(
                JSON_UNQUOTE(
                    JSON_EXTRACT(
                        memory_data,
                        '$.schema_version'
                    )
                )
                AS UNSIGNED
            )
        ) STORED
        AFTER memory_data;

CREATE INDEX idx_work_pillar_memory_schema_version
    ON work_pillar_memory (
        memory_schema_version
    );

CREATE INDEX idx_memory_revisions_schema_version
    ON work_pillar_memory_revisions (
        memory_schema_version
    );