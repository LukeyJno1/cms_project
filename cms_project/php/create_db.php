<?php
require_once 'db_config.php';

$dbName = $_POST['dbName'] ?? '';
$overwrite = $_POST['overwrite'] ?? false;

$response = ['status' => '', 'message' => ''];

if (!empty($dbName)) {
    $conn = new mysqli($host, $username, $password);
    if ($conn->connect_error) {
        $response['status'] = 'error';
        $response['message'] = 'Connection failed: ' . $conn->connect_error;
        echo json_encode($response);
        exit;
    }

    $dbCheck = $conn->query("SHOW DATABASES LIKE '$dbName'");
    if ($dbCheck->num_rows > 0) {
        if ($overwrite) {
            $conn->query("DROP DATABASE $dbName");
            if ($conn->query("CREATE DATABASE $dbName") === TRUE) {
                $response['status'] = 'success';
                $response['message'] = 'Database replaced successfully!';
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Error replacing database: ' . $conn->error;
            }
        } else {
            $response['status'] = 'exists';
            $response['message'] = 'Database already exists. Do you want to replace it?';
        }
    } else {
        if ($conn->query("CREATE DATABASE $dbName") === TRUE) {
            $response['status'] = 'success';
            $response['message'] = 'Database created successfully!';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Error creating database: ' . $conn->error;
        }
    }
    $conn->close();
} else {
    $response['status'] = 'error';
    $response['message'] = 'Database name is required!';
}

echo json_encode($response);
