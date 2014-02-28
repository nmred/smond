<?php
require 'core.php';

use \lib\monitor\sw_monitor;

$params = array(
	'device' => '/dev/sda1',
);
$data = sw_monitor::run('harddisk', $params);
var_dump($data);

