<?php
    $server = "localhost";
    $username = "root";
    $password = "";
    $port = 3306;

    $conn = new mysqli($server, $username, $password, "alumni", $port);

    if($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

