<?php
require('../common.php');

$opts = getOptions($QUERY);
extract($opts);
$cancel_option = i($QUERY, 'cancel_option', '');
$checks['status'] = "C.status='cancelled'";
if($cancel_option) $checks['cancel_option'] = "C.cancel_option LIKE '$cancel_option%'";

$page_title = "Classes Cancelled";

$data = $sql->getAll("SELECT City.name AS city_name, Ctr.name AS center_name, CONCAT(L.grade, L.name) AS level, DATE_FORMAT(C.class_on, '%d %b %Y(%a), %l:%i %p') AS class_on, C.cancel_option, C.cancel_reason
		FROM Class C
		INNER JOIN Level L ON L.id=C.level_id
		INNER JOIN Batch B ON B.id=C.batch_id
		INNER JOIN Center Ctr ON B.center_id=Ctr.id
		INNER JOIN City ON Ctr.city_id=City.id
		WHERE B.year=$year AND "
		. implode(' AND ', $checks)
		. " ORDER BY City.name, center_name, level, C.class_on DESC");

render('listing.php');