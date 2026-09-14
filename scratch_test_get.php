<?php
chdir(__DIR__ . '/controllers');
$_GET['action'] = 'get';
$_GET['id'] = 11;
$_REQUEST['action'] = 'get';
$_REQUEST['id'] = 11;
require 'arcos_controller.php';
