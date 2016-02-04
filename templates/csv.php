<?php
header("Content-type: text/csv");

$all_cities = $sql->getById("SELECT id, name FROM City");

if($header) {
	if(isset($csv_format)) print implode(",", array_values($csv_format)) . "\n";
	else {
		print "City,Center,Date,$page_title,";
		if(isset($output_unmarked_format)) print "Unmarked,";
		print "Total\n"; // . implode(",", $week_dates) . "\n";
	}
}

foreach ($data as $center_info) {
	$csv_template = array($all_cities[$center_info['city_id']], $center_info['center_name']);

	foreach ($center_info['center_data'] as $key => $value) {
		if(!$key) continue; // Ignore the header row.

		if(!isset($csv_format)) {
			$csv = $csv_template;
			$csv[] = i($week_dates, $key - 1, ''); // Sunday date
			$csv[] = i($value, $output_data_format, 0); // Data point
			if(isset($output_unmarked_format)) $csv[] = i($value, $output_unmarked_format, 0); // Data point
			$csv[] = i($value, $output_total_format, 0); // Data point
		
		} else {
			foreach ($csv_format as $csv_key => $csv_title) {
				if($csv_key == 'week') $output = $week_dates[$key - 1];
				elseif($csv_key == 'city_name') $output = $all_cities[$center_info['city_id']];
				elseif($csv_key == 'center_name') $output =  $center_info['center_name'];
				else $output = i($value, $csv_key, 0);

				$csv[] = $output;
			}
		}
		dump($csv_format, $csv); exit;

		print '"' . implode('","', $csv) . '"' . "\n";
	}
}
