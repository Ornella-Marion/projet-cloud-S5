CREATE TABLE status (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    label VARCHAR(50) NOT NULL UNIQUE,
    percentage INTEGER NOT NULL CHECK (percentage IN (0, 50, 100))
);


CREATE TABLE roadworks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    road_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    enterprise_id INTEGER,
    status_id INTEGER NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at DATETIME,
    finished_at DATETIME,

    budget DECIMAL(12,2),
    area DECIMAL(10,2),
    description TEXT,

    updated_at DATETIME,

    FOREIGN KEY (road_id) REFERENCES roads(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (enterprise_id) REFERENCES enterprises(id),
    FOREIGN KEY (status_id) REFERENCES status(id)
);


CREATE TABLE roadwork_photos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    roadwork_id INTEGER NOT NULL,
    photo_url TEXT NOT NULL,
    uploaded_by INTEGER NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (roadwork_id) REFERENCES roadworks(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);


CREATE TABLE status_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    roadwork_id INTEGER NOT NULL,
    old_status_id INTEGER NOT NULL,
    new_status_id INTEGER NOT NULL,

    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    changed_by INTEGER NOT NULL,

    FOREIGN KEY (roadwork_id) REFERENCES roadworks(id) ON DELETE CASCADE,
    FOREIGN KEY (old_status_id) REFERENCES status(id),
    FOREIGN KEY (new_status_id) REFERENCES status(id),
    FOREIGN KEY (changed_by) REFERENCES users(id)
);

CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    user_id INTEGER NOT NULL,
    roadwork_id INTEGER,
    message TEXT NOT NULL,

    read_at DATETIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (roadwork_id) REFERENCES roadworks(id)
);

CREATE TABLE firebase_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    user_id INTEGER NOT NULL,
    fcm_token TEXT NOT NULL UNIQUE,
    device_type VARCHAR(20) CHECK (device_type IN ('android', 'ios')),

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME,

    FOREIGN KEY (user_id) REFERENCES users(id)
);


INSERT INTO status (label, percentage) VALUES
('nouveau', 0),
('en_cours', 50),
('termine', 100);
