CREATE TABLE IF NOT EXISTS `user2` (
        `user_id` INTEGER PRIMARY KEY,
        `user_ip` varchar(64) NOT NULL,
        `user_name` varchar(64) NOT NULL,
        `user_password_hash` varchar(255) NOT NULL,
        `user_timezone` varchar(64) NOT NULL,
        'user_location' varchar(255),
        'user_map_srv' INTEGER NOT NULL DEFAULT 0
        );

CREATE TABLE IF NOT EXISTS device (
        'device_id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        'registered_by_user' integer not null,
        'device_session_id' TEXT NOT NULL,
        'device_name' TEXT NOT NULL,
        'device_description' TEXT,
        'device_ip' TEXT NOT NULL,
        'device_password' TEXT NOT NULL ,
        'device_status' TEXT,
        'time_set' varchar(19) NOT NULL,
        'time_last_active' DATETIME NOT NULL,
        'device_location' varchar(255),
        CONSTRAINT fk_user
        FOREIGN KEY(registered_by_user) 
        REFERENCES user (user_id)
        ON DELETE CASCADE
                      );

CREATE TABLE IF NOT EXISTS history_device_location (
        'history_id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        'history_device_id' INTEGER NOT NULL,
        'history_time_last_active' DATETIME NOT NULL ,
        'history_device_location' varchar(128) NOT NULL,
        CONSTRAINT fk_device
        FOREIGN KEY(history_device_id) 
        REFERENCES device (device_id)
        ON DELETE CASCADE
                      );

CREATE UNIQUE INDEX IF NOT EXISTS `user_name_UNIQUE` ON `user` (`user_name` ASC);
CREATE UNIQUE INDEX IF NOT EXISTS `user_ip_UNIQUE` ON `user` (`user_ip` ASC);
CREATE UNIQUE INDEX IF NOT EXISTS `device_ip_UNIQUE` ON device ( `device_ip` ASC);
CREATE UNIQUE INDEX IF NOT EXISTS `device_session_id_UNIQUE` ON device ( `device_session_id` ASC);
                

        