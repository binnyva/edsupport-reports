<h1><?php echo $page_title ?></h1>

<?php if($data) { ?>
<table class="table table-striped">
<tr><?php $first_row = reset($data);
$header = array_keys($first_row);
foreach ($header as $label) {
	print "<th>" . format($label) . "</th>";
}
?></tr>

<?php foreach ($data as $row) { ?>
<tr>
	<td><?php echo implode("</td><td>", array_values($row)) ?></td>
</tr>
<?php } ?>
</table>
<?php } ?>