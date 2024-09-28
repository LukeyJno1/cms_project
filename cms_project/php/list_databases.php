<?php
if (isset($_POST['host'], $_POST['username'], $_POST['password'])) {
    $host = $_POST['host'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Connect to MySQL server with the provided credentials
    $conn = new mysqli($host, $username, $password);

    if ($conn->connect_error) {
        echo "Connection failed: " . $conn->connect_error;
        exit;
    }

    // List all databases
    $sql = "SHOW DATABASES";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Generate radio buttons for each available database
            echo '<input type="radio" name="database" value="' . $row['Database'] . '"> ' . $row['Database'] . '<br>';
        }
    } else {
        echo "No databases found.";
    }

    $conn->close();
}
?>
