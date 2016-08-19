<?php
require '../../common.php';

/// Cron job to send a weekly report of cancelled classes to a given email id. Lend the link of the actual report with his more dig-downable.

$from_last_week_date = date('Y-m-d', strtotime('-7 days'));
$from_last_4_days_date = date('Y-m-d', strtotime('-4 days'));
$to_date = date('Y-m-d');

$from_last_week_date_formated = date('jS M, Y', strtotime($from_last_week_date));
$from_last_4_days_formated = date('jS M, Y', strtotime($from_last_4_days_date));
$to_date_formated = date('jS M, Y');

$cancelled_last_week = $sql->getOne("SELECT COUNT(C.id) FROM Class C 
	INNER JOIN Level L ON L.id=C.level_id
	WHERE C.class_on > '$from_last_week_date' AND L.year=$year AND C.status='cancelled'");
$cancelled_last_4_days = $sql->getOne("SELECT COUNT(C.id) FROM Class C 
	INNER JOIN Level L ON L.id=C.level_id
	WHERE C.class_on > '$from_last_4_days_date' AND L.year=$year AND C.status='cancelled'");
$cancelled_this_year = $sql->getOne("SELECT COUNT(C.id) FROM Class C 
	INNER JOIN Level L ON L.id=C.level_id
	WHERE L.year=$year AND C.status='cancelled'");


$email=<<<END
Hey,

Canceled class in...

Last week($from_last_week_date_formated to $to_date_formated): $cancelled_last_week
Last four days($from_last_4_days_formated to $to_date_formated): $cancelled_last_4_days
This year: $cancelled_this_year

For detailed report go to the cancelled classes report...
http://makeadiff.in/apps/reports/class_cancellation_details.php?city_id=0&center_id=0&from=$from_last_week_date&to=$to_date

--
MAD Bot
END;

$send_to = array('loy@makeadiff.in', 'aswin@makeadiff.in', 'sheltersupport1.operations@makeadiff.in', 'sheltersupport2.operations@makeadiff.in', 'sheltersupport3.operations@makeadiff.in', 'sheltersupport1.design@makeadiff.in', 'binnyva@makeadiff.in');

foreach ($send_to as $to) {
	@email($to, "$cancelled_last_week classes cancelled last week", $email);
}
print $email;
