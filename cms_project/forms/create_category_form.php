<?php
// Database connection
$conn = new mysqli('localhost', 'root', '');

// Fetch all databases with the correct tables and structure
function getValidDatabases($conn) {
    $validDatabases = [];
    $result = $conn->query("SHOW DATABASES");

    while ($row = $result->fetch_assoc()) {
        $dbname = $row['Database'];
        $conn->select_db($dbname);

        // Check if both categories and category_hierarchy tables exist
        $tablesResult = $conn->query("SHOW TABLES LIKE 'categories'");
        $hierarchyResult = $conn->query("SHOW TABLES LIKE 'category_hierarchy'");

        if ($tablesResult->num_rows > 0 && $hierarchyResult->num_rows > 0) {
            $validDatabases[] = $dbname;
        }
    }

    return $validDatabases;
}

$databases = getValidDatabases($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Category</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

<h2>Create a New Category</h2>

<!-- Form for Creating a Category -->
<form method="POST" id="createCategoryForm" enctype="multipart/form-data">
    <!-- Database Selection -->
    <label for="database">Select Database:</label>
    <select name="database" id="database" required>
        <?php foreach ($databases as $dbname): ?>
            <option value="<?php echo htmlspecialchars($dbname); ?>"><?php echo htmlspecialchars($dbname); ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <!-- Category Name Input -->
    <label for="categoryName">New Category:</label>
    <input type="text" name="categoryName" id="categoryName" required><br><br>

    <!-- Parent Category Selection -->
    <label for="parentCategories">Select Parent Categories:</label>
    <select name="parentCategories[]" id="parentCategories" multiple>
        <option value="none">No Parent (Top Level)</option>
        <!-- Categories will be loaded dynamically via JavaScript -->
    </select><br><br>

    <!-- Category Description -->
    <label for="categoryDescription">Category Description:</label>
    <textarea name="categoryDescription" id="categoryDescription" rows="4" cols="50"></textarea><br><br>

    <!-- Image Upload -->
    <label for="categoryImage">Category Image:</label>
    <input type="file" name="categoryImage" id="categoryImage" accept="image/*"><br><br>

    <!-- Image Description -->
    <label for="imageDescription">Image Description (for screen readers):</label>
    <input type="text" name="imageDescription" id="imageDescription"><br><br>

    <!-- Keywords -->
    <label for="keywords">Keywords (comma-separated):</label>
    <input type="text" name="keywords" id="keywords"><br><br>

    <button type="submit" id="submitCategory">Submit Category</button>
</form>

<!-- Result message area -->
<div id="resultMessage" style="display:none;"></div>

<!-- Load jQuery via CDN -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<script>
$(document).ready(function() {
    // On database change, load categories
    $('#database').on('change', function() {
        const dbName = $(this).val();
        
        if (dbName) {
            // Fetch parent categories dynamically
            $.ajax({
                url: 'get_category_tree.php',
                type: 'POST',
                data: { database: dbName },
                dataType: 'json',
                success: function(categoryTree) {
                    let parentDropdown = $('#parentCategories');
                    parentDropdown.html('<option value="none">No Parent (Top Level)</option>');

                    if (Array.isArray(categoryTree)) {
                        function appendCategories(dropdown, categories, level = 0) {
                            Object.values(categories).forEach(category => {
                                const prefix = '─ '.repeat(level);
                                dropdown.append($('<option>', {
                                    value: category.id,
                                    text: prefix + category.name
                                }));
                                if (category.children && Object.keys(category.children).length > 0) {
                                    appendCategories(dropdown, category.children, level + 1);
                                }
                            });
                        }

                        appendCategories(parentDropdown, categoryTree);
                    } else {
                        alert('Error loading categories. Please check the console.');
                        console.error('Invalid response format:', categoryTree);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Failed to load parent categories.');
                    console.error('Error:', error);
                }
            });
        }
    });

    // Load categories on initial page load
    $('#database').trigger('change');
});

$('#createCategoryForm').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            url: 'process_category_creation.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Response:', response);
                if (response.status === 'success') {
                    $('#resultMessage').html('Category created successfully!').css('color', 'green').show();
                    // Optionally reset the form here
                    $('#createCategoryForm')[0].reset();
                    // Reload the parent categories
                    $('#database').trigger('change');
                } else {
                    $('#resultMessage').html('Error: ' + response.message).css('color', 'red').show();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                $('#resultMessage').html('An error occurred while creating the category.').css('color', 'red').show();
            },
            cache: false,
            contentType: false,
            processData: false
        });
    });

</script>

</body>
</html>
