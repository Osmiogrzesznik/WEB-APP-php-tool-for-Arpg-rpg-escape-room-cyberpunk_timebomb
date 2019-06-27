CREATE TABLE IF NOT EXISTS `user` (
        `user_id` INTEGER PRIMARY KEY,
        `user_ip` varchar(64) NOT NULL,
        `http_user_agent` varchar(512) NOT NULL,
        `user_name` varchar(64) NOT NULL,
        `user_password_hash` varchar(255) NOT NULL,
        `user_email` varchar(64));
        CREATE UNIQUE INDEX `user_name_UNIQUE` ON `users` (`user_name` ASC);
        CREATE UNIQUE INDEX `user_email_UNIQUE` ON `users` (`user_email` ASC);
        
        CREATE TABLE devices
        ('device_id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
        'device_name' text,
        'device_description' TEXT,
         'device_ip' TEXT NOT NULL,
         'device_http_user_agent' TEXT NOT NULL, 
        'device_password' TEXT NOT NULL ,
        'device_type_id' INTEGER NOT NULL , 
        'device_status' TEXT,
         'time_last_uppdated' DATETIME DEFAULT CURRENT_TIMESTAMP,)
         CREATE UNIQUE INDEX `device_ip_UNIQUE` ON `users` (`user_name` ASC);
        CREATE UNIQUE INDEX `device_ip_UNIQUE` ON `users` (`user_email` ASC);
        ); 
        