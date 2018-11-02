<h1><?php echo $page_title ?> Report</h1>

<?php 
include('_filter.php');
?>

<ul>
	<li><a href="mentor_participation.php">Mentor Participation</a></li>
	<li><a href="mentor_participation.php?type=fellow">Fellow Participation</a></li>
	<li><a href="mentor_participation.php?type=es_fellows">ES Fellow Participation</a></li>
</ul>

<table class="table table-striped">
<tr>
	<?php if($type == 'mentor') { ?><th>Shelter</th><?php } ?>
	<th>Mentor</th><th># Classes to date</th><th># Classes attended</th><th>Attendandance %</th>
	<?php if($type == 'mentor') { ?><th>Classes Substituted</th><th>Total Attended %</th><?php } ?>
</tr>
<?php foreach($monitors as $user) {
	$batch = [];
	foreach ($all_batches as $batch_id => $batch_info) {
		if($batch_info['batch_head_id'] == $user['id']) $batch = $batch_info;
	}
	$classes_till_date = '';
	if($batch) {
		$classes_till_date = date('W') - date('W', strtotime($first_classes[$batch['center_id']]));
	} else {
		$classes_till_date = date('W') - date('W', strtotime($first_class));
	}
	?>
<tr>
	<?php if($type == 'mentor') { ?><td><?php if($batch) echo $all_centers[$batch['center_id']]['name']; ?></td><?php } ?>
	<td><?php echo $user['name'] ?></td>
	<td><?php echo $classes_till_date ?></td>
	<?php if($type == 'mentor') { ?>
	<td><?php echo $user['attended_own_batch'] ?></td>
	<td><?php if($classes_till_date) echo round($user['attended_own_batch'] / $classes_till_date * 100, 2) ?>%</td>
	<td><?php echo $user['attended'] ?></td>
	<td><?php if($classes_till_date) echo round(($user['attended'] + $user['attended_own_batch']) / $classes_till_date * 100, 2) ?>%</td>
	<?php } else { ?>
	<td><?php echo $user['attended'] ?></td>
	<td><?php if($classes_till_date) echo round(($user['attended']) / $classes_till_date * 100, 2) ?>%</td>
	<?php } ?>
</tr>
<?php } ?>
</table>
