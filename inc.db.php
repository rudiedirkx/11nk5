<?php

require DB_PATH . '/db_mysql.php';
$db = db_mysql::open(array('host' => DB_HOST, 'user' => DB_USER, 'pass' => DB_PASS, 'db' => DB_NAME));
