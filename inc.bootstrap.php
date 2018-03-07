<?php

// Always
header('Content-type: text/html; charset="utf-8"');

// Config
require 'env.php';
require 'vendor/autoload.php';
ini_set('date.timezone', 'Europe/Amsterdam');
error_reporting(E_ALL & ~E_STRICT);

// Database
$db = db_mysql::open(array('host' => DB_HOST, 'user' => DB_USER, 'pass' => DB_PASS, 'db' => DB_NAME));
if ( !$db ) {
	header('HTTP/1.1 500 Connection error', true, 500);
	exit('Connection error');
}

// Xnary
Xnary::$range = implode(range('A', 'Z')) . implode(range('0', '9'));
