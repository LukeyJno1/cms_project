<?php
// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the 'database' POST parameter is set
    if (isset($_POST['database']) && !empty($_POST['database'])) {
        $database = $_POST['database'];

        // Establish a connection to the selected database
        $conn = new mysqli('localhost', 'root', '', $database);

        // Check if the connection was successful
        if ($conn->connect_error) {
            echo json_encode(["status" => "error", "message" => "Failed to connect to the database: " . $conn->connect_error]);
            exit;
        }

        // Query to fetch the categories
        $query = "SELECT name FROM categories ORDER BY name ASC";
        $result = $conn->query($query);

        // Initialize an array to store categories
        $categories = [];

        if ($result) {
            // Fetch all categories and store in the array
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            // Return categories as JSON response
            echo json_encode($categories);
        } else {
            // Return error if query fails
            echo json_encode(["status" => "error", "message" => "Error fetching categories."]);
        }

        // Close the connection
        $conn->close();
    } else {
        // Return error if database is not provided
        echo json_encode(["status" => "error", "message" => "No database provided."]);
    }
} else {
    // Return error if the request method is not POST
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
