document.addEventListener('DOMContentLoaded', () => {
    // Prevent Double-Binding: If we already initialized the menu, stop here!
    if (window.navMenuInitialized) return;
    window.navMenuInitialized = true;

    const mobileToggle = document.querySelector('.mobile-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (mobileToggle && navLinks) {
        mobileToggle.addEventListener('click', (e) => {
            e.preventDefault(); // Stop any default button behavior
            
            // Toggle the visibility class
            navLinks.classList.toggle('nav-active');
            
            // Check if the menu is now active
            if (navLinks.classList.contains('nav-active')) {
                // Menu is open, inject the X mark
                mobileToggle.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            } else {
                // Menu is closed, inject the hamburger bars
                mobileToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
            }
        });
    }
});
document.addEventListener('DOMContentLoaded', () => {
    // Multi-file attachment preview & deletion logic
    document.querySelectorAll('.multi-file-input').forEach(input => {
        const previewDiv = input.nextElementSibling;
        let dt = new DataTransfer();

        input.addEventListener('change', function() {
            dt = new DataTransfer(); // Reset memory on new selection
            for(let file of this.files) { dt.items.add(file); }
            input.files = dt.files;
            renderPreview();
        });

        function renderPreview() {
            previewDiv.innerHTML = '';
            Array.from(input.files).forEach((file, index) => {
                let fileRow = document.createElement('div');
                fileRow.style.cssText = "display: inline-flex; align-items: center; background: #f1f5f9; padding: 6px 12px; border-radius: 4px; font-size: 13px; border: 1px solid #e2e8f0; width: max-content; margin-top: 5px; color: #475569;";
                fileRow.innerHTML = `<span style="margin-right:10px;">${file.name}</span> <i class="fa-solid fa-times remove-file-btn" style="cursor: pointer; color: #dc2626; padding: 2px;" data-index="${index}"></i>`;
                previewDiv.appendChild(fileRow);
            });

            // Re-bind the delete buttons
            previewDiv.querySelectorAll('.remove-file-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    let indexToRemove = parseInt(this.getAttribute('data-index'));
                    let newDt = new DataTransfer();
                    Array.from(input.files).forEach((f, i) => { 
                        if (i !== indexToRemove) newDt.items.add(f); 
                    });
                    input.files = newDt.files;
                    dt = newDt;
                    renderPreview();
                });
            });
        }
    });
});