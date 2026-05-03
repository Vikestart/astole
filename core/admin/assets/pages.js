document.addEventListener('DOMContentLoaded', () => {
    
    const csrfToken = document.getElementById('global-csrf').value;
    const msgBox = document.getElementById('ajax-msgbox');
    const tableBody = document.getElementById('pages-table-body');
    const viewList = document.getElementById('view-list');
    const viewForm = document.getElementById('view-form');
    const formTitle = document.getElementById('form-view-title');
    const pageForm = document.getElementById('page-form');
    
    // Form Inputs
    const inputId = document.getElementById('page-id');
    const inputTitle = document.getElementById('page-title-input');
    const inputSlug = document.getElementById('page-slug-input');
    const inputDesc = document.getElementById('pagedesc');
    const typeSelector = document.getElementById('page-type-selector');
    const contentGroup = document.getElementById('content-group');
    const hiddenContent = document.getElementById('hidden-pagecontents');
    
    // Helper: Show Feedback Message
    function showMsg(type, msg) {
        msgBox.className = `msgbox msgbox-${type}`;
        msgBox.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'times-circle'}"></i> ${msg}`;
        msgBox.style.display = 'flex';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => { msgBox.style.display = 'none'; }, 4000);
    }

    // 1. Initialize Quill
    var quill = new Quill('#editor-container', {
        theme: 'snow',
        placeholder: 'Start writing your content here...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link', 'blockquote', 'code-block'],
                ['clean']
            ]
        }
    });

    quill.on('text-change', function() {
        hiddenContent.value = quill.root.innerHTML;
    });

    // 2. Type Selector Logic (Hide Editor for Ticket Portal)
    typeSelector.addEventListener('change', () => {
        contentGroup.style.display = (typeSelector.value === 'ticket_portal') ? 'none' : 'block';
    });

    // 3. Smart Slug Logic
    let autoGenerateSlug = false;

    const generateSlug = (text) => {
        return text.toString().toLowerCase().trim()
            .replace(/[\s_]+/g, '-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-')
            .replace(/^-+/, '')
            .replace(/-+$/, '')
            .substring(0, 40);
    };

    inputSlug.addEventListener('input', function() {
        if (this.value.trim() === '') { autoGenerateSlug = true; } 
        else { autoGenerateSlug = false; }
    });

    inputTitle.addEventListener('input', function() {
        if (autoGenerateSlug) {
            inputSlug.value = generateSlug(this.value);
            inputSlug.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });

    // 4. AJAX: Load the Table List
    window.loadList = function() {
        const formData = new FormData();
        formData.append('action', 'get_list');
        formData.append('csrf_token', csrfToken);

        fetch('/core/actions/adm/ajax-pages.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') tableBody.innerHTML = data.html;
        });
    };

    // 5. AJAX: Show Form (New or Edit) + History API
    window.showForm = function(pageId = 0) {
        viewList.style.display = 'none';
        viewForm.style.display = 'block';
        msgBox.style.display = 'none';
        
        if (pageId === 0) {
            formTitle.innerHTML = '<i class="fa-solid fa-file-circle-plus mr-10"></i> Create New Page';
            pageForm.reset();
            inputId.value = 0;
            quill.root.innerHTML = '';
            typeSelector.dispatchEvent(new Event('change'));
            autoGenerateSlug = true; 
            
            // Cleanly update URL without refreshing
            window.history.pushState({}, '', '/adm/pages?action=new');
        } 
        else {
            formTitle.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-10"></i> Loading...';
            window.history.pushState({}, '', `/adm/pages?action=edit&p=${pageId}`);
            
            const formData = new FormData();
            formData.append('action', 'get_page');
            formData.append('page_id', pageId);
            formData.append('csrf_token', csrfToken);

            fetch('/core/actions/adm/ajax-pages.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const p = data.data;
                    formTitle.innerHTML = `<i class="fa-solid fa-file-pen mr-10"></i> Edit Page: ${p.page_title}`;
                    inputId.value = p.page_id;
                    inputTitle.value = p.page_title;
                    inputSlug.value = p.page_slug;
                    inputDesc.value = p.page_desc;
                    typeSelector.value = p.page_type || 'standard';
                    quill.root.innerHTML = p.page_contents || '';
                    
                    typeSelector.dispatchEvent(new Event('change'));
                    autoGenerateSlug = false;
                }
            });
        }
    };

    // 6. Navigation: Back to List
    window.showList = function() {
        viewForm.style.display = 'none';
        viewList.style.display = 'block';
        window.history.pushState({}, '', '/adm/pages'); // Reset URL
        loadList(); 
    };

    // 7. AJAX: Save Page
    pageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (typeSelector.value === 'standard' && quill.getText().trim().length === 0) {
            alert("Please enter some content before saving a Standard page.");
            return;
        }

        const formData = new FormData(pageForm);
        formData.append('action', 'save_page');
        formData.append('csrf_token', csrfToken);
        formData.append('pagecontents', typeSelector.value === 'standard' ? quill.root.innerHTML : '');

        fetch('/core/actions/adm/ajax-pages.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            showMsg(data.status, data.message);
            if (data.status === 'success') showList();
        });
    });

    // 8. Custom Modal Delete Logic
    let pageToDelete = null;
    const deleteModal = document.getElementById('delete-modal');

    // Triggered by the delete button in the table
    window.deletePage = function(pageId) {
        pageToDelete = pageId;
        deleteModal.style.display = 'flex'; // Show modal
    };

    // Triggered by "Cancel"
    window.closeDeleteModal = function() {
        pageToDelete = null;
        deleteModal.style.display = 'none';
    };

    // Triggered by "Yes, Delete Page"
    document.getElementById('confirm-delete-btn').addEventListener('click', function() {
        if (!pageToDelete) return;
        
        // Change button state to loading
        const btn = this;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-10"></i> Deleting...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'delete_page');
        formData.append('page_id', pageToDelete);
        formData.append('csrf_token', csrfToken);

        fetch('/core/actions/adm/ajax-pages.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            closeDeleteModal();
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            showMsg(data.status, data.message);
            if (data.status === 'success') loadList();
        });
    });

    // 9. Initial Load Router (Fixes Dashboard Links!)
    const urlParams = new URLSearchParams(window.location.search);
    const routeAction = urlParams.get('action');
    const routePageId = urlParams.get('p');

    if (routeAction === 'new') {
        showForm(0);
    } else if (routeAction === 'edit' && routePageId) {
        showForm(routePageId);
    } else {
        loadList();
    }
});