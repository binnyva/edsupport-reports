<?php
require '../common.php';

$files = ls('*.php');

$files = array_remove($files, 'index.php');
$files = array_remove($files, 'configuration.php');
foreach ($files as $f) {
	if(strpos($f, 'listing') !== false) $files = array_remove($files, $f);
}

render();
