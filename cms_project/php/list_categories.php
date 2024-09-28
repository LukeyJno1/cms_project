<?php
if (isset($_POST['dbname'], $_POST['host'], $_POST['username'], $_POST['password'])) {
    $dbname = $_POST['dbname'];
    $host = $_POST['host'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Connect to MySQL server with the provided credentials and database
    $conn = new mysqli($host, $username, $password, $dbname);

    if ($conn->connect_error) {
        echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
        exit;
    }

    // Fetch all categories
    $sql = "SELECT id, name, parent_id FROM categories ORDER BY parent_id, name";
    $result = $conn->query($sql);

    $categories = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }

    echo json_encode($categories);  // Return categories as JSON

    $conn->close();
}
?>
