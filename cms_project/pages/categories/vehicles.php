<?php
    require_once('../../php/db_config.php');
    $database = "plant";
    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    $categoryName = "Vehicles";
    $stmt = $conn->prepare('SELECT id FROM categories WHERE name = ?');
    $stmt->bind_param('s', $categoryName);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    $categoryId = $category['id'];

    // Fetch all ancestors (for multiple parent scenarios)
    $ancestorsSql = 'SELECT c.id, c.name, c.slug, ch.depth
                      FROM categories c
                      JOIN category_hierarchy ch ON c.id = ch.ancestor_id
                      WHERE ch.descendant_id = ? AND ch.depth > 0
                      ORDER BY ch.depth DESC';
    $ancestorsStmt = $conn->prepare($ancestorsSql);
    $ancestorsStmt->bind_param('i', $categoryId);
    $ancestorsStmt->execute();
    $ancestorsResult = $ancestorsStmt->get_result();
    $ancestors = $ancestorsResult->fetch_all(MYSQLI_ASSOC);

    // Group ancestors by depth to handle multiple parents
    $ancestorPaths = [];
    foreach ($ancestors as $ancestor) {
        $ancestorPaths[$ancestor['depth']][] = $ancestor;
    }
    krsort($ancestorPaths); // Sort by depth, deepest first

    // Fetch immediate children
    $childrenSql = 'SELECT c.id, c.name, c.slug FROM categories c
                     JOIN category_hierarchy ch ON c.id = ch.descendant_id
                     WHERE ch.ancestor_id = ? AND ch.depth = 1';
    $childrenStmt = $conn->prepare($childrenSql);
    $childrenStmt->bind_param('i', $categoryId);
    $childrenStmt->execute();
    $childrenResult = $childrenStmt->get_result();
    $children = $childrenResult->fetch_all(MYSQLI_ASSOC);

    $conn->close();
    ?>

    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title><?php echo htmlspecialchars($categoryName); ?></title>
        <meta name='description' content='<?php echo htmlspecialchars(""); ?>'>
        <meta name='keywords' content='<?php echo htmlspecialchars(""); ?>'>
        <link rel='stylesheet' href='../../css/styles.css'>
    </head>
    <body>
        <h1><?php echo htmlspecialchars($categoryName); ?></h1>
        
        <?php if (!empty($ancestorPaths)): ?>
            <nav>
                <h2>Breadcrumbs:</h2>
                <?php foreach ($ancestorPaths as $depth => $pathAncestors): ?>
                    <ul>
                        <?php foreach ($pathAncestors as $ancestor): ?>
                            <li><a href='<?php echo htmlspecialchars($ancestor['slug']); ?>.php'><?php echo htmlspecialchars($ancestor['name']); ?></a></li>
                        <?php endforeach; ?>
                        <li><?php echo htmlspecialchars($categoryName); ?></li>
                    </ul>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <?php if (!empty("")): ?>
            <img src='../../<?php echo htmlspecialchars(""); ?>' alt='<?php echo htmlspecialchars(""); ?>'>
        <?php endif; ?>

        <p><?php echo htmlspecialchars(""); ?></p>

        <?php if (!empty($children)): ?>
            <h2>Subcategories</h2>
            <ul>
                <?php foreach ($children as $child): ?>
                    <li><a href='<?php echo htmlspecialchars($child['slug']); ?>.php'><?php echo htmlspecialchars($child['name']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <script src='../../js/script.js'></script>
    </body>
    </html>