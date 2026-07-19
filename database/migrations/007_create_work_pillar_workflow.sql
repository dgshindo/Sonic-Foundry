CREATE TABLE IF NOT EXISTS work_pillar_workflow (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    work_id BIGINT UNSIGNED NOT NULL,
    pillar VARCHAR(40) NOT NULL,

    status VARCHAR(40) NOT NULL
        DEFAULT 'locked',

    unlocked_at DATETIME NULL,
    completed_at DATETIME NULL,

    revision INT UNSIGNED NOT NULL
        DEFAULT 1,

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_work_pillar_workflow_work
        FOREIGN KEY (work_id)
        REFERENCES works(id)
        ON DELETE CASCADE,

    CONSTRAINT uq_work_pillar_workflow
        UNIQUE (work_id, pillar),

    INDEX idx_work_pillar_workflow_work (
        work_id
    ),

    INDEX idx_work_pillar_workflow_status (
        status
    )
);

CREATE TABLE IF NOT EXISTS work_pillar_workflow_revisions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    workflow_id BIGINT UNSIGNED NOT NULL,
    work_id BIGINT UNSIGNED NOT NULL,
    pillar VARCHAR(40) NOT NULL,

    status VARCHAR(40) NOT NULL,

    unlocked_at DATETIME NULL,
    completed_at DATETIME NULL,

    revision INT UNSIGNED NOT NULL,

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_workflow_revision_workflow
        FOREIGN KEY (workflow_id)
        REFERENCES work_pillar_workflow(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_workflow_revision_work
        FOREIGN KEY (work_id)
        REFERENCES works(id)
        ON DELETE CASCADE,

    CONSTRAINT uq_workflow_revision
        UNIQUE (workflow_id, revision),

    INDEX idx_workflow_revision_work_pillar (
        work_id,
        pillar,
        revision
    )
);