CREATE TABLE IF NOT EXISTS creative_artifacts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    work_id BIGINT UNSIGNED NOT NULL,

    artifact_type VARCHAR(40) NOT NULL,

    title VARCHAR(180) NOT NULL,

    content LONGTEXT NOT NULL,

    revision INT UNSIGNED NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_creative_artifacts_work
        FOREIGN KEY (work_id)
        REFERENCES works(id)
        ON DELETE CASCADE,

    CONSTRAINT uq_creative_artifacts_work_type
        UNIQUE (work_id, artifact_type),

    INDEX idx_creative_artifacts_work_id (
        work_id
    ),

    INDEX idx_creative_artifacts_type (
        artifact_type
    ),

    INDEX idx_creative_artifacts_updated (
        updated_at
    )
);