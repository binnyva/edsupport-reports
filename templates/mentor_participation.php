<h1><?php echo $page_title ?> Report</h1>

<?php 
include('_filter.php');
?>

<table class="table table-striped">
<tr><th>Shelter</th><th>Mentor</th><th># Classes to date</th><th># Classes attended</th><th>Attendandance %</th><th>Classes Substituted</th><th>Total Attended %</th></tr>
<?php foreach($monitors as $user) {
	$batch = [];
	foreach ($all_batches as $batch_id => $batch_info) {
		if($batch_info['batch_head_id'] == $user['id']) $batch = $batch_info;
	}
	$classes_till_date = '';
	if($batch) {
		$classes_till_date = date('W') - date('W', strtotime($first_classes[$batch['center_id']]));
	}
	?>
<tr>
	<td><?php if($batch) echo $all_centers[$batch['center_id']]['name']; ?></td>
	<td><?php echo $user['name'] ?></td>
	<td><?php echo $classes_till_date ?></td>
	<td><?php echo $user['attended_own_batch'] ?></td>
	<td><?php if($classes_till_date) echo round($user['attended_own_batch'] / $classes_till_date * 100, 2) ?>%</td>
	<td><?php echo $user['attended'] ?></td>
	<td><?php if($classes_till_date) echo round($user['attended'] / $classes_till_date * 100, 2) ?>%</td>
</tr>
<?php } ?>
</table>
