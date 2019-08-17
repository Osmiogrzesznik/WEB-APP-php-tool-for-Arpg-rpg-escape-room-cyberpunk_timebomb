PRAGMA foreign_keys=off;

BEGIN TRANSACTION;

ALTER TABLE device RENAME TO device_old;

CREATE TABLE IF NOT EXISTS device (
        'device_id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
        'device_name' TEXT NOT NULL,
        'device_description' TEXT,
        'device_ip' TEXT NOT NULL,
        'device_http_user_agent' TEXT NOT NULL, 
        'timebomb_password' TEXT NOT NULL ,
        'timebomb_status' TEXT,
        'registered_by_user' integer not null,
        'timebomb_time_set' INTEGER,
        'time_last_active' DATETIME NOT NULL,
        CONSTRAINT fk_user
        FOREIGN KEY(registered_by_user) 
        REFERENCES user (user_id)
        ON DELETE CASCADE
);

INSERT INTO device SELECT * FROM _device_old;

COMMIT;

PRAGMA foreign_keys=on;