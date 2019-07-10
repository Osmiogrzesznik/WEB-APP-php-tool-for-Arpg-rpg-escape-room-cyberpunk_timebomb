CREATE TABLE IF NOT EXISTS `user` (
        `user_id` INTEGER PRIMARY KEY,
        `user_ip` varchar(64) NOT NULL,
        `http_user_agent` varchar(512) NOT NULL,
        `user_name` varchar(64) NOT NULL,
        `user_password_hash` varchar(255) NOT NULL,
        `user_timezone` varchar(64) NOT NULL
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
        'device_status' TEXT,
        'registered_by_user' integer not null,
        'time_set' INTEGER,
        'time_last_active' DATETIME NOT NULL,
        CONSTRAINT fk_user
        FOREIGN KEY(registered_by_user) 
        REFERENCES user (user_id)
        ON DELETE CASCADE
                      );

CREATE UNIQUE INDEX `device_ip_UNIQUE` ON device ( `device_ip` ASC);
CREATE UNIQUE INDEX `device_name_UNIQUE` ON device ( `device_name` ASC);
                

        