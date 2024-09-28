<?php
if (isset($_POST['dbname'], $_POST['host'], $_POST['username'], $_POST['password'], $_POST['categoryName'])) {
    $dbname = $_POST['dbname'];
    $host = $_POST['host'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $categoryName = $_POST['categoryName'];
    $parentID = isset($_POST['parent_id']) && $_POST['parent_id'] !== "" ? $_POST['parent_id'] : null;

    // Connect to MySQL server with the provided credentials and database
    $conn = new mysqli($host, $username, $password, $dbname);

    if ($conn->connect_error) {
        echo "Connection failed: " . $conn->connect_error;
        exit;
    }

    // Begin transaction to ensure data integrity across multiple inserts
    $conn->begin_transaction();

    // Check if the category already exists at the same level
    $sql = "SELECT * FROM categories WHERE name = ? AND (parent_id IS NULL OR parent_id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $categoryName, $parentID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "Category already exists at this level.";
    } else {
        // Determine the level of the new category
        $level = 0;
        if ($parentID) {
            // Fetch the level of the parent category
            $sql = "SELECT level FROM categories WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $parentID);
            $stmt->execute();
            $parentResult = $stmt->get_result();
            if ($parentResult->num_rows > 0) {
                $parentRow = $parentResult->fetch_assoc();
                $level = $parentRow['level'] + 1;
            }
        }

        // Insert the new category with its calculated level
        $sql = "INSERT INTO categories (name, parent_id, level) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $categoryName, $parentID, $level);

        if ($stmt->execute()) {
            $newCategoryId = $conn->insert_id;

            // Insert into the closure table for the new category
            // First, insert the category as an ancestor of itself (depth 0)
            $sql = "INSERT INTO category_closure (ancestor, descendant, depth) VALUES (?, ?, 0)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $newCategoryId, $newCategoryId);
            $stmt->execute();

            // If the category has a parent, insert the paths from the parent and its ancestors
            if ($parentID) {
                $sql = "INSERT INTO category_closure (ancestor, descendant, depth)
                        SELECT ancestor, ?, depth + 1 FROM category_closure WHERE descendant = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $newCategoryId, $parentID);
                $stmt->execute();
            }

            // Commit the transaction
            $conn->commit();
            echo "Category '$categoryName' added successfully!";
        } else {
            $conn->rollback();  // Rollback transaction in case of error
            echo "Error adding category: " . $conn->error;
        }
    }

    $conn->close();
}
?>
