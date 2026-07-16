CREATE TABLE IF NOT EXISTS works (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(180) NOT NULL,
    work_type VARCHAR(40) NOT NULL,

    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    current_pillar VARCHAR(40) NOT NULL DEFAULT 'story',

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_works_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    INDEX idx_works_user_id (user_id),
    INDEX idx_works_user_updated (user_id, updated_at),
    INDEX idx_works_status (status)
);