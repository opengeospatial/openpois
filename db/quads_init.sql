DROP TABLE IF EXISTS quads CASCADE;
CREATE TABLE quads (
  oid serial primary key, 
  priority varchar(1), 
  yelp boolean
);

SELECT AddGeometryColumn('quads', 'bbox', 4326, 'POLYGON', 2 );

CREATE INDEX quad_bbox_idx on quad USING GIST (bbox);
