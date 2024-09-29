<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection details
$host = 'localhost';
$dbname = 'your_database_name';
$username = 'your_username';
$password = 'your_password';

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get the raw POST data
        $json = file_get_contents('php://input');
        
        // Decode the JSON data
        $layout = json_decode($json, true);

        if ($layout === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data');
        }

        // Start a transaction
        $pdo->beginTransaction();

        // Clear existing layout data
        $pdo->exec("DELETE FROM containers");
        $pdo->exec("DELETE FROM columns");

        // Prepare the SQL statement for inserting containers
        $containerStmt = $pdo->prepare("INSERT INTO containers (id, custom_styles) VALUES (:id, :custom_styles)");

        // Prepare the SQL statement for inserting columns
        $columnStmt = $pdo->prepare("INSERT INTO columns (container_id, id, content, custom_styles) VALUES (:container_id, :id, :content, :custom_styles)");

        foreach ($layout as $container) {
            // Insert container
            $containerStmt->execute([
                'id' => $container['id'],
                'custom_styles' => json_encode($container['custom_styles'] ?? [])
            ]);
            $containerId = $pdo->lastInsertId();

            foreach ($container['columns'] as $column) {
                // Insert column
                $columnStmt->execute([
                    'container_id' => $containerId,
                    'id' => $column['id'],
                    'content' => $column['content'],
                    'custom_styles' => json_encode($column['custom_styles'] ?? [])
                ]);
            }
        }

        // Commit the transaction
        $pdo->commit();

        // Send a success response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Layout saved successfully']);
    } else {
        // If the request method is not POST, display the HTML form
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Layout Creator</title>
            <link rel="stylesheet" href="styles.css">
        </head>
        <body>
            <h1>Layout Creator</h1>
            <div id="toolbar">
                <button id="add-container-btn">Add Container</button>
                <button id="save-layout-btn">Save Layout</button>
                <button id="preview-btn">Preview</button>
                <button id="undo-btn">Undo</button>
                <button id="redo-btn">Redo</button>
                <button id="export-btn">Export</button>
                <button id="import-btn">Import</button>
                <input type="file" id="import-input" style="display: none;">
            </div>
            <div id="layout-container"></div>
            <div id="responsive-tools">
                <button id="responsive-preview-btn">Responsive Preview</button>
                <select id="device-selector">
                    <option value="desktop">Desktop</option>
                    <option value="tablet">Tablet</option>
                    <option value="mobile">Mobile</option>
                </select>
            </div>
            <div id="custom-css-tools">
                <button id="custom-css-btn">Custom CSS</button>
            </div>
            <div id="template-tools">
                <select id="template-select">
                    <option value="template1">Template 1</option>
                    <option value="template2">Template 2</option>
                </select>
                <button id="apply-template-btn">Apply Template</button>
            </div>
            <div id="color-tools">
                <input type="color" id="color-picker">
                <button id="apply-color-btn">Apply Color</button>
            </div>
            <div id="font-tools">
                <select id="font-selector">
                    <option value="Arial">Arial</option>
                    <option value="Helvetica">Helvetica</option>
                    <option value="Times New Roman">Times New Roman</option>
                </select>
                <button id="apply-font-btn">Apply Font</button>
            </div>
            <div id="grid-tools">
                <input type="number" id="grid-rows" placeholder="Rows">
                <input type="number" id="grid-columns" placeholder="Columns">
                <button id="apply-grid-btn">Apply Grid</button>
            </div>
            <div id="breakpoint-tools">
                <input type="number" id="breakpoint-width" placeholder="Width">
                <input type="text" id="breakpoint-name" placeholder="Name">
                <button id="add-breakpoint-btn">Add Breakpoint</button>
                <div id="breakpoints-list"></div>
            </div>
            <div id="versioning-tools">
                <button id="save-version-btn">Save Version</button>
                <div id="versions-list"></div>
            </div>
            <div id="accessibility-tools">
                <button id="run-accessibility-check-btn">Run Accessibility Check</button>
                <div id="accessibility-results"></div>
            </div>
            <div id="optimization-tools">
                <button id="optimize-layout-btn">Optimize Layout</button>
            </div>
            <div id="collaboration-tools">
                <button id="share-layout-btn">Share Layout</button>
            </div>

            <!-- Modals -->
            <div id="preview-modal" class="modal">
                <div class="modal-content">
                    <span id="close-preview-btn" class="close">&times;</span>
                    <div id="preview-content"></div>
                </div>
            </div>
            <div id="responsive-preview-modal" class="modal">
                <div class="modal-content">
                    <span id="close-responsive-preview-btn" class="close">&times;</span>
                    <div id="responsive-preview-content"></div>
                </div>
            </div>
            <div id="custom-css-modal" class="modal">
                <div class="modal-content">
                    <span id="close-custom-css-btn" class="close">&times;</span>
                    <textarea id="custom-css-textarea"></textarea>
                    <button id="apply-custom-css-btn">Apply Custom CSS</button>
                </div>
            </div>

            <script src="layout_creator.js"></script>
        </body>
        </html>
        <?php
    }
} catch (Exception $e) {
    // Rollback the transaction if an error occurred
    if (isset($pdo)) {
        $pdo->rollBack();
    }

    // Send an error response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
