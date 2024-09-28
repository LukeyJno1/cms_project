<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: application/json');

require_once('../php/db_config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = $_POST['database'];
    $categoryName = $_POST['categoryName'];
    $parentCategories = isset($_POST['parentCategories']) ? $_POST['parentCategories'] : [];
    $categoryDescription = $_POST['categoryDescription'];
    $imageDescription = $_POST['imageDescription'];
    $keywords = $_POST['keywords'];

    if (empty($database) || empty($categoryName)) {
        echo json_encode(["status" => "error", "message" => "Error: Database and category name are required."]);
        exit;
    }

    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
        exit;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn->begin_transaction();

        // Check if category already exists
        $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt->bind_param("s", $categoryName);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            throw new Exception("The category '$categoryName' already exists.");
        }
        $stmt->close();

        // Handle image upload
        $imageUrl = '';
if (isset($_FILES['categoryImage']) && $_FILES['categoryImage']['error'] == 0) {
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/cms_project/uploads/categories/';
    $imageFileName = time() . '_' . basename($_FILES['categoryImage']['name']);
    $targetFilePath = $uploadDir . $imageFileName;
    
    if (move_uploaded_file($_FILES['categoryImage']['tmp_name'], $targetFilePath)) {
        $imageUrl = 'uploads/categories/' . $imageFileName;
    } else {
        throw new Exception("Failed to upload image.");
    }
}

        // Generate URL-friendly slug for the category
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $categoryName)));
        
        // Determine parent_id and level
$parent_id = null;
$level = 0;
if (!empty($parentCategories) && $parentCategories[0] !== 'none') {
    $parent_id = $parentCategories[0]; // Use the first parent as the main parent
    $levelQuery = "SELECT level FROM categories WHERE id = ?";
    $levelStmt = $conn->prepare($levelQuery);
    $levelStmt->bind_param("i", $parent_id);
    $levelStmt->execute();
    $levelResult = $levelStmt->get_result();
    $parentLevel = $levelResult->fetch_assoc()['level'];
    $level = $parentLevel + 1;
    $levelStmt->close();
}
        // Insert new category
        $level = 0; // Default level for top-level categories
        $insertStmt = $conn->prepare("INSERT INTO categories (name, slug, level, description, image_url, image_description, url, keywords) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("ssisisss", $categoryName, $slug, $level, $categoryDescription, $imageUrl, $imageDescription, $slug, $keywords);

        if (!$insertStmt->execute()) {
            throw new Exception("Failed to insert new category.");
        }

        $newCategoryId = $insertStmt->insert_id;
        $insertStmt->close();

        // Function to check for cycles
        function wouldCreateCycle($conn, $childId, $parentId) {
            $stmt = $conn->prepare("
                WITH RECURSIVE category_path (id, path) AS (
                    SELECT id, CAST(id AS CHAR(200))
                    FROM categories
                    WHERE id = ?
                    UNION ALL
                    SELECT c.id, CONCAT(cp.path, ',', c.id)
                    FROM category_path cp
                    JOIN category_hierarchy ch ON cp.id = ch.descendant_id
                    JOIN categories c ON ch.ancestor_id = c.id
                    WHERE FIND_IN_SET(c.id, cp.path) = 0
                )
                SELECT 1 FROM category_path WHERE id = ?
            ");
            $stmt->bind_param("ii", $parentId, $childId);
            $stmt->execute();
            $result = $stmt->get_result();
            $wouldCycle = $result->num_rows > 0;
            $stmt->close();
            return $wouldCycle;
        }

        // Process parent categories
foreach ($parentCategories as $parentId) {
    if ($parentId != 'none') {
        if (wouldCreateCycle($conn, $newCategoryId, $parentId)) {
            throw new Exception("Cannot add parent category: it would create a cycle.");
        }

        // Update the level of the new category
        $parentLevelStmt = $conn->prepare("SELECT level FROM categories WHERE id = ?");
        $parentLevelStmt->bind_param("i", $parentId);
        $parentLevelStmt->execute();
        $parentLevelResult = $parentLevelStmt->get_result();
        $parentLevel = $parentLevelResult->fetch_assoc()['level'];
        $newLevel = $parentLevel + 1;
        $parentLevelStmt->close();

        // Update the level of the new category if it's higher than the current level
        if ($newLevel > $level) {
            $updateLevelStmt = $conn->prepare("UPDATE categories SET level = ? WHERE id = ?");
            $updateLevelStmt->bind_param("ii", $newLevel, $newCategoryId);
            $updateLevelStmt->execute();
            $updateLevelStmt->close();
            $level = $newLevel;
        }

        // Insert into category_hierarchy
        $hierarchyStmt = $conn->prepare("INSERT IGNORE INTO category_hierarchy (ancestor_id, descendant_id, depth) VALUES (?, ?, 1)");
        $hierarchyStmt->bind_param("ii", $parentId, $newCategoryId);
        $hierarchyStmt->execute();
        $hierarchyStmt->close();

        // Insert ancestors into category_hierarchy
        $ancestorHierarchyStmt = $conn->prepare("
            INSERT IGNORE INTO category_hierarchy (ancestor_id, descendant_id, depth)
            SELECT ancestor_id, ?, depth + 1
            FROM category_hierarchy
            WHERE descendant_id = ?
        ");
        $ancestorHierarchyStmt->bind_param("ii", $newCategoryId, $parentId);
        $ancestorHierarchyStmt->execute();
        $ancestorHierarchyStmt->close();

        // Insert into category_closure
        $closureStmt = $conn->prepare("
            INSERT IGNORE INTO category_closure (ancestor_id, descendant_id, depth)
            SELECT c.ancestor_id, ?, c.depth + 1
            FROM category_closure c
            WHERE c.descendant_id = ?
            UNION ALL SELECT ?, ?, 0
        ");
        $closureStmt->bind_param("iiii", $newCategoryId, $parentId, $newCategoryId, $newCategoryId);
        $closureStmt->execute();
        $closureStmt->close();
    }
}
        // Insert the new category as its own ancestor in both tables if it's a top-level category
        if (empty($parentCategories) || $parentCategories[0] == 'none') {
            $selfHierarchyStmt = $conn->prepare("INSERT IGNORE INTO category_hierarchy (ancestor_id, descendant_id, depth) VALUES (?, ?, 0)");
            $selfHierarchyStmt->bind_param("ii", $newCategoryId, $newCategoryId);
            $selfHierarchyStmt->execute();
            $selfHierarchyStmt->close();

            $selfClosureStmt = $conn->prepare("INSERT IGNORE INTO category_closure (ancestor_id, descendant_id, depth) VALUES (?, ?, 0)");
            $selfClosureStmt->bind_param("ii", $newCategoryId, $newCategoryId);
            $selfClosureStmt->execute();
            $selfClosureStmt->close();
        }

        // Generate category page
$pageContent = generateCategoryPage($categoryName, $categoryDescription, $imageUrl, $imageDescription, $keywords, $database);
$pageFileName = "../pages/categories/{$slug}.php";
if (file_put_contents($pageFileName, $pageContent) === false) {
    throw new Exception("Failed to create category page file.");
}


        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Category '$categoryName' created successfully."]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Error: " . $e->getMessage()]);
    } finally {
        $conn->close();
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
}

function generateCategoryPage($name, $description, $imageUrl, $imageDescription, $keywords, $database) {
    $content = "<?php
    require_once('../../php/db_config.php');
    \$database = " . json_encode($database) . ";
    \$conn = new mysqli(\$host, \$username, \$password, \$database);
    if (\$conn->connect_error) {
        die('Connection failed: ' . \$conn->connect_error);
    }

    \$categoryName = " . json_encode($name) . ";
    \$stmt = \$conn->prepare('SELECT id FROM categories WHERE name = ?');
    \$stmt->bind_param('s', \$categoryName);
    \$stmt->execute();
    \$result = \$stmt->get_result();
    \$category = \$result->fetch_assoc();
    \$categoryId = \$category['id'];

    // Fetch all ancestors (for multiple parent scenarios)
    \$ancestorsSql = 'SELECT c.id, c.name, c.slug, ch.depth
                      FROM categories c
                      JOIN category_hierarchy ch ON c.id = ch.ancestor_id
                      WHERE ch.descendant_id = ? AND ch.depth > 0
                      ORDER BY ch.depth DESC';
    \$ancestorsStmt = \$conn->prepare(\$ancestorsSql);
    \$ancestorsStmt->bind_param('i', \$categoryId);
    \$ancestorsStmt->execute();
    \$ancestorsResult = \$ancestorsStmt->get_result();
    \$ancestors = \$ancestorsResult->fetch_all(MYSQLI_ASSOC);

    // Group ancestors by depth to handle multiple parents
    \$ancestorPaths = [];
    foreach (\$ancestors as \$ancestor) {
        \$ancestorPaths[\$ancestor['depth']][] = \$ancestor;
    }
    krsort(\$ancestorPaths); // Sort by depth, deepest first

    // Fetch immediate children
    \$childrenSql = 'SELECT c.id, c.name, c.slug FROM categories c
                     JOIN category_hierarchy ch ON c.id = ch.descendant_id
                     WHERE ch.ancestor_id = ? AND ch.depth = 1';
    \$childrenStmt = \$conn->prepare(\$childrenSql);
    \$childrenStmt->bind_param('i', \$categoryId);
    \$childrenStmt->execute();
    \$childrenResult = \$childrenStmt->get_result();
    \$children = \$childrenResult->fetch_all(MYSQLI_ASSOC);

    \$conn->close();
    ?>

    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title><?php echo htmlspecialchars(\$categoryName); ?></title>
        <meta name='description' content='<?php echo htmlspecialchars(" . json_encode($description) . "); ?>'>
        <meta name='keywords' content='<?php echo htmlspecialchars(" . json_encode($keywords) . "); ?>'>
        <link rel='stylesheet' href='../../css/styles.css'>
    </head>
    <body>
        <h1><?php echo htmlspecialchars(\$categoryName); ?></h1>
        
        <?php if (!empty(\$ancestorPaths)): ?>
            <nav>
                <h2>Breadcrumbs:</h2>
                <?php foreach (\$ancestorPaths as \$depth => \$pathAncestors): ?>
                    <ul>
                        <?php foreach (\$pathAncestors as \$ancestor): ?>
                            <li><a href='<?php echo htmlspecialchars(\$ancestor['slug']); ?>.php'><?php echo htmlspecialchars(\$ancestor['name']); ?></a></li>
                        <?php endforeach; ?>
                        <li><?php echo htmlspecialchars(\$categoryName); ?></li>
                    </ul>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <?php if (!empty(" . json_encode($imageUrl) . ")): ?>
            <img src='../../<?php echo htmlspecialchars(" . json_encode($imageUrl) . "); ?>' alt='<?php echo htmlspecialchars(" . json_encode($imageDescription) . "); ?>'>
        <?php endif; ?>

        <p><?php echo htmlspecialchars(" . json_encode($description) . "); ?></p>

        <?php if (!empty(\$children)): ?>
            <h2>Subcategories</h2>
            <ul>
                <?php foreach (\$children as \$child): ?>
                    <li><a href='<?php echo htmlspecialchars(\$child['slug']); ?>.php'><?php echo htmlspecialchars(\$child['name']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <script src='../../js/script.js'></script>
    </body>
    </html>";

    return $content;
}
?>