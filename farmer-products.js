// Farmer Products JavaScript
document.addEventListener('DOMContentLoaded', function () {
    // Load products
    loadProducts();

    // Setup event listeners
    setupEventListeners();
});

// Setup all event listeners
function setupEventListeners() {
    // Add product form
    const addProductForm = document.getElementById('addProductForm');
    if (addProductForm) {
        addProductForm.addEventListener('submit', handleAddProduct);
    }

    // Image URL preview
    const prodImageInput = document.getElementById('prodImage');
    if (prodImageInput) {
        prodImageInput.addEventListener('input', handleImagePreview);
        prodImageInput.addEventListener('blur', handleImagePreview);
    }

    // Logout button
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }

    // Change password modal
    setupChangePasswordModal();
}

// Handle image preview in form
function handleImagePreview(e) {
    const imageUrl = e.target.value.trim();
    let previewContainer = document.getElementById('imagePreviewContainer');

    if (!previewContainer) {
        previewContainer = document.createElement('div');
        previewContainer.id = 'imagePreviewContainer';
        previewContainer.style.cssText = 'margin-top: 1rem; text-align: center;';
        e.target.parentElement.appendChild(previewContainer);
    }

    if (imageUrl && isValidUrl(imageUrl)) {
        previewContainer.innerHTML = `
            <div style="margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-light); font-weight: 500;">Image Preview:</div>
            <div style="width: 100%; max-width: 300px; height: 200px; margin: 0 auto; border: 2px solid var(--border-color); border-radius: 8px; overflow: hidden; background: #f5f5f5;">
                <img src="${imageUrl}" alt="Preview" 
                     style="width: 100%; height: 100%; object-fit: cover; display: block;"
                     onerror="this.parentElement.innerHTML='<div style=\\'display: flex; align-items: center; justify-content: center; height: 100%; color: #dc3545;\\'><i class=\\'fa-solid fa-exclamation-triangle\\'></i> Invalid image URL</div>'">
            </div>
        `;
    } else if (imageUrl) {
        previewContainer.innerHTML = `
            <div style="color: #dc3545; font-size: 0.85rem; margin-top: 0.5rem;">
                <i class="fa-solid fa-exclamation-triangle"></i> Please enter a valid image URL
            </div>
        `;
    } else {
        previewContainer.innerHTML = '';
    }
}

// Validate URL
function isValidUrl(string) {
    try {
        const url = new URL(string);
        return url.protocol === 'http:' || url.protocol === 'https:';
    } catch (_) {
        return false;
    }
}

// Handle add product form submission
function handleAddProduct(e) {
    e.preventDefault();

    // Get form values
    const productName = document.getElementById('prodName').value.trim();
    const productPrice = parseFloat(document.getElementById('prodPrice').value);
    const productDesc = document.getElementById('prodDesc').value.trim();
    const productStock = parseInt(document.getElementById('prodStock').value) || 0;
    const productImage = document.getElementById('prodImage').value.trim();

    // Validate
    if (!productName || productName.length < 2) {
        showMessage('Product name must be at least 2 characters', 'error');
        return;
    }

    if (!productPrice || productPrice <= 0) {
        showMessage('Price must be greater than 0', 'error');
        return;
    }

    if (productStock < 0) {
        showMessage('Stock quantity cannot be negative', 'error');
        return;
    }

    // Prepare form data
    const formData = new FormData();
    formData.append('product_name', productName);
    formData.append('product_price', productPrice);
    formData.append('product_description', productDesc);
    formData.append('product_stock', productStock);

    // Add image URL if provided
    if (productImage) {
        formData.append('product_image', productImage);
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

                // Clear image preview
                const previewContainer = document.getElementById('imagePreviewContainer');
                if (previewContainer) {
                    previewContainer.innerHTML = '';
                }

                // Reload products list
                loadProducts();
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

// Load products for the farmer
function loadProducts() {
    const productsContainer = document.getElementById('farmerProducts');
    if (!productsContainer) return;

    // Show loading state
    productsContainer.innerHTML = `
        <div style="text-align: center; padding: 2rem;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size: 2rem; color: #28a745;"></i>
            <p style="margin-top: 1rem; color: #666;">Loading products...</p>
        </div>
    `;

    // Fetch products from PHP backend
    fetch('api/get_products.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const products = data.products;

                if (products.length === 0) {
                    productsContainer.innerHTML = `
                        <div style="text-align: center; padding: 3rem; color: #666;">
                            <i class="fa-solid fa-seedling" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: #28a745;"></i>
                            <h3 style="color: #333;">No products yet</h3>
                            <p style="margin: 1rem 0;">Add your first product to get started!</p>
                        </div>
                    `;
                    return;
                }

                // Display products in a grid (exact buyer shop structure)
                productsContainer.innerHTML = products.map(product => {
                    // Calculate prices - use price_kg as main price, price_gram if available
                    const priceKg = parseFloat(product.price_kg || product.price || 0);
                    // Check if gram price exists - be more lenient with the check
                    const hasGramPrice = product.price_gram !== null && 
                                        product.price_gram !== undefined && 
                                        product.price_gram !== '' &&
                                        !isNaN(parseFloat(product.price_gram)) &&
                                        parseFloat(product.price_gram) > 0;
                    const priceGram = hasGramPrice ? parseFloat(product.price_gram) : null;
                    
                    // Debug logging
                    console.log('Rendering product:', product.name, {
                        price_kg: product.price_kg,
                        price_gram: product.price_gram,
                        price: product.price,
                        calculated_priceKg: priceKg,
                        hasGramPrice: hasGramPrice,
                        priceGram: priceGram
                    });
                    
                    // Build price display HTML - always show kg price with label
                    let priceDisplay = `
                        <div style="display: flex; align-items: baseline; gap: 0.4rem; margin-bottom: 0.25rem;">
                            <span class="price" style="font-weight: 700; color: #28a745; font-size: 1.4rem; line-height: 1.3;">
                                ₱${priceKg.toFixed(2)}
                            </span>
                            <span style="font-size: 1rem; font-weight: 600; color: #666; letter-spacing: 0.5px;">kg</span>
                        </div>`;
                    
                    // Add gram price if available
                    if (hasGramPrice && priceGram) {
                        priceDisplay += `
                        <div style="display: flex; align-items: baseline; gap: 0.4rem;">
                            <span class="price" style="font-weight: 600; color: #555; font-size: 1.05rem; line-height: 1.2;">
                                ₱${priceGram.toFixed(2)}
                            </span>
                            <span style="font-size: 0.9rem; font-weight: 500; color: #888;">g</span>
                        </div>`;
                    }
                    
                    return `
                    <div class="product-card">
                        <button onclick="deleteProduct(${product.id})" style="position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; z-index: 10; box-shadow: 0 2px 8px rgba(0,0,0,0.2); transition: all 0.2s;" title="Delete Product" onmouseover="this.style.transform='scale(1.1)'; this.style.background='#c82333';" onmouseout="this.style.transform='scale(1)'; this.style.background='#dc3545';">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                        <div class="product-image-wrap">
                            ${product.image_url ? `<img src="${product.image_url}" alt="${product.name}">` : '<div class="product-image placeholder"></div>'}
                        </div>
                        <div class="product-info">
                            <div class="product-title">
                                <strong>${product.name}</strong>
                            </div>
                            ${product.description ? `<p style="margin: 0.5rem 0; color: var(--text-light); font-size: 0.9rem; line-height: 1.4;">${product.description}</p>` : ''}
                            <div class="product-meta" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
                                <div style="display: flex; flex-direction: column; gap: 0.25rem; min-width: 150px;">
                                    ${priceDisplay}
                                </div>
                                <span class="stock ${product.stock > 10 ? 'in-stock' : product.stock > 0 ? 'low-stock' : 'out-of-stock'}" style="padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 500; white-space: nowrap;">
                                    <i class="fa-solid ${product.stock > 10 ? 'fa-check-circle' : product.stock > 0 ? 'fa-exclamation-circle' : 'fa-times-circle'}"></i>
                                    ${product.stock || 0} in stock
                                </span>
                            </div>
                            <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color);">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <label for="stock-${product.id}" style="color: var(--text-dark); font-size: 0.9rem; font-weight: 500; flex-shrink: 0;">Update Stock:</label>
                                    <input 
                                        type="number" 
                                        id="stock-${product.id}" 
                                        value="${product.stock || 0}" 
                                        min="0"
                                        style="flex: 1; padding: 0.5rem; border: 2px solid var(--border-color); border-radius: 6px; font-size: 0.9rem; max-width: 100px; transition: border-color 0.2s;"
                                        onchange="updateProductStock(${product.id}, this.value)"
                                        onfocus="this.style.borderColor='var(--primary-green)';"
                                        onblur="this.style.borderColor='var(--border-color)';"
                                    />
                                    <span style="color: var(--text-light); font-size: 0.85rem;">units</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    `;
                }).join('');

            } else {
                productsContainer.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: #dc3545;">
                        <i class="fa-solid fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>Error loading products: ${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            productsContainer.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: #dc3545;">
                    <i class="fa-solid fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Network error. Please try again.</p>
                </div>
            `;
        });
}

// Handle image error - show placeholder
window.handleImageError = function (imageId, productName) {
    const img = document.getElementById(imageId);
    if (img) {
        img.style.display = 'none';
        const imageWrap = img.parentElement;
        if (imageWrap) {
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

// View image in modal (global scope for onclick)
window.viewImageModal = function (imageUrl, productName) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('imageViewModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'imageViewModal';
        modal.className = 'modal';
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 90vw; max-height: 90vh; background: transparent; box-shadow: none;">
                <div style="position: relative; background: var(--white); border-radius: 12px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                    <button class="icon-btn" id="closeImageModal" style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.5); color: white; z-index: 1000; border-radius: 50%; width: 40px; height: 40px;" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <img id="modalImage" src="" alt="" style="max-width: 100%; max-height: 90vh; display: block; margin: 0 auto;">
                    <div style="padding: 1rem; background: var(--white); text-align: center;">
                        <h3 id="modalImageTitle" style="margin: 0; color: var(--text-dark);"></h3>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // Close modal handlers
        const closeBtn = document.getElementById('closeImageModal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = 'auto';
            });
        }

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = 'auto';
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = 'auto';
            }
        });
    }

    // Set image and title
    const modalImage = document.getElementById('modalImage');
    const modalTitle = document.getElementById('modalImageTitle');
    if (modalImage) modalImage.src = imageUrl;
    if (modalImage) modalImage.alt = productName;
    if (modalTitle) modalTitle.textContent = productName;

    // Show modal
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
};

// Delete product function (global scope for onclick)
window.deleteProduct = function (productId) {
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('product_id', productId);

        fetch('api/delete_product.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Product deleted successfully!', 'success');
                    loadProducts(); // Reload products list
                } else {
                    showMessage(data.message || 'Failed to delete product', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Network error. Please try again.', 'error');
            });
    }
};

// Update product stock function (global scope for onchange)
window.updateProductStock = function (productId, stock) {
    const stockValue = parseInt(stock);

    if (isNaN(stockValue) || stockValue < 0) {
        showMessage('Invalid stock value', 'error');
        loadProducts(); // Reload to show original value
        return;
    }

    // Update via update_product.php
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('product_stock', stockValue);

    fetch('api/update_product.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Stock updated successfully!', 'success');
                loadProducts(); // Reload to show updated value
            } else {
                showMessage(data.message || 'Failed to update stock', 'error');
                loadProducts(); // Reload to show original value
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Network error. Please try again.', 'error');
            loadProducts(); // Reload to show original value
        });
};

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
    
    /* Use existing styles from style.css - just add farmer-specific overrides */
    #farmerProducts .product-card {
        position: relative;
    }
    
    #farmerProducts .product-card button[onclick*="deleteProduct"] {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 10;
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


