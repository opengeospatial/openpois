-- COPY geo (myid,parentid,objname,id,value,href,type,created,updated,deleted,authorid,licenseid,lang,base,userid,term,scheme,geomtype,nativesrsuri,nativecoords,geompt) FROM '/srv/openpoidb/databases/osm/RI/dbfiles/geo.in';
-- COPY location (myid,parentid,objname) from '/srv/openpoidb/databases/osm/RI/dbfiles/location.in';
-- COPY poibasetype from '/srv/openpoidb/databases/osm/RI/dbfiles/poibasetype.in';
-- COPY poitermtype from '/srv/openpoidb/databases/osm/RI/dbfiles/poitermtype.in';

COPY geo (myid,parentid,objname,id,value,href,type,created,updated,deleted,authorid,licenseid,lang,base,userid,term,scheme,geomtype,nativesrsuri,nativecoords,geompt) FROM '/var/lib/postgresql/osm/dbfiles/geo.in';
COPY location (myid,parentid,objname) from '/var/lib/postgresql/osm/dbfiles/location.in';
COPY poibasetype from '/var/lib/postgresql/osm/dbfiles/poibasetype.in';
COPY poitermtype from '/var/lib/postgresql/osm/dbfiles/poitermtype.in';
