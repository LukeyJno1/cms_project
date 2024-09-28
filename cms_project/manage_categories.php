<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/manage_categories.js"></script>
</head>
<body>
    <?php include('includes/header.php'); ?>

    <h2>Manage Categories</h2>

    <!-- MySQL credentials form -->
    <form id="mysqlCredentialsForm">
        <label for="host">MySQL Host:</label>
        <input type="text" id="host" name="host" value="localhost" required><br>

        <label for="username">MySQL Username:</label>
        <input type="text" id="username" name="username" required><br>

        <label for="password">MySQL Password:</label>
        <input type="password" id="password" name="password"><br>

        <button type="submit">Save Credentials</button>
        <div id="dbConnectionMessage"></div> <!-- Message for connection status -->
    </form>

    <hr>

    <!-- Form for selecting a database -->
    <div id="databaseSelection" style="display:none;">
        <h3>Select a Database</h3>
        <form id="selectDatabaseForm">
            <div id="dbList">
                <!-- Dynamically populated list of databases -->
            </div>
            <button type="submit">Select Database</button>
        </form>
    </div>

    <hr>

    <!-- Form for managing categories -->
    <form id="categoryForm" style="display: none;">
        <h3>Add a New Category</h3>

        <label for="parentCategory">Select Parent Category (optional):</label>
        <select id="parentCategory" name="parent_id">
            <option value="">None (Top Level)</option>
            <!-- Dynamically populated -->
        </select><br>

        <label for="categoryName">New Category Name:</label>
        <input type="text" id="categoryName" name="categoryName" required><br>

        <button type="submit">Add Category</button>
        <div id="categoryMessage"></div> <!-- Message for category creation -->
    </form>

    <hr>

    <h3>Current Categories</h3>
    <div id="categoryList">
        <!-- Current categories loaded here via AJAX -->
    </div>

    <?php include('includes/footer.php'); ?>
</body>
</html>
