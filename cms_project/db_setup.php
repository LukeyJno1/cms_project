<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js"></script>
</head>
<body>
    <?php include('includes/header.php'); ?>

    <h2>CMS Management System</h2>
    <a href="index.php">Home</a> |
    <a href="db_setup.php">Database Setup</a>

    <!-- Database setup area -->
    <div id="dbSetupArea">
        <h3>Database Setup</h3>

        <!-- MySQL credentials form -->
        <form id="mysqlCredentialsForm">
            <label for="host">MySQL Host:</label>
            <input type="text" id="host" name="host" value="localhost" required><br>

            <label for="username">MySQL Username:</label>
            <input type="text" id="username" name="username" required><br>

            <label for="password">MySQL Password:</label>
            <input type="password" id="password" name="password"><br>

            <button type="submit">Save Credentials</button>
        </form>

        <!-- Connection status message -->
        <div id="dbConnectionMessage"></div>
    </div>

    <!-- Database setup options (initially hidden) -->
    <div id="dbSetupOptions" style="display: none;">
        <h3>Database Setup Options</h3>

        <!-- Message about creating a new or existing database -->
        <p id="setupNote">Note: Please create a new database or select an existing one.</p>

        <!-- Form to create a new database -->
        <form id="dbCreationForm">
            <label for="dbName">Create A New Database?:</label>
            <input type="text" id="dbName" name="dbName" required>
            <button type="submit">Create Database</button>
        </form>
    </div>

    <!-- Link to go to the table creation page (initially hidden) -->
    <div id="createTableLink" style="display: none;">
        <a id="createTableLinkAnchor" href="#">Go to Create Tables</a>
        <div id="successMessage" style="display: none; color: green;"></div>
    </div>

    <?php include('includes/footer.php'); ?>
</body>
</html>
