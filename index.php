<?php
require '../common.php';

$files = ls('*.php');
$files = array_remove($files, 'index.php');
$files = array_remove($files, 'configuration.php');

render();
