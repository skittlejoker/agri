// Farmer Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function () {
    // Initialize dashboard
    initializeDashboard();

    // Setup event listeners
    setupEventListeners();

    // Load initial data
    loadDashboardData();
});

// Initialize dashboard components
function initializeDashboard() {
    // Set farmer name (in real app, this would come from session)
    const farmerName = localStorage.getItem('farmerName') || 'John';
    document.getElementById('farmer-name').textContent = farmerName;

    // Initialize mobile menu
    initializeMobileMenu();

    // Initialize image preview
    initializeImagePreview();
}

// Setup all event listeners
function setupEventListeners() {
    // Sidebar navigation
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', handleNavigation);
    });

    // Add product form
    const addProductForm = document.getElementById('add-product-form');
    if (addProductForm) {
        addProductForm.addEventListener('submit', handleAddProduct);
    }

    // Mobile menu toggle
    const mobileToggle = document.getElementById('mobile-menu-toggle');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', toggleMobileMenu);
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', function (e) {
        const sidebar = document.querySelector('.sidebar');
        const mobileToggle = document.getElementById('mobile-menu-toggle');

        if (window.innerWidth <= 768 &&
            !sidebar.contains(e.target) &&
            !mobileToggle.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    });
}

// Handle sidebar navigation
function handleNavigation(e) {
    e.preventDefault();

    const targetSection = e.currentTarget.getAttribute('data-section');

    // Update active nav link
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    e.currentTarget.classList.add('active');

    // Show target section
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });

    const targetElement = document.getElementById(targetSection);
    if (targetElement) {
        targetElement.classList.add('active');

        // Load section-specific data
        loadSectionData(targetSection);
    }

    // Close mobile menu
    document.querySelector('.sidebar').classList.remove('open');
}

// Load section-specific data
function loadSectionData(section) {
    switch (section) {
        case 'products':
            loadProducts();
            break;
        case 'dashboard':
            loadDashboardData();
            break;
        case 'orders':
            loadOrders();
            break;
        case 'earnings':
            loadEarnings();
            break;
    }
}

// Initialize mobile menu functionality
function initializeMobileMenu() {
    const sidebar = document.querySelector('.sidebar');
    const mobileToggle = document.getElementById('mobile-menu-toggle');

    // Handle window resize
    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('open');
        }
    });
}

// Toggle mobile menu
function toggleMobileMenu() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('open');
}

// Initialize image preview functionality
function initializeImagePreview() {
    const imageInput = document.getElementById('product-image');
    const imagePreview = document.getElementById('image-preview');

    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function (e) {
            previewImage(e.target);
        });
    }
}

// Preview uploaded image
function previewImage(input) {
    const preview = document.getElementById('image-preview');

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function (e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Product preview">`;
        };

        reader.readAsDataURL(input.files[0]);
    }
}

// Handle add product form submission
function handleAddProduct(e) {
    e.preventDefault();

    const formData = new FormData(e.target);

    // Validate form data
    const productData = {
        name: formData.get('product_name'),
        price: parseFloat(formData.get('product_price')),
        description: formData.get('product_description'),
        stock: parseInt(formData.get('product_stock')),
        image: formData.get('product_image')
    };

    if (!validateProductForm(productData)) {
        return;
    }

    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';
    submitBtn.disabled = true;

    // Send to PHP backend
    fetch('api/add_product.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Product added successfully!', 'success');

                // Reset form
                e.target.reset();
                document.getElementById('image-preview').innerHTML = `
                <i class="fa-solid fa-cloud-upload-alt"></i>
                <p>Click to upload or drag and drop</p>
            `;

                // Update products list if on products page
                if (document.getElementById('products').classList.contains('active')) {
                    loadProducts();
                }

                // Update dashboard data
                loadDashboardData();
            } else {
                showMessage(data.message || 'Failed to add product', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Network error. Please try again.', 'error');
        })
        .finally(() => {
            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
}

// Validate product form
function validateProductForm(data) {
    if (!data.name || data.name.trim().length < 2) {
        showMessage('Product name must be at least 2 characters long', 'error');
        return false;
    }

    if (!data.price || data.price <= 0) {
        showMessage('Price must be greater than 0', 'error');
        return false;
    }

    if (!data.stock || data.stock < 0) {
        showMessage('Stock quantity must be 0 or greater', 'error');
        return false;
    }

    return true;
}

// Add product to local storage
function addProductToStorage(productData) {
    let products = JSON.parse(localStorage.getItem('farmerProducts') || '[]');

    const newProduct = {
        id: Date.now(),
        ...productData,
        dateAdded: new Date().toISOString()
    };

    products.push(newProduct);
    localStorage.setItem('farmerProducts', JSON.stringify(products));
}

// Load dashboard data
function loadDashboardData() {
    const products = JSON.parse(localStorage.getItem('farmerProducts') || '[]');

    // Update summary cards
    document.getElementById('total-products').textContent = products.length;
    document.getElementById('total-orders').textContent = Math.floor(Math.random() * 10) + 5; // Simulated
    document.getElementById('total-earnings').textContent = '$' + (products.reduce((sum, p) => sum + (p.price * Math.floor(Math.random() * 5)), 0)).toFixed(2);
    document.getElementById('pending-deliveries').textContent = Math.floor(Math.random() * 5) + 1; // Simulated
}

// Load products table
function loadProducts() {
    const tableBody = document.getElementById('products-table-body');

    // Show loading state
    tableBody.innerHTML = `
        <tr>
            <td colspan="5" style="text-align: center; padding: 2rem;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-green);"></i>
                <p style="margin-top: 1rem; color: var(--text-light);">Loading products...</p>
            </td>
        </tr>
    `;

    // Fetch products from PHP backend
    fetch('api/get_products.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const products = data.products;

                if (products.length === 0) {
                    tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-light);">
                            <i class="fa-solid fa-seedling" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                            No products found. Add your first product!
                        </td>
                    </tr>
                `;
                    return;
                }

                tableBody.innerHTML = products.map(product => `
                <tr>
                    <td>
                        ${product.image_url ?
                        `<img src="${product.image_url}" alt="${product.name}" class="product-image">` :
                        `<div class="product-image" style="background: var(--light-gray); display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-image" style="color: var(--text-light);"></i>
                            </div>`
                    }
                    </td>
                    <td>
                        <strong>${product.name}</strong>
                        ${product.description ? `<br><small style="color: var(--text-light);">${product.description}</small>` : ''}
                    </td>
                    <td><strong>$${product.price.toFixed(2)}</strong></td>
                    <td>
                        <span class="stock-badge ${product.stock > 10 ? 'in-stock' : product.stock > 0 ? 'low-stock' : 'out-of-stock'}">
                            ${product.stock} units
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn edit-btn" onclick="editProduct(${product.id})" title="Edit">
                                <i class="fa-solid fa-edit"></i>
                            </button>
                            <button class="action-btn delete-btn" onclick="deleteProduct(${product.id})" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            } else {
                tableBody.innerHTML = `
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem; color: #dc3545;">
                        <i class="fa-solid fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                        Error loading products: ${data.message}
                    </td>
                </tr>
            `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tableBody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align: center; padding: 2rem; color: #dc3545;">
                    <i class="fa-solid fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    Network error. Please try again.
                </td>
            </tr>
        `;
        });
}

// Load orders (placeholder)
function loadOrders() {
    console.log('Loading orders...');
    // Implement orders loading logic
}

// Load earnings (placeholder)
function loadEarnings() {
    console.log('Loading earnings...');
    // Implement earnings loading logic
}

// Edit product
function editProduct(productId) {
    const products = JSON.parse(localStorage.getItem('farmerProducts') || '[]');
    const product = products.find(p => p.id === productId);

    if (product) {
        // Navigate to add product section
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        document.querySelector('[data-section="add-product"]').classList.add('active');

        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });
        document.getElementById('add-product').classList.add('active');

        // Populate form with product data
        document.getElementById('product-name').value = product.name;
        document.getElementById('product-price').value = product.price;
        document.getElementById('product-description').value = product.description || '';
        document.getElementById('product-stock').value = product.stock;

        // Update form title
        document.querySelector('#add-product .page-header h1').textContent = 'Edit Product';
        document.querySelector('#add-product button[type="submit"]').innerHTML = '<i class="fa-solid fa-save"></i> Update Product';

        showMessage('Product loaded for editing', 'success');
    }
}

// Delete product
function deleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product?')) {
        const formData = new FormData();
        formData.append('product_id', productId);

        fetch('api/delete_product.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Product deleted successfully', 'success');
                    loadProducts();
                    loadDashboardData();
                } else {
                    showMessage(data.message || 'Failed to delete product', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Network error. Please try again.', 'error');
            });
    }
}

// Show message to user
function showMessage(message, type = 'success') {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.message');
    existingMessages.forEach(msg => msg.remove());

    // Create new message
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = message;

    // Insert message at the top of the active section
    const activeSection = document.querySelector('.content-section.active');
    if (activeSection) {
        activeSection.insertBefore(messageDiv, activeSection.firstChild);

        // Auto-remove message after 5 seconds
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
}

// Add CSS for stock badges
const style = document.createElement('style');
style.textContent = `
    .stock-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .stock-badge.in-stock {
        background: #d4edda;
        color: #155724;
    }
    
    .stock-badge.low-stock {
        background: #fff3cd;
        color: #856404;
    }
    
    .stock-badge.out-of-stock {
        background: #f8d7da;
        color: #721c24;
    }
`;
document.head.appendChild(style);

// Terms and Conditions Modal Functionality
document.addEventListener('DOMContentLoaded', function () {
    // Initialize terms modal functionality
    initializeTermsModal();
});

function initializeTermsModal() {
    const openTermsBtn = document.getElementById('openTerms');
    const termsModal = document.getElementById('termsModal');
    const closeTermsBtn = document.getElementById('closeTerms');
    const agreeTermsBtn = document.getElementById('agreeTermsBtn');
    const acceptTermsCheckbox = document.getElementById('acceptTerms');
    const showTermsBtn = document.getElementById('showTermsBtn');
    const termsContent = document.getElementById('termsContent');
    const termsPlaceholder = document.getElementById('termsPlaceholder');

    // Open terms modal
    if (openTermsBtn && termsModal) {
        openTermsBtn.addEventListener('click', function (e) {
            e.preventDefault();
            termsModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
    }

    // Show terms content when "View Terms" is clicked
    if (showTermsBtn && termsContent && termsPlaceholder) {
        showTermsBtn.addEventListener('click', function () {
            termsPlaceholder.style.display = 'none';
            termsContent.classList.remove('hidden');
        });
    }

    // Close terms modal
    if (closeTermsBtn && termsModal) {
        closeTermsBtn.addEventListener('click', function () {
            termsModal.classList.remove('show');
            document.body.style.overflow = 'auto';
            // Reset to placeholder view
            resetTermsModal();
        });
    }

    // Close modal when clicking outside
    if (termsModal) {
        termsModal.addEventListener('click', function (e) {
            if (e.target === termsModal) {
                termsModal.classList.remove('show');
                document.body.style.overflow = 'auto';
                // Reset to placeholder view
                resetTermsModal();
            }
        });
    }

    // Handle "I Understand" button click
    if (agreeTermsBtn && acceptTermsCheckbox) {
        agreeTermsBtn.addEventListener('click', function () {
            // Check all checkboxes in the terms modal
            const termCheckboxes = termsModal.querySelectorAll('input[type="checkbox"]');
            termCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
            });

            // Check the main accept terms checkbox
            acceptTermsCheckbox.checked = true;

            // Close the modal
            termsModal.classList.remove('show');
            document.body.style.overflow = 'auto';

            // Show success message
            showTermsSuccessMessage();

            // Reset to placeholder view
            resetTermsModal();
        });
    }

    // Function to reset modal to placeholder view
    function resetTermsModal() {
        if (termsContent && termsPlaceholder) {
            termsContent.classList.add('hidden');
            termsPlaceholder.style.display = 'flex';
        }
    }
}

function showTermsSuccessMessage() {
    // Create a temporary success message
    const successMessage = document.createElement('div');
    successMessage.className = 'terms-success-message';
    successMessage.innerHTML = `
        <div style="
            position: fixed;
            top: 20px;
            right: 20px;
            background: #d4edda;
            color: #155724;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            border: 1px solid #c3e6cb;
            font-weight: 500;
            animation: slideInRight 0.3s ease-out;
        ">
            <i class="fa-solid fa-check-circle" style="margin-right: 0.5rem; color: #28a745;"></i>
            All terms and conditions accepted!
        </div>
    `;

    // Add animation styles
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
    `;
    document.head.appendChild(style);

    document.body.appendChild(successMessage);

    // Remove message after 3 seconds
    setTimeout(() => {
        successMessage.remove();
    }, 3000);
}

// Change Password Functionality
document.addEventListener('DOMContentLoaded', function () {
    // Check if change password modal exists on this page
    const changePasswordModal = document.getElementById('changePasswordModal');
    const changePasswordLink = document.getElementById('changePasswordLink');
    const closePasswordModal = document.getElementById('closePasswordModal');
    const cancelPasswordBtn = document.getElementById('cancelPasswordBtn');
    const changePasswordForm = document.getElementById('changePasswordForm');

    if (changePasswordModal && changePasswordLink) {
        // Open change password modal
        changePasswordLink.addEventListener('click', function (e) {
            e.preventDefault();
            changePasswordModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
    }

    if (closePasswordModal) {
        closePasswordModal.addEventListener('click', function () {
            changePasswordModal.classList.remove('show');
            document.body.style.overflow = 'auto';
            resetPasswordForm();
        });
    }

    if (cancelPasswordBtn) {
        cancelPasswordBtn.addEventListener('click', function () {
            changePasswordModal.classList.remove('show');
            document.body.style.overflow = 'auto';
            resetPasswordForm();
        });
    }

    // Close modal when clicking outside
    if (changePasswordModal) {
        changePasswordModal.addEventListener('click', function (e) {
            if (e.target === changePasswordModal) {
                changePasswordModal.classList.remove('show');
                document.body.style.overflow = 'auto';
                resetPasswordForm();
            }
        });
    }

    // Handle form submission
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            // Clear previous errors
            clearPasswordErrors();

            const oldPassword = document.getElementById('oldPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmNewPassword = document.getElementById('confirmNewPassword').value;

            // Client-side validation
            if (!oldPassword || !newPassword || !confirmNewPassword) {
                showPasswordError('All fields are required', 'oldPasswordError');
                return;
            }

            if (newPassword.length < 6) {
                showPasswordError('New password must be at least 6 characters long', 'newPasswordError');
                return;
            }

            if (newPassword !== confirmNewPassword) {
                showPasswordError('New passwords do not match', 'confirmNewPasswordError');
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

                if (result.success) {
                    showMessage('Password changed successfully!', 'success');
                    changePasswordModal.classList.remove('show');
                    document.body.style.overflow = 'auto';
                    resetPasswordForm();
                } else {
                    // Show error in appropriate field based on error message
                    const errorMsg = result.error || result.message || 'Failed to change password';
                    const errorLower = errorMsg.toLowerCase();
                    
                    if (errorLower.includes('current') || errorLower.includes('old') || errorLower.includes('incorrect')) {
                        showPasswordError(errorMsg, 'oldPasswordError');
                    } else if (errorLower.includes('new') || errorLower.includes('confirm') || errorLower.includes('match')) {
                        showPasswordError(errorMsg, 'confirmNewPasswordError');
                    } else if (errorLower.includes('length') || errorLower.includes('characters')) {
                        showPasswordError(errorMsg, 'newPasswordError');
                    } else {
                        showPasswordError(errorMsg, 'oldPasswordError');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                const errorMsg = error.message || 'Network error. Please try again.';
                showPasswordError(errorMsg, 'oldPasswordError');
            }
        });
    }
});

function resetPasswordForm() {
    const form = document.getElementById('changePasswordForm');
    if (form) {
        form.reset();
    }
    clearPasswordErrors();
}

function clearPasswordErrors() {
    const errorElements = document.querySelectorAll('#changePasswordForm .error');
    errorElements.forEach(el => {
        el.textContent = '';
    });
}

function showPasswordError(message, errorId) {
    const errorElement = document.getElementById(errorId);
    if (errorElement) {
        errorElement.textContent = message;
    }
}