<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = $_POST['database'];

    if (empty($database)) {
        echo json_encode(["error" => "Database name is required."]);
        exit;
    }

    $conn = new mysqli('localhost', 'root', '', $database);
    if ($conn->connect_error) {
        echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
        exit;
    }

    try {
        $stmt = $conn->prepare("
            SELECT c.id, c.name, c.level, ch.ancestor_id as parent_id
            FROM categories c
            LEFT JOIN category_hierarchy ch ON c.id = ch.descendant_id AND ch.depth = 1
            ORDER BY COALESCE(ch.ancestor_id, 0), c.name
        ");
        $stmt->execute();
        $result = $stmt->get_result();

        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'level' => $row['level'],
                'parent_id' => $row['parent_id']
            ];
        }

        $stmt->close();

        function buildTree(array &$elements, $parentId = null) {
            $branch = array();
            foreach ($elements as &$element) {
                if ($element['parent_id'] == $parentId) {
                    $children = buildTree($elements, $element['id']);
                    if ($children) {
                        $element['children'] = $children;
                    }
                    $branch[$element['id']] = $element;
                    unset($element);
                }
            }
            return $branch;
        }

        $tree = buildTree($categories);
        echo json_encode(array_values($tree));
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    } finally {
        $conn->close();
    }
} else {
    echo json_encode(["error" => "Invalid request method."]);
}
?>
