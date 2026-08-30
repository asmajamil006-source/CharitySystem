<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$server = "localhost\\SQLEXPRESS";
$dbname = "CharityDB";

try {
    $conn = new PDO(
        "sqlsrv:Server=$server;Database=$dbname",
        "",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die("DB ERROR: " . $e->getMessage());
}
?>