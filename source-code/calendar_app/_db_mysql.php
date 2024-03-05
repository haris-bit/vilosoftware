<?php
$host = "127.0.0.1";
$port = 3306;
$username = "anomozco_octcric";
$password = 'rWg#M$vFYk]+';
$database = "anomozco_octcric";

error_reporting(E_ERROR);
ini_set('display_errors', '1');

$db = new PDO("mysql:host=$host;port=$port",
               $username,
               $password);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("use `$database`");
include("./global.php");
