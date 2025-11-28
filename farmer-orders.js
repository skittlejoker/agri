// Farmer Orders JavaScript
document.addEventListener('DOMContentLoaded', function () {
    console.log('Farmer orders page loaded');

    // Wait a bit to ensure DOM is fully ready
    setTimeout(() => {
        // Load orders
        loadOrders();

        // Setup event listeners
        setupEventListeners();
    }, 100);
});

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

// Load orders for farmer - organized by status
function loadOrders() {
    console.log('loadOrders function called');

    const ordersToShipContainer = document.getElementById('ordersToShip');
    const shippedOrdersContainer = document.getElementById('shippedOrders');
    const allOrdersContainer = document.getElementById('allOrders');

    if (!ordersToShipContainer || !shippedOrdersContainer || !allOrdersContainer) {
        console.error('Order containers not found:', {
            ordersToShip: !!ordersToShipContainer,
            shippedOrders: !!shippedOrdersContainer,
            allOrders: !!allOrdersContainer
        });
        return;
    }

    console.log('All containers found, fetching orders...');

    // Show loading state
    const loadingHTML = `
        <div style="text-align: center; padding: 1rem;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size: 1.5rem; color: #28a745;"></i>
            <p style="margin-top: 0.5rem; color: #666;">Loading orders...</p>
        </div>
    `;
    ordersToShipContainer.innerHTML = loadingHTML;
    shippedOrdersContainer.innerHTML = loadingHTML;
    allOrdersContainer.innerHTML = loadingHTML;

    // Add timeout to fetch
    const fetchWithTimeout = (url, timeout = 10000) => {
        return Promise.race([
            fetch(url),
            new Promise((_, reject) =>
                setTimeout(() => reject(new Error('Request timeout')), timeout)
            )
        ]);
    };

    fetchWithTimeout('api/get_orders.php')
        .then(async response => {
            if (!response.ok) {
                const errorText = await response.text();
                console.error('API Error Response:', errorText);
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON Response:', text);
                throw new Error('Response is not JSON');
            }
            return response.json();
        })
        .then(data => {
            console.log('Orders data received:', data);
            if (data.success) {
                const orders = data.orders || [];

                if (orders.length === 0) {
                    ordersToShipContainer.innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: #666;">
                            <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block; color: #999;"></i>
                            <p style="margin: 0; font-size: 0.9rem;">No orders yet</p>
                        </div>
                    `;
                    shippedOrdersContainer.innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: #666;">
                            <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block; color: #999;"></i>
                            <p style="margin: 0; font-size: 0.9rem;">No orders yet</p>
                        </div>
                    `;
                    allOrdersContainer.innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: #666;">
                            <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block; color: #999;"></i>
                            <p style="margin: 0; font-size: 0.9rem;">No orders yet</p>
                        </div>
                    `;
                    return;
                }

                // Filter orders by status
                const toShipOrders = orders.filter(order => {
                    const orderHasEnhanced = order.payment_method !== undefined;
                    if (orderHasEnhanced) {
                        return order.payment_status === 'paid' && order.shipping_status === 'to_ship';
                    } else {
                        return order.status === 'pending' || order.status === 'confirmed';
                    }
                });

                const shippedOrders = orders.filter(order => {
                    const orderHasEnhanced = order.payment_method !== undefined;
                    if (orderHasEnhanced) {
                        return order.shipping_status === 'shipped';
                    } else {
                        return order.status === 'shipped';
                    }
                });

                // Render orders to ship
                if (toShipOrders.length === 0) {
                    ordersToShipContainer.innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: #666;">
                            <i class="fa-solid fa-box-open" style="font-size: 2rem; margin-bottom: 0.5rem; display: block; color: #999;"></i>
                            <p style="margin: 0; font-size: 0.9rem;">No orders to ship</p>
                        </div>
                    `;
                } else {
                    ordersToShipContainer.innerHTML = toShipOrders.map(order => renderOrderCard(order)).join('');
                }

                // Render shipped orders
                if (shippedOrders.length === 0) {
                    shippedOrdersContainer.innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: #666;">
                            <i class="fa-solid fa-truck-fast" style="font-size: 2rem; margin-bottom: 0.5rem; display: block; color: #999;"></i>
                            <p style="margin: 0; font-size: 0.9rem;">No shipped orders</p>
                        </div>
                    `;
                } else {
                    shippedOrdersContainer.innerHTML = shippedOrders.map(order => renderOrderCard(order)).join('');
                }

                // Render all orders
                allOrdersContainer.innerHTML = orders.map(order => renderOrderCard(order)).join('');
            } else {
                const errorHTML = `
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block; color: #999;"></i>
                        <p style="margin: 0; font-size: 0.9rem;">${data.message || 'No orders found.'}</p>
                    </div>
                `;
                ordersToShipContainer.innerHTML = errorHTML;
                shippedOrdersContainer.innerHTML = errorHTML;
                allOrdersContainer.innerHTML = errorHTML;
            }
        })
        .catch(error => {
            console.error('Error loading orders:', error);
            const errorHTML = `
                <div style="text-align: center; padding: 2rem; color: #dc3545;">
                    <i class="fa-solid fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    <p style="margin: 0.5rem 0;">Error loading orders. Please refresh the page.</p>
                    <p style="margin: 0; font-size: 0.85rem; color: #999;">Error: ${error.message}</p>
                </div>
            `;
            ordersToShipContainer.innerHTML = errorHTML;
            shippedOrdersContainer.innerHTML = errorHTML;
            allOrdersContainer.innerHTML = errorHTML;
        });
}

// Render individual order card
function renderOrderCard(order) {
    const orderHasEnhanced = order.payment_method !== undefined;
    const currentStatus = orderHasEnhanced ?
        (order.shipping_status || order.status || 'to_ship') :
        (order.status || 'pending');

    const statusClass = {
        'pending': 'low-stock',
        'confirmed': 'in-stock',
        'to_ship': 'low-stock',
        'shipped': 'in-stock',
        'completed': 'in-stock',
        'cancelled': 'out-of-stock'
    }[currentStatus] || '';

    const statusOptions = orderHasEnhanced ?
        ['to_ship', 'shipped', 'completed', 'cancelled'] :
        ['pending', 'confirmed', 'shipped', 'completed', 'cancelled'];

    const statusButtons = statusOptions.map(status => {
        const isSelected = currentStatus === status;
        return `
            <option value="${status}" ${isSelected ? 'selected' : ''}>${status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ')}</option>
        `;
    }).join('');

    const statusDisplay = currentStatus.toUpperCase().replace('_', ' ');
    const shippedDate = order.shipped_at ? new Date(order.shipped_at).toLocaleDateString() : null;

    return `
        <div style="background: white; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-weight: 600; color: #333; margin-bottom: 0.5rem;">${order.product_name}</div>
            <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.25rem;">
                Buyer: ${order.counterparty?.name || 'Unknown'}
            </div>
            <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.5rem;">
                Quantity: ${order.quantity} x $${order.unit_price.toFixed(2)} = <strong>$${order.total_price.toFixed(2)}</strong>
            </div>
            <div style="margin-top: 0.5rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <label for="status-${order.id}" style="color: #666; font-size: 0.9rem; font-weight: 500;">Status:</label>
                <select 
                    id="status-${order.id}" 
                    onchange="updateOrderStatus(${order.id}, this.value)"
                    style="padding: 0.5rem; border: 1px solid #333; border-radius: 4px; background: white; cursor: pointer; font-size: 0.9rem; min-width: 120px;"
                >
                    ${statusButtons}
                </select>
                <span class="stock ${statusClass}" style="padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">${statusDisplay}</span>
            </div>
            <div style="font-size: 0.75rem; color: #999; margin-top: 0.5rem; display: flex; justify-content: space-between; flex-wrap: wrap;">
                ${shippedDate ? `<span><i class="fa-solid fa-box"></i> Shipped: ${shippedDate}</span>` : ''}
                <span><i class="fa-solid fa-clock"></i> Ordered: ${new Date(order.created_at).toLocaleString()}</span>
            </div>
        </div>
    `;
}

// Update order status function (global scope for onchange)
window.updateOrderStatus = function (orderId, status) {
    // Map shipping statuses to proper status and shipping_status
    let finalStatus = status;
    let shippingStatus = undefined;
    
    // Handle shipping-related statuses
    if (status === 'shipped') {
        finalStatus = 'confirmed'; // Keep order as confirmed
        shippingStatus = 'shipped'; // Set shipping_status to shipped
    } else if (status === 'to_ship') {
        finalStatus = 'confirmed'; // Keep order as confirmed
        shippingStatus = 'to_ship'; // Set shipping_status to to_ship
    } else if (status === 'completed') {
        finalStatus = 'completed'; // Set status to completed
        shippingStatus = 'delivered'; // Set shipping_status to delivered
    } else {
        // For pending, confirmed, cancelled - use as is
        finalStatus = status;
        shippingStatus = undefined;
    }
    
    // Build request body - only include shipping_status if it has a value
    const requestBody = {
        order_id: orderId,
        status: finalStatus
    };
    
    // Only add shipping_status if it's defined and not null
    if (shippingStatus !== undefined && shippingStatus !== null) {
        requestBody.shipping_status = shippingStatus;
    }
    
    console.log('Updating order status:', requestBody); // Debug log
    
    fetch('api/update_order_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestBody)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Order status updated successfully!', 'success');
                loadOrders(); // Reload all orders sections
            } else {
                showMessage(data.message || 'Failed to update order status', 'error');
                loadOrders(); // Reload to show original status
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Network error. Please try again.', 'error');
            loadOrders(); // Reload to show original status
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

