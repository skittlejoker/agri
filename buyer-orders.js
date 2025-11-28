// Buyer Orders JavaScript
let allOrders = [];
let currentFilter = 'all';
let searchQuery = '';

document.addEventListener('DOMContentLoaded', function () {
    // Load orders
    loadOrders();

    // Setup event listeners
    setupEventListeners();
});

// Setup all event listeners
function setupEventListeners() {
    // Tab buttons
    const tabButtons = document.querySelectorAll('.order-tab');
    tabButtons.forEach(button => {
        button.addEventListener('click', function () {
            // Remove active class from all tabs
            tabButtons.forEach(tab => tab.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');

            // Update filter
            currentFilter = this.getAttribute('data-status');
            filterOrders();
        });
    });

    // Search input
    const searchInput = document.getElementById('searchOrders');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            searchQuery = this.value.toLowerCase().trim();
            filterOrders();
        });
    }

    // Logout button
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }

    // Change password modal
    setupChangePasswordModal();

    // Review modal
    setupReviewModal();
}

// Load orders from API
async function loadOrders() {
    const ordersList = document.getElementById('ordersList');
    if (!ordersList) return;

    // Show loading state
    ordersList.innerHTML = `
        <div style="text-align: center; padding: 2rem;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size: 2rem; color: #28a745;"></i>
            <p style="margin-top: 1rem; color: #666;">Loading orders...</p>
        </div>
    `;

    try {
        const response = await fetch('api/get_orders.php');
        if (!response.ok) {
            throw new Error('Failed to load orders');
        }

        const result = await response.json();
        if (result.success) {
            allOrders = result.orders || [];
            filterOrders();
        } else {
            ordersList.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="50" cy="50" r="45" stroke="#ddd" stroke-width="2" fill="none"/>
                            <path d="M30 40 L50 60 L70 40" stroke="#ddd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                            <circle cx="50" cy="50" r="20" fill="#f5f5f5"/>
                            <circle cx="45" cy="45" r="2" fill="#999"/>
                            <circle cx="55" cy="45" r="2" fill="#999"/>
                            <path d="M 40 55 Q 50 60 60 55" stroke="#999" stroke-width="2" stroke-linecap="round" fill="none"/>
                            <circle cx="48" cy="52" r="3" fill="#999" opacity="0.3"/>
                            <circle cx="52" cy="52" r="3" fill="#999" opacity="0.3"/>
                        </svg>
                    </div>
                    <h3>No orders yet</h3>
                    <p>Start shopping!</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error:', error);
        ordersList.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fa-solid fa-exclamation-triangle" style="font-size: 4rem; color: #dc3545;"></i>
                </div>
                <h3>Error loading orders</h3>
                <p>Please try again later</p>
            </div>
        `;
    }
}

// Filter orders based on current filter and search
function filterOrders() {
    const ordersList = document.getElementById('ordersList');
    if (!ordersList) return;

    let filteredOrders = allOrders;

    // Apply status filter
    if (currentFilter !== 'all') {
        filteredOrders = filteredOrders.filter(order => {
            const orderHasEnhanced = order.payment_method !== undefined;

            if (orderHasEnhanced) {
                // Enhanced orders with payment_status and shipping_status
                switch (currentFilter) {
                    case 'unpaid':
                        return order.payment_status === 'unpaid';
                    case 'to_ship':
                        return order.payment_status === 'paid' && order.shipping_status === 'to_ship';
                    case 'shipped':
                        return order.shipping_status === 'shipped';
                    case 'to_review':
                        return order.shipping_status === 'delivered' && !order.review_rating;
                    case 'returns':
                        return order.status === 'cancelled' || order.shipping_status === 'returned';
                    default:
                        return true;
                }
            } else {
                // Legacy orders
                switch (currentFilter) {
                    case 'unpaid':
                        return order.status === 'pending';
                    case 'to_ship':
                        return order.status === 'confirmed';
                    case 'shipped':
                        return order.status === 'shipped' || order.status === 'completed';
                    case 'to_review':
                        return order.status === 'completed' && !order.review_rating;
                    case 'returns':
                        return order.status === 'cancelled';
                    default:
                        return true;
                }
            }
        });
    }

    // Apply search filter
    if (searchQuery) {
        filteredOrders = filteredOrders.filter(order => {
            const searchableText = [
                order.product_name,
                order.id.toString(),
                order.counterparty?.name || '',
                order.delivery_address || ''
            ].join(' ').toLowerCase();

            return searchableText.includes(searchQuery);
        });
    }

    // Display filtered orders
    if (filteredOrders.length === 0) {
        ordersList.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="45" stroke="#ddd" stroke-width="2" fill="none"/>
                        <path d="M30 40 L50 60 L70 40" stroke="#ddd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        <circle cx="50" cy="50" r="20" fill="#f5f5f5"/>
                        <circle cx="45" cy="45" r="2" fill="#999"/>
                        <circle cx="55" cy="45" r="2" fill="#999"/>
                        <path d="M 40 55 Q 50 60 60 55" stroke="#999" stroke-width="2" stroke-linecap="round" fill="none"/>
                        <circle cx="48" cy="52" r="3" fill="#999" opacity="0.3"/>
                        <circle cx="52" cy="52" r="3" fill="#999" opacity="0.3"/>
                    </svg>
                </div>
                <h3>No orders yet</h3>
                <p>Start shopping!</p>
            </div>
        `;
        return;
    }

    // Render orders
    ordersList.innerHTML = filteredOrders.map(order => renderOrderCard(order)).join('');

    // Attach review button handlers
    filteredOrders.forEach(order => {
        const orderHasEnhanced = order.payment_method !== undefined;
        const isDelivered = orderHasEnhanced
            ? order.shipping_status === 'delivered'
            : order.status === 'completed';

        if (isDelivered && !order.review_rating) {
            const reviewBtn = document.getElementById(`reviewBtn-${order.id}`);
            if (reviewBtn) {
                reviewBtn.addEventListener('click', () => openReviewModal(order.id));
            }
        }
    });
}

// Render individual order card
function renderOrderCard(order) {
    const orderHasEnhanced = order.payment_method !== undefined;

    // Determine status for badge
    let statusText = '';
    let statusClass = '';

    if (orderHasEnhanced) {
        if (order.payment_status === 'unpaid') {
            statusText = 'Unpaid';
            statusClass = 'pending';
        } else if (order.shipping_status === 'to_ship') {
            statusText = 'To Ship';
            statusClass = 'to-ship';
        } else if (order.shipping_status === 'shipped') {
            statusText = 'Shipped';
            statusClass = 'shipped';
        } else if (order.shipping_status === 'delivered') {
            statusText = order.review_rating ? 'Completed' : 'To Review';
            statusClass = order.review_rating ? 'completed' : 'to-ship';
        } else if (order.shipping_status === 'returned' || order.status === 'cancelled') {
            statusText = 'Returned';
            statusClass = 'cancelled';
        }
    } else {
        // Legacy orders
        statusText = order.status.charAt(0).toUpperCase() + order.status.slice(1);
        statusClass = order.status === 'pending' ? 'pending' :
            order.status === 'confirmed' ? 'to-ship' :
                order.status === 'shipped' ? 'shipped' :
                    order.status === 'completed' ? 'completed' : 'cancelled';
    }

    // Check if can review
    const canReview = orderHasEnhanced
        ? order.shipping_status === 'delivered' && !order.review_rating
        : order.status === 'completed' && !order.review_rating;

    // Payment method text
    const paymentMethodText = order.payment_method === 'ewallet' ? 'E-Wallet' :
        order.payment_method === 'gcash' ? 'GCash' :
            order.payment_method === 'bank_transfer' ? 'Bank Transfer' :
                order.payment_method === 'cash_on_delivery' ? 'Cash on Delivery' : '';

    return `
        <div class="order-card">
            <div class="order-header">
                <div class="order-info">
                    <h4>Order #${order.id}</h4>
                    <p>${new Date(order.created_at).toLocaleDateString()}</p>
                </div>
                <span class="order-status-badge ${statusClass}">${statusText}</span>
            </div>
            
            <div class="order-details">
                <div class="order-item">
                    ${order.image_url ?
            `<img src="${order.image_url}" alt="${order.product_name}" class="order-item-image">` :
            `<div class="order-item-image" style="display: flex; align-items: center; justify-content: center; background: #f5f5f5;">
                            <i class="fa-solid fa-image" style="color: #ccc;"></i>
                        </div>`
        }
                    <div class="order-item-info">
                        <h5>${order.product_name}</h5>
                        <p>Quantity: ${order.quantity} x $${order.unit_price.toFixed(2)}</p>
                        ${order.counterparty?.name ? `<p style="font-size: 0.8rem; color: #999;">Seller: ${order.counterparty.name}</p>` : ''}
                    </div>
                </div>
            </div>

            ${order.delivery_address ? `
                <div style="margin-top: 0.75rem; padding: 0.75rem; background: #f8f9fa; border-radius: 4px; font-size: 0.85rem;">
                    <i class="fa-solid fa-location-dot" style="color: #666; margin-right: 0.5rem;"></i>
                    ${order.delivery_address}
                </div>
            ` : ''}

            ${paymentMethodText ? `
                <div style="margin-top: 0.75rem; font-size: 0.85rem; color: #666;">
                    <i class="fa-solid fa-wallet" style="margin-right: 0.5rem;"></i>
                    Payment: ${paymentMethodText}
                    ${order.payment_status === 'paid' ?
                '<span style="color: #28a745; margin-left: 0.5rem;"><i class="fa-solid fa-check-circle"></i> Paid</span>' :
                '<span style="color: #ffc107; margin-left: 0.5rem;"><i class="fa-solid fa-clock"></i> Unpaid</span>'
            }
                </div>
            ` : ''}

            ${order.shipping_status === 'shipped' && order.estimated_delivery_time ? `
                <div style="margin-top: 0.75rem; padding: 0.75rem; background: #e7f5e7; border-radius: 4px; font-size: 0.85rem;">
                    <i class="fa-solid fa-truck" style="color: #28a745; margin-right: 0.5rem;"></i>
                    Estimated delivery: ${new Date(order.estimated_delivery_time).toLocaleDateString()}
                </div>
            ` : ''}

            <div class="order-total">
                <strong>Total: $${order.total_price.toFixed(2)}</strong>
            </div>

            ${canReview ? `
                <div class="order-actions">
                    <button id="reviewBtn-${order.id}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-star"></i> Write Review
                    </button>
                </div>
            ` : ''}

            ${order.review_rating ? `
                <div style="margin-top: 0.75rem; padding: 0.75rem; background: #fffbf0; border-radius: 4px;">
                    <div style="font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem;">Your Review:</div>
                    <div style="font-size: 0.9rem;">
                        ${'⭐'.repeat(order.review_rating)} ${order.review_rating}/5
                    </div>
                    ${order.review_comment ? `
                        <div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">${order.review_comment}</div>
                    ` : ''}
                </div>
            ` : ''}
        </div>
    `;
}

// Open review modal
function openReviewModal(orderId) {
    const modal = document.getElementById('reviewModal');
    if (!modal) return;

    const orderIdInput = document.getElementById('reviewOrderId');
    const ratingInput = document.getElementById('reviewRating');
    const commentInput = document.getElementById('reviewComment');

    if (orderIdInput) orderIdInput.value = orderId;
    if (ratingInput) ratingInput.value = '0';
    if (commentInput) commentInput.value = '';

    // Reset stars
    const ratingStarsContainer = document.getElementById('ratingStars');
    if (ratingStarsContainer) {
        const stars = ratingStarsContainer.querySelectorAll('[data-rating]');
        stars.forEach(star => {
            star.classList.remove('fa-solid');
            star.classList.add('fa-regular');
            star.style.opacity = '1';
        });
    }

    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Setup review modal
function setupReviewModal() {
    const modal = document.getElementById('reviewModal');
    const closeBtn = document.getElementById('closeReviewModal');
    const cancelBtn = document.getElementById('cancelReviewBtn');
    const form = document.getElementById('reviewForm');
    const ratingStarsContainer = document.getElementById('ratingStars');

    // Star rating - use event delegation for better reliability
    if (ratingStarsContainer) {
        ratingStarsContainer.addEventListener('click', function (e) {
            const star = e.target.closest('[data-rating]');
            if (!star) return;

            const rating = parseInt(star.getAttribute('data-rating'));
            if (isNaN(rating) || rating < 1 || rating > 5) return;

            const ratingInput = document.getElementById('reviewRating');
            if (ratingInput) {
                ratingInput.value = rating;
            }

            // Update star display
            const stars = ratingStarsContainer.querySelectorAll('[data-rating]');
            stars.forEach((s, index) => {
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

        // Add hover effect for better UX
        ratingStarsContainer.addEventListener('mouseover', function (e) {
            const star = e.target.closest('[data-rating]');
            if (!star) return;

            const hoverRating = parseInt(star.getAttribute('data-rating'));
            if (isNaN(hoverRating)) return;

            const stars = ratingStarsContainer.querySelectorAll('[data-rating]');
            stars.forEach((s) => {
                const starRating = parseInt(s.getAttribute('data-rating'));
                if (starRating <= hoverRating) {
                    s.style.opacity = '1';
                } else {
                    s.style.opacity = '0.5';
                }
            });
        });

        ratingStarsContainer.addEventListener('mouseout', function () {
            const stars = ratingStarsContainer.querySelectorAll('[data-rating]');
            stars.forEach((s) => {
                s.style.opacity = '1';
            });
        });
    }

    // Close modal
    if (closeBtn) {
        closeBtn.addEventListener('click', closeReviewModal);
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeReviewModal);
    }
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeReviewModal();
            }
        });
    }

    // Submit form
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const orderId = document.getElementById('reviewOrderId').value;
            const ratingInput = document.getElementById('reviewRating');
            const rating = ratingInput ? parseInt(ratingInput.value) : 0;
            const comment = document.getElementById('reviewComment') ? document.getElementById('reviewComment').value.trim() : '';

            // Validate rating
            if (!rating || isNaN(rating) || rating < 1 || rating > 5) {
                showMessage('Please select a rating', 'error');
                // Highlight the stars to indicate error
                const starsContainer = document.getElementById('ratingStars');
                if (starsContainer) {
                    starsContainer.style.animation = 'shake 0.5s';
                    setTimeout(() => {
                        starsContainer.style.animation = '';
                    }, 500);
                }
                return;
            }

            try {
                const response = await fetch('api/submit_review.php', {
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
                    showMessage('Review submitted successfully!', 'success');
                    closeReviewModal();
                    loadOrders(); // Reload orders to show the review
                } else {
                    showMessage(result.message || 'Failed to submit review', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Network error. Please try again.', 'error');
            }
        });
    }
}

// Close review modal
function closeReviewModal() {
    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = 'auto';
    }
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

    if (closePasswordModal) {
        closePasswordModal.addEventListener('click', () => closeChangePasswordModal());
    }

    if (cancelPasswordBtn) {
        cancelPasswordBtn.addEventListener('click', () => closeChangePasswordModal());
    }

    if (changePasswordModal) {
        changePasswordModal.addEventListener('click', function (e) {
            if (e.target === changePasswordModal) {
                closeChangePasswordModal();
            }
        });
    }

    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            clearPasswordErrors();

            const oldPassword = document.getElementById('oldPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmNewPassword = document.getElementById('confirmNewPassword').value;

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
                console.error('Error changing password:', error);
                const errorMsg = error.message || 'Network error. Please try again.';
                showPasswordError(errorMsg, 'oldPasswordError');
            }
        });
    }
}

function closeChangePasswordModal() {
    const modal = document.getElementById('changePasswordModal');
    if (modal) {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = 'auto';
        resetPasswordForm();
    }
}

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
                window.location.href = 'index.html';
            });
    }
}

// Show message
function showMessage(message, type = 'success') {
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

    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

// Add styles
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

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    .modal {
        display: none;
    }

    .modal.show {
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

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #333;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }

    .form-group .error {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: block;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 6px;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }

    .btn-primary {
        background: #28a745;
        color: white;
    }

    .btn-primary:hover {
        background: #218838;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }
`;
document.head.appendChild(style);


