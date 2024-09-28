<?php include '../includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced CSS Layout Creator</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/layout_creator.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <h1>Advanced CSS Layout Creator</h1>
    <p><a href="layout_db_setup.php">Setup Database</a></p>

    <div id="layout-creator">
        <div id="controls">
            <button id="add-container">Add Container</button>
            <button id="add-row">Add Row</button>
            <button id="add-column">Add Column</button>
            <button id="undo">Undo</button>
            <button id="delete-element"><i class="fas fa-trash"></i> Delete Element</button>
            <button id="save-layout">Save Layout</button>
            <button id="load-layout">Load Layout</button>
            <button id="add-breakpoint">Add Breakpoint</button>
        </div>

        <div id="layout-preview-container">
            <div id="layout-preview"></div>
            <div id="preview-resizer"></div>
        </div>

        <div id="element-editor">
            <h3>Edit Element: <span id="element-name"></span></h3>
            <label for="element-custom-name">Custom Name:</label>
            <input type="text" id="element-custom-name" placeholder="Custom Name">
            
            <label for="width">Width:</label>
            <input type="text" id="width" placeholder="Width">
            <select id="width-unit">
                <option value="px">px</option>
                <option value="%">%</option>
                <option value="auto">auto</option>
            </select>
            
            <label for="height">Height:</label>
            <input type="text" id="height" placeholder="Height">
            <select id="height-unit">
                <option value="px">px</option>
                <option value="%">%</option>
                <option value="auto">auto</option>
            </select>
            
            <label for="padding">Padding:</label>
            <input type="text" id="padding" placeholder="Padding">
            
            <label for="margin">Margin:</label>
            <input type="text" id="margin" placeholder="Margin">
            
            <label for="border-width">Border Width:</label>
            <input type="text" id="border-width" placeholder="Border Width">
            
            <label for="border-style">Border Style:</label>
            <select id="border-style">
                <option value="none">None</option>
                <option value="solid">Solid</option>
                <option value="dashed">Dashed</option>
                <option value="dotted">Dotted</option>
            </select>
            
            <label for="border-color">Border Color:</label>
            <input type="color" id="border-color">
            
            <label for="bg-color">Background Color:</label>
            <input type="color" id="bg-color" title="Background Color">
            
            <label for="bg-gradient">Background Gradient:</label>
            <input type="text" id="bg-gradient" placeholder="e.g., linear-gradient(to right, #ff0000, #00ff00)">
            
            <label for="bg-image">Background Image URL:</label>
            <input type="text" id="bg-image" placeholder="Background Image URL">
            
            <label for="bg-repeat">Background Repeat:</label>
            <select id="bg-repeat">
                <option value="repeat">Repeat</option>
                <option value="no-repeat">No Repeat</option>
                <option value="repeat-x">Repeat X</option>
                <option value="repeat-y">Repeat Y</option>
            </select>
            
            <label for="horizontal-align">Horizontal Alignment:</label>
            <select id="horizontal-align">
                <option value="">Default</option>
                <option value="left">Left</option>
                <option value="center">Center</option>
                <option value="right">Right</option>
            </select>
            
            <label for="vertical-align">Vertical Alignment:</label>
            <select id="vertical-align">
                <option value="">Default</option>
                <option value="top">Top</option>
                <option value="middle">Middle</option>
                <option value="bottom">Bottom</option>
            </select>
            
            <label for="border-radius">Border Radius:</label>
            <input type="text" id="border-radius" placeholder="Border Radius">
            
            <button id="apply-styles">Apply Styles</button>

            <div id="breakpoint-editor">
                <h4>Breakpoints</h4>
                <div id="breakpoint-list"></div>
            </div>
        </div>
    </div>

    <div id="responsive-previews">
        <h3>Responsive Previews</h3>
        <div class="preview-container" id="mobile-preview">
            <h4>Mobile (320px)</h4>
            <iframe src="preview.php" width="320" height="480"></iframe>
        </div>
        <div class="preview-container" id="tablet-preview">
            <h4>Tablet (768px)</h4>
            <iframe src="preview.php" width="768" height="1024"></iframe>
        </div>
        <div class="preview-container" id="desktop-preview">
            <h4>Desktop (1024px)</h4>
            <iframe src="preview.php" width="1024" height="768"></iframe>
        </div>
    </div>

    <div id="css-output"></div>
    <div id="debug-output"></div>

    <script src="../js/layout_creator.js"></script>
    <script>
    function saveLayout() {
        var layoutName = document.getElementById('layoutName').value;
        var layoutData = JSON.stringify(containers);
        
        $.ajax({
            url: 'save_layout.php',
            method: 'POST',
            data: { name: layoutName, layout: layoutData },
            success: function(response) {
                alert('Layout saved successfully!');
            },
            error: function() {
                alert('Error saving layout');
            }
        });
    }
    </script>
</body>
</html>

<?php include '../includes/footer.php'; ?>
