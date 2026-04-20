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
        let dt = new DataTransfer(); // Persistent memory!

        input.addEventListener('change', function() {
            // Append newly selected files to our existing memory
            for(let file of this.files) { dt.items.add(file); }
            input.files = dt.files; // Sync the HTML input with our memory
            renderPreview();
        });

        // Event Delegation for the Remove Button
        previewDiv.addEventListener('click', function(e) {
            if(e.target.classList.contains('remove-file-btn')) {
                e.preventDefault();
                let indexToRemove = parseInt(e.target.getAttribute('data-index'));
                let newDt = new DataTransfer();
                
                Array.from(input.files).forEach((f, i) => { 
                    if (i !== indexToRemove) newDt.items.add(f); 
                });
                
                input.files = newDt.files;
                dt = newDt; // Update memory
                renderPreview();
            }
        });

        function renderPreview() {
            previewDiv.innerHTML = '';
            Array.from(input.files).forEach((file, index) => {
                let fileRow = document.createElement('div');
                fileRow.className = 'file-preview-item';
                fileRow.innerHTML = `<span>${file.name}</span> <i class="fa-solid fa-times remove-file-btn" data-index="${index}"></i>`;
                previewDiv.appendChild(fileRow);
            });
        }
    });
});