CREATE TABLE IF NOT EXISTS `user` (
        `user_id` INTEGER PRIMARY KEY,
        `user_ip` varchar(64) NOT NULL,
        `http_user_agent` varchar(512) NOT NULL,
        `user_name` varchar(64) NOT NULL,
        `user_password_hash` varchar(255) NOT NULL,
        `user_email` varchar(64)
        );

CREATE UNIQUE INDEX `user_name_UNIQUE` ON `user` (`user_name` ASC);
CREATE UNIQUE INDEX `user_ip_UNIQUE` ON `user` (`user_ip` ASC);


CREATE TABLE IF NOT EXISTS device (
        'device_id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
        'device_name' TEXT NOT NULL,
        'device_description' TEXT,
        'device_ip' TEXT NOT NULL,
        'device_http_user_agent' TEXT NOT NULL, 
        'device_password' TEXT NOT NULL ,
        -- 'type_id' INTEGER NOT NULL , 
        'device_status' TEXT,
        'time_set' INTEGER,
        'time_last_uppdated' DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        -- FOREIGN KEY(type_id) REFERENCES type (type_id)
         );

CREATE UNIQUE INDEX `device_ip_UNIQUE` ON device ( `device_ip` ASC);
CREATE UNIQUE INDEX `device_name_UNIQUE` ON device ( `device_name` ASC);
        -- CREATE TABLE type (
        --         'type_id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
        --         'type_name' TEXT NOT NULL,
        --         'type_description' TEXT,
        --         'type_template_path' TEXT NOT NULL,
        --         'type_status' TEXT,
        --         'time_last_uppdated' DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        --         FOREIGN KEY(type_id) REFERENCES type (type_id)
        --          );

        