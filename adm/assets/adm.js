// Utility function to write a cookie
function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        let date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    // We set path=/ so the cookie is readable across the entire admin panel
    document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}

document.addEventListener('DOMContentLoaded', () => {

    // --- 1. Message Box Auto-Fader ---
    const msgBoxes = document.querySelectorAll('.msgbox');
    msgBoxes.forEach(box => {
        setTimeout(() => {
            box.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            box.style.opacity = '0';
            box.style.transform = 'translateY(-10px)';
            setTimeout(() => box.remove(), 500); 
        }, 4500);
    });

    // --- 2. Password Generator ---
    const passwordField = document.getElementById('user_pass_field');
    const generatePassBtn = document.getElementById('generate_pass_btn');

    if (generatePassBtn && passwordField) {
        generatePassBtn.addEventListener('click', function(e) {
            e.preventDefault(); 
            const lower = "abcdefghijklmnopqrstuvwxyz";
            const upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            const numbers = "0123456789";
            const symbols = "!@#$%^&*()_+~|}{[]:;?><,./-=";
            const allChars = lower + upper + numbers + symbols;

            let password = "";
            password += lower.charAt(Math.floor(Math.random() * lower.length));
            password += upper.charAt(Math.floor(Math.random() * upper.length));
            password += numbers.charAt(Math.floor(Math.random() * numbers.length));
            password += symbols.charAt(Math.floor(Math.random() * symbols.length));

            for (let i = 4; i < 15; i++) {
                password += allChars.charAt(Math.floor(Math.random() * allChars.length));
            }

            password = password.split('').sort(() => 0.5 - Math.random()).join('');
            passwordField.value = password;
            passwordField.type = 'text'; 
            setTimeout(() => { passwordField.type = 'password'; }, 10000);
        });
    }

    // --- 3. Sidebar Collapse Logic (Now using Cookies!) ---
    const adminLayout = document.getElementById('admin-layout');
    const desktopToggleBtn = document.querySelector('.desktop-menu-btn');
    
    // PHP handles the initial load state. JS only handles the button clicks now.
    if(desktopToggleBtn && adminLayout) {
        desktopToggleBtn.addEventListener('click', () => {
            adminLayout.classList.toggle('collapsed');
            
            // Save the state to a cookie for 365 days
            const isCollapsed = adminLayout.classList.contains('collapsed');
            setCookie('sidebar_collapsed', isCollapsed ? 'true' : 'false', 365);
        });
    }

    // --- 4. Mobile Menu Toggle & Overlay ---
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const overlay = document.getElementById('mobile-overlay');
    const sidebar = document.querySelector('.admin-sidebar'); // <-- Added missing variable!
    
    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('mobile-open');
            if(overlay) overlay.classList.toggle('active');
        });
    }
    
    // Close menu when clicking the darkened overlay
    if (overlay && sidebar) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        });
    }

    // --- 5. Secure Deletion Confirmations ---
    const deleteForms = document.querySelectorAll('.form-delete');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to delete this? This action cannot be undone.')) {
                e.preventDefault(); 
            }
        });
    });

    // --- 6. Form Change Tracking (Enable/Disable Submit Buttons) ---
    const trackForms = document.querySelectorAll('.track-changes');
    trackForms.forEach(form => {
        const submitBtn = form.querySelector('button[type="submit"]');
        if(!submitBtn) return;
        
        submitBtn.disabled = true; // Grey out initially
        
        // Grab all inputs to track their original state
        const inputs = form.querySelectorAll('input:not([type="hidden"]), textarea, select');
        const initialValues = {};
        inputs.forEach(input => initialValues[input.name] = input.value);
        
        form.addEventListener('input', () => {
            let hasChanged = false;
            inputs.forEach(input => {
                if (input.value !== initialValues[input.name]) hasChanged = true;
            });
            submitBtn.disabled = !hasChanged;
        });
    });

// --- 7. Profile Password Strength & Dynamic Fields ---
    const newPassField = document.getElementById('profile_newpass');
    const confirmPassGroup = document.getElementById('confirm-password-group');
    const confirmPassField = document.getElementById('profile_confirmpass');
    const strengthContainer = document.getElementById('password-strength-container');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    const profileGenBtn = document.getElementById('profile_generate_btn');

    // Helper to safely swap FontAwesome icons for checklist
    function updateReq(elementId, isMet) {
        const li = document.getElementById(elementId);
        if (!li) return;
        const iconBox = li.querySelector('.icon-container');
        
        if (isMet) {
            li.classList.replace('req-unmet', 'req-met');
            iconBox.innerHTML = '<i class="fa-solid fa-check"></i>';
        } else {
            li.classList.replace('req-met', 'req-unmet');
            iconBox.innerHTML = '<i class="fa-solid fa-times"></i>';
        }
    }

    if (newPassField) {
        newPassField.addEventListener('input', function() {
            const val = this.value;
            if (val.length > 0) {
                confirmPassGroup.style.display = 'block';
                strengthContainer.style.display = 'block';
                confirmPassField.required = true;
                
                // Calculate Exact Requirements
                const hasLength = val.length >= 8;
                const hasUpper = /[A-Z]/.test(val);
                const hasLower = /[a-z]/.test(val);
                const hasNumber = /\d/.test(val);
                const hasSymbol = /[\W_]/.test(val);
                
                // Update UI Checklist
                updateReq('req-length', hasLength);
                updateReq('req-upper', hasUpper);
                updateReq('req-lower', hasLower);
                updateReq('req-number', hasNumber);
                updateReq('req-symbol', hasSymbol);

                // Calculate Bar Strength
                let strength = 0;
                if (hasLength) strength++;
                if (hasUpper) strength++;
                if (hasLower) strength++;
                if (hasNumber) strength++;
                if (hasSymbol) strength++;
                
                strengthBar.style.width = (strength / 5) * 100 + '%';
                
                if (strength <= 2) {
                    strengthBar.style.background = 'var(--danger)';
                    strengthText.textContent = 'Weak Password';
                    strengthText.style.color = 'var(--danger)';
                } else if (strength <= 4) {
                    strengthBar.style.background = '#f59e0b';
                    strengthText.textContent = 'Moderate Password';
                    strengthText.style.color = '#f59e0b';
                } else {
                    strengthBar.style.background = 'var(--success)';
                    strengthText.textContent = 'Strong & Secure!';
                    strengthText.style.color = 'var(--success)';
                }
            } else {
                confirmPassGroup.style.display = 'none';
                strengthContainer.style.display = 'none';
                confirmPassField.required = false;
                confirmPassField.value = '';
            }
        });
    }

    // Profile Password Generator
    if (profileGenBtn && newPassField) {
        profileGenBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const lower = "abcdefghijklmnopqrstuvwxyz";
            const upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            const numbers = "0123456789";
            const symbols = "!@#$%^&*()_+~|}{[]:;?><,./-=";
            const allChars = lower + upper + numbers + symbols;

            let password = "";
            password += lower.charAt(Math.floor(Math.random() * lower.length));
            password += upper.charAt(Math.floor(Math.random() * upper.length));
            password += numbers.charAt(Math.floor(Math.random() * numbers.length));
            password += symbols.charAt(Math.floor(Math.random() * symbols.length));

            for (let i = 4; i < 24; i++) {
                password += allChars.charAt(Math.floor(Math.random() * allChars.length));
            }
            password = password.split('').sort(() => 0.5 - Math.random()).join('');
            
            newPassField.value = password;
            newPassField.type = 'text';
            
            confirmPassGroup.style.display = 'block';
            strengthContainer.style.display = 'block';
            confirmPassField.required = true;
            confirmPassField.value = password;
            confirmPassField.type = 'text';
            
            // Trigger input event to update checklist
            newPassField.dispatchEvent(new Event('input', { bubbles: true }));
            
            setTimeout(() => { 
                newPassField.type = 'password'; 
                confirmPassField.type = 'password';
            }, 10000);
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
document.addEventListener('DOMContentLoaded', () => {
    // Settings Tabs Logic
    const tabBtns = document.querySelectorAll('.admin-tab-btn');
    const tabPanes = document.querySelectorAll('.admin-tab-pane');

    if (tabBtns.length > 0) {
        tabBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Remove active classes from everything
                tabBtns.forEach(b => b.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));
                
                // Add active class to clicked button and target pane
                btn.classList.add('active');
                const targetId = btn.getAttribute('data-tab');
                document.getElementById(targetId).classList.add('active');
            });
        });
    }
});
// --- Settings Change Detection Logic ---
const settingsForm = document.querySelector('form[action="process-settings.php"]');
const saveBtn = document.getElementById('save-settings-btn');

if (settingsForm && saveBtn) {
    // Capture the exact state of the form on page load
    let initialData = new FormData(settingsForm);

    // Listen for ANY change in the form (typing, clicking, toggling)
    settingsForm.addEventListener('input', checkFormChanges);
    settingsForm.addEventListener('change', checkFormChanges);

    function checkFormChanges() {
        let currentData = new FormData(settingsForm);
        let hasChanged = false;
        
        // 1. Check if any initial value has changed
        for (let [key, value] of initialData.entries()) {
            if (currentData.get(key) !== value) {
                hasChanged = true;
                break;
            }
        }
        
        // 2. Check if any NEW values were added (handles checkboxes turning on)
        if (!hasChanged) {
            for (let [key, value] of currentData.entries()) {
                if (initialData.get(key) !== value) {
                    hasChanged = true;
                    break;
                }
            }
        }

        // Toggle the disabled attribute based on the result
        if (hasChanged) {
            saveBtn.removeAttribute('disabled');
        } else {
            saveBtn.setAttribute('disabled', 'disabled');
        }
    }
}
/* =========================================
   Menu Builder AJAX & Drag-and-Drop System
========================================= */

const sortableContainer = document.getElementById('sortable-menu');
const saveOrderBtn = document.getElementById('ajax-save-order-btn');
const msgBox = document.getElementById('ajax-msgbox');
const csrfInput = document.getElementById('menu-csrf');
const activeMenuInput = document.getElementById('active-menu-id');

// Dynamic Radio Listeners
function setupRadioToggles(formId, radioName, urlWrapId, pageWrapId) {
    const form = document.getElementById(formId);
    if(!form) return;
    const radios = form.querySelectorAll(`input[name="${radioName}"]`);
    radios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            if(e.target.checked) {
                document.getElementById(urlWrapId).style.display = e.target.value === 'url' ? 'block' : 'none';
                document.getElementById(pageWrapId).style.display = e.target.value === 'page' ? 'block' : 'none';
            }
        });
    });
}
setupRadioToggles('ajax-add-menu-form', 'link_type', 'wrapper-url', 'wrapper-page');
setupRadioToggles('ajax-edit-item-form', 'link_type', 'edit-wrapper-url', 'edit-wrapper-page');

function showMenuMsg(type, msg) {
    msgBox.className = `msgbox msgbox-${type}`;
    msgBox.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'times-circle'}"></i> ${msg}`;
    msgBox.style.display = 'block';
    setTimeout(() => { msgBox.style.display = 'none'; }, 4000);
}

function loadMenuAjax() {
    if (!sortableContainer) return;
    const formData = new FormData();
    formData.append('action', 'get_html'); 
    formData.append('menu_id', activeMenuInput ? activeMenuInput.value : 1); 
    formData.append('csrf_token', csrfInput.value);

    fetch('ajax-menu.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            sortableContainer.innerHTML = data.html;
            bindDragEvents();
            document.querySelectorAll('.dynamic-parent-select').forEach(select => {
                const currentVal = select.value;
                select.innerHTML = data.parent_opts;
                if(currentVal) select.value = currentVal; 
            });
        }
    });
}

// Add Item
const addMenuForm = document.getElementById('ajax-add-menu-form');
if (addMenuForm) {
    addMenuForm.addEventListener('submit', (e) => {
        e.preventDefault();
        fetch('ajax-menu.php', { method: 'POST', body: new FormData(addMenuForm) })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                loadMenuAjax();
                addMenuForm.reset();
                document.getElementById('add_type_url').checked = true;
                document.getElementById('add_type_url').dispatchEvent(new Event('change'));
                showMenuMsg('success', 'Link added successfully.');
            } else { showMenuMsg('error', data.message); }
        });
    });
}

// Edit Item
const editMenuForm = document.getElementById('ajax-edit-item-form');
if (editMenuForm) {
    editMenuForm.addEventListener('submit', (e) => {
        e.preventDefault();
        fetch('ajax-menu.php', { method: 'POST', body: new FormData(editMenuForm) })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                loadMenuAjax();
                document.getElementById('edit-item-modal').style.display = 'none';
                showMenuMsg('success', 'Link updated successfully.');
            } else { showMenuMsg('error', data.message); }
        });
    });
}

// Event Delegation (Edit/Delete)
if (sortableContainer) {
    sortableContainer.addEventListener('click', (e) => {
        const delBtn = e.target.closest('.ajax-delete-btn');
        if (delBtn && confirm('Are you sure you want to delete this link?')) {
            const formData = new FormData();
            formData.append('action', 'delete_item');
            formData.append('item_id', delBtn.dataset.id);
            formData.append('csrf_token', csrfInput.value);

            fetch('ajax-menu.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => { if (data.status === 'success') { loadMenuAjax(); showMenuMsg('success', 'Link deleted.'); } });
        }

        const editBtn = e.target.closest('.ajax-edit-btn');
        if (editBtn) {
            const ds = editBtn.dataset;
            document.getElementById('edit-item-id').value = ds.id;
            document.getElementById('edit-title').value = ds.title;
            document.getElementById('edit-parent').value = ds.parent;
            document.getElementById('edit-url').value = ds.url;
            
            const isPage = (ds.page && ds.page !== '0' && ds.page !== '');
            document.getElementById('edit-page').value = isPage ? ds.page : '';
            
            const targetRadio = isPage ? document.getElementById('edit_type_page') : document.getElementById('edit_type_url');
            targetRadio.checked = true;
            targetRadio.dispatchEvent(new Event('change'));

            if (ds.target === '_blank') document.getElementById('edit_target_blank').checked = true;
            else document.getElementById('edit_target_self').checked = true;

            document.getElementById('edit-item-modal').style.display = 'flex';
        }
    });
}

// Drag & Drop: Save Order & Intelligently Reassign Parents
if (saveOrderBtn) {
    saveOrderBtn.addEventListener('click', () => {
        const rows = document.querySelectorAll('.menu-item-row');
        const newOrder = [];
        let currentParent = null;

        rows.forEach((row, index) => {
            if (row.classList.contains('is-top-item')) {
                currentParent = row.dataset.id;
                newOrder.push({ id: row.dataset.id, parent_id: null, order: index });
            } else {
                // It's a sub-item. Adopt the closest preceding top-level item as parent.
                newOrder.push({ id: row.dataset.id, parent_id: currentParent, order: index });
            }
        });

        const formData = new FormData();
        formData.append('action', 'update_order');
        formData.append('sort_order_data', JSON.stringify(newOrder));
        formData.append('csrf_token', csrfInput.value);

        fetch('ajax-menu.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                saveOrderBtn.style.display = 'none';
                loadMenuAjax(); // Pull fresh structure from DB
                showMenuMsg('success', 'Menu order and hierarchy saved.');
            }
        });
    });
}

function bindDragEvents() {
    const draggables = document.querySelectorAll('.draggable');
    let draggedChildren = []; // Array to hold sub-items during drag

    draggables.forEach(draggable => {
        draggable.addEventListener('dragstart', () => { 
            draggable.classList.add('dragging'); 
            draggedChildren = [];
            
            // If it's a top-level item, grab its sub-items
            if (draggable.classList.contains('is-top-item')) {
                let next = draggable.nextElementSibling;
                // Loop through all following elements until we hit another top-item
                while (next && next.classList.contains('is-sub-item') && !next.classList.contains('dragging')) {
                    draggedChildren.push(next);
                    next.style.display = 'none'; // Hide them to keep the drop zone clean
                    next = next.nextElementSibling;
                }
            }
        });
        
        draggable.addEventListener('dragend', () => {
            draggable.classList.remove('dragging');
            
            // Snap children back into place immediately after the dropped parent
            let refNode = draggable;
            draggedChildren.forEach(child => {
                child.style.display = 'flex'; // Restore flex visibility
                refNode.parentNode.insertBefore(child, refNode.nextSibling);
                refNode = child; // Update reference to maintain their original order
            });

            saveOrderBtn.style.display = 'inline-block'; 
        });
    });

    sortableContainer.addEventListener('dragover', e => {
        e.preventDefault();
        const afterElement = getDragAfterElement(sortableContainer, e.clientY);
        const draggable = document.querySelector('.dragging');
        if (draggable) {
            if (afterElement == null) { sortableContainer.appendChild(draggable); } 
            else { sortableContainer.insertBefore(draggable, afterElement); }
        }
    });
}

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.draggable:not(.dragging)')];
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) return { offset: offset, element: child };
        else return closest;
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}