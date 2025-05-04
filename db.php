<?php
$host = 'localhost'; // Database host
$dbname = 'parkingapptest'; // Database name
$username = 'root'; // Database username
$password = ''; // Database password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>