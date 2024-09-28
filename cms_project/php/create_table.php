<?php
if (isset($_GET['dbname'])) {
    $dbname = $_GET['dbname'];
} else {
    $dbname = '';  // Default empty value if no database is selected
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Tables</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

    <h2>Create Tables in Database: <?php echo htmlspecialchars($dbname); ?></h2>

    <form method="POST" action="create_table.php">
        <label for="databases">Select Databases:</label>
        <select id="databases" name="databases[]" multiple required>
            <?php
            $conn = new mysqli('localhost', 'root', '');
            $result = $conn->query("SHOW DATABASES");
            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . $row['Database'] . "'>" . $row['Database'] . "</option>";
            }
            $conn->close();
            ?>
        </select><br><br>

        <label for="tableType">Table Type:</label>
        <select id="tableType" name="tableType">
            <option value="category_storage">Category Storage (Categories & Hierarchy)</option>
        </select><br><br>

        <button type="submit" name="createTables">Create Tables</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createTables'])) {
        if (isset($_POST['databases']) && !empty($_POST['databases'])) {
            $databases = $_POST['databases'];
            $tableType = $_POST['tableType'];
            $host = 'localhost';
            $username = 'root';
            $password = '';

            $conn = new mysqli($host, $username, $password);

            function columnExists($conn, $dbname, $table, $column) {
                $result = $conn->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '$dbname' AND TABLE_NAME = '$table' AND COLUMN_NAME = '$column'");
                return ($result && $result->num_rows > 0);
            }

            foreach ($databases as $dbname) {
                if (!$conn->select_db($dbname)) {
                    echo "Failed to select database: $dbname.<br>";
                    continue;
                }

                // 1. Create 'categories' table (if it doesn't exist)
                $sql = "
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    parent_id INT DEFAULT NULL,
    level INT NOT NULL DEFAULT 0,
    description TEXT,
    image_url VARCHAR(255),
    image_description TEXT,
    keywords TEXT,
    UNIQUE(name),
    UNIQUE(slug)
);
";
                if ($conn->query($sql) === TRUE) {
                    echo "Table 'categories' created successfully in $dbname.<br>";
                } else {
                    echo "Error creating table 'categories' in $dbname: " . $conn->error . "<br>";
                }

                // 2. Add new columns to the 'categories' table if they don't exist
                $columnsToAdd = [
                    'description' => 'TEXT',
                    'image_url' => 'VARCHAR(255)',
                    'image_description' => 'TEXT',
                    'url' => 'VARCHAR(255)',
                    'keywords' => 'TEXT'
                ];

                foreach ($columnsToAdd as $column => $type) {
                    if (!columnExists($conn, $dbname, 'categories', $column)) {
                        $sql = "ALTER TABLE categories ADD $column $type";
                        if ($conn->query($sql) === TRUE) {
                            echo "Column '$column' added to 'categories' in $dbname.<br>";
                        } else {
                            echo "Error adding column '$column' in $dbname: " . $conn->error . "<br>";
                        }
                    } else {
                        echo "Column '$column' already exists in 'categories' in $dbname.<br>";
                    }
                }

                // 3. Create 'category_hierarchy' table for multi-parent relationships
                $sql = "
                CREATE TABLE IF NOT EXISTS category_hierarchy (
                    ancestor_id INT NOT NULL,
                    descendant_id INT NOT NULL,
                    depth INT NOT NULL,
                    PRIMARY KEY (ancestor_id, descendant_id),
                    FOREIGN KEY (ancestor_id) REFERENCES categories(id) ON DELETE CASCADE,
                    FOREIGN KEY (descendant_id) REFERENCES categories(id) ON DELETE CASCADE
                );
                ";
                if ($conn->query($sql) === TRUE) {
                    echo "Table 'category_hierarchy' created successfully in $dbname.<br>";
                } else {
                    echo "Error creating table 'category_hierarchy' in $dbname: " . $conn->error . "<br>";
                }

                // 4. Create 'category_closure' table for multi-parent relationships
                $conn->query("DROP TABLE IF EXISTS category_closure"); // Drop table if it exists
                $sql = "
                CREATE TABLE category_closure (
                    ancestor_id INT NOT NULL,
                    descendant_id INT NOT NULL,
                    depth INT NOT NULL,
                    PRIMARY KEY (ancestor_id, descendant_id),
                    FOREIGN KEY (ancestor_id) REFERENCES categories(id) ON DELETE CASCADE,
                    FOREIGN KEY (descendant_id) REFERENCES categories(id) ON DELETE CASCADE
                );
                ";
                if ($conn->query($sql) === TRUE) {
                    echo "Table 'category_closure' created successfully in $dbname.<br>";
                } else {
                    echo "Error creating table 'category_closure' in $dbname: " . $conn->error . "<br>";
                }

                // Display the structure of created tables
                echo "<h3>Table Layout in Database: $dbname</h3>";

                // Fetch table layout for 'categories'
                $sql = "DESCRIBE categories";
                $result = $conn->query($sql);
                if ($result) {
                    echo "<h4>'categories' Table Structure:</h4><ul>";
                    while ($row = $result->fetch_assoc()) {
                        echo "<li>{$row['Field']} - {$row['Type']}</li>";
                    }
                    echo "</ul>";
                }

                // Fetch table layout for 'category_hierarchy'
                $sql = "DESCRIBE category_hierarchy";
                $result = $conn->query($sql);
                if ($result) {
                    echo "<h4>'category_hierarchy' Table Structure:</h4><ul>";
                    while ($row = $result->fetch_assoc()) {
                        echo "<li>{$row['Field']} - {$row['Type']}</li>";
                    }
                    echo "</ul>";
                }

                // Fetch table layout for 'category_closure'
                $sql = "DESCRIBE category_closure";
                $result = $conn->query($sql);
                if ($result) {
                    echo "<h4>'category_closure' Table Structure:</h4><ul>";
                    while ($row = $result->fetch_assoc()) {
                        echo "<li>{$row['Field']} - {$row['Type']}</li>";
                    }
                    echo "</ul>";
                }
            }

            $conn->close();
        } else {
            echo "<p style='color: red;'>Error: Please select at least one database.</p>";
        }
    }
    ?>

</body>
</html>
