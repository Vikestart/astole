document.addEventListener('DOMContentLoaded', () => {
    
    const csrfToken = document.getElementById('global-csrf').value;
    const msgBox = document.getElementById('ajax-msgbox');
    const tableBody = document.getElementById('menus-table-body');
    const viewList = document.getElementById('view-list');
    const viewBuilder = document.getElementById('view-builder');
    
    const builderTitle = document.getElementById('builder-title');
    const activeMenuInput = document.getElementById('active-menu-id');
    const sortableContainer = document.getElementById('sortable-menu');
    const saveOrderBtn = document.getElementById('ajax-save-order-btn');

    // Helper: Show Feedback Message
    function showMsg(type, msg) {
        msgBox.className = `msgbox msgbox-${type}`;
        msgBox.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'times-circle'}"></i> ${msg}`;
        msgBox.style.display = 'flex';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => { msgBox.style.display = 'none'; }, 4000);
    }

    // ==========================================
    // 1. TOP-LEVEL MENU CRUD
    // ==========================================
    
    window.loadMenuList = function() {
        const formData = new FormData();
        formData.append('action', 'get_menu_list');
        formData.append('csrf_token', csrfToken);

        fetch('/core/actions/adm/ajax-menus.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => { if (data.status === 'success') tableBody.innerHTML = data.html; });
    };

    // Create Menu
    const createMenuForm = document.getElementById('create-menu-form');
    if (createMenuForm) {
        createMenuForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(createMenuForm);
            formData.append('action', 'create_menu');
            formData.append('csrf_token', csrfToken);

            fetch('/core/actions/adm/ajax-menus.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                showMsg(data.status, data.message);
                if (data.status === 'success') {
                    document.getElementById('create-menu-modal').style.display = 'none';
                    createMenuForm.reset();
                    loadMenuList();
                }
            });
        });
    }

    // Edit Menu Settings
    const editMenuForm = document.getElementById('edit-menu-form');
    if (editMenuForm) {
        editMenuForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(editMenuForm);
            formData.append('action', 'edit_menu');
            formData.append('csrf_token', csrfToken);

            fetch('/core/actions/adm/ajax-menus.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                showMsg(data.status, data.message);
                if (data.status === 'success') {
                    document.getElementById('settings-menu-modal').style.display = 'none';
                    loadMenuList();
                }
            });
        });
    }

    window.openSettingsModal = function(id, name, identifier) {
        document.getElementById('settings-menu-id').value = id;
        document.getElementById('settings-menu-name').value = name;
        document.getElementById('settings-menu-identifier').value = identifier;
        document.getElementById('settings-menu-modal').style.display = 'flex';
    };

    // Delete Menu Logic
    let deleteType = null; 
    let idToDelete = null;
    const deleteModal = document.getElementById('delete-modal');

    window.deleteMenu = function(id) {
        deleteType = 'menu'; idToDelete = id;
        deleteModal.style.display = 'flex';
    };
    window.deleteItem = function(id) {
        deleteType = 'item'; idToDelete = id;
        deleteModal.style.display = 'flex';
    };
    window.closeDeleteModal = function() {
        deleteType = null; idToDelete = null;
        deleteModal.style.display = 'none';
    };

    document.getElementById('confirm-delete-btn').addEventListener('click', function() {
        if (!idToDelete) return;
        
        // Cache the variables so they survive after the modal closes
        const currentDeleteType = deleteType;
        const currentId = idToDelete;

        // OPTIMISTIC UI UPDATE: Close modal and vanish the item instantly!
        closeDeleteModal();
        if (currentDeleteType === 'item') {
            const itemRow = document.querySelector(`.menu-item-row[data-id="${currentId}"]`);
            if (itemRow) itemRow.style.display = 'none';
        }

        const formData = new FormData();
        formData.append('action', currentDeleteType === 'menu' ? 'delete_menu' : 'delete_item');
        formData.append(currentDeleteType === 'menu' ? 'menu_id' : 'item_id', currentId);
        formData.append('csrf_token', csrfToken);

        fetch('/core/actions/adm/ajax-menus.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            showMsg(data.status, data.message);
            if (data.status === 'success') {
                if (currentDeleteType === 'menu') showList(); 
                else loadBuilderData(true); // Silent reload to keep DOM perfectly synchronized
            } else {
                // If deletion failed on the server, reload to bring the item back
                if (currentDeleteType === 'item') loadBuilderData(true);
            }
        });
    });

    // ==========================================
    // 2. MENU BUILDER LOGIC
    // ==========================================

    window.showList = function() {
        viewBuilder.style.display = 'none';
        viewList.style.display = 'block';
        window.history.pushState({}, '', '/adm/menus');
        loadMenuList();
    };

    window.showBuilder = function(menuId) {
        viewList.style.display = 'none';
        viewBuilder.style.display = 'block';
        activeMenuInput.value = menuId;
        window.history.pushState({}, '', `/adm/menus?action=edit&menu_id=${menuId}`);
        
        // Link the Header Delete Button
        document.getElementById('header-delete-menu-btn').onclick = () => window.deleteMenu(menuId);
        
        loadBuilderData(false); // First load shows spinner
    };

    // FIX: Added 'silent' parameter to prevent flickering!
    function loadBuilderData(silent = false) {
        if (!silent) {
            sortableContainer.innerHTML = '<div class="text-center p-20 text-muted"><i class="fa-solid fa-spinner fa-spin mr-10"></i> Loading structure...</div>';
        }
        
        const formData = new FormData();
        formData.append('action', 'get_builder_data'); 
        formData.append('menu_id', activeMenuInput.value); 
        formData.append('csrf_token', csrfToken);

        fetch('/core/actions/adm/ajax-menus.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                builderTitle.innerHTML = `<i class="fa-solid fa-list-ul mr-10"></i> Builder: ${data.menu_name}`;
                sortableContainer.innerHTML = data.html;
                bindDragEvents();
                // Update dropdowns silently
                document.querySelectorAll('.dynamic-parent-select').forEach(select => {
                    const currentVal = select.value;
                    select.innerHTML = data.parent_opts;
                    if(currentVal) select.value = currentVal; 
                });
                document.querySelectorAll('.page-select-dropdown').forEach(select => {
                    const currentVal = select.value;
                    select.innerHTML = data.pages_opts;
                    if(currentVal) select.value = currentVal; 
                });
            }
        });
    }

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
    setupRadioToggles('ajax-add-item-form', 'link_type', 'wrapper-url', 'wrapper-page');
    setupRadioToggles('ajax-edit-item-form', 'link_type', 'edit-wrapper-url', 'edit-wrapper-page');

    // Add Item (Optimistic "Ghost Row" UX)
    const addItemForm = document.getElementById('ajax-add-item-form');
    if (addItemForm) {
        addItemForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(addItemForm);
            
            // 1. Capture the title for the Ghost Row
            const newTitle = formData.get('title');
            
            // 2. Instantly reset the form so the user can rapidly add multiple links
            addItemForm.reset();
            document.getElementById('add_type_url').checked = true;
            document.getElementById('add_type_url').dispatchEvent(new Event('change'));

            // 3. Inject a temporary "Ghost" row into the UI instantly
            const ghostRow = document.createElement('div');
            ghostRow.className = 'menu-item-row';
            ghostRow.style.opacity = '0.5';
            ghostRow.style.pointerEvents = 'none'; // Prevent dragging before it has an ID
            ghostRow.innerHTML = `
                <div class='menu-item-drag-handle'><i class='fa-solid fa-spinner fa-spin text-muted'></i></div>
                <div class='menu-item-details'>
                    <span class='menu-item-title'>${newTitle}</span>
                    <span class='menu-item-url text-muted'>Saving to database...</span>
                </div>
            `;
            sortableContainer.appendChild(ghostRow);

            // Scroll to the bottom to see the new item
            sortableContainer.scrollTop = sortableContainer.scrollHeight;

            // 4. Send the actual data to the server
            formData.append('action', 'add_item');
            formData.append('menu_id', activeMenuInput.value);
            formData.append('csrf_token', csrfToken);

            fetch('/core/actions/adm/ajax-menus.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                showMsg(data.status, data.message);
                if (data.status === 'success') {
                    // Silent reload swaps the ghost row with the real, draggable row
                    loadBuilderData(true); 
                } else {
                    // If it failed, remove the ghost row
                    ghostRow.remove(); 
                }
            });
        });
    }

    // Edit Item
    const editItemForm = document.getElementById('ajax-edit-item-form');
    if (editItemForm) {
        editItemForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(editItemForm);
            formData.append('action', 'edit_item');
            formData.append('csrf_token', csrfToken);

            fetch('/core/actions/adm/ajax-menus.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                showMsg(data.status, data.message);
                if (data.status === 'success') {
                    document.getElementById('edit-item-modal').style.display = 'none';
                    loadBuilderData(true); // Silent reload
                }
            });
        });
    }

    // FIX: Combined Item Click Delegation (Edit AND Delete logic)
    if (sortableContainer) {
        sortableContainer.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.ajax-edit-btn');
            const delBtn = e.target.closest('.ajax-delete-btn');
            
            // Delete Logic
            if (delBtn) {
                window.deleteItem(delBtn.dataset.id);
            }

            // Edit Logic
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

    // Auto-Save Order Logic
    function saveMenuOrder() {
        const rows = document.querySelectorAll('.menu-item-row');
        const newOrder = [];
        let currentParent = null;

        rows.forEach((row, index) => {
            if (row.classList.contains('is-top-item')) {
                currentParent = row.dataset.id;
                newOrder.push({ id: row.dataset.id, parent_id: null, order: index });
            } else {
                newOrder.push({ id: row.dataset.id, parent_id: currentParent, order: index });
            }
        });

        const formData = new FormData();
        formData.append('action', 'update_order');
        formData.append('sort_order_data', JSON.stringify(newOrder));
        formData.append('csrf_token', csrfToken);

        fetch('/core/actions/adm/ajax-menus.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            showMsg(data.status, data.message);
            if (data.status === 'success') {
                loadBuilderData(true); // Silent reload
            }
        });
    }

    function bindDragEvents() {
        const draggables = document.querySelectorAll('.draggable');
        let draggedChildren = []; 

        draggables.forEach(draggable => {
            draggable.addEventListener('dragstart', () => { 
                draggable.classList.add('dragging'); 
                draggedChildren = [];
                if (draggable.classList.contains('is-top-item')) {
                    let next = draggable.nextElementSibling;
                    while (next && next.classList.contains('is-sub-item') && !next.classList.contains('dragging')) {
                        draggedChildren.push(next);
                        next.style.display = 'none'; 
                        next = next.nextElementSibling;
                    }
                }
            });
            
            draggable.addEventListener('dragend', () => {
                draggable.classList.remove('dragging');
                let refNode = draggable;
                draggedChildren.forEach(child => {
                    child.style.display = 'flex'; 
                    refNode.parentNode.insertBefore(child, refNode.nextSibling);
                    refNode = child; 
                });
                
                // OPTIMISTIC AUTO-SAVE: Fire the save logic the second they drop it!
                saveMenuOrder();
            });
        });
    }

    if (sortableContainer) {
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

    // ==========================================
    // 3. INITIAL LOAD ROUTER
    // ==========================================
    const urlParams = new URLSearchParams(window.location.search);
    const routeAction = urlParams.get('action');
    const routeMenuId = urlParams.get('menu_id');

    if (routeAction === 'edit' && routeMenuId) {
        showBuilder(routeMenuId);
    } else {
        loadMenuList();
    }
});