$(document).ready(function() {
    let mysqlHost = '';
    let mysqlUsername = '';
    let mysqlPassword = '';
    let selectedDB = '';

    // Ensure the database creation form and table link are hidden initially
    $('#dbCreationForm').hide();
    $('#dbSelection').hide();
    $('#createTableLink').hide(); // Hide the link initially

    // Handle MySQL credentials form submission
    $('#mysqlCredentialsForm').on('submit', function(e) {
        e.preventDefault();
        mysqlHost = $('#host').val();
        mysqlUsername = $('#username').val();
        mysqlPassword = $('#password').val();

        // Test MySQL connection and display message
        $.ajax({
            url: 'php/test_connection.php',
            type: 'POST',
            data: {
                host: mysqlHost,
                username: mysqlUsername,
                password: mysqlPassword
            },
            success: function(response) {
                $('#dbConnectionMessage').text(response);

                if (response === 'Connection successful') {
                    // Highlight message in green and minimize the setup area after 2 seconds
                    $('#dbConnectionMessage').css('color', 'green');
                    setTimeout(function() {
                        $('#dbSetupArea').slideUp();  // Hide the setup area
                        $('#dbSetupOptions').slideDown();  // Show the database options area
                    }, 2000);
                } else {
                    $('#dbConnectionMessage').text("Connection failed: " + response).css('color', 'red');
                }
            },
            error: function(xhr, status, error) {
                $('#dbConnectionMessage').text('Error: ' + error).css('color', 'red');
            }
        });
    });

    // Handle MySQL database creation form submission
    $('#dbCreationForm').on('submit', function(e) {
        e.preventDefault();

        const dbName = $('#dbName').val();

        if (dbName === '') {
            alert('Please enter a database name.');
            return;
        }

        // AJAX request to create the database
        $.ajax({
            url: 'php/create_db.php',
            type: 'POST',
            data: { dbName: dbName },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'exists') {
                    if (confirm(response.message)) {
                        $.ajax({
                            url: 'php/create_db.php',
                            type: 'POST',
                            data: { dbName: dbName, overwrite: true },
                            dataType: 'json',
                            success: function(overwriteResponse) {
                                alert(overwriteResponse.message);
                                $('#successMessage').text(overwriteResponse.message).show();
                                $('#setupNote').hide(); // Hide the note about creating/selecting databases
                                showCreateTableLink(dbName);  // Show link to create tables
                            }
                        });
                    }
                } else {
                    alert(response.message);
                    $('#successMessage').text(response.message).show();
                    $('#setupNote').hide(); // Hide the note about creating/selecting databases
                    showCreateTableLink(dbName);  // Show link to create tables
                }
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
    });

    // Function to show the link to create tables
    function showCreateTableLink(dbName) {
        $('#createTableLink').show();
        $('#createTableLinkAnchor').attr('href', 'php/create_table.php?dbname=' + dbName);  // Adjust path here
    }

    // Load the databases for selection
    function loadDatabases() {
        $.ajax({
            url: 'php/list_databases.php',
            type: 'POST',
            data: {
                host: mysqlHost,
                username: mysqlUsername,
                password: mysqlPassword
            },
            success: function(data) {
                $('#dbList').html(data);  // Populate the database list dynamically
            }
        });
    }

    // Create categories table in the selected database
    function createCategoriesTable(database) {
        $.ajax({
            url: 'php/create_table.php',
            type: 'POST',
            data: {
                dbname: database,
                host: mysqlHost,
                username: mysqlUsername,
                password: mysqlPassword
            },
            success: function(response) {
                $('#dbSelectionMessage').text(response);  // Show success or failure message
                $('#categoryForm').show();  // Show the category form once the tables are created
                loadCategories();           // Load categories for the selected database
            }
        });
    }

    // Handle category form submission
    $('#categoryForm').on('submit', function(e) {
        e.preventDefault();
        const categoryName = $('#categoryName').val();
        const parentID = $('#parentCategory').val();

        $.ajax({
            url: 'php/add_category.php',
            type: 'POST',
            data: {
                dbname: selectedDB,
                host: mysqlHost,
                username: mysqlUsername,
                password: mysqlPassword,
                categoryName: categoryName,
                parent_id: parentID
            },
            success: function(response) {
                $('#categoryMessage').text(response);  // Display success or error message
                loadCategories();  // Reload the category list after adding a new category
            },
            error: function() {
                $('#categoryMessage').text("Error adding category.");
            }
        });
    });

    // Load categories for the selected database
    function loadCategories() {
        $.ajax({
            url: 'php/list_categories.php',
            type: 'POST',
            data: {
                dbname: selectedDB,
                host: mysqlHost,
                username: mysqlUsername,
                password: mysqlPassword
            },
            success: function(data) {
                $('#categoryList').html(data);  // Update the category list dynamically
                updateParentCategoryDropdown(data);  // Update the parent category dropdown
            }
        });
    }

    // Update the parent category dropdown dynamically
    function updateParentCategoryDropdown(data) {
        let categoryOptions = '<option value="">None (Top Level)</option>';
        
        try {
            const categories = JSON.parse(data);  // Parse the JSON data
            categories.forEach(function(category) {
                categoryOptions += `<option value="${category.id}">${category.name}</option>`;
            });
        } catch (e) {
            console.error("Error parsing categories: ", e);  // Error handling
        }
        
        $('#parentCategory').html(categoryOptions);  // Populate the dropdown with options
    }

    // Load the databases for selection (preserved from original logic)
    $('#selectDbButton').on('click', function() {
        selectedDB = $('input[name="database"]:checked').val();
        if (selectedDB) {
            $('#createTableLink').show(); // Show the link to create tables
            $('#createTableLinkAnchor').attr('href', 'php/create_table.php?dbname=' + selectedDB);
        } else {
            alert('Please select a database.');
        }
    });
});
