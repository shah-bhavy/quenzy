<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db_name = 'quenzy';

$mysqli = new mysqli($host, $user, $pass, $db_name);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
?>
