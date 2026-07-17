CREATE TABLE IF NOT EXISTS work_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    work_id BIGINT UNSIGNED NOT NULL,
    pillar VARCHAR(40) NOT NULL,
    role VARCHAR(40) NOT NULL,

    content TEXT NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_work_messages_work
        FOREIGN KEY (work_id)
        REFERENCES works(id)
        ON DELETE CASCADE,

    INDEX idx_work_messages_work_pillar (
        work_id,
        pillar,
        id
    ),

    INDEX idx_work_messages_created_at (
        created_at
    )
);