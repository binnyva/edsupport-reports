<?php
require('../common.php');

$opts = getOptions($QUERY);
extract($opts);
$credit = i($QUERY, 'credit', 0);
unset($checks['from']);
unset($checks['to']);

$checks['credit'] = "U.credit < $credit";
$page_title = "Substitutions";

$data = $sql->getAll("SELECT DISTINCT U.name, Ctr.name AS center_name, U.credit
		FROM User U
		INNER JOIN UserGroup UG ON UG.user_id=U.id
		INNER JOIN UserBatch UB ON UB.user_id=U.id
		INNER JOIN Batch B ON B.id=UB.batch_id
		INNER JOIN Center Ctr ON Ctr.id=B.center_id
		WHERE U.status='1' AND UG.group_id=9 AND "
		. implode(' AND ', $checks)
		. " ORDER BY U.name");
print "SELECT DISTINCT U.name, U.credit 
		FROM User U
		INNER JOIN UserGroup UG ON UG.user_id=U.id
		INNER JOIN UserBatch UB ON UB.user_id=U.id
		INNER JOIN Batch B ON B.id=UB.batch_id
		INNER JOIN Center Ctr ON Ctr.id=B.center_id
		WHERE U.status='1' AND UG.group_id=9 AND "
		. implode(' AND ', $checks)
		. " ORDER BY U.name";
render('listing.php');
