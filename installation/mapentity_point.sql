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
-- Data dump for mapentity_point, a total of 0 rows
----