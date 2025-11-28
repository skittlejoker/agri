// Farmer Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function () {
    // Load farmer data
    loadFarmerData();

    // Setup event listeners
    setupEventListeners();
});

// Load farmer data from session
function loadFarmerData() {
    // Get farmer name from session or localStorage
    const farmerName = localStorage.getItem('farmer_name') || 'Farmer';
    const welcomeElement = document.getElementById('farmerWelcome');
    if (welcomeElement) {
        welcomeElement.textContent = farmerName;
    }
}

// Setup all event listeners
function setupEventListeners() {
    // Logout button
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }

    // Change password modal
    setupChangePasswordModal();
}

// Setup change password modal
function setupChangePasswordModal() {
    const changePasswordModal = document.getElementById('changePasswordModal');
    const changePasswordLink = document.getElementById('changePasswordLink');
    const closePasswordModal = document.getElementById('closePasswordModal');
    const cancelPasswordBtn = document.getElementById('cancelPasswordBtn');
    const changePasswordForm = document.getElementById('changePasswordForm');

    // Setup password toggle buttons
    setupPasswordToggles();

    // Open modal
    if (changePasswordLink && changePasswordModal) {
        changePasswordLink.addEventListener('click', function (e) {
            e.preventDefault();
            changePasswordModal.setAttribute('aria-hidden', 'false');
            changePasswordModal.classList.add('show');
            document.body.style.overflow = 'hidden';
            // Re-setup password toggles when modal opens
            setupPasswordToggles();
        });
    }

    // Close modal
    if (closePasswordModal) {
        closePasswordModal.addEventListener('click', function () {
            closeChangePasswordModal();
        });
    }

    if (cancelPasswordBtn) {
        cancelPasswordBtn.addEventListener('click', function () {
            closeChangePasswordModal();
        });
    }

    // Close on background click
    if (changePasswordModal) {
        changePasswordModal.addEventListener('click', function (e) {
            if (e.target === changePasswordModal) {
                closeChangePasswordModal();
            }
        });
    }

    // Handle form submission
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            // Clear errors
            clearPasswordErrors();

            const oldPassword = document.getElementById('oldPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmNewPassword = document.getElementById('confirmNewPassword').value;

            // Validate
            if (!oldPassword || !newPassword || !confirmNewPassword) {
                showPasswordError('All fields are required', 'oldPasswordError');
                return;
            }

            if (newPassword.length < 6) {
                showPasswordError('New password must be at least 6 characters', 'newPasswordError');
                return;
            }

            if (newPassword !== confirmNewPassword) {
                showPasswordError('Passwords do not match', 'confirmNewPasswordError');
                return;
            }

            try {
                const response = await fetch('api/change_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        oldPassword: oldPassword,
                        newPassword: newPassword,
                        confirmPassword: confirmNewPassword
                    })
                });

                // Try to parse JSON response
                let result;
                try {
                    const responseText = await response.text();
                    if (!responseText || responseText.trim() === '') {
                        showPasswordError('Empty response from server. Please try again.', 'oldPasswordError');
                        return;
                    }
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Error parsing response:', parseError);
                    showPasswordError('Invalid response from server. Please try again.', 'oldPasswordError');
                    return;
                }

                // Handle response
                if (result.success) {
                    showMessage('Password changed successfully!', 'success');
                    // Reset form and close modal
                    resetPasswordForm();
                    closeChangePasswordModal();
                } else {
                    // Show error in appropriate field
                    const errorField = result.error?.toLowerCase().includes('current') || result.error?.toLowerCase().includes('old') 
                        ? 'oldPasswordError' 
                        : result.error?.toLowerCase().includes('new') || result.error?.toLowerCase().includes('confirm')
                        ? 'confirmNewPasswordError'
                        : 'oldPasswordError';
                    showPasswordError(result.error || 'Failed to change password', errorField);
                }
            } catch (error) {
                console.error('Error changing password:', error);
                showPasswordError('Network error. Please check your connection and try again.', 'oldPasswordError');
            }
        });
    }
}

// Close change password modal
function closeChangePasswordModal() {
    const modal = document.getElementById('changePasswordModal');
    if (modal) {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = 'auto';
        resetPasswordForm();
    }
}

// Reset password form
function resetPasswordForm() {
    const form = document.getElementById('changePasswordForm');
    if (form) {
        form.reset();
        // Reset all password toggles to show password (hidden state)
        const toggleButtons = form.querySelectorAll('.password-toggle-btn');
        toggleButtons.forEach(button => {
            const targetId = button.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = button.querySelector('i');
            if (input && icon) {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                button.setAttribute('aria-label', 'Show password');
            }
        });
    }
    clearPasswordErrors();
}

// Clear password errors
function clearPasswordErrors() {
    const errorElements = document.querySelectorAll('#changePasswordForm .error');
    errorElements.forEach(el => {
        el.textContent = '';
    });
}

// Show password error
function showPasswordError(message, errorId) {
    const errorElement = document.getElementById(errorId);
    if (errorElement) {
        errorElement.textContent = message;
    }
}

// Setup password toggle functionality
function setupPasswordToggles() {
    // Use event delegation to handle toggles
    const modal = document.getElementById('changePasswordModal');
    if (!modal) return;
    
    // Remove existing listeners by cloning and replacing
    modal.removeEventListener('click', handlePasswordToggle);
    modal.addEventListener('click', handlePasswordToggle);
}

function handlePasswordToggle(e) {
    // Check if clicked element is the button or icon inside it
    const button = e.target.closest('.password-toggle-btn');
    if (!button) return;
    
    e.preventDefault();
    e.stopPropagation();
    
    const targetId = button.getAttribute('data-target');
    const input = document.getElementById(targetId);
    const icon = button.querySelector('i') || button.querySelector('.fa-eye') || button.querySelector('.fa-eye-slash');
    
    if (input && icon) {
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            button.setAttribute('aria-label', 'Hide password');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            button.setAttribute('aria-label', 'Show password');
        }
    }
}

// Handle logout
function handleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        fetch('api/logout.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'index.html';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Force redirect anyway
                window.location.href = 'index.html';
            });
    }
}

// Show message to user
function showMessage(message, type = 'success') {
    // Create message element
    const messageDiv = document.createElement('div');
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        animation: slideInRight 0.3s ease-out;
        background: ${type === 'success' ? '#d4edda' : '#f8d7da'};
        color: ${type === 'success' ? '#155724' : '#721c24'};
        border: 1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'};
        font-weight: 500;
    `;
    messageDiv.innerHTML = `
        <i class="fa-solid ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}" style="margin-right: 0.5rem;"></i>
        ${message}
    `;

    document.body.appendChild(messageDiv);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

// Add animation styles for messages
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    
    #changePasswordModal {
        display: none;
    }
    
    #changePasswordModal.show {
        display: flex;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }
    
    .modal-content {
        background: white;
        border-radius: 8px;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        border-bottom: 1px solid #e5e5e5;
    }
    
    .modal-body {
        padding: 1.5rem;
    }
    
    .modal-footer {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        padding-top: 1rem;
    }
    
    .icon-btn {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #666;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .icon-btn:hover {
        background: #f5f5f5;
        border-radius: 4px;
    }
    
    #changePasswordForm .form-group {
        margin-bottom: 1.5rem;
    }
    
    #changePasswordForm label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #333;
    }
    
    #changePasswordForm input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }
    
    #changePasswordForm .error {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: block;
    }
`;
document.head.appendChild(style);

