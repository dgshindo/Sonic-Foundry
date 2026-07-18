CREATE TABLE IF NOT EXISTS work_pillar_memory (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    work_id BIGINT UNSIGNED NOT NULL,
    pillar VARCHAR(40) NOT NULL,

    summary TEXT NULL,
    perspective VARCHAR(255) NULL,
    core_tension TEXT NULL,
    listener_takeaway TEXT NULL,

    themes JSON NOT NULL,
    key_subjects JSON NOT NULL,

    confidence DECIMAL(5, 4) NULL,

    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    revision INT UNSIGNED NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_work_pillar_memory_work
        FOREIGN KEY (work_id)
        REFERENCES works(id)
        ON DELETE CASCADE,

    CONSTRAINT uq_work_pillar_memory_work_pillar
        UNIQUE (work_id, pillar),

    INDEX idx_work_pillar_memory_work (
        work_id
    ),

    INDEX idx_work_pillar_memory_status (
        status
    )
);

CREATE TABLE IF NOT EXISTS work_pillar_memory_revisions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    memory_id BIGINT UNSIGNED NOT NULL,
    work_id BIGINT UNSIGNED NOT NULL,
    pillar VARCHAR(40) NOT NULL,

    summary TEXT NULL,
    perspective VARCHAR(255) NULL,
    core_tension TEXT NULL,
    listener_takeaway TEXT NULL,

    themes JSON NOT NULL,
    key_subjects JSON NOT NULL,

    confidence DECIMAL(5, 4) NULL,

    status VARCHAR(40) NOT NULL,
    revision INT UNSIGNED NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_memory_revisions_memory
        FOREIGN KEY (memory_id)
        REFERENCES work_pillar_memory(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_memory_revisions_work
        FOREIGN KEY (work_id)
        REFERENCES works(id)
        ON DELETE CASCADE,

    CONSTRAINT uq_memory_revision
        UNIQUE (memory_id, revision),

    INDEX idx_memory_revisions_work_pillar (
        work_id,
        pillar,
        revision
    )
);