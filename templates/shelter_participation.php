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
	<th>Shelter</th><th>Batch</th><th>Batch Conducted</th><th>Batch Attended</th>
</tr>
<?php foreach($data as $shelter) { ?>
<tr>
	<td><?php echo $shelter['center'] ?></td>
	<td><?php echo $shelter['batch'] ?></td>
	<td><?php echo count($shelter['conducted']) ?></td>
	<td><?php echo $shelter['attended'] ?></td>
</tr>
<?php } ?>
</table>
