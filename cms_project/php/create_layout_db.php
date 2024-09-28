<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $db_name = $_POST['db_name'];

    // Create connection
    $conn = new mysqli($db_host, $db_user, $db_pass);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS $db_name";
    if ($conn->query($sql) === TRUE) {
        echo "Database created successfully<br>";
    } else {
        echo "Error creating database: " . $conn->error . "<br>";
    }

    // Select the database
    $conn->select_db($db_name);

   // Create layouts table
$sql = "CREATE TABLE IF NOT EXISTS layouts (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    layout_name VARCHAR(255) NOT NULL,
    layout_html TEXT NOT NULL,
    layout_css TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

    if ($conn->query($sql) === TRUE) {
        echo "Table 'layouts' created successfully<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }

    // Create config file
    $config_content = "<?php
    define('DB_HOST', '$db_host');
    define('DB_USER', '$db_user');
    define('DB_PASS', '$db_pass');
    define('DB_NAME', '$db_name');
    ?>";

    $config_file = __DIR__ . '/layout_db_config.php';
    if (file_put_contents($config_file, $config_content) !== false) {
        echo "Configuration file created successfully<br>";
    } else {
        echo "Error creating configuration file<br>";
    }

    $conn->close();

    echo "<br><a href='../pages/layout_creator.php'>Go to Layout Creator</a>";
} else {
    echo "Invalid request method";
}
?>