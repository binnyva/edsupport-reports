<?php
require('../common.php');

$opts = getOptions($QUERY);
extract($opts);

$checks['status'] = "UC.substitute_id != 0";
$page_title = "Substitutions";
$data = $sql->getAll("SELECT Ctr.name AS center_name, CONCAT(L.grade, L.name) AS level, DATE_FORMAT(C.class_on, '%d %b %Y(%a), %l:%i %p') AS class_on, U.name AS volunteer_name
		FROM Class C
		INNER JOIN Batch B ON B.id=C.batch_id
		INNER JOIN Level L ON L.id=C.level_id
		INNER JOIN Center Ctr ON B.center_id=Ctr.id
		INNER JOIN UserClass UC ON UC.class_id=C.id
		INNER JOIN User U ON U.id=UC.substitute_id
		WHERE C.status='happened' AND B.year=$year AND "
		. implode(' AND ', $checks)
		. " ORDER BY C.class_on DESC");

render('listing.php');
