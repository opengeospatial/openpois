-- to copy from one table to another, you will do some thing like
-- insert into dest_table(id,uname,x11,x21) (select id,uname,x1,x2 from source_table)

DROP VIEW minipoi;
SELECT DropGeometryColumn('geo', 'geompt');
SELECT DropGeometryColumn('geo', 'geomline');
SELECT DropGeometryColumn('geo', 'geompoly');

DROP TABLE IF EXISTS poiroot CASCADE;
CREATE TABLE poiroot (
  myid uuid not null, 
  parentid  uuid, 
  objname text, 
  id  text,
  value text, 
  href  text, 
  type  text, 
  created timestamp with time zone DEFAULT now(), 
  updated timestamp with time zone DEFAULT now(), 
  deleted timestamp with time zone DEFAULT NULL, 
  authorid  uuid, 
  licenseid uuid, 
  lang  varchar(6), 
  base  text, 
  userid varchar(24) DEFAULT NULL
);
COMMENT ON COLUMN poiroot.id IS 'id should start with base if base not null';

DROP TABLE IF EXISTS poibasetype CASCADE;
CREATE TABLE poibasetype (
)
INHERITS(poiroot);

DROP TABLE IF EXISTS poitermtype CASCADE;
CREATE TABLE poitermtype (
  term  text NOT NULL, 
  scheme  text
)
INHERITS(poiroot);

-- DROP TABLE IF EXISTS metadata CASCADE;
-- CREATE TABLE metadata (
--   parentid  uuid, 
--   objname text
-- );

DROP TABLE IF EXISTS location CASCADE;
CREATE TABLE location (
  undetermined  text
)
INHERITS(poiroot);

-- DROP TABLE IF EXISTS poigeos;
-- CREATE TABLE poigeos(
--   gid serial PRIMARY KEY, 
--   the_geog geography
-- );


DROP TABLE IF EXISTS geo CASCADE;
CREATE TABLE geo (
  oid serial primary key, 
  term  text NOT NULL, 
  scheme  text,
  geomtype    varchar(12), 
  nativesrsuri  text, 
  nativecoords  text, 
  geogpt  GEOGRAPHY(POINT,4326)
)
INHERITS(poiroot);
    
SELECT AddGeometryColumn('geo', 'geompt', 4326, 'POINT', 2 );
SELECT AddGeometryColumn('geo', 'geomline', 4326, 'LINESTRING', 2 );
SELECT AddGeometryColumn('geo', 'geompoly', 4326, 'POLYGON', 2 );
COMMENT ON COLUMN geo.nativecoords IS 'geometry stored as 2D lat-lon for mapping so this text is for output';
COMMENT ON COLUMN geo.geomtype IS 'either POINT, LINESTRING, or POLYGON to know which column of geompt, geomline, and geompoly is not null';

DROP TABLE IF EXISTS relationship CASCADE;
CREATE TABLE relationship (
  term  text NOT NULL, 
  scheme  text,
  targetpoi    text
)
INHERITS(poiroot);

DROP TABLE IF EXISTS poilog CASCADE;
CREATE TABLE poilog (
  date TIMESTAMP NOT NULL, 
  type VARCHAR(12) NOT NULL, 
  msg VARCHAR(255) NOT NULL
);


CREATE OR REPLACE FUNCTION update_poihref() RETURNS trigger AS $poibasetype$
BEGIN 
  IF ( NEW.objname LIKE 'POI' ) THEN 
    NEW.href := 'http://openpois.ogcnetwork.net/pois/' || NEW.myid;
  END IF;
  RETURN NEW;
END;
$poibasetype$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION update_geography() RETURNS trigger AS $$
BEGIN 
  NEW.geogpt = geography(NEW.geompt);
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_poi_href BEFORE INSERT OR UPDATE ON poibasetype 
  FOR EACH ROW 
    EXECUTE PROCEDURE update_poihref();

CREATE TRIGGER update_geography_geo BEFORE INSERT OR UPDATE ON geo 
  FOR EACH ROW 
    EXECUTE PROCEDURE update_geography();


CREATE VIEW minipoi AS 
SELECT geo.oid as oid, loc.parentid AS poiuuid, labels.value as label, geo.geompt AS geompt, geo.geogpt AS geogpt, ('http://openpois.ogcnetwork.net/pois/'||loc.parentid) as href 
FROM poitermtype labels, geo, location loc 
WHERE geo.parentid = loc.myid AND labels.parentid = loc.parentid AND labels.objname LIKE 'LABEL' 
AND geo.deleted IS NULL AND loc.deleted IS NULL AND labels.deleted IS NULL;

DELETE FROM geometry_columns WHERE f_table_name LIKE 'minipoi';
INSERT INTO geometry_columns VALUES ('','public','minipoi','geompt',2,4326,'POINT');

GRANT SELECT ON geometry_columns to poiwebuser;
GRANT SELECT ON geo to poiwebuser;
GRANT SELECT ON minipoi to poiwebuser;
GRANT INSERT,UPDATE ON poilog to poiwebuser;

INSERT INTO poitermtype (myid,parentid,objname,id,value,href,type,authorid,licenseid,lang,base,created,updated,deleted,term) VALUES ('88f72b90-5cbe-4a7a-b1c1-6c9d83cd3998',NULL,'AUTHOR','http://geonames.org','GeoNames',NULL,'text/plain',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'publisher');
INSERT INTO poitermtype (myid,parentid,objname,id,value,href,type,authorid,licenseid,lang,base,created,updated,deleted,term) VALUES ('cd633a71-a93b-48bb-9275-694cbba3d0ed',NULL,'LICENSE','cd633a71-a93b-48bb-9275-694cbba3d0ed',NULL,'http://creativecommons.org/licenses/by-sa/3.0/','text/html',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CC-BY-SA');
INSERT INTO poitermtype (myid,parentid,objname,id,value,href,type,authorid,licenseid,lang,base,created,updated,deleted,term) VALUES ('d3360fcf-119f-4257-9c7e-efd5be8a5441',NULL,'AUTHOR','http://openstreetmap.org','OSM',NULL,'text/plain',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'publisher');
INSERT INTO poitermtype (myid,parentid,objname,id,value,href,type,authorid,licenseid,lang,base,created,updated,deleted,term) VALUES ('9140ea71-4e54-49b3-8210-9681574886b7',NULL,'LICENSE','9140ea71-4e54-49b3-8210-9681574886b7','ODBL','http://opendatacommons.org/licenses/odbl/','text/html',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'odbl');
INSERT INTO poitermtype (myid,parentid,objname,id,value,href,type,authorid,licenseid,lang,base,created,updated,deleted,term) VALUES ('d69f88f4-4c90-4d73-a317-29fbf95df9c4',NULL,'AUTHOR','http://factual.com','Factual',NULL,'text/plain',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'publisher');
INSERT INTO poitermtype (myid,parentid,objname,id,value,href,type,authorid,licenseid,lang,base,created,updated,deleted,term) VALUES ('0143b2fa-dd1d-4fef-8ca0-3fe4c0c5e30b',NULL,'AUTHOR','CHGIS','CHGIS, Version 4 Cambridge: Harvard Yenching Institute, January 2007','http://www.fas.harvard.edu/~chgis/','text/plain',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'publisher');
INSERT INTO poitermtype (myid,parentid,objname,id,value,href,type,authorid,licenseid,lang,base,created,updated,deleted,term) VALUES ('4774b6ec-e2a8-4efe-b5e2-aac5c8c4b0e9',NULL,'AUTHOR','http://dbpedia.org','DBPedia',NULL,'text/plain',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'publisher');
INSERT INTO poitermtype (myid,parentid,objname,id,value,href,type,authorid,licenseid,lang,base,created,updated,deleted,term) VALUES ('9884079a-3f80-4c77-bdac-0843600bcfa7',NULL,'AUTHOR','http://www.futouring.com',NULL,NULL,'text/plain',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'publisher');


-- Trigger to make a geography point data column stay in synch with geometry 
-- (we need this because we have to do mapping with Mapserver using geography, but everything else with geography)
-- CREATE OR REPLACE FUNCTION update_geography() RETURNS trigger AS $$
-- BEGIN 
--   NEW.geogpt = geography(NEW.geompt);
--   RETURN NEW;
-- END;
-- $$ LANGUAGE plpgsql;
-- 
-- CREATE TRIGGER update_geography_geo BEFORE INSERT OR UPDATE 
--   ON geo FOR EACH ROW 
--   EXECUTE PROCEDURE update_geography();


