-- SQL to create contact messages table
CREATE TABLE contact_messages (
    message_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    NAME VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=INNODB;
