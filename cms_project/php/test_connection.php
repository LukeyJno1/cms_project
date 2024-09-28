<?php
if (isset($_POST['host'], $_POST['username'], $_POST['password'])) {
    $host = $_POST['host'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Try connecting to the MySQL server
    $conn = new mysqli($host, $username, $password);

    if ($conn->connect_error) {
        echo "Connection failed: " . $conn->connect_error;
    } else {
        echo "Connection successful";
    }

    $conn->close();
}
?>
