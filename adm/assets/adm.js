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

    // --- 4. Mobile Menu Logic ---
    const mobileToggleBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if(mobileToggleBtn && sidebar) {
        mobileToggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-open');
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

});