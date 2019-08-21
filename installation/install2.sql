CREATE TABLE IF NOT EXISTS `user` (
        `user_id` INTEGER PRIMARY KEY,
        `user_ip` varchar(64) NOT NULL,
        `http_user_agent` varchar(512) NOT NULL,
        `user_name` varchar(64) NOT NULL,
        `user_password_hash` varchar(255) NOT NULL,
        `user_timezone` varchar(64) NOT NULL
        );

CREATE TABLE IF NOT EXISTS device (
        'device_id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
        'device_name' TEXT NOT NULL,
        'device_description' TEXT,
        'device_ip' TEXT NOT NULL,
        'device_http_user_agent' TEXT NOT NULL, 
        'timebomb_password' TEXT NOT NULL ,
        'timebomb_status' TEXT,
        'user_id_fk' integer not null,
        'timebomb_time_set' INTEGER,
        'time_last_active' DATETIME NOT NULL,
        'device_location' varchar(255),
        CONSTRAINT fk_user
        FOREIGN KEY(user_id_fk) 
        REFERENCES user (user_id)
        ON DELETE CASCADE
                      );

CREATE UNIQUE INDEX IF NOT EXISTS `user_name_UNIQUE` ON `user` (`user_name` ASC);
CREATE UNIQUE INDEX IF NOT EXISTS `device_ip_UNIQUE` ON device ( `device_ip` ASC);
CREATE UNIQUE INDEX IF NOT EXISTS `device_name_UNIQUE` ON device ( `device_name` ASC);
        -- CREATE TABLE type (
        --         'type_id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
        --         'type_name' TEXT NOT NULL,
        --         'type_description' TEXT,
        --         'type_template_path' TEXT NOT NULL,
        --         'type_status' TEXT,
        --         'time_last_active' DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        --         FOREIGN KEY(type_id) REFERENCES type (type_id)
        --          );

        