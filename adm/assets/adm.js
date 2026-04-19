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

    // --- 6. Form Change Tracking (Enable/Disable Submit Buttons) ---
    const trackForms = document.querySelectorAll('.track-changes');
    trackForms.forEach(form => {
        const submitBtn = form.querySelector('button[type="submit"]');
        if(!submitBtn) return;
        
        submitBtn.disabled = true; // Grey out initially
        
        // Grab all inputs to track their original state
        const inputs = form.querySelectorAll('input:not([type="hidden"]), select');
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