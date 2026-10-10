<?php

$conn = new mysqli("localhost", "root", "", "sweet_chicken_db");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

?>