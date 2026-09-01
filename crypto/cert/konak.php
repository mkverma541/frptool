<?php
$config = include('../../../config.php');
$koneksi = new mysqli('localhost', $config['db_user'], $config['db_pass'], $config['db_name']);
if ($koneksi->connect_error) {
    die("Connection failed: " . $koneksi->connect_error);
}
?>
