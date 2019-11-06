<div class="container">

<?php 
if(!empty($title)) echo "<h1>$title</h1>";
if(!empty($show_filter)) include('_filter.php'); 
?>

<?php foreach($files as $f) { ?>
<div class="tile col-md-3" style="background-color:<?php echo color() ?>">
<a href="<?php echo $f ?>" target="_blank"><?php echo format(str_replace(".php", "", $f)); ?> Report</a>
</div>
<?php } ?>

<div id="text">
<?php if(isset($text)) echo $text; ?>
</div>
</div>