<?php
require('../common.php');

$opts = getOptions($QUERY);
extract($opts);

$sql_checks = $checks;
unset($sql_checks['city_id']);  // We want everything - because we need to calculate national avg as well.
unset($sql_checks['center_id']);
unset($opts['checks']);

$page_title = 'Substitutions';
list($data, $cache_key) = getCacheAndKey('data', $opts); //* If you want to clear Cache */ $data = array();

$output_data_format = 'percentage';
if($format == 'csv') $output_data_format = 'substitution';
$output_total_format = 'total_class';
$output_unmarked_format = 'unmarked';

// Output format...
// Total Number of Classes, Total Number of classes that are not cancelled, Classes with Original Teachers Present, 
// Total number of teachers, Total number of teachers in classes that were not cancelled, Total number of teachers present(Unsubstituted), Total number of substitutes, Total number of absent teachers, Unmarked classes.

if(!$data) {
	$cache_status = false;
	$data = array();

	if($center_id == -1) $all_centers_in_city = $sql->getCol("SELECT id FROM Center WHERE city_id=$city_id AND status='1'");
	else $all_centers_in_city = array($center_id);
	$sql_checks['city_id'] = $city_id;

	$template_array = array('total_class' => 0, 'substitution' => 0, 'all_present' => 0, 'absent' => 0, 'class_count' => 0, 'happened_class_count' => 0, 'cancelled' => 0,
		'marked' => 0, 'unmarked' => 0, 'percentage' => 0);
	$data_template = array($template_array, $template_array, $template_array, $template_array);
	$national = $data_template;


	if($format == 'csv') {
		$sql_checks['city_id'] = "Ctr.city_id=$city_id"; // If we don't want the entire national data - CSV don't have national avg.
	}

	$all_classes = $sql->getAll("SELECT UC.id, UC.substitute_id, UC.class_id, C.class_on, Ctr.city_id, B.center_id, C.status, UC.status user_status
		FROM UserClass UC
		INNER JOIN Class C ON UC.class_id=C.id
		INNER JOIN Batch B ON B.id=C.batch_id
		INNER JOIN Center Ctr ON B.center_id=Ctr.id
		WHERE B.year=$year AND "
		. implode(' AND ', $sql_checks)
		. " ORDER BY C.class_on DESC, UC.class_id");

	if($format != 'csv') {
		foreach ($all_classes as $c) {
			if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

			$index = findWeekIndex($c['class_on']);
			if(!isset($national[$index])) $national[$index] = $template_array;

			$national[$index]['total_class']++;
			if($c['substitute_id']) $national[$index]['substitution']++;
			elseif($c['status'] == 'projected') $national[$index]['unmarked']++;
			if($c['status'] != 'projected') $national[$index]['marked']++;
		}
		foreach($national as $index => $value) {
			if($national[$index]['marked']) $national[$index]['percentage'] = round($national[$index]['substitution'] / $national[$index]['marked'] * 100, 2);
		}
	}

	foreach ($all_centers_in_city as $this_center_id) {
		if($format != 'csv') $data[$this_center_id]['adoption'] = getAdoptionDataPercentage($city_id, $this_center_id, $all_cities, $all_centers, 'volunteer');

		// Initializations
		$center_data = $data_template;
		$annual_data = $template_array;
		$last_class_id = 0;
		$is_substituted = 0;
		$is_absent = 0;
		$is_cancelled = 0;
		$marked = 1;

		foreach ($all_classes as $c) {
			if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

			$index = findWeekIndex($c['class_on']);
			if(!isset($center_data[$index])) $center_data[$index] = $template_array;

			if((!$this_center_id or ($c['center_id'] == $this_center_id)) and (!$city_id or ($c['city_id'] == $city_id))) {
				$annual_data['total_class']++;
				$center_data[$index]['total_class']++;

				if($last_class_id != $c['class_id']) { // New class
					$center_data[$index]['class_count']++;
					$annual_data['class_count']++;

					// This is the only place where classes are marked
					if($marked) {
						$center_data[$index]['marked']++;
						$annual_data['marked']++;

						if($is_substituted and !$is_absent) {
							$center_data[$index]['substitution']++;
							$annual_data['substitution']++;
						}

						if($is_absent) {
							$center_data[$index]['absent']++;
							$annual_data['absent']++;
						}

						if(!$is_cancelled and !$is_substituted and !$is_absent) {
							$center_data[$index]['all_present']++;
							$annual_data['all_present']++;
						}
						if(!$is_cancelled) {
							$center_data[$index]['happened_class_count']++;
							$annual_data['happened_class_count']++;
						} else {
							$center_data[$index]['cancelled']++;
							$annual_data['cancelled']++;
						}
					} else {
						$annual_data['unmarked']++;
						$center_data[$index]['unmarked']++;
					}

					// Re-initialize for next class
					$last_class_id = $c['class_id'];
					$is_substituted = 0;
					$is_absent = 0;
					$is_cancelled = 0;
					$marked = 1;

				}

				if($last_class_id == $c['class_id'] or $last_class_id) { // Next instance of the Same class 
					if($c['status'] == 'projected') $marked = 0;
					elseif($c['substitute_id']) $is_substituted = 1;
					if($c['user_status'] == 'absent') $is_absent = 1;
					if($c['status'] == 'cancelled') $is_cancelled = 1;
				}
			}
		}

		foreach($center_data as $index => $value) {
			if($center_data[$index]['marked']) $center_data[$index]['percentage'] = round($center_data[$index]['substitution'] / $center_data[$index]['marked'] * 100, 2);
		}
		if($annual_data['marked']) $annual_data['percentage'] = round($annual_data['substitution'] / $annual_data['marked'] * 100, 2);

		$weekly_graph_data = array(
				array('Weekly ' . $page_title, '%', 'National Average'),
				array('Four week Back', $center_data[3][$output_data_format], $national[3][$output_data_format]),
				array('Three Week Back',$center_data[2][$output_data_format], $national[2][$output_data_format]),
				array('Two Week Back', 	$center_data[1][$output_data_format], $national[1][$output_data_format]),
				array('Last Week',   	$center_data[0][$output_data_format], $national[0][$output_data_format]),
			);
		$annual_graph_data = array(
				array('Year', '% of Substitutions'),
				array('Regular Classes',	$annual_data['marked']),
				array('Substituted Classes',$annual_data['substitution']),
			);

		$data[$this_center_id]['weekly_graph_data'] = $weekly_graph_data;
		$data[$this_center_id]['annual_graph_data'] = $annual_graph_data;
		
		$data[$this_center_id]['week_dates'] = $week_dates;
		$data[$this_center_id]['center_data'] = $center_data;

		$opts['center_id'] = $this_center_id;
		$data[$this_center_id]['listing_link'] = getLink('substitutions_listing.php', $opts);
		$data[$this_center_id]['listing_text'] = 'List Substitutions';

		$data[$this_center_id]['city_id'] = $city_id;
		$data[$this_center_id]['center_id'] = $this_center_id;
		$data[$this_center_id]['center_name'] = ($this_center_id) ? $sql->getOne("SELECT name FROM Center WHERE id=$this_center_id") : '';
	}
	setCache($cache_key, $data);
}
// dump($data); exit;
$colors = array('#16a085', '#e74c3c');

$csv_format = array(
		'city_name'		=> 'City',
		'center_name'	=> 'Center',
		'week'			=> 'Week',
		'all_present'	=> 'All Original Teachers',
		'absent'		=> 'At Least One Absent',
		'substitution'	=> 'At Least One Substitution',
		'cancelled'		=> 'Cancelled',
		'marked'		=> 'Marked',
		'unmarked'		=> 'Unmarked',
		'happened_class_count' => 'Classes Conducted',
		'class_count'	=> 'Class to be Conducted',
	);

if($format == 'csv') render('csv.php', false);
else render('multi_graph.php');
