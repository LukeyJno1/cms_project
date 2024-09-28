$(document).ready(function() {
    let mysqlHost = '';
    let mysqlUsername = '';
    let mysqlPassword = '';
    let selectedDB = '';

    // Handle MySQL credentials form submission
    $('#mysqlCredentialsForm').on('submit', function(e) {
        e.preventDefault();
        mysqlHost = $('#host').val();
        mysqlUsername = $('#username').val();
        mysqlPassword = $('#password').val();

        // Test connection and display message
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
                    // Dynamically show the database selection section
                    $('#databaseSelection').show();  // Show the form for database selection
                    loadDatabases();  // Load existing databases
                }
            }
        });
    });

    // Load existing databases
    function loadDatabases() {
        $.ajax({
            url: 'php/list_databases.php',
            type: 'GET',
            success: function(data) {
                $('#dbList').html(data);  // Populate database list
                // Ensure only one submit handler is attached
                $('#selectDatabaseForm').off('submit').on('submit', function(e) {
                    e.preventDefault();
                    selectedDB = $('input[name="database"]:checked').val();
                    if (selectedDB) {
                        loadCategories();  // Load categories for the selected database
                    } else {
                        alert('Please select a database.');
                    }
                });
            }
        });
    }

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
                let categories;
                try {
                    categories = JSON.parse(data);  // Ensure it's JSON
                    $('#categoryList').html(data);  // Update the category list
                    updateParentCategoryDropdown(categories);  // Update the parent category dropdown
                    $('#categoryForm').show();  // Show the category form
                } catch (e) {
                    console.error("Error parsing categories: ", e);
                    $('#categoryList').html("Error loading categories");
                }
            }
        });
    }

    // Update the parent category dropdown
    function updateParentCategoryDropdown(categories) {
        let categoryOptions = '<option value="">None (Top Level)</option>';

        if (Array.isArray(categories)) {
            categories.forEach(function(category) {
                categoryOptions += `<option value="${category.id}">${category.name}</option>`;
            });
        } else {
            console.error("Categories data is not an array");
        }

        $('#parentCategory').html(categoryOptions);  // Update the dropdown
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
                $('#categoryMessage').text(response);  // Show success or error message
                loadCategories();  // Reload the category list
            }
        });
    });
});
