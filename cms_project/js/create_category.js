$(document).ready(function() {
    // Function to dynamically load categories into the "Select Parent Category" dropdown
    function loadCategories(selectedDB) {
        // Cache busting by adding a timestamp to the URL
        let url = `load_categories.php?dbname=${selectedDB}&_=${new Date().getTime()}`;
        
        $.ajax({
            url: url,  // Using the cache-busting URL
            method: 'GET',
            success: function(data) {
                $('#parentCategory').html(data); // Populate the dropdown
            },
            error: function(xhr, status, error) {
                console.error("Error loading categories:", error);
            }
        });
    }

    // Trigger when the database selection changes
    $('#databaseSelect').on('change', function() {
        const selectedDB = $(this).val();
        loadCategories(selectedDB);  // Load categories for the selected database
    });

    // Initially load categories when the page is ready
    const initialDB = $('#databaseSelect').val();
    if (initialDB) {
        loadCategories(initialDB);  // Load categories for the initially selected database
    }

    // Handle form submission for category creation
    $('#createCategoryForm').on('submit', function(e) {
        e.preventDefault();
        const categoryName = $('#newCategory').val();
        const selectedParent = $('#parentCategory').val();
        const selectedDB = $('#databaseSelect').val();

        $.ajax({
            url: 'process_category_creation.php',
            method: 'POST',
            data: {
                dbname: selectedDB,
                name: categoryName,
                parent_id: selectedParent
            },
            success: function(response) {
                alert(response.message); // Notify user of success or error
                if (response.status === 'success') {
                    // Refresh the categories without refreshing the page
                    loadCategories(selectedDB);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error creating category:", error);
            }
        });
    });
});
