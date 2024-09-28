$(document).ready(function() {
    let layoutId = 0;
    let selectedElement = null;
    let undoStack = [];
    let breakpoints = [];
    let currentElement = null;
    let isDragging = false;
    let isResizing = false;
    let resizeDirection = '';
    let startX, startY, startLeft, startTop, startWidth, startHeight;

    function debug(message) {
        $('#debug-output').append(`<p>${message}</p>`);
    }

    debug("Script loaded");

    function createContainer(type, parent) {
        layoutId++;
        const name = type.charAt(0).toUpperCase() + type.slice(1) + layoutId;
        debug(`Creating ${type}: ${name}`);
        const $element = $(`<div id="layout-${layoutId}" class="${type}" data-name="${name}">${name}</div>`);
        
        if (type === 'column' && !parent.hasClass('row')) {
            const $row = createContainer('row', parent);
            $row.append($element);
        } else {
            parent.append($element);
        }

        $element.on('click', function(e) {
            e.stopPropagation();
            selectElement($(this));
        });

        addResizeHandles($element[0]);
        addToUndoStack();
        return $element;
    }

    function updateElementName($element, name) {
        $element.attr('data-name', name);
        $element.text(name);
        debug(`Updated element name: ${name}`);
    }

    function selectElement($element) {
        debug(`Selecting element: ${$element.attr('data-name')}`);
        $('.selected').removeClass('selected');
        $element.addClass('selected');
        selectedElement = $element;
        
        $('#element-name').text($element.attr('data-name'));
        $('#element-custom-name').val($element.attr('data-name'));
        $('#width').val($element.css('width'));
        $('#height').val($element.css('height'));
        $('#padding').val($element.css('padding'));
        $('#margin').val($element.css('margin'));
        $('#border-width').val($element.css('border-width'));
        $('#border-style').val($element.css('border-style'));
        $('#border-color').val(rgb2hex($element.css('border-color')));
        $('#bg-color').val(rgb2hex($element.css('background-color')));
        $('#bg-image').val($element.css('background-image').replace(/url\(['"]?(.+?)['"]?\)/i, '$1'));
        $('#bg-repeat').val($element.css('background-repeat'));
        $('#horizontal-align').val($element.css('text-align'));
        $('#vertical-align').val($element.css('vertical-align'));
        $('#border-radius').val($element.css('border-radius'));

        $('#element-editor').show();
        debug("Element editor shown");
    }

    function rgb2hex(rgb) {
        if (rgb.startsWith('rgb')) {
            rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
            return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
        }
        return rgb;
    }

    function hex(x) {
        return ("0" + parseInt(x).toString(16)).slice(-2);
    }

    function addToUndoStack() {
        undoStack.push($('#layout-preview').html());
        if (undoStack.length > 20) {
            undoStack.shift();
        }
    }

    $('#add-container').on('click', function() {
        debug("Add container clicked");
        const $container = createContainer('container', $('#layout-preview'));
        selectElement($container);
    });

    $('#add-row').on('click', function() {
        debug("Add row clicked");
        if (selectedElement) {
            const $row = createContainer('row', selectedElement);
            selectElement($row);
        } else {
            debug("No element selected for adding row");
        }
    });

    $('#add-column').on('click', function() {
        debug("Add column clicked");
        if (selectedElement) {
            const $column = createContainer('column', selectedElement);
            selectElement($column);
        } else {
            debug("No element selected for adding column");
        }
    });

    $('#undo').on('click', function() {
        if (undoStack.length > 1) {
            undoStack.pop(); // Remove current state
            const previousState = undoStack.pop();
            $('#layout-preview').html(previousState);
            reattachEvents();
            debug("Undo performed");
        } else {
            debug("Nothing to undo");
        }
    });

    $('#delete-element').on('click', function() {
        if (selectedElement) {
            selectedElement.remove();
            selectedElement = null;
            $('#element-editor').hide();
            addToUndoStack();
            debug("Element deleted");
        } else {
            debug("No element selected for deletion");
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Delete' && selectedElement) {
            selectedElement.remove();
            selectedElement = null;
            $('#element-editor').hide();
            addToUndoStack();
            debug("Element deleted with Delete key");
        }
    });

    $('#apply-styles').on('click', function() {
        debug("Apply styles clicked");
        if (selectedElement) {
            const customName = $('#element-custom-name').val();
            updateElementName(selectedElement, customName);

            selectedElement.css({
                width: $('#width').val() + $('#width-unit').val(),
                height: $('#height').val() + $('#height-unit').val(),
                padding: $('#padding').val(),
                margin: $('#margin').val(),
                borderWidth: $('#border-width').val(),
                borderStyle: $('#border-style').val(),
                borderColor: $('#border-color').val(),
                backgroundColor: $('#bg-color').val(),
                backgroundImage: $('#bg-gradient').val() || ($('#bg-image').val() ? `url(${$('#bg-image').val()})` : ''),
                backgroundRepeat: $('#bg-repeat').val(),
                textAlign: $('#horizontal-align').val(),
                verticalAlign: $('#vertical-align').val(),
                borderRadius: $('#border-radius').val()
            });

            // Apply responsive styles
            breakpoints.forEach(bp => {
                const responsiveStyles = {};
                if ($('#width').val()) responsiveStyles.width = $('#width').val() + $('#width-unit').val();
                if ($('#height').val()) responsiveStyles.height = $('#height').val() + $('#height-unit').val();
                // Add other properties as needed

                selectedElement.data(`responsive-${bp}`, $.param(responsiveStyles).replace(/&/g, '; ').replace(/=/g, ': '));
            });

            updateCSSOutput();
            addToUndoStack();
            debug("Styles applied");
        } else {
            debug("No element selected for applying styles");
        }
    });

    $('#element-custom-name').on('change', function() {
        if (selectedElement) {
            updateElementName(selectedElement, $(this).val());
        }
    });

    function updateCSSOutput() {
        let css = '';
        $('#layout-preview').find('.container, .row, .column').each(function() {
            const id = $(this).attr('id');
            const styles = $(this).attr('style');
            if (styles) {
                css += `#${id} { ${styles} }\n`;
            }
        });

        breakpoints.forEach((bp, index) => {
            css += `\n@media (max-width: ${bp}px) {\n`;
            $('#layout-preview').find('.container, .row, .column').each(function() {
                const id = $(this).attr('id');
                const responsiveStyles = $(this).data(`responsive-${bp}`);
                if (responsiveStyles) {
                    css += `  #${id} { ${responsiveStyles} }\n`;
                }
            });
            css += `}\n`;
        });

        $('#css-output').text(css);
        updatePreviews();
        debug("CSS output updated");
    }

    $('#save-layout').on('click', function() {
        debug("Save layout clicked");
        const layout = $('#layout-preview').html();
        const css = $('#css-output').text();
        const layoutName = prompt("Enter a name for this layout:", "My Layout");
        
        if (layoutName) {
            $.ajax({
                url: '../php/save_layout.php',
                method: 'POST',
                data: { layout_name: layoutName, layout: layout, css: css },
                success: function(response) {
                    alert('Layout saved successfully!');
                    debug("Layout saved");
                },
                error: function() {
                    alert('Error saving layout.');
                    debug("Error saving layout");
                }
            });
        }
    });

    $('#load-layout').on('click', function() {
        debug("Load layout clicked");
        $.ajax({
            url: '../php/load_layout.php',
            method: 'GET',
            success: function(response) {
                const data = JSON.parse(response);
                $('#layout-preview').html(data.layout_html);
                $('#css-output').text(data.layout_css);
                reattachEvents();
                debug("Layout loaded");
            },
            error: function() {
                alert('Error loading layout.');
                debug("Error loading layout");
            }
        });
    });

    function reattachEvents() {
        $('#layout-preview').find('.container, .row, .column').each(function() {
            $(this).on('click', function(e) {
                e.stopPropagation();
                selectElement($(this));
            });
        });
    }

    // Prevent hiding the element editor
    $('#layout-preview').on('click', function(e) {
        if ($(e.target).is('#layout-preview')) {
            selectedElement = null;
            $('.selected').removeClass('selected');
            $('#element-editor').hide();
            debug("Element editor hidden");
        }
    });

    function initializeResizablePreview() {
        let startX, startWidth;

        $('#preview-resizer').mousedown(function(e) {
            startX = e.clientX;
            startWidth = $('#layout-preview').width();
            $(document).mousemove(function(e) {
                let newWidth = startWidth + (e.clientX - startX);
                $('#layout-preview').css('width', newWidth + 'px');
                updatePreviewWidth(newWidth);
            });
        });

        $(document).mouseup(function() {
            $(document).off('mousemove');
        });
    }

    function updatePreviewWidth(width) {
        $('#layout-preview').css('width', width + 'px');
        $('.preview-width').text(width + 'px');
    }

    function addBreakpoint() {
        const width = prompt("Enter breakpoint width in pixels:", "768");
        if (width) {
            breakpoints.push(parseInt(width));
            breakpoints.sort((a, b) => a - b);
            updateBreakpointList();
            updateCSSOutput();
        }
    }

    function updateBreakpointList() {
        const $list = $('#breakpoint-list');
        $list.empty();
        breakpoints.forEach((bp, index) => {
            $list.append(`
                <div>
                    <input type="number" value="${bp}" data-index="${index}">
                    <button class="remove-breakpoint" data-index="${index}">Remove</button>
                </div>
            `);
        });
    }

    function updatePreviews() {
        $('#responsive-previews iframe').each(function() {
            this.contentWindow.postMessage('reload', '*');
        });
    }

    $('#add-breakpoint').on('click', addBreakpoint);

    $('#breakpoint-list').on('change', 'input', function() {
        const index = $(this).data('index');
        breakpoints[index] = parseInt($(this).val());
        breakpoints.sort((a, b) => a - b);
        updateBreakpointList();
        updateCSSOutput();
    });

    $('#breakpoint-list').on('click', '.remove-breakpoint', function() {
        const index = $(this).data('index');
        breakpoints.splice(index, 1);
        updateBreakpointList();
        updateCSSOutput();
    });

    function startDragging(e) {
        if ($(e.target).hasClass('layout-element')) {
            isDragging = true;
            currentElement = e.target;
            startX = e.clientX;
            startY = e.clientY;
            startLeft = currentElement.offsetLeft;
            startTop = currentElement.offsetTop;
            $(currentElement).css('cursor', 'move');
        }
    }

    function drag(e) {
        if (!isDragging) return;
        
        let newX = startLeft + e.clientX - startX;
        let newY = startTop + e.clientY - startY;
        
        // Snap to other elements
        $('.layout-element').each(function() {
            if (this !== currentElement) {
                if (Math.abs(newX - this.offsetLeft) < 10) newX = this.offsetLeft;
                if (Math.abs(newY - this.offsetTop) < 10) newY = this.offsetTop;
            }
        });
        
        $(currentElement).css({
            left: newX + 'px',
            top: newY + 'px'
        });
    }

    function stopDragging() {
        if (isDragging) {
            isDragging = false;
            $(currentElement).css('cursor', 'default');
        }
    }

    function startResizing(e) {
        if ($(e.target).hasClass('resize-handle')) {
            isResizing = true;
            currentElement = e.target.parentElement;
            resizeDirection = $(e.target).data('direction');
            startX = e.clientX;
            startY = e.clientY;
            startWidth = parseInt($(currentElement).css('width'), 10);
            startHeight = parseInt($(currentElement).css('height'), 10);
            $(document).on('mousemove', resize);
            $(document).on('mouseup', stopResizing);
        }
    }

    function resize(e) {
        if (!isResizing) return;
        
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        
        if (resizeDirection.includes('e')) {
            $(currentElement).css('width', (startWidth + dx) + 'px');
        }
        if (resizeDirection.includes('s')) {
            $(currentElement).css('height', (startHeight + dy) + 'px');
        }
        if (resizeDirection.includes('w')) {
            $(currentElement).css({
                width: (startWidth - dx) + 'px',
                left: (startLeft + dx) + 'px'
            });
        }
        if (resizeDirection.includes('n')) {
            $(currentElement).css({
                height: (startHeight - dy) + 'px',
                top: (startTop + dy) + 'px'
            });
        }
        
        updateSizeDisplay(currentElement);
    }

    function stopResizing() {
        isResizing = false;
        $(document).off('mousemove', resize);
        $(document).off('mouseup', stopResizing
