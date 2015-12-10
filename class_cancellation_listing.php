<?php
require('../common.php');

$opts = getOptions($QUERY);
extract($opts);
$checks['status'] = "C.status='cancelled'";

$page_title = "Classes Cancelled";

$data = $sql->getAll("SELECT Ctr.name AS center_name, CONCAT(L.grade, L.name) AS level, DATE_FORMAT(C.class_on, '%d %b %Y(%a), %l:%i %p') AS class_on, C.cancel_option, C.cancel_reason
		FROM Class C
		INNER JOIN Level L ON L.id=C.level_id
		INNER JOIN Batch B ON B.id=C.batch_id
		INNER JOIN Center Ctr ON B.center_id=Ctr.id
		WHERE B.year=$year AND "
		. implode(' AND ', $checks)
		. " ORDER BY C.class_on DESC");

render('listing.php');