<?php
$mime = i($QUERY, 'mime', 'csv');
header("Content-type: text/$mime");

$all_cities = $sql->getById("SELECT id, name FROM City");

if($header) {
	if(isset($csv_format)) print implode(",", array_values($csv_format)) . "\n";
	else {
		print "City,Center,Date,$page_title,";
		if(isset($output_unmarked_format)) print "Unmarked,";
		print "Total\n";
	}
}

foreach ($data as $center_info) {
	$csv_template = array($all_cities[$center_info['city_id']], $center_info['center_name']);

	foreach ($center_info['center_data'] as $key => $value) {
		if($key <= 0) continue; // Ignore the header row.

		if(!isset($csv_format)) {
			$csv = $csv_template;
			$csv[] = i($week_dates, $key - 1, ''); // Sunday date
			$csv[] = i($value, $output_data_format, 0); // Data point
			if(isset($output_unmarked_format)) $csv[] = i($value, $output_unmarked_format, 0); // Data point
			$csv[] = i($value, $output_total_format, 0); // Data point
		
		} else {
			$csv = array();
			foreach ($csv_format as $csv_key => $csv_title) {
				if($csv_key == 'week') $output = i($week_dates, $key - 1);
				elseif($csv_key == 'city_name') $output = $all_cities[$center_info['city_id']];
				elseif($csv_key == 'center_name') $output =  $center_info['center_name'];
				elseif($csv_key == 'participation_1_2') $output =  $value['participation_1'] + $value['participation_2'];
				elseif($csv_key == 'participation_4_5') $output =  $value['participation_4'] + $value['participation_5'];
				else $output = i($value, $csv_key, 0);

				$csv[] = $output;
			}
		}

		print '"' . implode('","', $csv) . '"' . "\n";
	}
}
