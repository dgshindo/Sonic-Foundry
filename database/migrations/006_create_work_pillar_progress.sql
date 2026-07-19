CREATE TABLE IF NOT EXISTS work_pillar_progress (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    work_id BIGINT UNSIGNED NOT NULL,
    pillar VARCHAR(40) NOT NULL,

    status VARCHAR(40) NOT NULL
        DEFAULT 'developing',

    readiness_score TINYINT UNSIGNED NOT NULL
        DEFAULT 0,

    is_ready TINYINT(1) NOT NULL
        DEFAULT 0,

    criteria JSON NOT NULL,

    recommendation TEXT NULL,

    revision INT UNSIGNED NOT NULL
        DEFAULT 1,

    evaluated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_work_pillar_progress_work
        FOREIGN KEY (work_id)
        REFERENCES works(id)
        ON DELETE CASCADE,

    CONSTRAINT uq_work_pillar_progress
        UNIQUE (work_id, pillar),

    INDEX idx_work_pillar_progress_work (
        work_id
    ),

    INDEX idx_work_pillar_progress_status (
        status
    ),

    INDEX idx_work_pillar_progress_ready (
        is_ready
    )
);

CREATE TABLE IF NOT EXISTS work_pillar_progress_revisions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    progress_id BIGINT UNSIGNED NOT NULL,
    work_id BIGINT UNSIGNED NOT NULL,
    pillar VARCHAR(40) NOT NULL,

    status VARCHAR(40) NOT NULL,
    readiness_score TINYINT UNSIGNED NOT NULL,
    is_ready TINYINT(1) NOT NULL,

    criteria JSON NOT NULL,

    recommendation TEXT NULL,

    revision INT UNSIGNED NOT NULL,

    evaluated_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_progress_revision_progress
        FOREIGN KEY (progress_id)
        REFERENCES work_pillar_progress(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_progress_revision_work
        FOREIGN KEY (work_id)
        REFERENCES works(id)
        ON DELETE CASCADE,

    CONSTRAINT uq_progress_revision
        UNIQUE (progress_id, revision),

    INDEX idx_progress_revision_work_pillar (
        work_id,
        pillar,
        revision
    )
);