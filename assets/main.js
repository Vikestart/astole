document.addEventListener('DOMContentLoaded', () => {
    // Prevent Double-Binding: If we already initialized the menu, stop here!
    if (window.navMenuInitialized) return;
    window.navMenuInitialized = true;

    const mobileToggle = document.querySelector('.mobile-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (mobileToggle && navLinks) {
        // Toggle menu on button click
        mobileToggle.addEventListener('click', (e) => {
            e.preventDefault(); 
            e.stopPropagation(); // <-- THIS IS THE FIX! Stops the click from instantly triggering the document listener below.
            
            navLinks.classList.toggle('nav-active');
            updateMenuIcon();
        });

        // Close menu when clicking ANYWHERE outside of it
        document.addEventListener('click', (e) => {
            if (navLinks.classList.contains('nav-active')) {
                // If the click was NOT inside the menu links
                if (!navLinks.contains(e.target)) {
                    navLinks.classList.remove('nav-active');
                    updateMenuIcon();
                }
            }
        });

        // Helper function to swap the icon
        function updateMenuIcon() {
            if (navLinks.classList.contains('nav-active')) {
                mobileToggle.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            } else {
                mobileToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
            }
        }
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
document.addEventListener('DOMContentLoaded', () => {
    // Mobile Submenu Accordion Logic (Double-Tap to Navigate)
    const dropdownTriggers = document.querySelectorAll('.nav-dropdown-trigger');
    
    dropdownTriggers.forEach(trigger => {
        const link = trigger.querySelector('.nav-item');
        const arrow = trigger.querySelector('.submenu-toggle');
        const parentDropdown = trigger.closest('.nav-dropdown');

        // 1. Logic for clicking the actual text link
        if (link) {
            link.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    // First click: If closed, prevent navigation and expand it instead
                    if (!parentDropdown.classList.contains('mobile-expanded')) {
                        e.preventDefault();
                        parentDropdown.classList.add('mobile-expanded');
                    }
                    // Second click: If already expanded, do nothing (it will naturally follow the href!)
                }
            });
        }

        // 2. Logic for clicking the arrow icon specifically (always toggles)
        if (arrow) {
            arrow.addEventListener('click', function(e) {
                e.preventDefault();
                if (window.innerWidth <= 768) {
                    parentDropdown.classList.toggle('mobile-expanded');
                }
            });
        }
    });
});