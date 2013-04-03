COPY geo (myid,parentid,objname,id,value,href,type,created,updated,deleted,authorid,licenseid,lang,base,userid,term,scheme,geomtype,nativesrsuri,nativecoords,geompt) FROM '/srv/openpoidb/databases/geonames/tmp/geo.in';
COPY location (myid,parentid,objname) from '/srv/openpoidb/databases/geonames/tmp/location.in';
COPY poibasetype from '/srv/openpoidb/databases/geonames/tmp/poibasetype.in';
COPY poitermtype from '/srv/openpoidb/databases/geonames/tmp/poitermtype.in';
---- no longer needed
---- UPDATE geo SET geompt = ST_SetSRID(ST_MakePoint(split_part(nativecoords,' ',2)::double precision, split_part(nativecoords,' ',1)::double precision),4326);
