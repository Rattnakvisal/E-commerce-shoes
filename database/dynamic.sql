CREATE TABLE navbar_parents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    `position` INT DEFAULT 0
);

CREATE TABLE navbar_groups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id INT UNSIGNED NULL,
    group_title VARCHAR(150) NOT NULL,
    `position` INT DEFAULT 0,
    link_url VARCHAR(300),
    CONSTRAINT fk_navbar_group_parent
        FOREIGN KEY (parent_id)
        REFERENCES navbar_parents(id)
        ON DELETE CASCADE
);

CREATE TABLE navbar_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id INT UNSIGNED NOT NULL,
    item_title VARCHAR(150) NOT NULL,
    `position` INT DEFAULT 0,
    link_url VARCHAR(300),
    CONSTRAINT fk_navbar_item_group
        FOREIGN KEY (group_id)
        REFERENCES navbar_groups(id)
        ON DELETE CASCADE
);

CREATE TABLE slides (
    slides_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200),
    description VARCHAR(500),
    image_url VARCHAR(500),
    link_url VARCHAR(300),
    button_text VARCHAR(100),
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
