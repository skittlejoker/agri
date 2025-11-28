(function () {
    // API endpoints
    const API_BASE = 'api/';
    const ENDPOINTS = {
        register: API_BASE + 'register.php',
        login: API_BASE + 'login.php',
        logout: API_BASE + 'logout.php',
        checkSession: API_BASE + 'check_session.php',
        sendResetLink: API_BASE + 'send_reset_link.php',
        resetPassword: API_BASE + 'reset_password.php',
        sendOTP: API_BASE + 'send_otp_code.php',
        verifyOTP: API_BASE + 'verify_otp.php'
    };

    // Helper function to make API calls
    async function apiCall(endpoint, data = null, method = 'GET') {
        try {
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            };

            if (data && method !== 'GET') {
                options.body = JSON.stringify(data);
            }

            const response = await fetch(endpoint, options);
            
            // Get response text first to check if it's empty
            const responseText = await response.text();
            
            // Check if response is empty
            if (!responseText || responseText.trim() === '') {
                throw new Error('Empty response from server. Please try again.');
            }

            // Try to parse JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError);
                console.error('Response text:', responseText);
                throw new Error('Invalid response from server. Please try again.');
            }

            // Check if response indicates an error
            if (!response.ok) {
                throw new Error(result.error || result.message || 'API call failed');
            }

            return result;
        } catch (error) {
            console.error('API Error:', error);
            // Re-throw with a more user-friendly message if it's a JSON parse error
            if (error.message.includes('JSON') || error.message.includes('Unexpected')) {
                throw new Error('Server response error. Please try again.');
            }
            throw error;
        }
    }

    // Check if user is logged in
    async function checkSession() {
        try {
            const result = await apiCall(ENDPOINTS.checkSession);
            return result.logged_in ? result.user : null;
        } catch (error) {
            return null;
        }
    }

    // Navigate to URL
    function navigate(url) {
        window.location.href = url;
    }

    // Show error message
    function showError(el, msg) {
        if (!el) return;
        el.textContent = msg || '';
    }

    // Show message notification (replaces alert)
    // DISABLED: Notification popup is hidden per user request
    function showMessage(message, type = 'success') {
        // Notification popup is disabled - messages will not be displayed
        // Uncomment the code below if you want to re-enable notifications
        return;
        
        /* DISABLED CODE - Uncomment to re-enable notifications
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
            max-width: 400px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        `;
        messageDiv.innerHTML = `
            <div style="display: flex; align-items: center; flex: 1;">
                <i class="fa-solid ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}" style="margin-right: 0.5rem;"></i>
                <span>${message}</span>
            </div>
            <button onclick="this.parentElement.remove()" style="
                background: none;
                border: none;
                color: ${type === 'success' ? '#155724' : '#721c24'};
                cursor: pointer;
                padding: 0;
                font-size: 1.2rem;
                opacity: 0.7;
                transition: opacity 0.2s;
            " onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'" title="Close">
                <i class="fa-solid fa-times"></i>
            </button>
        `;

        document.body.appendChild(messageDiv);

        // Auto-remove after 2 seconds (reduced from 5 seconds)
        setTimeout(() => {
            if (messageDiv.parentElement) {
                messageDiv.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => {
                    if (messageDiv.parentElement) {
                        messageDiv.remove();
                    }
                }, 300);
            }
        }, 2000);
        */
    }

    // Add animation styles for messages (only once)
    if (!document.getElementById('message-animations')) {
        const style = document.createElement('style');
        style.id = 'message-animations';
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
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }

    // Handle login page
    function handleLoginPage() {
        const form = document.getElementById('loginForm');
        if (!form) return;

        const usernameInput = document.getElementById('loginUsername');
        const passwordInput = document.getElementById('loginPassword');
        const userTypeSelect = document.getElementById('loginUserType');
        const usernameError = document.getElementById('loginUsernameError');
        const passwordError = document.getElementById('loginPasswordError');
        const userTypeError = document.getElementById('loginUserTypeError');
        const rememberMe = document.getElementById('rememberMe');
        const forgotLink = document.getElementById('forgotPassword');

        // Check for remembered username
        const remembered = localStorage.getItem('agri_remembered_user');
        if (remembered) {
            usernameInput.value = remembered;
            rememberMe.checked = true;
        }

        forgotLink?.addEventListener('click', function (e) {
            e.preventDefault();
            showMessage('Password reset is not implemented in this demo.', 'error');
        });

        // Add show/hide toggle for password
        setTimeout(() => enablePasswordToggle(['loginPassword']), 100);

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            showError(usernameError, '');
            showError(passwordError, '');
            showError(userTypeError, '');

            const username = usernameInput.value.trim();
            const password = passwordInput.value.trim();
            const userType = userTypeSelect?.value || '';

            let valid = true;
            if (!username) { showError(usernameError, 'Username is required'); valid = false; }
            if (!password) { showError(passwordError, 'Password is required'); valid = false; }
            if (!userType) { showError(userTypeError, 'Select a user type'); valid = false; }
            if (!valid) return;

            try {
                const result = await apiCall(ENDPOINTS.login, {
                    username: username,
                    password: password,
                    userType: userType
                }, 'POST');

                // Handle remember me
                if (rememberMe.checked) {
                    localStorage.setItem('agri_remembered_user', username);
                } else {
                    localStorage.removeItem('agri_remembered_user');
                }

                console.log('Login successful for', result.user.username);
                showMessage('Login successful!', 'success');

                // Redirect based on user type
                if (result.user.userType === 'farmer') {
                    navigate('farmer_dashboard.html');
                } else {
                    navigate('buyer_dashboard.html');
                }

            } catch (error) {
                // Check if email verification is required
                if (error.message && error.message.includes('Email not verified')) {
                    showError(passwordError, 'Please verify your email before logging in.');
                    showMessage('Email not verified. Please check your email for the verification code.', 'error');
                    setTimeout(() => navigate('verify.html'), 3000);
                } else {
                    showError(passwordError, error.message);
                }
            }
        });
    }

    // Handle registration page
    function handleRegisterPage() {
        const form = document.getElementById('registerForm');
        if (!form) return;

        const fullName = document.getElementById('fullName');
        const email = document.getElementById('email');
        const username = document.getElementById('username');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');
        const userType = document.getElementById('userType');
        const acceptTerms = document.getElementById('acceptTerms');

        const fullNameError = document.getElementById('fullNameError');
        const emailError = document.getElementById('emailError');
        const usernameError = document.getElementById('usernameError');
        const passwordError = document.getElementById('passwordError');
        const confirmPasswordError = document.getElementById('confirmPasswordError');
        const userTypeError = document.getElementById('userTypeError');

        const termsModal = document.getElementById('termsModal');
        const openTerms = document.getElementById('openTerms');
        const closeTerms = document.getElementById('closeTerms');
        const agreeTermsBtn = document.getElementById('agreeTermsBtn');
        const showTermsBtn = document.getElementById('showTermsBtn');
        const termsContent = document.getElementById('termsContent');
        const termsPlaceholder = document.getElementById('termsPlaceholder');

        function toggleTerms(show) {
            if (!termsModal) return;
            termsModal.classList.toggle('show', !!show);
            termsModal.setAttribute('aria-hidden', show ? 'false' : 'true');
        }

        function showTermsContent() {
            if (termsContent && termsPlaceholder) {
                termsContent.classList.remove('hidden');
                termsPlaceholder.classList.add('hidden');
            }
        }

        function resetTermsModal() {
            if (termsContent && termsPlaceholder) {
                termsContent.classList.add('hidden');
                termsPlaceholder.classList.remove('hidden');
            }
        }

        openTerms?.addEventListener('click', (e) => {
            e.preventDefault();
            resetTermsModal();
            toggleTerms(true);
        });
        closeTerms?.addEventListener('click', () => {
            toggleTerms(false);
            resetTermsModal();
        });
        agreeTermsBtn?.addEventListener('click', () => {
            toggleTerms(false);
            resetTermsModal();
        });
        showTermsBtn?.addEventListener('click', () => {
            showTermsContent();
        });
        termsModal?.addEventListener('click', (e) => {
            if (e.target === termsModal) {
                toggleTerms(false);
                resetTermsModal();
            }
        });

        // Add show/hide toggles for password fields
        setTimeout(() => enablePasswordToggle(['password', 'confirmPassword']), 100);

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            [fullNameError, emailError, usernameError, passwordError, confirmPasswordError, userTypeError].forEach(el => showError(el, ''));

            const values = {
                fullName: fullName.value.trim(),
                email: email.value.trim(),
                username: username.value.trim(),
                password: password.value.trim(),
                confirmPassword: confirmPassword.value.trim(),
                userType: userType?.value || '',
                acceptTerms: acceptTerms.checked
            };

            let valid = true;
            if (!values.fullName) { showError(fullNameError, 'Full name is required'); valid = false; }
            if (!values.email) { showError(emailError, 'Email is required'); valid = false; }
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email)) { showError(emailError, 'Enter a valid email'); valid = false; }
            if (!values.username) { showError(usernameError, 'Username is required'); valid = false; }
            if (!values.password) { showError(passwordError, 'Password is required'); valid = false; }
            if (!values.confirmPassword) { showError(confirmPasswordError, 'Confirm your password'); valid = false; }
            else if (values.password !== values.confirmPassword) { showError(confirmPasswordError, 'Passwords do not match'); valid = false; }
            if (!values.userType) { showError(userTypeError, 'Select a user type'); valid = false; }
            if (!values.acceptTerms) { showMessage('You must accept the Terms and Conditions to register.', 'error'); valid = false; }

            if (!valid) return;

            try {
                const result = await apiCall(ENDPOINTS.register, {
                    fullName: values.fullName,
                    email: values.email,
                    username: values.username,
                    password: values.password,
                    confirmPassword: values.confirmPassword,
                    userType: values.userType
                }, 'POST');

                console.log('Registration successful for', result.user.username);
                console.log('User email:', result.user.email || values.email);
                
                // Get email from result or form values
                const userEmail = result.user?.email || values.email;
                
                // Check if email was sent successfully
                if (result.email_sent) {
                    showMessage('Registration successful! Please check your email (' + userEmail + ') for verification code.', 'success');
                } else {
                    showMessage('Registration successful! However, email sending failed. Please use the resend code feature.', 'error');
                }
                
                // Redirect to verification page with email pre-filled
                setTimeout(() => {
                    const verifyUrl = 'verify.html?email=' + encodeURIComponent(userEmail);
                    console.log('Redirecting to:', verifyUrl);
                    window.location.href = verifyUrl;
                }, 2000);

            } catch (error) {
                // Show error in appropriate field
                if (error.message.includes('username')) {
                    showError(usernameError, error.message);
                } else if (error.message.includes('email')) {
                    showError(emailError, error.message);
                } else {
                    showMessage(error.message, 'error');
                }
            }
        });
    }

    // Handle home/landing page
    function handleHomePage() {
        if (document.body.getAttribute('data-page') !== 'landing') return;

        // Check if user is already logged in and redirect
        checkSession().then(user => {
            if (user) {
                if (user.userType === 'farmer') {
                    navigate('farmer_dashboard.html');
                } else {
                    navigate('buyer_dashboard.html');
                }
            }
        });
    }

    // Handle farmer dashboard
    function handleFarmerDashboard() {
        if (document.body.getAttribute('data-page') !== 'farmer-dashboard') return;

        checkSession().then(user => {
            if (!user || user.userType !== 'farmer') {
                navigate('index.html');
                return;
            }

            const welcome = document.getElementById('farmerWelcome');
            if (welcome) welcome.textContent = user.fullName || user.username;

            const logout = document.getElementById('logoutBtn');
            logout?.addEventListener('click', async () => {
                try {
                    await apiCall(ENDPOINTS.logout);
                    navigate('index.html');
                } catch (error) {
                    console.error('Logout error:', error);
                    navigate('index.html');
                }
            });

            // Bind change password modal interactions (shared UI)
            bindChangePasswordModal();
        });
    }

    // Handle buyer dashboard
    function handleBuyerDashboard() {
        if (document.body.getAttribute('data-page') !== 'buyer-dashboard') return;

        checkSession().then(user => {
            if (!user || user.userType !== 'buyer') {
                navigate('index.html');
                return;
            }

            const welcome = document.getElementById('buyerWelcome');
            if (welcome) welcome.textContent = user.fullName || user.username;

            const logout = document.getElementById('logoutBtn');
            logout?.addEventListener('click', async () => {
                try {
                    await apiCall(ENDPOINTS.logout);
                    navigate('index.html');
                } catch (error) {
                    console.error('Logout error:', error);
                    navigate('index.html');
                }
            });

            // Bind change password modal interactions (shared UI)
            bindChangePasswordModal();

            // Load and render shop + cart
            initializeBuyerShop(user);
        });
    }

    // Shared: Change Password modal binder for pages containing #changePasswordModal
    function bindChangePasswordModal() {
        const changePasswordModal = document.getElementById('changePasswordModal');
        const changePasswordLink = document.getElementById('changePasswordLink');
        const closePasswordModal = document.getElementById('closePasswordModal');
        const cancelPasswordBtn = document.getElementById('cancelPasswordBtn');
        const changePasswordForm = document.getElementById('changePasswordForm');
        const oldPasswordEl = document.getElementById('oldPassword');
        const newPasswordEl = document.getElementById('newPassword');
        const confirmNewPasswordEl = document.getElementById('confirmNewPassword');
        const oldPasswordError = document.getElementById('oldPasswordError');
        const newPasswordError = document.getElementById('newPasswordError');
        const confirmNewPasswordError = document.getElementById('confirmNewPasswordError');

        // Add show/hide toggles for change password fields
        // Call when modal opens and also immediately
        setTimeout(() => enablePasswordToggle(['oldPassword', 'newPassword', 'confirmNewPassword']), 100);

        // Also enable when modal is opened
        if (changePasswordLink && changePasswordModal) {
            const originalClick = changePasswordLink.onclick;
            changePasswordLink.addEventListener('click', function (e) {
                setTimeout(() => enablePasswordToggle(['oldPassword', 'newPassword', 'confirmNewPassword']), 200);
            });
        }

        function clearErrors() {
            if (oldPasswordError) oldPasswordError.textContent = '';
            if (newPasswordError) newPasswordError.textContent = '';
            if (confirmNewPasswordError) confirmNewPasswordError.textContent = '';
        }

        function resetForm() {
            changePasswordForm?.reset();
            // Reset all password toggles to show password (hidden state)
            const toggleButtons = changePasswordForm?.querySelectorAll('.password-toggle-btn');
            if (toggleButtons) {
                toggleButtons.forEach(button => {
                    const inputId = button.getAttribute('data-target') || button.closest('.form-group')?.querySelector('input[type="password"], input[type="text"]')?.id;
                    const input = inputId ? document.getElementById(inputId) : null;
                    const icon = button.querySelector('i');
                    if (input && icon) {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                        button.setAttribute('aria-label', 'Show password');
                    }
                });
            }
            clearErrors();
        }

        if (changePasswordLink && changePasswordModal) {
            changePasswordLink.addEventListener('click', function (e) {
                e.preventDefault();
                changePasswordModal.classList.add('show');
                document.body.style.overflow = 'hidden';
            });
        }
        if (closePasswordModal && changePasswordModal) {
            closePasswordModal.addEventListener('click', function () {
                changePasswordModal.classList.remove('show');
                document.body.style.overflow = 'auto';
                resetForm();
            });
        }
        if (cancelPasswordBtn && changePasswordModal) {
            cancelPasswordBtn.addEventListener('click', function () {
                changePasswordModal.classList.remove('show');
                document.body.style.overflow = 'auto';
                resetForm();
            });
        }
        if (changePasswordModal) {
            changePasswordModal.addEventListener('click', function (e) {
                if (e.target === changePasswordModal) {
                    changePasswordModal.classList.remove('show');
                    document.body.style.overflow = 'auto';
                    resetForm();
                }
            });
        }
        if (changePasswordForm) {
            changePasswordForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                clearErrors();

                const oldPassword = oldPasswordEl?.value || '';
                const newPassword = newPasswordEl?.value || '';
                const confirmNewPassword = confirmNewPasswordEl?.value || '';

                if (!oldPassword || !newPassword || !confirmNewPassword) {
                    if (oldPasswordError) oldPasswordError.textContent = 'All fields are required';
                    return;
                }
                if (newPassword.length < 6) {
                    if (newPasswordError) newPasswordError.textContent = 'New password must be at least 6 characters long';
                    return;
                }
                if (newPassword !== confirmNewPassword) {
                    if (confirmNewPasswordError) confirmNewPasswordError.textContent = 'New passwords do not match';
                    return;
                }

                try {
                    const result = await apiCall(API_BASE + 'change_password.php', {
                        oldPassword: oldPassword,
                        newPassword: newPassword,
                        confirmPassword: confirmNewPassword
                    }, 'POST');

                    if (result.success) {
                        showMessage('Password changed successfully!', 'success');
                        // Reset form and close modal
                        resetForm();
                        changePasswordModal?.classList.remove('show');
                        changePasswordModal?.setAttribute('aria-hidden', 'true');
                        document.body.style.overflow = 'auto';
                    } else {
                        // Show error in appropriate field based on error message
                        const errorMsg = result.error || result.message || 'Failed to change password';
                        const errorLower = errorMsg.toLowerCase();
                        
                        if (errorLower.includes('current') || errorLower.includes('old') || errorLower.includes('incorrect')) {
                            if (oldPasswordError) oldPasswordError.textContent = errorMsg;
                        } else if (errorLower.includes('new') || errorLower.includes('confirm') || errorLower.includes('match')) {
                            if (confirmNewPasswordError) confirmNewPasswordError.textContent = errorMsg;
                        } else if (errorLower.includes('length') || errorLower.includes('characters')) {
                            if (newPasswordError) newPasswordError.textContent = errorMsg;
                        } else {
                            if (oldPasswordError) oldPasswordError.textContent = errorMsg;
                        }
                    }
                } catch (err) {
                    console.error('Change password error:', err);
                    const errorMsg = err.message || 'Network error. Please try again.';
                    
                    // Show user-friendly error message
                    if (oldPasswordError) {
                        oldPasswordError.textContent = errorMsg;
                    }
                }
            });
        }
    }

    // Buyer: Shop and Cart
    function initializeBuyerShop(user) {
        const productsContainer = document.getElementById('shopProducts');
        const cartContainer = document.getElementById('cartItems');
        const searchInput = document.getElementById('search');
        const cartKey = 'agri_cart_' + (user?.id || 'guest');
        let allProducts = [];
        let cart = loadCart();

        function loadCart() {
            try {
                return JSON.parse(localStorage.getItem(cartKey) || '{}');
            } catch (_) {
                return {};
            }
        }
        function saveCart() {
            localStorage.setItem(cartKey, JSON.stringify(cart));
        }
        function renderProducts(list) {
            if (!productsContainer) return;
            if (!list || list.length === 0) {
                productsContainer.innerHTML = '<p style="color: var(--text-light)">No products available.</p>';
                return;
            }
            productsContainer.innerHTML = list.map(p => {
                const qty = cart[p.id]?.quantity || 0;
                const canIncrement = p.stock > 0 && qty < p.stock;
                const hasImage = p.image_url && p.image_url.trim() !== '';
                const imageId = `product-img-${p.id}`;

                return `
                    <div class="product-card">
                        <div class="product-image-wrap" ${hasImage ? `onclick="viewProductImage('${p.image_url}', '${p.name.replace(/'/g, "\\'")}')" style="cursor: pointer;"` : ''}>
                            ${hasImage ? `
                                <img 
                                    id="${imageId}"
                                    src="${p.image_url}" 
                                    alt="${p.name}"
                                    loading="lazy"
                                    onerror="handleProductImageError('${imageId}')"
                                >
                                <div class="image-overlay">
                                    <i class="fa-solid fa-expand" style="font-size: 1.5rem; color: white;"></i>
                                    <span style="margin-top: 0.5rem; font-size: 0.85rem;">View Full Image</span>
                                </div>
                                <div class="image-error" style="display: none;"></div>
                            ` : `
                                <div class="product-image placeholder"></div>
                            `}
                        </div>
                        <div class="product-info">
                            <div class="product-title">
                                <strong>${p.name}</strong>
                            </div>
                            <div class="product-meta">
                                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                    ${p.price_gram ? `<span class="price">₱${parseFloat(p.price_gram).toFixed(2)}/g</span>` : ''}
                                    <span class="price">₱${parseFloat(p.price_kg || p.price || 0).toFixed(2)}/kg</span>
                                </div>
                                <span class="stock ${p.stock > 10 ? 'in-stock' : p.stock > 0 ? 'low-stock' : 'out-of-stock'}">${p.stock} in stock</span>
                            </div>
                            <div class="farmer-meta">
                                <i class="fa-solid fa-seedling"></i>
                                <span>${p.farmer?.name || 'Farmer'}</span>
                            </div>
                            <div class="cart-actions">
                                <button class="btn btn-secondary" data-action="decrement" data-id="${p.id}" ${qty === 0 ? 'disabled' : ''}>-</button>
                                <span class="qty" id="qty_${p.id}">${qty}</span>
                                <button class="btn btn-primary" data-action="increment" data-id="${p.id}" ${!canIncrement ? 'disabled' : ''}>Add</button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
        function renderCart() {
            if (!cartContainer) return;
            const items = Object.values(cart);
            const checkoutSection = document.getElementById('checkoutSection');
            if (items.length === 0) {
                cartContainer.innerHTML = '<p style="color: var(--text-light)">Your cart is empty.</p>';
                if (checkoutSection) checkoutSection.style.display = 'none';
                return;
            }
            let total = 0;
            cartContainer.innerHTML = items.map(it => {
                total += it.price * it.quantity;
                return `
                    <div class="cart-item">
                        <div class="cart-info">
                            <strong>${it.name}</strong>
                            <div class="muted">Seller: ${it.farmer?.name || 'Farmer'}</div>
                            <div>$${it.price.toFixed(2)} x ${it.quantity} = <strong>$${(it.price * it.quantity).toFixed(2)}</strong></div>
                        </div>
                        <div class="cart-controls">
                            <button class="icon-btn" data-action="cart-dec" data-id="${it.id}"><i class="fa-solid fa-minus"></i></button>
                            <button class="icon-btn" data-action="cart-inc" data-id="${it.id}"><i class="fa-solid fa-plus"></i></button>
                            <button class="icon-btn" data-action="cart-remove" data-id="${it.id}"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                `;
            }).join('') + `
                <div class="cart-total">
                    <div><strong>Total: $${total.toFixed(2)}</strong></div>
                </div>
            `;
            if (checkoutSection) checkoutSection.style.display = 'block';
        }
        function attachEvents() {
            productsContainer?.addEventListener('click', function (e) {
                const btn = e.target.closest('button[data-action]');
                if (!btn) return;
                const id = parseInt(btn.getAttribute('data-id'));
                const action = btn.getAttribute('data-action');
                const product = allProducts.find(p => p.id === id);
                if (!product) return;
                const currentQty = cart[id]?.quantity || 0;

                if (action === 'increment') {
                    if (currentQty < product.stock) {
                        cart[id] = {
                            id: product.id,
                            name: product.name,
                            price: product.price,
                            farmer: product.farmer,
                            quantity: currentQty + 1
                        };
                        saveCart();
                        updateQtyUI(id);
                        renderCart();
                    }
                }
                if (action === 'decrement') {
                    if (currentQty > 0) {
                        const next = currentQty - 1;
                        if (next === 0) delete cart[id]; else cart[id].quantity = next;
                        saveCart();
                        updateQtyUI(id);
                        renderCart();
                    }
                }
            });

            cartContainer?.addEventListener('click', function (e) {
                const btn = e.target.closest('button[data-action]');
                if (!btn) return;
                const id = parseInt(btn.getAttribute('data-id'));
                const action = btn.getAttribute('data-action');
                const product = allProducts.find(p => p.id === id);
                if (!product) return;

                if (action === 'cart-inc') {
                    const cur = cart[id]?.quantity || 0;
                    if (cur < product.stock) {
                        cart[id].quantity = cur + 1;
                        saveCart();
                        updateQtyUI(id);
                        renderCart();
                    }
                }
                if (action === 'cart-dec') {
                    const cur = cart[id]?.quantity || 0;
                    if (cur > 1) {
                        cart[id].quantity = cur - 1;
                    } else {
                        delete cart[id];
                    }
                    saveCart();
                    updateQtyUI(id);
                    renderCart();
                }
                if (action === 'cart-remove') {
                    delete cart[id];
                    saveCart();
                    updateQtyUI(id);
                    renderCart();
                }
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const q = (this.value || '').toLowerCase();
                    const filtered = allProducts.filter(p =>
                        p.name.toLowerCase().includes(q) ||
                        (p.description || '').toLowerCase().includes(q) ||
                        (p.farmer?.name || '').toLowerCase().includes(q)
                    );
                    renderProducts(filtered);
                });
            }
        }
        function updateQtyUI(id) {
            const qtyEl = document.getElementById('qty_' + id);
            if (qtyEl) qtyEl.textContent = (cart[id]?.quantity || 0);
            const decBtn = document.querySelector(`button[data-action="decrement"][data-id="${id}"]`);
            if (decBtn) decBtn.disabled = !(cart[id]?.quantity > 0);

            // Update increment button based on current stock
            const product = allProducts.find(p => p.id === id);
            if (product) {
                const incBtn = document.querySelector(`button[data-action="increment"][data-id="${id}"]`);
                const currentQty = cart[id]?.quantity || 0;
                if (incBtn) {
                    incBtn.disabled = product.stock === 0 || currentQty >= product.stock;
                }
            }
        }

        // Function to fetch and update products
        function fetchProducts() {
            return fetch(API_BASE + 'get_all_products.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        // Update allProducts with fresh data
                        const oldProducts = allProducts;
                        allProducts = data.products || [];

                        // Update cart quantities if stock changed
                        let cartUpdated = false;
                        for (const productId in cart) {
                            const product = allProducts.find(p => p.id === parseInt(productId));
                            if (product) {
                                // If cart quantity exceeds available stock, reduce it
                                if (cart[productId].quantity > product.stock) {
                                    if (product.stock === 0) {
                                        delete cart[productId];
                                    } else {
                                        cart[productId].quantity = product.stock;
                                    }
                                    cartUpdated = true;
                                }
                                // Update product info in cart (price might have changed)
                                cart[productId].price = product.price;
                            }
                        }

                        if (cartUpdated) {
                            saveCart();
                        }

                        // Re-render products with updated stock
                        renderProducts(allProducts);
                        renderCart();

                        // Re-attach events if not already attached
                        if (!eventsAttached) {
                            attachEvents();
                            eventsAttached = true;
                        }

                        return true;
                    } else {
                        console.error('Error loading products:', data.message);
                        return false;
                    }
                })
                .catch(err => {
                    console.error('Network error loading products:', err);
                    return false;
                });
        }

        let eventsAttached = false;
        let refreshInterval = null;

        // Multi-Step Checkout System
        let currentCheckoutStep = 1;
        let checkoutData = {
            cartItems: [],
            shipping: {},
            payment: {},
            total: 0
        };

        // Function to show checkout modal and initialize Step 1
        function showCheckoutModal() {
            const items = Object.values(cart);
            if (items.length === 0) {
                showMessage('Your cart is empty', 'error');
                return;
            }

            // Initialize checkout data
            checkoutData.cartItems = items;
            checkoutData.total = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);

            // Reset to step 1
            currentCheckoutStep = 1;
            showCheckoutStep(1);
            renderCheckoutCart();

            const modal = document.getElementById('checkoutModal');
            if (modal) {
                modal.setAttribute('aria-hidden', 'false');
                modal.style.display = 'flex';
            }
        }

        // Function to show specific checkout step
        function showCheckoutStep(step) {
            // Hide all steps
            for (let i = 1; i <= 4; i++) {
                const stepContent = document.getElementById(`checkout-step-${i}`);
                if (stepContent) {
                    stepContent.style.display = 'none';
                }
                const stepNumber = document.querySelector(`.checkout-step[data-step="${i}"] .step-number`);
                if (stepNumber) {
                    stepNumber.classList.remove('active');
                }
            }

            // Show current step
            const currentStepContent = document.getElementById(`checkout-step-${step}`);
            if (currentStepContent) {
                currentStepContent.style.display = 'block';
            }
            const currentStepNumber = document.querySelector(`.checkout-step[data-step="${step}"] .step-number`);
            if (currentStepNumber) {
                currentStepNumber.classList.add('active');
            }

            currentCheckoutStep = step;

            // If step 4, render review summary
            if (step === 4) {
                renderOrderReview();
            }
        }

        // Render cart in checkout Step 1
        function renderCheckoutCart() {
            const checkoutCartItems = document.getElementById('checkoutCartItems');
            const checkoutTotal = document.getElementById('checkoutTotal');

            if (!checkoutCartItems) return;

            if (checkoutData.cartItems.length === 0) {
                checkoutCartItems.innerHTML = '<p>Your cart is empty</p>';
                return;
            }

            checkoutCartItems.innerHTML = checkoutData.cartItems.map(item => `
                <div class="cart-item" style="margin-bottom: 0.75rem;">
                    <div style="flex: 1;">
                        <strong>${item.name}</strong>
                        <div class="muted">Seller: ${item.farmer_name || 'Unknown'}</div>
                        <div>Quantity: ${item.quantity} x $${item.price.toFixed(2)}</div>
                    </div>
                    <div style="font-weight: 600; color: var(--primary-green);">
                        $${(item.price * item.quantity).toFixed(2)}
                    </div>
                </div>
            `).join('');

            if (checkoutTotal) {
                checkoutTotal.textContent = `$${checkoutData.total.toFixed(2)}`;
            }
        }

        // Render order review summary in Step 4
        function renderOrderReview() {
            const reviewSummary = document.getElementById('orderReviewSummary');
            if (!reviewSummary) return;

            const paymentMethodNames = {
                'ewallet': 'E-Wallet',
                'cash_on_delivery': 'Cash on Delivery',
                'gcash': 'GCash',
                'bank_transfer': 'Bank Transfer'
            };

            const deliveryMethodNames = {
                'standard': 'Standard Delivery (3-5 days)',
                'express': 'Express Delivery (1-2 days)',
                'pickup': 'Store Pickup'
            };

            reviewSummary.innerHTML = `
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <h5 style="margin-bottom: 0.75rem;">Order Items</h5>
                    ${checkoutData.cartItems.map(item => `
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                            <span>${item.name} x ${item.quantity}</span>
                            <strong>$${(item.price * item.quantity).toFixed(2)}</strong>
                        </div>
                    `).join('')}
                    <div style="display: flex; justify-content: space-between; padding-top: 0.5rem; margin-top: 0.5rem; border-top: 2px solid var(--primary-green); font-weight: 600; font-size: 1.1rem;">
                        <span>Total:</span>
                        <span style="color: var(--primary-green);">$${checkoutData.total.toFixed(2)}</span>
                    </div>
                </div>
                
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <h5 style="margin-bottom: 0.75rem;">Shipping Information</h5>
                    <div style="line-height: 1.8;">
                        <div><strong>Name:</strong> ${checkoutData.shipping.fullName || ''}</div>
                        <div><strong>Contact:</strong> ${checkoutData.shipping.contactNumber || ''}</div>
                        <div><strong>Address:</strong> ${checkoutData.shipping.deliveryAddress || ''}</div>
                        <div><strong>Method:</strong> ${deliveryMethodNames[checkoutData.shipping.deliveryMethod] || ''}</div>
                    </div>
                </div>

                <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                    <h5 style="margin-bottom: 0.75rem;">Payment Method</h5>
                    <div style="font-size: 1.1rem; color: var(--primary-green); font-weight: 600;">
                        ${paymentMethodNames[checkoutData.payment.method] || ''}
                    </div>
                </div>
            `;
        }

        // Function to validate and proceed to next step
        function validateAndProceedToStep(step) {
            if (currentCheckoutStep === 1) {
                // Validate cart has items
                if (checkoutData.cartItems.length === 0) {
                    showMessage('Your cart is empty', 'error');
                    return false;
                }
            } else if (currentCheckoutStep === 2) {
                // Validate shipping form
                const fullName = document.getElementById('fullName')?.value.trim();
                const contactNumber = document.getElementById('contactNumber')?.value.trim();
                const deliveryAddress = document.getElementById('deliveryAddress')?.value.trim();
                const deliveryMethod = document.getElementById('deliveryMethod')?.value;

                if (!fullName || !contactNumber || !deliveryAddress || !deliveryMethod) {
                    showMessage('Please fill in all shipping information fields', 'error');
                    return false;
                }

                checkoutData.shipping = {
                    fullName,
                    contactNumber,
                    deliveryAddress,
                    deliveryMethod
                };
            } else if (currentCheckoutStep === 3) {
                // Validate payment method
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
                if (!paymentMethod) {
                    showMessage('Please select a payment method', 'error');
                    return false;
                }
                checkoutData.payment.method = paymentMethod;
            }

            showCheckoutStep(step);
            return true;
        }

        // Function to place order
        async function confirmAndPlaceOrder() {
            const confirmBtn = document.getElementById('confirmOrderBtn');
            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Placing Order...';
            }

            try {
                // Validate shipping data is complete
                if (!checkoutData.shipping.fullName || !checkoutData.shipping.contactNumber || 
                    !checkoutData.shipping.deliveryAddress || !checkoutData.shipping.deliveryMethod) {
                    showMessage('Please complete all shipping information', 'error');
                    if (confirmBtn) {
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = '<i class="fa-solid fa-check"></i> Place Order';
                    }
                    return;
                }

                // Validate payment method is selected
                if (!checkoutData.payment.method) {
                    showMessage('Please select a payment method', 'error');
                    if (confirmBtn) {
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = '<i class="fa-solid fa-check"></i> Place Order';
                    }
                    return;
                }

                const cartItems = checkoutData.cartItems.map(item => ({
                    product_id: item.id,
                    quantity: item.quantity,
                    unit_price: item.price
                }));

                // Format delivery address from shipping info
                const deliveryAddress = `${checkoutData.shipping.fullName}\n${checkoutData.shipping.contactNumber}\n${checkoutData.shipping.deliveryAddress}`;

                const response = await fetch(API_BASE + 'create_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        cart_items: cartItems,
                        delivery_address: deliveryAddress,
                        payment_method: checkoutData.payment.method
                    })
                });

                // Parse response
                const responseText = await response.text();
                
                // Check if response is ok
                if (!response.ok) {
                    let errorData;
                    try {
                        errorData = JSON.parse(responseText);
                    } catch (e) {
                        errorData = { message: responseText || 'Server error occurred' };
                    }
                    throw new Error(errorData.message || 'Failed to place order');
                }

                // Parse JSON response
                if (!responseText || responseText.trim() === '') {
                    throw new Error('Empty response from server');
                }
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error('Failed to parse response:', responseText);
                    throw new Error('Invalid response from server');
                }

                if (result.success) {
                    // Clear cart
                    cart = {};
                    saveCart();
                    renderCart();

                    // Close checkout modal
                    const checkoutModal = document.getElementById('checkoutModal');
                    if (checkoutModal) {
                        checkoutModal.setAttribute('aria-hidden', 'true');
                        checkoutModal.style.display = 'none';
                    }

                    // Show order confirmation
                    const orderId = result.orders && result.orders[0] ? result.orders[0].order_id : result.message.match(/Order #?(\d+)/i)?.[1] || 'N/A';
                    showOrderConfirmation(orderId);

                    // Refresh products and orders
                    await fetchProducts();
                    if (typeof fetchOrders === 'function') {
                        fetchOrders();
                    }
                } else {
                    // Handle error response
                    let errorMessage = result.message || 'Unknown error';
                    showMessage(`Failed to place order: ${errorMessage}`, 'error');
                    if (confirmBtn) {
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = '<i class="fa-solid fa-check"></i> Place Order';
                    }
                }
            } catch (error) {
                console.error('Error placing order:', error);
                
                // Show user-friendly error message
                const errorMessage = error.message || 'Network error. Please try again.';
                showMessage(errorMessage, 'error');
                
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="fa-solid fa-check"></i> Place Order';
                }
            }
        }

        // Function to show order confirmation
        function showOrderConfirmation(orderId) {
            const confirmationModal = document.getElementById('orderConfirmationModal');
            const orderNumberDisplay = document.getElementById('orderNumberDisplay');

            if (orderNumberDisplay) {
                orderNumberDisplay.textContent = `#${orderId}`;
            }

            if (confirmationModal) {
                confirmationModal.setAttribute('aria-hidden', 'false');
                confirmationModal.style.display = 'flex';
            }
        }

        // Attach checkout button events
        const proceedToCheckoutBtn = document.getElementById('proceedToCheckoutBtn');
        if (proceedToCheckoutBtn) {
            proceedToCheckoutBtn.addEventListener('click', showCheckoutModal);
        }

        // Step navigation buttons
        const nextToShippingBtn = document.getElementById('nextToShippingBtn');
        if (nextToShippingBtn) {
            nextToShippingBtn.addEventListener('click', () => validateAndProceedToStep(2));
        }

        const backToCartBtn = document.getElementById('backToCartBtn');
        if (backToCartBtn) {
            backToCartBtn.addEventListener('click', () => showCheckoutStep(1));
        }

        const nextToPaymentBtn = document.getElementById('nextToPaymentBtn');
        if (nextToPaymentBtn) {
            nextToPaymentBtn.addEventListener('click', () => validateAndProceedToStep(3));
        }

        const backToShippingBtn = document.getElementById('backToShippingBtn');
        if (backToShippingBtn) {
            backToShippingBtn.addEventListener('click', () => showCheckoutStep(2));
        }

        const nextToReviewBtn = document.getElementById('nextToReviewBtn');
        if (nextToReviewBtn) {
            nextToReviewBtn.addEventListener('click', () => validateAndProceedToStep(4));
        }

        const backToPaymentBtn = document.getElementById('backToPaymentBtn');
        if (backToPaymentBtn) {
            backToPaymentBtn.addEventListener('click', () => showCheckoutStep(3));
        }

        const confirmOrderBtn = document.getElementById('confirmOrderBtn');
        if (confirmOrderBtn) {
            confirmOrderBtn.addEventListener('click', confirmAndPlaceOrder);
        }

        // Close checkout modal
        const closeCheckoutModal = document.getElementById('closeCheckoutModal');
        const cancelCheckoutBtn = document.getElementById('cancelCheckoutBtn');
        if (closeCheckoutModal) {
            closeCheckoutModal.addEventListener('click', () => {
                const modal = document.getElementById('checkoutModal');
                if (modal) {
                    modal.setAttribute('aria-hidden', 'true');
                    modal.style.display = 'none';
                }
            });
        }
        if (cancelCheckoutBtn) {
            cancelCheckoutBtn.addEventListener('click', () => {
                const modal = document.getElementById('checkoutModal');
                if (modal) {
                    modal.setAttribute('aria-hidden', 'true');
                    modal.style.display = 'none';
                }
            });
        }

        // Order confirmation modal buttons
        const viewOrdersBtn = document.getElementById('viewOrdersBtn');
        const continueShoppingBtn = document.getElementById('continueShoppingBtn');
        if (viewOrdersBtn) {
            viewOrdersBtn.addEventListener('click', () => {
                const confirmationModal = document.getElementById('orderConfirmationModal');
                if (confirmationModal) {
                    confirmationModal.setAttribute('aria-hidden', 'true');
                    confirmationModal.style.display = 'none';
                }
                // Redirect to orders page
                window.location.href = 'buyer_orders.html';
            });
        }
        if (continueShoppingBtn) {
            continueShoppingBtn.addEventListener('click', () => {
                const confirmationModal = document.getElementById('orderConfirmationModal');
                if (confirmationModal) {
                    confirmationModal.setAttribute('aria-hidden', 'true');
                    confirmationModal.style.display = 'none';
                }
            });
        }

        // Function to calculate time remaining
        function getTimeRemaining(shippedAt, estimatedTime) {
            if (!shippedAt || !estimatedTime) return null;
            const shipped = new Date(shippedAt);
            const deliveryTime = new Date(shipped.getTime() + estimatedTime * 60000);
            const now = new Date();
            const remaining = deliveryTime - now;

            if (remaining <= 0) return 'Arrived';

            const hours = Math.floor(remaining / 3600000);
            const minutes = Math.floor((remaining % 3600000) / 60000);

            if (hours > 0) {
                return `${hours}h ${minutes}m remaining`;
            } else {
                return `${minutes}m remaining`;
            }
        }

        // Function to fetch and display orders
        async function fetchOrders() {
            const ordersList = document.getElementById('ordersList');
            if (!ordersList) return;

            try {
                const response = await fetch(API_BASE + 'get_orders.php');

                // Check if response is ok
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('API Error:', response.status, errorText);
                    ordersList.innerHTML = `<p style="color: #dc3545">Error loading orders (${response.status}). Please check the console for details.</p>`;
                    return;
                }

                const result = await response.json();

                if (result.success) {
                    const orders = result.orders || [];
                    if (orders.length === 0) {
                        ordersList.innerHTML = '<p style="color: var(--text-light)">No orders found.</p>';
                        return;
                    }

                    // Check if any order has enhanced features
                    const hasEnhanced = orders.length > 0 && orders[0].payment_method !== undefined;

                    // Group orders by status for better organization
                    const groupedOrders = {
                        'active': [], // To Ship, Shipped
                        'pending_payment': [], // Unpaid orders
                        'completed': [], // Delivered
                        'other': [] // Cancelled, etc.
                    };

                    orders.forEach(order => {
                        const orderHasEnhanced = order.payment_method !== undefined;
                        if (orderHasEnhanced) {
                            if (order.shipping_status === 'delivered') {
                                groupedOrders.completed.push(order);
                            } else if (order.payment_status === 'unpaid' && order.shipping_status === 'to_ship') {
                                groupedOrders.pending_payment.push(order);
                            } else if (order.shipping_status === 'to_ship' || order.shipping_status === 'shipped') {
                                groupedOrders.active.push(order);
                            } else {
                                groupedOrders.other.push(order);
                            }
                        } else {
                            // Legacy orders
                            if (order.status === 'completed') {
                                groupedOrders.completed.push(order);
                            } else if (order.status === 'pending') {
                                groupedOrders.pending_payment.push(order);
                            } else {
                                groupedOrders.active.push(order);
                            }
                        }
                    });

                    // Build organized HTML
                    let ordersHTML = '';

                    // Active Orders (To Ship / Shipped)
                    if (groupedOrders.active.length > 0) {
                        ordersHTML += `
                            <div class="order-group" style="margin-bottom: 2rem;">
                            <div class="order-group-header" style="background: var(--primary-green); color: white; padding: 0.75rem 1rem; border-radius: 8px 8px 0 0; font-weight: 600; margin-bottom: 0;">
                                <i class="fa-solid fa-truck-fast"></i> Active Orders (${groupedOrders.active.length})
                            </div>
                            <div class="order-group-content" style="background: #f8f9fa; padding: 1rem; border-radius: 0 0 8px 8px;">
                                ${groupedOrders.active.map(order => renderOrderCard(order, hasEnhanced, user)).join('')}
                            </div>
                        </div>`;
                    }

                    // Pending Payment Orders
                    if (groupedOrders.pending_payment.length > 0) {
                        ordersHTML += `
                            <div class="order-group" style="margin-bottom: 2rem;">
                            <div class="order-group-header" style="background: #ffc107; color: #333; padding: 0.75rem 1rem; border-radius: 8px 8px 0 0; font-weight: 600; margin-bottom: 0;">
                                <i class="fa-solid fa-credit-card"></i> Pending Payment (${groupedOrders.pending_payment.length})
                            </div>
                            <div class="order-group-content" style="background: #fffbf0; padding: 1rem; border-radius: 0 0 8px 8px;">
                                ${groupedOrders.pending_payment.map(order => renderOrderCard(order, hasEnhanced, user)).join('')}
                            </div>
                        </div>`;
                    }

                    // Completed Orders
                    if (groupedOrders.completed.length > 0) {
                        ordersHTML += `
                            <div class="order-group" style="margin-bottom: 2rem;">
                            <div class="order-group-header" style="background: #28a745; color: white; padding: 0.75rem 1rem; border-radius: 8px 8px 0 0; font-weight: 600; margin-bottom: 0;">
                                <i class="fa-solid fa-check-circle"></i> Completed Orders (${groupedOrders.completed.length})
                            </div>
                            <div class="order-group-content" style="background: #f0fff4; padding: 1rem; border-radius: 0 0 8px 8px;">
                                ${groupedOrders.completed.map(order => renderOrderCard(order, hasEnhanced, user)).join('')}
                            </div>
                        </div>`;
                    }

                    // Other Orders
                    if (groupedOrders.other.length > 0) {
                        ordersHTML += `
                            <div class="order-group" style="margin-bottom: 2rem;">
                            <div class="order-group-header" style="background: #6c757d; color: white; padding: 0.75rem 1rem; border-radius: 8px 8px 0 0; font-weight: 600; margin-bottom: 0;">
                                <i class="fa-solid fa-list"></i> Other Orders (${groupedOrders.other.length})
                            </div>
                            <div class="order-group-content" style="background: #f8f9fa; padding: 1rem; border-radius: 0 0 8px 8px;">
                                ${groupedOrders.other.map(order => renderOrderCard(order, hasEnhanced, user)).join('')}
                            </div>
                        </div>`;
                    }

                    ordersList.innerHTML = ordersHTML;

                    // Helper function to render individual order card
                    function renderOrderCard(order) {
                        const orderHasEnhanced = order.payment_method !== undefined;
                        const counterpartyLabel = user?.user_type === 'buyer' ? 'Seller' : 'Buyer';

                        // Payment status badge
                        let paymentBadge = '';
                        if (orderHasEnhanced) {
                            const paymentStatusClass = order.payment_status === 'paid' ? 'in-stock' : 'low-stock';
                            const paymentText = order.payment_status === 'paid' ? 'Paid' : 'Unpaid';
                            paymentBadge = `<span class="stock ${paymentStatusClass}" style="margin-left: 0.5rem;">${paymentText}</span>`;
                        }

                        // Shipping status and countdown
                        let shippingInfo = '';
                        if (orderHasEnhanced && order.shipping_status) {
                            const shippingStatusClass = {
                                'to_ship': 'low-stock',
                                'shipped': 'in-stock',
                                'delivered': 'in-stock'
                            }[order.shipping_status] || 'low-stock';

                            const shippingText = {
                                'to_ship': 'To Ship',
                                'shipped': 'Shipped',
                                'delivered': 'Delivered'
                            }[order.shipping_status] || order.shipping_status;

                            shippingInfo = `<div style="margin-top: 0.5rem;">
                                Shipping: <span class="stock ${shippingStatusClass}">${shippingText}</span>`;

                            // Show countdown if shipped
                            if (order.shipping_status === 'shipped' && order.shipped_at && order.estimated_delivery_time) {
                                const timeRemaining = getTimeRemaining(order.shipped_at, order.estimated_delivery_time);
                                if (timeRemaining) {
                                    shippingInfo += ` <span style="color: #28a745; font-weight: 600;">(${timeRemaining})</span>`;
                                }
                            }

                            shippingInfo += `</div>`;

                            // Delivery info
                            if (order.delivery_distance > 0) {
                                shippingInfo += `<div class="muted" style="font-size: 0.85rem;">Distance: ${order.delivery_distance}km</div>`;
                            }
                        }

                        // Review section (only for buyers with delivered orders)
                        let reviewSection = '';
                        if (user?.user_type === 'buyer' && orderHasEnhanced && order.shipping_status === 'delivered') {
                            if (order.review_rating) {
                                // Show existing review
                                const stars = '⭐'.repeat(order.review_rating);
                                reviewSection = `<div style="margin-top: 0.5rem; padding: 0.75rem; background: #f8f9fa; border-radius: 4px;">
                                    <div><strong>Your Review:</strong> ${stars} (${order.review_rating}/5)</div>
                                    ${order.review_comment ? `<div class="muted" style="margin-top: 0.25rem;">${order.review_comment}</div>` : ''}
                                </div>`;
                            } else {
                                // Show review button
                                reviewSection = `<div style="margin-top: 0.5rem;">
                                    <button class="btn btn-secondary" onclick="openReviewModal(${order.id})" style="font-size: 0.85rem;">
                                        <i class="fa-solid fa-star"></i> Write Review
                                    </button>
                                </div>`;
                            }
                        }

                        // Legacy status (if no enhanced features)
                        let statusBadge = '';
                        if (!orderHasEnhanced) {
                            const statusClass = {
                                'pending': 'low-stock',
                                'confirmed': 'in-stock',
                                'completed': 'in-stock',
                                'cancelled': 'out-of-stock'
                            }[order.status] || '';
                            statusBadge = `<div style="margin-top: 0.5rem;">
                                Status: <span class="stock ${statusClass}">${order.status.toUpperCase()}</span>
                            </div>`;
                        }

                        return `
                            <div class="order-card" style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 2px solid #eee;">
                                    <div>
                                        <div style="font-size: 0.75rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem;">Order Number</div>
                                        <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary-green);">#${order.id}</div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 0.75rem; color: #666; margin-bottom: 0.25rem;">Order Date</div>
                                        <div style="font-size: 0.85rem; font-weight: 500;">${new Date(order.created_at).toLocaleDateString()}</div>
                                    </div>
                                </div>
                                
                                <div style="margin-bottom: 1rem;">
                                    <div style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-dark);">
                                        ${order.product_name}
                                    </div>
                                    <div class="muted" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                        <i class="fa-solid fa-user"></i> ${counterpartyLabel}: <strong>${order.counterparty?.name || 'Unknown'}</strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f8f9fa; border-radius: 6px; margin-bottom: 0.5rem;">
                                        <span style="font-size: 0.9rem;">Quantity: <strong>${order.quantity}</strong> x $${order.unit_price.toFixed(2)}</span>
                                        <span style="font-size: 1.1rem; font-weight: 700; color: var(--primary-green);">$${order.total_price.toFixed(2)}</span>
                                    </div>
                                    ${paymentBadge ? `<div style="margin-bottom: 0.5rem;">${paymentBadge}</div>` : ''}
                                    ${orderHasEnhanced && order.payment_method ? `<div class="muted" style="font-size: 0.85rem; margin-bottom: 0.5rem;">
                                        <i class="fa-solid fa-wallet"></i> Payment: ${order.payment_method === 'ewallet' ? 'E-Wallet' : order.payment_method === 'gcash' ? 'GCash' : order.payment_method === 'bank_transfer' ? 'Bank Transfer' : 'Cash on Delivery'}
                                    </div>` : ''}
                                </div>

                                ${shippingInfo ? `<div style="margin-bottom: 1rem; padding: 0.75rem; background: #e7f5e7; border-left: 4px solid var(--primary-green); border-radius: 4px;">
                                    ${shippingInfo}
                                </div>` : ''}

                                ${statusBadge ? `<div style="margin-bottom: 1rem;">${statusBadge}</div>` : ''}

                                ${order.delivery_address && orderHasEnhanced ? `<div style="margin-bottom: 1rem; padding: 0.75rem; background: #f0f8ff; border-left: 4px solid #007bff; border-radius: 4px;">
                                    <div style="font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem; color: #007bff;">
                                        <i class="fa-solid fa-location-dot"></i> Delivery Address
                                    </div>
                                    <div style="font-size: 0.85rem; color: #666;">${order.delivery_address}</div>
                                </div>` : ''}

                                ${reviewSection ? `<div style="margin-top: 1rem;">${reviewSection}</div>` : ''}

                                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #eee; font-size: 0.8rem; color: #999;">
                                    <i class="fa-solid fa-clock"></i> Placed on ${new Date(order.created_at).toLocaleString()}
                                </div>
                            </div>
                        `;
                    }

                    // Start countdown timer for shipped orders (only once)
                    const anyOrderHasEnhanced = orders.some(order => order.payment_method !== undefined);
                    if (anyOrderHasEnhanced && !window.countdownInterval) {
                        window.countdownInterval = setInterval(() => {
                            const hasShippedOrders = orders.some(order =>
                                order.shipping_status === 'shipped' && order.shipped_at
                            );
                            if (hasShippedOrders) {
                                fetchOrders(); // Refresh to update countdown
                            }
                        }, 60000); // Update every minute
                    }
                } else {
                    ordersList.innerHTML = '<p style="color: var(--text-light)">' + (result.message || 'No orders found.') + '</p>';
                }
            } catch (error) {
                console.error('Error fetching orders:', error);
                const errorMsg = error.message || 'Unknown error';
                ordersList.innerHTML = `<p style="color: #dc3545">
                    Error loading orders. <br>
                    <small style="font-size: 0.85rem;">${errorMsg}</small>
                    <br><button onclick="location.reload()" class="btn btn-secondary" style="margin-top: 0.5rem; font-size: 0.85rem;">Refresh Page</button>
                </p>`;
            }
        }

        // Review modal functions
        function openReviewModal(orderId) {
            const modal = document.getElementById('reviewModal');
            const orderIdInput = document.getElementById('reviewOrderId');
            if (orderIdInput) orderIdInput.value = orderId;
            if (modal) {
                modal.setAttribute('aria-hidden', 'false');
                modal.style.display = 'flex';
                // Reset stars
                resetStars();
            }
        }

        function resetStars() {
            const stars = document.querySelectorAll('#ratingStars i');
            stars.forEach(star => {
                star.classList.remove('fa-solid');
                star.classList.add('fa-regular');
            });
            document.getElementById('reviewRating').value = 0;
        }

        // Star rating interaction
        const ratingStars = document.getElementById('ratingStars');
        if (ratingStars) {
            ratingStars.addEventListener('click', (e) => {
                const star = e.target.closest('[data-rating]');
                if (!star) return;
                const rating = parseInt(star.getAttribute('data-rating'));
                if (isNaN(rating) || rating < 1 || rating > 5) return;

                const ratingInput = document.getElementById('reviewRating');
                if (ratingInput) {
                    ratingInput.value = rating;
                }

                const stars = ratingStars.querySelectorAll('[data-rating]');
                stars.forEach((s) => {
                    const starRating = parseInt(s.getAttribute('data-rating'));
                    if (starRating <= rating) {
                        s.classList.remove('fa-regular');
                        s.classList.add('fa-solid');
                    } else {
                        s.classList.remove('fa-solid');
                        s.classList.add('fa-regular');
                    }
                });
            });
        }

        // Submit review
        const reviewForm = document.getElementById('reviewForm');
        if (reviewForm) {
            reviewForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const orderId = document.getElementById('reviewOrderId').value;
                const ratingInput = document.getElementById('reviewRating');
                const rating = ratingInput ? parseInt(ratingInput.value) : 0;
                const commentInput = document.getElementById('reviewComment');
                const comment = commentInput ? commentInput.value.trim() : '';

                if (!rating || isNaN(rating) || rating < 1 || rating > 5) {
                    showMessage('Please select a rating', 'error');
                    return;
                }

                try {
                    const response = await fetch(API_BASE + 'submit_review.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            order_id: orderId,
                            rating: rating,
                            comment: comment
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        const modal = document.getElementById('reviewModal');
                        if (modal) {
                            modal.setAttribute('aria-hidden', 'true');
                            modal.style.display = 'none';
                        }
                        document.getElementById('reviewComment').value = '';
                        resetStars();
                        fetchOrders(); // Refresh orders
                        showMessage('Review submitted successfully!', 'success');
                    } else {
                        showMessage('Failed to submit review: ' + (result.message || 'Unknown error'), 'error');
                    }
                } catch (error) {
                    console.error('Error submitting review:', error);
                    showMessage('Network error. Please try again.', 'error');
                }
            });
        }

        // Close review modal
        const closeReviewModal = document.getElementById('closeReviewModal');
        const cancelReviewBtn = document.getElementById('cancelReviewBtn');
        if (closeReviewModal) {
            closeReviewModal.addEventListener('click', () => {
                const modal = document.getElementById('reviewModal');
                if (modal) {
                    modal.setAttribute('aria-hidden', 'true');
                    modal.style.display = 'none';
                }
                resetStars();
                document.getElementById('reviewComment').value = '';
            });
        }
        if (cancelReviewBtn) {
            cancelReviewBtn.addEventListener('click', () => {
                const modal = document.getElementById('reviewModal');
                if (modal) {
                    modal.setAttribute('aria-hidden', 'true');
                    modal.style.display = 'none';
                }
                resetStars();
                document.getElementById('reviewComment').value = '';
            });
        }

        // Make openReviewModal globally accessible
        window.openReviewModal = openReviewModal;

        // Handle product image error
        window.handleProductImageError = function (imageId) {
            const img = document.getElementById(imageId);
            if (img) {
                img.style.display = 'none';
                const imageWrap = img.parentElement;
                if (imageWrap) {
                    const overlay = imageWrap.querySelector('.image-overlay');
                    if (overlay) overlay.style.display = 'none';

                    const errorDiv = imageWrap.querySelector('.image-error');
                    if (errorDiv) {
                        errorDiv.style.display = 'flex';
                        errorDiv.className = 'product-image placeholder';
                    } else {
                        imageWrap.innerHTML = '<div class="product-image placeholder"></div>';
                    }
                }
            }
        };

        // View product image in full size modal
        window.viewProductImage = function (imageUrl, productName) {
            // Create modal if it doesn't exist
            let modal = document.getElementById('productImageModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'productImageModal';
                modal.className = 'modal';
                modal.setAttribute('aria-hidden', 'true');
                modal.innerHTML = `
                    <div class="modal-content" style="max-width: 90vw; max-height: 90vh; background: transparent; box-shadow: none; padding: 0;">
                        <div style="position: relative; background: var(--white); border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.4);">
                            <button class="icon-btn" id="closeImageModal" style="position: absolute; top: 15px; right: 15px; background: rgba(0,0,0,0.6); color: white; z-index: 1000; border-radius: 50%; width: 44px; height: 44px; backdrop-filter: blur(10px);" aria-label="Close" title="Close (ESC)">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                            <img id="modalProductImage" src="" alt="" style="max-width: 90vw; max-height: 85vh; display: block; margin: 0 auto; object-fit: contain;">
                            <div style="padding: 1.5rem; background: var(--white); text-align: center; border-top: 1px solid var(--border-color);">
                                <h3 id="modalProductTitle" style="margin: 0; color: var(--text-dark); font-size: 1.25rem;"></h3>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);

                // Close modal handlers
                const closeBtn = document.getElementById('closeImageModal');
                if (closeBtn) {
                    closeBtn.addEventListener('click', () => closeImageModal());
                }

                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        closeImageModal();
                    }
                });

                // Close on Escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && modal.classList.contains('show')) {
                        closeImageModal();
                    }
                });
            }

            // Set image and title
            const modalImage = document.getElementById('modalProductImage');
            const modalTitle = document.getElementById('modalProductTitle');
            if (modalImage) {
                modalImage.src = imageUrl;
                modalImage.alt = productName;
            }
            if (modalTitle) modalTitle.textContent = productName;

            // Show modal
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };

        function closeImageModal() {
            const modal = document.getElementById('productImageModal');
            if (modal) {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = 'auto';
            }
        }

        // Initial fetch
        fetchProducts().then(success => {
            if (!success) {
                productsContainer.innerHTML = '<p style="color:#dc3545">Network error loading products.</p>';
            }
        });

        // Fetch orders on page load
        fetchOrders();

        // Set up periodic refresh every 30 seconds to update stock quantities
        refreshInterval = setInterval(() => {
            fetchProducts();
        }, 30000); // Refresh every 30 seconds

        // Clean up interval when page is hidden (optional optimization)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                    refreshInterval = null;
                }
            } else {
                if (!refreshInterval) {
                    refreshInterval = setInterval(() => {
                        fetchProducts();
                    }, 30000);
                }
                // Also refresh immediately when page becomes visible
                fetchProducts();
            }
        });
    }

    // Handle forgot password page with OTP flow
    function handleForgotPasswordPage() {
        if (document.body.getAttribute('data-page') !== 'forgot-password') return;

        // Step 1: Request OTP
        const form = document.getElementById('forgotPasswordForm');
        const verifyForm = document.getElementById('verifyOTPForm');
        const resetForm = document.getElementById('resetPasswordForm');
        const resendOTP = document.getElementById('resendOTP');

        if (form) {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();

                const emailInput = document.getElementById('email');
                const usernameInput = document.getElementById('username');
                const emailError = document.getElementById('emailError');
                const usernameError = document.getElementById('usernameError');

                showError(emailError, '');
                showError(usernameError, '');

                const email = emailInput.value.trim();
                const username = usernameInput.value.trim();

                let valid = true;
                if (!email) {
                    showError(emailError, 'Email is required');
                    valid = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    showError(emailError, 'Enter a valid email');
                    valid = false;
                }
                if (!username) {
                    showError(usernameError, 'Username is required');
                    valid = false;
                }

                if (!valid) return;

                try {
                    const result = await apiCall(ENDPOINTS.sendOTP, {
                        email: email,
                        username: username
                    }, 'POST');

                    if (result.success) {
                        // Store user_id for next step (ensure it's stored)
                        const userId = result.user_id;
                        if (!userId) {
                            showMessage('Error: User ID not received. Please try again.', 'error');
                            return;
                        }
                        document.getElementById('userId').value = userId;
                        console.log('User ID stored:', userId);

                        // Hide request section, show verify section
                        document.getElementById('requestOTPSection').style.display = 'none';
                        document.getElementById('verifyOTPSection').style.display = 'block';

                        // Show message (OTP code auto-fill removed per user request)
                        let message = 'Verification code sent to ' + email + '. Please check your inbox and spam folder.';
                        showMessage(message, 'success');
                    } else {
                        let errorMsg = result.error || 'Failed to send verification code';
                        
                        // Still show verify section if user_id is available
                        if (result.user_id) {
                            document.getElementById('userId').value = result.user_id || '';
                            document.getElementById('requestOTPSection').style.display = 'none';
                            document.getElementById('verifyOTPSection').style.display = 'block';
                        }
                        
                        showMessage(errorMsg, 'error');
                    }

                } catch (error) {
                    showMessage(error.message || 'Failed to send verification code', 'error');
                }
            });
        }

        // Step 2: Verify OTP
        if (verifyForm) {
            verifyForm.addEventListener('submit', async function (e) {
                e.preventDefault();

                const userId = document.getElementById('userId').value;
                let otpCode = document.getElementById('otpCode').value.trim();
                const otpError = document.getElementById('otpError');

                showError(otpError, '');

                if (!userId || userId === '') {
                    showError(otpError, 'User ID is missing. Please request a new verification code.');
                    return;
                }

                // Normalize OTP code - remove any non-digit characters and pad with leading zeros if needed
                otpCode = otpCode.replace(/\D/g, ''); // Remove all non-digit characters
                
                if (!otpCode || otpCode.length === 0) {
                    showError(otpError, 'Enter a valid 6-digit code');
                    return;
                }
                
                // Pad with leading zeros to ensure exactly 6 digits
                otpCode = otpCode.padStart(6, '0');
                
                if (otpCode.length !== 6) {
                    showError(otpError, 'Code must be exactly 6 digits');
                    return;
                }

                // Ensure OTP code is exactly 6 digits
                if (!/^\d{6}$/.test(otpCode)) {
                    showError(otpError, 'Code must be exactly 6 digits');
                    return;
                }

                console.log('Verifying OTP - User ID:', userId, 'OTP Code (normalized):', otpCode);

                try {
                    const result = await apiCall(ENDPOINTS.verifyOTP, {
                        user_id: parseInt(userId, 10), // Ensure it's an integer
                        otp_code: otpCode // Send normalized code
                    }, 'POST');

                    if (result.success) {
                        // Store reset token for next step
                        if (result.reset_token) {
                            document.getElementById('resetToken').value = result.reset_token;
                            console.log('Reset token stored:', result.reset_token.substring(0, 10) + '... (length: ' + result.reset_token.length + ')');
                            
                            // Verify token is stored
                            const storedToken = document.getElementById('resetToken').value;
                            if (storedToken !== result.reset_token) {
                                console.error('Token storage failed! Expected:', result.reset_token.substring(0, 10), 'Got:', storedToken.substring(0, 10));
                                showError(otpError, 'Failed to store reset token. Please try again.');
                                return;
                            }
                        } else {
                            console.error('No reset token received from server! Response:', result);
                            showError(otpError, 'Failed to get reset token. Please try again.');
                            return;
                        }

                        // Hide verify section, show reset password section
                        document.getElementById('verifyOTPSection').style.display = 'none';
                        document.getElementById('resetPasswordSection').style.display = 'block';
                        
                        // Clear any previous password inputs
                        document.getElementById('newPassword').value = '';
                        document.getElementById('confirmPassword').value = '';
                        
                        // Enable password toggles for reset password form
                        setTimeout(() => enablePasswordToggle(['newPassword', 'confirmPassword']), 100);
                        
                        // Focus on new password field
                        document.getElementById('newPassword').focus();
                    }

                } catch (error) {
                    showError(otpError, error.message || 'Invalid verification code');
                }
            });

            // Resend OTP
            if (resendOTP) {
                resendOTP.addEventListener('click', async function () {
                    // Get email and username from the request section (they should still be accessible)
                    let emailInput = document.querySelector('#requestOTPSection input[name="email"]');
                    let usernameInput = document.querySelector('#requestOTPSection input[name="username"]');
                    
                    let email = emailInput ? emailInput.value.trim() : '';
                    let username = usernameInput ? usernameInput.value.trim() : '';

                    if (!email || !username) {
                        // Try alternative selectors (direct ID)
                        const altEmail = document.getElementById('email');
                        const altUsername = document.getElementById('username');
                        
                        if (altEmail && altUsername) {
                            email = altEmail.value.trim();
                            username = altUsername.value.trim();
                        }
                        
                        if (!email || !username) {
                            showError(otpError, 'Email and username are required. Please go back to request a new code.');
                            return;
                        }
                    }

                    // Disable button during request
                    resendOTP.disabled = true;
                    const originalText = resendOTP.innerHTML;
                    resendOTP.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';

                    try {
                        const result = await apiCall(ENDPOINTS.sendOTP, {
                            email: email,
                            username: username
                        }, 'POST');

                        // Check if request was successful
                        if (result.success !== false) {
                            // Update user_id if received
                            if (result.user_id) {
                                document.getElementById('userId').value = result.user_id;
                                console.log('User ID updated on resend:', result.user_id);
                            }

                            // Clear OTP input field and any errors
                            const otpInput = document.getElementById('otpCode');
                            if (otpInput) {
                                otpInput.value = '';
                            }
                            showError(otpError, '');
                            
                            let message = 'New verification code sent to ' + email + '! Please check your inbox and spam folder.';
                            if (result.otp_code) {
                                console.log('New OTP code (for testing):', result.otp_code);
                            }
                            showMessage(message, 'success');
                        } else {
                            // API returned success: false
                            const errorMsg = result.error || result.message || 'Failed to resend code. Please try again.';
                            showError(otpError, errorMsg);
                            console.error('Resend OTP failed:', result);
                        }
                    } catch (error) {
                        console.error('Resend OTP error:', error);
                        showError(otpError, error.message || 'Failed to resend code. Please try again.');
                    } finally {
                        // Re-enable button
                        resendOTP.disabled = false;
                        resendOTP.innerHTML = originalText;
                    }
                });
            }
        }

        // Step 3: Reset Password
        if (resetForm) {
            // Add password toggle functionality
            setTimeout(() => enablePasswordToggle(['newPassword', 'confirmPassword']), 100);
            
            resetForm.addEventListener('submit', async function (e) {
                e.preventDefault();

                const resetToken = document.getElementById('resetToken').value;
                const newPassword = document.getElementById('newPassword').value.trim();
                const confirmPassword = document.getElementById('confirmPassword').value.trim();
                const newPasswordError = document.getElementById('newPasswordError');
                const confirmPasswordError = document.getElementById('confirmPasswordError');

                showError(newPasswordError, '');
                showError(confirmPasswordError, '');

                let valid = true;
                if (!newPassword || newPassword.length < 6) {
                    showError(newPasswordError, 'Password must be at least 6 characters long');
                    valid = false;
                }
                if (newPassword !== confirmPassword) {
                    showError(confirmPasswordError, 'Passwords do not match');
                    valid = false;
                }

                if (!valid) return;

                // Validate reset token exists
                if (!resetToken || resetToken === '') {
                    showError(newPasswordError, 'Reset token is missing. Please verify your code again.');
                    console.error('Reset token is missing! Token value:', resetToken);
                    console.error('Hidden field value:', document.getElementById('resetToken').value);
                    return;
                }

                // Validate token format (should be 64 character hex string)
                if (!/^[a-f0-9]{64}$/i.test(resetToken)) {
                    showError(newPasswordError, 'Invalid reset token format. Please verify your code again.');
                    console.error('Invalid token format! Token length:', resetToken.length, 'Token preview:', resetToken.substring(0, 20) + '...');
                    console.error('Token value:', resetToken);
                    return;
                }

                console.log('Resetting password with token:', resetToken.substring(0, 10) + '... (length: ' + resetToken.length + ')');

                try {
                    // Log the data being sent
                    console.log('Sending reset password request:', {
                        token: resetToken.substring(0, 10) + '... (length: ' + resetToken.length + ')',
                        hasPassword: !!newPassword,
                        passwordsMatch: newPassword === confirmPassword
                    });
                    
                    const result = await apiCall(ENDPOINTS.resetPassword, {
                        token: resetToken,
                        newPassword: newPassword,
                        confirmPassword: confirmPassword
                    }, 'POST');

                    if (result.success) {
                        showMessage(result.message || 'Password created successfully! You can now login.', 'success');
                        setTimeout(() => navigate('login.html'), 2000);
                    } else {
                        // Show error in both fields if token error
                        if (result.error && result.error.includes('token')) {
                            showError(newPasswordError, result.error);
                            showError(confirmPasswordError, '');
                        } else {
                            showError(newPasswordError, result.error || 'Failed to create password');
                        }
                    }

                } catch (error) {
                    console.error('Reset password error:', error);
                    showError(newPasswordError, error.message || 'Failed to reset password. Please try again.');
                }
            });
        }
    }

    // Handle reset password page
    function handleResetPasswordPage() {
        if (document.body.getAttribute('data-page') !== 'reset-password') return;

        const form = document.getElementById('resetPasswordForm');
        if (!form) return;

        // Get token from URL
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');

        if (!token) {
            showMessage('Invalid reset link. Please request a new one.', 'error');
            setTimeout(() => navigate('forgot_password.html'), 1500);
            return;
        }

        // Store token in hidden input
        const tokenInput = document.getElementById('resetToken');
        if (tokenInput) tokenInput.value = token;

        const newPasswordInput = document.getElementById('newPassword');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const newPasswordError = document.getElementById('newPasswordError');
        const confirmPasswordError = document.getElementById('confirmPasswordError');

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            showError(newPasswordError, '');
            showError(confirmPasswordError, '');

            const newPassword = newPasswordInput.value.trim();
            const confirmPassword = confirmPasswordInput.value.trim();

            let valid = true;
            if (!newPassword) {
                showError(newPasswordError, 'New password is required');
                valid = false;
            } else if (newPassword.length < 6) {
                showError(newPasswordError, 'Password must be at least 6 characters long');
                valid = false;
            }
            if (!confirmPassword) {
                showError(confirmPasswordError, 'Password confirmation is required');
                valid = false;
            } else if (newPassword !== confirmPassword) {
                showError(confirmPasswordError, 'Passwords do not match');
                valid = false;
            }

            if (!valid) return;

            try {
                const result = await apiCall(ENDPOINTS.resetPassword, {
                    token: token,
                    newPassword: newPassword,
                    confirmPassword: confirmPassword
                }, 'POST');

                showMessage(result.message, 'success');
                setTimeout(() => navigate('login.html'), 1500);

            } catch (error) {
                showMessage(error.message || 'Failed to reset password', 'error');
            }
        });
    }

    // Initialize page handlers
    document.addEventListener('DOMContentLoaded', function () {
        const page = document.body.getAttribute('data-page');
        if (page === 'login') handleLoginPage();
        if (page === 'register') handleRegisterPage();
        if (page === 'landing') handleHomePage();
        if (page === 'farmer-dashboard') handleFarmerDashboard();
        if (page === 'buyer-dashboard') handleBuyerDashboard();
        if (page === 'forgot-password') handleForgotPasswordPage();
        if (page === 'reset-password') handleResetPasswordPage();
    });
})();

// Utility: Add show/hide password toggles for input IDs (global function)
window.enablePasswordToggle = function (inputIds) {
    if (!Array.isArray(inputIds)) return;
    inputIds.forEach((id) => {
        const input = document.getElementById(id);
        if (!input) return;
        const container = input.closest('.form-group');
        if (!container) return;
        container.classList.add('password-field');

        // Check if button already exists
        let btn = container.querySelector('.password-toggle-btn');
        
        if (btn) {
            // Button exists, ensure it has event listener
            // Remove existing listeners by cloning
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            btn = newBtn;
        } else {
            // Create new button
            btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'password-toggle-btn';
            btn.setAttribute('data-target', id);
            btn.setAttribute('aria-label', 'Show password');
            btn.innerHTML = '<i class="fa-solid fa-eye"></i>';
            container.appendChild(btn);
        }

        // Attach event listener
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const targetInput = document.getElementById(id);
            if (!targetInput) return;
            
            const isHidden = targetInput.type === 'password';
            targetInput.type = isHidden ? 'text' : 'password';
            const icon = btn.querySelector('i');
            if (icon) {
                icon.classList.remove(isHidden ? 'fa-eye' : 'fa-eye-slash');
                icon.classList.add(isHidden ? 'fa-eye-slash' : 'fa-eye');
            } else {
                btn.innerHTML = isHidden ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>';
            }
            btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        });
    });
};
