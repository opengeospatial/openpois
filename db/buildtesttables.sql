DROP TABLE IF EXISTS ngageonet CASCADE;
SELECT DropGeometryColumn('ngageonet', 'geompt');

CREATE TABLE ngageonet (
  ufi   integer, 
  fc    varchar(1), 
  name  varchar(255)  
);    
SELECT AddGeometryColumn('ngageonet', 'geompt', 4326, 'POINT', 2 );

DROP TABLE IF EXISTS geonames CASCADE;
CREATE TABLE geonames (
  id   integer, 
  name  varchar(200), 
  fclass  varchar(1), 
  fcode   varchar(10)
);    
SELECT AddGeometryColumn('geonames', 'geompt', 4326, 'POINT', 2 );
