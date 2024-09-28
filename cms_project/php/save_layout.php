<?php
require_once 'layout_db_config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$layout_name = $_POST['layout_name'] ?? 'Untitled Layout';
$layout = $_POST['layout'];
$css = $_POST['css'];

$sql = "INSERT INTO layouts (layout_name, layout_html, layout_css) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $layout_name, $layout, $css);

if ($stmt->execute()) {
    echo "Layout saved successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$stmt->close();
$conn->close();
?>