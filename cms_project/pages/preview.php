<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layout Preview</title>
    <style id="preview-styles"></style>
</head>
<body>
    <div id="preview-content"></div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function loadPreview() {
            $.ajax({
                url: '../php/load_layout.php',
                method: 'GET',
                success: function(response) {
                    const data = JSON.parse(response);
                    $('#preview-content').html(data.layout_html);
                    $('#preview-styles').text(data.layout_css);
                },
                error: function() {
                    console.error('Error loading layout for preview.');
                }
            });
        }

        $(document).ready(loadPreview);
        window.addEventListener('message', function(event) {
            if (event.data === 'reload') {
                loadPreview();
            }
        }, false);
    </script>
</body>
</html>
