<?php

// Unicode Reminder メモ

$opt['rootpath'] = '../../../';
require($opt['rootpath'] . 'lib2/web.inc.php');
sql_enable_debugger();

/*
	(1) Füge alle Einträge die diesem Filter entsprechen der Ergebnisliste hinzu

	Filtertyp: nach Entfernung
	Koordinaten: N 48° 30.000' E 9°30.000'
	Entfernung: 75 km
*/
sql('CREATE TEMPORARY TABLE result_caches (`distance` double NOT NULL, `cache_id` int(11) NOT NULL, `search_time` double default NULL, `status` int(11) default NULL, PRIMARY KEY (cache_id)) ENGINE=MEMORY SELECT (acos(cos(0.72431) * cos((90-`caches`.`latitude`) * 3.14159 / 180) + sin(0.72431) * sin((90-`caches`.`latitude`) * 3.14159 / 180) * cos((9.50000-`caches`.`longitude`) * 3.14159 / 180)) * 6370) `distance`, `caches`.`cache_id` `cache_id`, `caches`.`search_time` `search_time`, `caches`.`status` `status` FROM `caches` WHERE `longitude` > 8.48320014339 AND `longitude` < 10.5167998566 AND `latitude` > 47.8250539957 AND `latitude` < 49.1749460043 HAVING `distance` < 75');

/*
	(2) Entferne alle Einträge die diesem Filter entsprechen von der Ergebnisliste

	Filtertyp: nach Finder
	User: Team A
*/
sql('CREATE TEMPORARY TABLE remove_caches (`cache_id` int(11) NOT NULL, PRIMARY KEY (cache_id)) ENGINE=MEMORY SELECT DISTINCT result_caches.cache_id cache_id FROM result_caches, cache_logs WHERE result_caches.cache_id=cache_logs.cache_id AND cache_logs.user_id IN (101254, 101301)');
sql('DELETE FROM result_caches WHERE cache_id IN (SELECT cache_id FROM remove_caches) OR (status!=1) OR (search_time>2)');
sql('DROP TABLE remove_caches');

$tpl->display();

?>