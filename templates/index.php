<div id="content" class="container">
<?php foreach($files as $f) { ?>
<div class="tile col-md-3" style="background-color:<?php echo color() ?>">
<a href="<?php echo $f ?>"><?php echo format(str_replace(".php", "", $f)); ?> Report</a>
</div>
<?php } ?>
</div>