<?php
require 'common.php';

$from = i($QUERY, 'from', false);
$to = i($QUERY, 'to', false);
$city_id = i($QUERY, 'city_id', 0);
$center_id = i($QUERY, 'center_id', 0);
$level_id = i($QUERY, 'level_id', 0);
$student_id = i($QUERY, 'student_id', 0);
$project_id = 0;

$where = ["S.status='1' AND C.status='1'"];
if($from) $where['from'] = "DATE(R.added_on) >= '$from'";
if($to) $where['to'] = "DATE(R.added_on) < '$to'";
if($city_id) $where['city_id'] = "C.city_id = $city_id";
if($center_id) $where['center_id'] = "S.center_id = $center_id";
if($student_id) $where['student_id'] = "S.id = $student_id";

$data_sql = "SELECT S.id, R.user_id, R.added_on, R.question_id, R.response 
						FROM IS_Response R
						INNER JOIN Student S ON S.id = R.student_id
						INNER JOIN Center C ON S.center_id = C.id
						WHERE " . implode(" AND ", $where);
$data = $sql->getAll($data_sql);

unset($where['from']);
unset($where['to']);
$all_students = $sql->getById("SELECT S.id, S.name FROM Student S
								INNER JOIN Center C ON S.center_id = C.id
								WHERE " . implode(" AND ", $where));
$all_questions = $sql->getById("SELECT id, question FROM IS_Question WHERE status='1'");
// $data = $sql->getAll("SELECT S.id, ")

$students = [];
$teachers = [];
$responses = [0,0,0,0,0,0,0,0,0,0,0];
$totals = [0,0,0,0,0,0,0,0,0,0,0];
foreach ($data as $row) {
	if(!isset($teachers[$row['user_id']])) $teachers[$row['user_id']] = 0;
	$teachers[$row['user_id']]++; 

	$responses[$row['question_id']] += intval($row['response']);
	$totals[$row['question_id']]++;
}

$avg = [];
$text = '<dl>';
for($i=1; $i<7; $i++) {
	$avg[$i] = round($responses[$i] / $totals[$i], 2);

	$text .= "<dt>" . $all_questions[$i] . "</dt><dd>" . $avg[$i]. "</dd>";
}
$text .= "</dl>"; // . $data_sql;

# data for 2018-19 required for DXC reporting  Number of teachers who filled the impact survey  ,motivation% ,self esteem% ,perseverance% ,comprehension% , knowledge of fundamentals% ,exam readiness %

$files = [];
$show_filter = 1;
$title = 'Impact Survey Report';

render('index.php');
