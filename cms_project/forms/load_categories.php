<?php
// Enable error reporting for debugging purposes
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the database is passed
    if (isset($_POST['database']) && !empty($_POST['database'])) {
        $database = $_POST['database'];

        // Connect to the selected database
        $conn = new mysqli('localhost', 'root', '', $database);
        if ($conn->connect_error) {
            echo json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]);
            exit;
        }

        // Fetch categories from the database
        $sql = "SELECT name FROM categories ORDER BY name ASC";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $categories = [];

            // Prepare categories as an array
            while ($row = $result->fetch_assoc()) {
                $categories[] = ['name' => $row['name']];
            }

            // Return categories as a JSON response
            echo json_encode($categories);
        } else {
            echo json_encode([]);
        }

        $conn->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database not specified.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
