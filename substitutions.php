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

if(!$data) {
	$cache_status = false;
	$data = array();

	if($center_id == -1) $all_centers_in_city = $sql->getCol("SELECT id FROM Center WHERE city_id=$city_id AND status='1'");
	else $all_centers_in_city = array($center_id);

	$template_array = array('total_class' => 0, 'substitution' => 0, 'percentage' => 0);
	$data_template = array($template_array, $template_array, $template_array, $template_array);
	$national = $data_template;

	$all_classes = $sql->getAll("SELECT UC.id, UC.substitute_id, UC.class_id, C.class_on, Ctr.city_id, B.center_id
		FROM Class C
		INNER JOIN Batch B ON B.id=C.batch_id
		INNER JOIN Center Ctr ON B.center_id=Ctr.id
		INNER JOIN UserClass UC ON UC.class_id=C.id
		WHERE C.status='happened' AND B.year=$year AND "
		. implode(' AND ', $sql_checks));

	foreach ($all_classes as $c) {
		if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

		$index = findWeekIndex($c['class_on']);
		if($index <= 3 and $index >= 0) $national[$index]['total_class']++;
		if($c['substitute_id']) {
			if($index <= 3 and $index >= 0) $national[$index]['substitution']++;
		}
	}

	foreach ($all_centers_in_city as $this_center_id) {
		$adoption = getAdoptionDataPercentage($city_id, $this_center_id, $all_cities, $all_centers, 'volunteer');
		$center_data = $data_template;
		$annual_data = $template_array;

		foreach ($all_classes as $c) {
			if($c['class_on'] > date("Y-m-d H:i:s")) continue; // Don't count classes not happened yet.

			$index = findWeekIndex($c['class_on']);

			if((!$this_center_id or ($c['center_id'] == $this_center_id)) and (!$city_id or ($c['city_id'] == $city_id))) {
				$annual_data['total_class']++;
				if($index <= 3 and $index >= 0) $center_data[$index]['total_class']++;

				if($c['substitute_id']) {
					$annual_data['substitution']++;
					if($index <= 3 and $index >= 0) $center_data[$index]['substitution']++;
				}
			}
		}

		foreach($center_data as $index => $value) {
			if($center_data[$index]['total_class']) $center_data[$index]['percentage'] = round($center_data[$index]['substitution'] / $center_data[$index]['total_class'] * 100, 2);
			if($national[$index]['total_class']) $national[$index]['percentage'] = round($national[$index]['substitution'] / $national[$index]['total_class'] * 100, 2);
		}
		if($annual_data['total_class']) $annual_data['percentage'] = round($annual_data['substitution'] / $annual_data['total_class'] * 100, 2);

		
		$weekly_graph_data = array(
				array('Weekly ' . $page_title, '% of Substitutions', 'National Average'),
				array('Four week Back', $center_data[3]['percentage'], $national[3]['percentage']),
				array('Three Week Back',$center_data[2]['percentage'], $national[2]['percentage']),
				array('Two Week Back', 	$center_data[1]['percentage'], $national[1]['percentage']),
				array('Last Week',   	$center_data[0]['percentage'], $national[0]['percentage']),
			);
		$annual_graph_data = array(
				array('Year', '% of Substitutions'),
				array('Regular Classes',	100 - $annual_data['percentage']),
				array('Substituted Classes',$annual_data['percentage']),
			);

		$data[$this_center_id]['weekly_graph_data'] = $weekly_graph_data;
		$data[$this_center_id]['annual_graph_data'] = $annual_graph_data;

		$opts['center_id'] = $this_center_id;
		$data[$this_center_id]['listing_link'] = getLink('substitutions_listing.php', $opts);
		$data[$this_center_id]['listing_text'] = 'List Substitutions';

		$data[$this_center_id]['city_id'] = $city_id;
		$data[$this_center_id]['center_id'] = $this_center_id;
		$data[$this_center_id]['center_name'] = ($this_center_id) ? $sql->getOne("SELECT name FROM Center WHERE id=$this_center_id") : '';
	}
	setCache($cache_key, $data);
}

$colors = array('#16a085', '#e74c3c');

if($format == 'csv') render('csv.php', false);
else render('multi_graph.php');
