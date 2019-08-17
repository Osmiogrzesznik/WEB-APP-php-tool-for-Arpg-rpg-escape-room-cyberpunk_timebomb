----
-- phpLiteAdmin database dump (http://www.phpliteadmin.org/)
-- phpLiteAdmin version: 1.9.7.1
-- Exported: 2:39pm on August 12, 2019 (EEST)
-- database file: ./rafka_timebomb.sqlite
----

----
-- Table structure for mapentity
----

CREATE TABLE effect(
  effect_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  effect_name TEXT NOT NULL,
  effect_description TEXT,
  effect_destroys_types TEXT,
  effect_interference integer,
  effect_radioactivity integer
);


CREATE TABLE mapentity (
        mapentity_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        registered_by_user integer not null,
        mapentity_name TEXT NOT NULL,
        mapentity_description TEXT,
        mapentity_style text,
        mapentity_effect_id_fk integer not null,
        mapentity_effect_ison BOOLEAN,
        mapentity_json text,
        CONSTRAINT fk_user
        FOREIGN KEY(registered_by_user) 
        REFERENCES user (user_id)
        ON DELETE CASCADE
        CONSTRAINT fk_effect
        FOREIGN KEY(mapentity_effect_id_fk) 
        REFERENCES effect (effect_id)
        ON DELETE CASCADE
                      );

----
-- Table structure for mapentity_point
----
CREATE TABLE mapentity_point (
        point_fk_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        mapentity_fk_id INTEGER NOT NULL,
        point_location varchar(255),
        CONSTRAINT fk_mapentity_id,
        FOREIGN KEY(mapentity_fk_id) 
        REFERENCES mapentity (mapentity_id)
        ON DELETE CASCADE,

CONSTRAINT fk_point_id,
        FOREIGN KEY(point_fk_id) 
        REFERENCES point (point_id)
        ON DELETE CASCADE

  CONSTRAINT fk_mapentity_id,
        FOREIGN KEY(mapentity_fk_id) 
        REFERENCES point (mapentity_id)
        ON DELETE CASCADE
                      );

----
-- Table structure for device
----
CREATE TABLE device ( 
       device_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
       registered_by_user integer not null,
       device_session_id TEXT NOT NULL,
       device_name TEXT NOT NULL,
       device_description TEXT,
       device_ip TEXT NOT NULL,
       time_last_active DATETIME NOT NULL,
       timebomb_status TEXT,
       device_location varchar(255),
       device_fk_location_point INTEGER,

CONSTRAINT fk_location_point,
        FOREIGN KEY(device_fk_location_point) 
        REFERENCES point (point_id)
        ON DELETE CASCADE,
       
        CONSTRAINT fk_user
        FOREIGN KEY(registered_by_user) 
        REFERENCES user (user_id)
        ON DELETE CASCADE
                      );

--functionality
CREATE TABLE functionality (
        functionality_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        functionality_folder TEXT NOT NULL,
        functionality_name TEXTuniq_index not null,
        functionality_battery_depletion integer 
        );
 
CREATE TABLE timebomb (
  timebomb_device_id_fk integer not null,
  timebomb_status TEXT,
  timebomb_timebomb_time_set varchar(19),
  timebomb_mapentity_id_fk integer not null,
  timebomb_password TEXT NOT NULL
  );

CREATE TABLE geiger (
  geiger_device_id_fk integer not null,
  geiger_ison boolean not null,
  geiger_status TEXT
  );

CREATE TABLE radar (
  radar_device_id_fk integer not null,
  radar_ison boolean not null,
  radar_status TEXT,
  radar_radius integer
  );

CREATE TABLE inventory (
  inventory_device_id_fk integer not null,
  inventory_ison boolean not null,
  inventory_status TEXT,
  inventory_itemsJSON TEXT
  );

-- link table functionality to device
CREATE TABLE functionality_device (
        functionality_id_fk INTEGER not null,
        added_by_device_id_fk integer,
        device_id_fk TEXT NOT NULL,
        functionality_status TEXT,
        settings_JSON TEXT,
        time_added varchar(19) NOT NULL,

CONSTRAINT fk_functionality_id,
        FOREIGN KEY( functionality_id_fk)
        REFERENCES functionality (functionality_id)
        ON DELETE CASCADE,
       
      CONSTRAINT fk_device_func
        FOREIGN KEY (device_id_fk) 
        REFERENCES device (device_id)
        ON DELETE CASCADE 
);

CREATE TABLE mapentity_device (
        mapentity_id_fk INTEGER not null,
        device_id_fk TEXT NOT NULL,
        mapentity_status TEXT,
        time_added varchar(19) NOT NULL,

CONSTRAINT fk_mapentity_id,
        FOREIGN KEY( mapentity_id_fk)
        REFERENCES mapentity (mapentity_id)
ON DELETE CASCADE,
       
      CONSTRAINT fk_device_func
        FOREIGN KEY (device_id_fk) 
        REFERENCES device (device_id)
        ON DELETE CASCADE 
);
----
-- Table structure for history_device_location
----
CREATE TABLE history_device_location (
        history_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        history_device_id INTEGER NOT NULL,
        history_time_last_active DATETIME NOT NULL ,
        history_device_location varchar(128) NOT NULL,
        CONSTRAINT fk_device
        FOREIGN KEY(history_device_id) 
        REFERENCES device (device_id)
        ON DELETE CASCADE
                      );

----
-- Table structure for point
----
CREATE TABLE point ( point_id INTEGER PRIMARY KEY,
       point_longitude text not null,
       point_latitude text not null );

----
-- Table structure for user
----
CREATE TABLE user (
        user_id INTEGER PRIMARY KEY,
        user_ip varchar(64) NOT NULL,
        user_name varchar(64) NOT NULL,
        user_password_hash varchar(255) NOT NULL,
        user_timezone varchar(64) NOT NULL,
        user_location varchar(255),
        user_fk_location_point INTEGER,
        user_map_srv INTEGER NOT NULL DEFAULT 0,
user_green_filter boolean  not null default 1,

CONSTRAINT fk_location_point,
        FOREIGN KEY(user_fk_location_point) 
        REFERENCES point (point_id)
        ON DELETE CASCADE
        );

----
-- Table structure for drawnfeature3
----
CREATE TABLE drawnfeature3 (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
       creator_fk integer,
       creator integer not null,
       name TEXT,
       description TEXT,
       type varchar(16),
       geometryjson TEXT,
       effect_FK integer,
       color TEXT,
       pseudo_id TEXT not null); 
----
-- structure for index device_ip_UNIQUE on table device
----
CREATE UNIQUE INDEX device_ip_UNIQUE ON device ( device_ip ASC);

----
-- structure for index device_session_id_UNIQUE on table device
----
CREATE UNIQUE INDEX device_session_id_UNIQUE ON device ( device_session_id ASC);

----
-- structure for index user_name_UNIQUE on table user
----
CREATE UNIQUE INDEX user_name_UNIQUE ON user (user_name ASC);
