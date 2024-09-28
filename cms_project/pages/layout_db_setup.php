<?php include '../includes/header.php'; ?>

<h2>Layout Creator Database Setup</h2>

<form action="../php/create_layout_db.php" method="post">
    <label for="db_host">Database Host:</label>
    <input type="text" id="db_host" name="db_host" value="localhost" required><br><br>

    <label for="db_user">Database Username:</label>
    <input type="text" id="db_user" name="db_user" required><br><br>

    <label for="db_pass">Database Password:</label>
    <input type="password" id="db_pass" name="db_pass"><br><br>

    <label for="db_name">Database Name:</label>
    <input type="text" id="db_name" name="db_name" value="layout_creator_db" required><br><br>

    <input type="submit" value="Create Database and Table">
</form>

<?php include '../includes/footer.php'; ?>