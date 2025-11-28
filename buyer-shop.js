// Buyer Shop JavaScript
document.addEventListener('DOMContentLoaded', function () {
    // Initialize shop
    initializeShop();
});

// Show message notification (replaces alert)
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
        max-width: 400px;
    `;
    messageDiv.innerHTML = `
        <i class="fa-solid ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}" style="margin-right: 0.5rem;"></i>
        ${message}
    `;

    document.body.appendChild(messageDiv);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        messageDiv.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => {
            messageDiv.remove();
        }, 300);
    }, 5000);
}

// Add animation styles for messages (only once)
if (!document.getElementById('buyer-shop-message-animations')) {
    const style = document.createElement('style');
    style.id = 'buyer-shop-message-animations';
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

// Initialize shop functionality
async function initializeShop() {
    // Get user from session
    let user = null;
    try {
        const sessionResponse = await fetch('api/check_session.php');
        const sessionData = await sessionResponse.json();
        if (sessionData.logged_in && sessionData.user) {
            user = sessionData.user;
        }
    } catch (error) {
        console.error('Error checking session:', error);
    }

    const productsContainer = document.getElementById('shopProducts');
    const searchInput = document.getElementById('search');
    let allProducts = [];
    let eventsBound = false;

    function renderProducts(list) {
        if (!productsContainer) return;
        if (!list || list.length === 0) {
            productsContainer.innerHTML = '<p style="color: var(--text-light); text-align: center; padding: 2rem;">No products available.</p>';
            return;
        }
        productsContainer.innerHTML = list.map(p => {
            const imageUrl = p.image_url || '';
            const imageId = `product-img-${p.id}`;
            const hasImage = imageUrl && imageUrl.trim() !== '';
            const isOutOfStock = p.stock <= 0;

            return `
                <div class="product-card">
                    <div class="product-image-wrap" ${hasImage ? `onclick="viewProductImage('${imageUrl}', '${p.name.replace(/'/g, "\\'")}')" style="cursor: pointer;"` : ''}>
                        ${hasImage ? `
                            <img 
                                id="${imageId}"
                                src="${imageUrl}" 
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
                        ${p.description ? `<div style="font-size: 0.85rem; color: #666; margin: 0.5rem 0;">${p.description}</div>` : ''}
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
                        <div class="cart-actions" style="justify-content: center;">
                            <button class="btn btn-primary" data-action="buy-now" data-id="${p.id}" ${isOutOfStock ? 'disabled' : ''} style="width: 100%;">
                                <i class="fa-solid fa-shopping-bag"></i> ${isOutOfStock ? 'Out of Stock' : 'Buy Now'}
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Re-attach events
        attachEvents();
    }


    function attachEvents() {
        if (eventsBound) {
            return;
        }
        eventsBound = true;
        productsContainer?.addEventListener('click', function (e) {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;
            const id = parseInt(btn.getAttribute('data-id'));
            const action = btn.getAttribute('data-action');
            const product = allProducts.find(p => p.id === id);
            if (!product) return;

            if (action === 'buy-now') {
                // Direct checkout with single product
                if (product.stock <= 0) {
                    showMessage('Product is out of stock', 'error');
                    return;
                }

                // Set checkout data with single product
                checkoutData.cartItems = [{
                    id: product.id,
                    name: product.name,
                    price: product.price,
                    farmer: product.farmer,
                    farmer_name: product.farmer?.name || 'Farmer',
                    quantity: 1
                }];
                checkoutData.total = product.price;

                // Show checkout modal
                showCheckoutModal();
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


    // Function to fetch and update products
    async function fetchProducts() {
        try {
            const response = await fetch('api/get_all_products.php');
            const data = await response.json();
            if (data.success) {
                allProducts = data.products || [];


                renderProducts(allProducts);
                return true;
            } else {
                console.error('Error loading products:', data.message);
                if (productsContainer) {
                    productsContainer.innerHTML = `<p style="color: #dc3545; text-align: center; padding: 2rem;">Error loading products: ${data.message}</p>`;
                }
                return false;
            }
        } catch (err) {
            console.error('Network error loading products:', err);
            if (productsContainer) {
                productsContainer.innerHTML = '<p style="color: #dc3545; text-align: center; padding: 2rem;">Network error. Please try again.</p>';
            }
            return false;
        }
    }

    // Multi-Step Checkout System
    let currentCheckoutStep = 1;
    let checkoutData = {
        cartItems: [],
        shipping: {},
        payment: {},
        total: 0
    };
    let currentGcashTransaction = null;
    let currentOrderId = null;

    // Function to show checkout modal
    function showCheckoutModal() {
        if (checkoutData.cartItems.length === 0) {
            showMessage('No items selected', 'error');
            return;
        }

        currentCheckoutStep = 1;
        showCheckoutStep(1);
        renderCheckoutCart();

        const modal = document.getElementById('checkoutModal');
        if (modal) {
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    function showCheckoutStep(step) {
        for (let i = 1; i <= 4; i++) {
            const stepContent = document.getElementById(`checkout-step-${i}`);
            if (stepContent) stepContent.style.display = 'none';
            const stepNumber = document.querySelector(`.checkout-step[data-step="${i}"] .step-number`);
            if (stepNumber) stepNumber.classList.remove('active');
        }

        const currentStepContent = document.getElementById(`checkout-step-${step}`);
        if (currentStepContent) currentStepContent.style.display = 'block';
        const currentStepNumber = document.querySelector(`.checkout-step[data-step="${step}"] .step-number`);
        if (currentStepNumber) currentStepNumber.classList.add('active');

        currentCheckoutStep = step;

        if (step === 4) {
            renderOrderReview();
        }
    }

    function renderCheckoutCart() {
        const checkoutCartItems = document.getElementById('checkoutCartItems');
        const checkoutTotal = document.getElementById('checkoutTotal');

        if (!checkoutCartItems) return;

        if (checkoutData.cartItems.length === 0) {
            checkoutCartItems.innerHTML = '<p>No items selected</p>';
            return;
        }

        checkoutCartItems.innerHTML = checkoutData.cartItems.map(item => `
            <div class="cart-item" style="margin-bottom: 0.75rem; padding: 0.75rem; background: #f8f9fa; border-radius: 4px;">
                <div style="flex: 1;">
                    <strong>${item.name}</strong>
                    <div class="muted">Seller: ${item.farmer_name || 'Unknown'}</div>
                    <div>Quantity: ${item.quantity} x $${item.price.toFixed(2)}</div>
                </div>
                <div style="font-weight: 600; color: #28a745;">
                    $${(item.price * item.quantity).toFixed(2)}
                </div>
            </div>
        `).join('');

        if (checkoutTotal) {
            checkoutTotal.textContent = `$${checkoutData.total.toFixed(2)}`;
        }
    }

    function renderOrderReview() {
        const reviewSummary = document.getElementById('orderReviewSummary');
        if (!reviewSummary) return;

        const paymentMethodNames = {
            'ewallet': 'E-Wallet',
            'cash_on_delivery': 'Cash on Delivery',
            'gcash': 'GCash',
            'bank_transfer': 'Bank Transfer'
        };

        reviewSummary.innerHTML = `
            <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <h5 style="margin: 0 0 0.75rem 0;">Order Items</h5>
                ${checkoutData.cartItems.map(item => `
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem;">
                        <span>${item.name} x ${item.quantity}</span>
                        <span>$${(item.price * item.quantity).toFixed(2)}</span>
                    </div>
                `).join('')}
            </div>
            <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <h5 style="margin: 0 0 0.75rem 0;">Shipping Information</h5>
                <div style="font-size: 0.9rem;">
                    <div><strong>Name:</strong> ${checkoutData.shipping.fullName}</div>
                    <div><strong>Contact:</strong> ${checkoutData.shipping.contactNumber}</div>
                    <div><strong>Address:</strong> ${checkoutData.shipping.deliveryAddress}</div>
                    <div><strong>Method:</strong> ${checkoutData.shipping.deliveryMethod}</div>
                </div>
            </div>
            <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <h5 style="margin: 0 0 0.75rem 0;">Payment Method</h5>
                <div style="font-size: 0.9rem;">${paymentMethodNames[checkoutData.payment.method] || checkoutData.payment.method}</div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 600; padding-top: 1rem; border-top: 2px solid #eee;">
                <span>Total:</span>
                <span style="color: #28a745;">$${checkoutData.total.toFixed(2)}</span>
            </div>
        `;
    }

    // Setup checkout event listeners
    function setupCheckoutListeners() {
        // Next buttons
        const nextToShippingBtn = document.getElementById('nextToShippingBtn');
        if (nextToShippingBtn) {
            nextToShippingBtn.addEventListener('click', () => {
                // Just proceed to shipping step, no validation needed yet
                showCheckoutStep(2);
            });
        }

        const nextToPaymentBtn = document.getElementById('nextToPaymentBtn');
        if (nextToPaymentBtn) {
            nextToPaymentBtn.addEventListener('click', () => {
                // Validate shipping form before proceeding
                if (validateShippingForm()) {
                    saveShippingData();
                    showCheckoutStep(3);
                }
            });
        }

        const nextToReviewBtn = document.getElementById('nextToReviewBtn');
        if (nextToReviewBtn) {
            nextToReviewBtn.addEventListener('click', () => {
                savePaymentData();
                const paymentMethod = checkoutData.payment.method;

                // If GCash or Bank Transfer, show payment modal first
                if (paymentMethod === 'gcash' || paymentMethod === 'bank_transfer') {
                    // Don't proceed to review yet, show payment modal
                    if (paymentMethod === 'gcash') {
                        showGcashPaymentModal();
                    } else if (paymentMethod === 'bank_transfer') {
                        showBankTransferModal();
                    }
                } else {
                    // For other payment methods, proceed to review
                    showCheckoutStep(4);
                }
            });
        }

        // Back buttons
        const backToCartBtn = document.getElementById('backToCartBtn');
        if (backToCartBtn) {
            backToCartBtn.addEventListener('click', () => showCheckoutStep(1));
        }

        const backToShippingBtn = document.getElementById('backToShippingBtn');
        if (backToShippingBtn) {
            backToShippingBtn.addEventListener('click', () => showCheckoutStep(2));
        }

        const backToPaymentBtn = document.getElementById('backToPaymentBtn');
        if (backToPaymentBtn) {
            backToPaymentBtn.addEventListener('click', () => showCheckoutStep(3));
        }

        // Close buttons
        const closeCheckoutModal = document.getElementById('closeCheckoutModal');
        const cancelCheckoutBtn = document.getElementById('cancelCheckoutBtn');
        if (closeCheckoutModal) {
            closeCheckoutModal.addEventListener('click', closeCheckoutModalFunc);
        }
        if (cancelCheckoutBtn) {
            cancelCheckoutBtn.addEventListener('click', closeCheckoutModalFunc);
        }

        // Confirm order
        const confirmOrderBtn = document.getElementById('confirmOrderBtn');
        if (confirmOrderBtn) {
            confirmOrderBtn.addEventListener('click', placeOrder);
        }

        // Order confirmation modal buttons
        const viewOrdersBtn = document.getElementById('viewOrdersBtn');
        const continueShoppingBtn = document.getElementById('continueShoppingBtn');
        if (viewOrdersBtn) {
            viewOrdersBtn.addEventListener('click', () => {
                closeOrderConfirmationModal();
                window.location.href = 'buyer_orders.html';
            });
        }
        if (continueShoppingBtn) {
            continueShoppingBtn.addEventListener('click', () => {
                closeOrderConfirmationModal();
                // Clear checkout data
                checkoutData.cartItems = [];
                checkoutData.total = 0;
                fetchProducts();
            });
        }
    }

    function validateShippingForm() {
        const fullName = document.getElementById('fullName').value.trim();
        const contactNumber = document.getElementById('contactNumber').value.trim();
        const deliveryAddress = document.getElementById('deliveryAddress').value.trim();
        const deliveryMethod = document.getElementById('deliveryMethod').value;

        let isValid = true;

        if (!fullName) {
            document.getElementById('fullNameError').textContent = 'Full name is required';
            isValid = false;
        } else {
            document.getElementById('fullNameError').textContent = '';
        }

        if (!contactNumber) {
            document.getElementById('contactNumberError').textContent = 'Contact number is required';
            isValid = false;
        } else {
            document.getElementById('contactNumberError').textContent = '';
        }

        if (!deliveryAddress) {
            document.getElementById('deliveryAddressError').textContent = 'Delivery address is required';
            isValid = false;
        } else {
            document.getElementById('deliveryAddressError').textContent = '';
        }

        if (!deliveryMethod) {
            document.getElementById('deliveryMethodError').textContent = 'Delivery method is required';
            isValid = false;
        } else {
            document.getElementById('deliveryMethodError').textContent = '';
        }

        return isValid;
    }

    function saveShippingData() {
        checkoutData.shipping = {
            fullName: document.getElementById('fullName').value.trim(),
            contactNumber: document.getElementById('contactNumber').value.trim(),
            deliveryAddress: document.getElementById('deliveryAddress').value.trim(),
            deliveryMethod: document.getElementById('deliveryMethod').value
        };
    }

    function savePaymentData() {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        checkoutData.payment = {
            method: paymentMethod ? paymentMethod.value : 'ewallet'
        };
    }

    async function placeOrder() {
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

            const orderItems = checkoutData.cartItems.map(item => ({
                product_id: item.id,
                quantity: item.quantity,
                unit_price: item.price
            }));

            // Format delivery address from shipping info
            const deliveryAddress = `${checkoutData.shipping.fullName}\n${checkoutData.shipping.contactNumber}\n${checkoutData.shipping.deliveryAddress}`;

            const response = await fetch('api/create_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    cart_items: orderItems,
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
                // Clear checkout data
                checkoutData.cartItems = [];
                checkoutData.total = 0;

                // Close checkout modal
                closeCheckoutModalFunc();

                // Show confirmation modal
                // Extract order number from response
                let orderNumber = 'N/A';
                if (result.orders && result.orders.length > 0) {
                    // Use first order ID if multiple orders
                    orderNumber = result.orders[0].order_id;
                } else if (result.order_id) {
                    orderNumber = result.order_id;
                } else if (result.message) {
                    // Try to extract from message
                    const match = result.message.match(/order[#\s]*(\d+)/i);
                    if (match) orderNumber = match[1];
                }

                const orderNumberDisplay = document.getElementById('orderNumberDisplay');
                if (orderNumberDisplay) {
                    orderNumberDisplay.textContent = `#${orderNumber}`;
                }

                const confirmationModal = document.getElementById('orderConfirmationModal');
                if (confirmationModal) {
                    confirmationModal.setAttribute('aria-hidden', 'false');
                    confirmationModal.classList.add('show');
                }

                // Refresh products
                await fetchProducts();
            } else {
                // Handle error response
                let errorMessage = result.message || 'Unknown error';
                // Try to parse JSON if response is text
                if (typeof errorMessage === 'string' && errorMessage.includes('{')) {
                    try {
                        const parsed = JSON.parse(errorMessage);
                        errorMessage = parsed.message || errorMessage;
                    } catch (e) {
                        // Keep original message
                    }
                }
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

    function closeCheckoutModalFunc() {
        const modal = document.getElementById('checkoutModal');
        if (modal) {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = 'auto';
        }
    }

    function closeOrderConfirmationModal() {
        const modal = document.getElementById('orderConfirmationModal');
        if (modal) {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    function setupPaymentModals() {
        setupGcashModal();
        setupBankTransferModal();
    }

    function setupGcashModal() {
        const gcashModal = document.getElementById('gcashPaymentModal');
        const closeGcashModal = document.getElementById('closeGcashModal');
        const cancelGcashBtn = document.getElementById('cancelGcashBtn');
        const generateQRBtn = document.getElementById('generateGcashQRBtn');
        const verifyPaymentBtn = document.getElementById('verifyGcashPaymentBtn');

        if (closeGcashModal) {
            closeGcashModal.addEventListener('click', () => closeGcashModalFunc());
        }
        if (cancelGcashBtn) {
            cancelGcashBtn.addEventListener('click', () => closeGcashModalFunc());
        }
        if (gcashModal) {
            gcashModal.addEventListener('click', (e) => {
                if (e.target === gcashModal) {
                    closeGcashModalFunc();
                }
            });
        }

        if (generateQRBtn) {
            generateQRBtn.addEventListener('click', generateGcashQR);
        }

        if (verifyPaymentBtn) {
            verifyPaymentBtn.addEventListener('click', verifyGcashPayment);
        }
    }

    function showGcashPaymentModal() {
        const modal = document.getElementById('gcashPaymentModal');
        if (!modal) return;

        const total = checkoutData.total;
        document.getElementById('gcashAmount').textContent = `$${total.toFixed(2)}`;

        document.getElementById('gcashMobileNumber').value = '';
        document.getElementById('gcashVerificationCodeInput').value = '';
        document.getElementById('gcashQRCodeContainer').style.display = 'none';
        document.getElementById('gcashVerificationGroup').style.display = 'none';
        document.getElementById('verifyGcashPaymentBtn').style.display = 'none';
        document.getElementById('generateGcashQRBtn').style.display = 'block';
        document.getElementById('gcashPaymentStatus').style.display = 'none';

        document.getElementById('gcashMobileError').textContent = '';
        document.getElementById('gcashVerificationError').textContent = '';

        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeGcashModalFunc() {
        const modal = document.getElementById('gcashPaymentModal');
        if (modal) {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = 'auto';
        }
    }

    async function generateGcashQR() {
        const mobileNumber = document.getElementById('gcashMobileNumber').value.trim();
        const mobileError = document.getElementById('gcashMobileError');

        if (!mobileNumber || mobileNumber.length < 10) {
            mobileError.textContent = 'Please enter a valid GCash mobile number';
            return;
        }

        mobileError.textContent = '';

        const generateBtn = document.getElementById('generateGcashQRBtn');
        if (generateBtn) {
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';
        }

        try {
            if (!currentOrderId) {
                const orderResult = await createOrderForPayment();
                if (!orderResult.success) {
                    throw new Error(orderResult.message || 'Failed to create order');
                }
                currentOrderId = orderResult.order_id;
            }

            const response = await fetch('api/generate_gcash_qr.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    order_id: currentOrderId,
                    amount: checkoutData.total,
                    mobile_number: mobileNumber
                })
            });

            const responseText = await response.text();
            if (!response.ok) {
                let errorData;
                try {
                    errorData = JSON.parse(responseText);
                } catch (e) {
                    errorData = { message: responseText || 'Server error occurred' };
                }
                throw new Error(errorData.message || 'Failed to generate QR code');
            }

            const result = JSON.parse(responseText);

            if (result.success) {
                currentGcashTransaction = result.transaction_id;

                document.getElementById('gcashQRCode').src = result.qr_code_url;
                document.getElementById('gcashQRCodeContainer').style.display = 'block';
                document.getElementById('gcashVerificationCode').textContent = result.verification_code;
                document.getElementById('gcashVerificationGroup').style.display = 'block';
                document.getElementById('verifyGcashPaymentBtn').style.display = 'block';
                document.getElementById('generateGcashQRBtn').style.display = 'none';
            } else {
                throw new Error(result.message || 'Failed to generate QR code');
            }
        } catch (error) {
            console.error('Error generating GCash QR:', error);
            showMessage(error.message || 'Failed to generate QR code. Please try again.', 'error');
            if (generateBtn) {
                generateBtn.disabled = false;
                generateBtn.innerHTML = '<i class="fa-solid fa-qrcode"></i> Generate QR Code';
            }
        }
    }

    async function verifyGcashPayment() {
        const verificationCode = document.getElementById('gcashVerificationCodeInput').value.trim().toUpperCase();
        const verificationError = document.getElementById('gcashVerificationError');

        if (!verificationCode || verificationCode.length !== 8) {
            verificationError.textContent = 'Please enter a valid 8-character verification code';
            return;
        }

        verificationError.textContent = '';

        const verifyBtn = document.getElementById('verifyGcashPaymentBtn');
        if (verifyBtn) {
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying...';
        }

        try {
            const response = await fetch('api/verify_gcash_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    transaction_id: currentGcashTransaction,
                    verification_code: verificationCode
                })
            });

            const responseText = await response.text();
            if (!response.ok) {
                let errorData;
                try {
                    errorData = JSON.parse(responseText);
                } catch (e) {
                    errorData = { message: responseText || 'Server error occurred' };
                }
                throw new Error(errorData.message || 'Failed to verify payment');
            }

            const result = JSON.parse(responseText);

            if (result.success) {
                const statusDiv = document.getElementById('gcashPaymentStatus');
                statusDiv.style.display = 'block';
                statusDiv.style.background = '#d4edda';
                statusDiv.style.color = '#155724';
                statusDiv.innerHTML = `<i class="fa-solid fa-check-circle"></i> ${result.message}`;

                setTimeout(() => {
                    closeGcashModalFunc();
                    closeCheckoutModalFunc();
                    showOrderConfirmation(currentOrderId);
                }, 2000);
            } else {
                throw new Error(result.message || 'Payment verification failed');
            }
        } catch (error) {
            console.error('Error verifying GCash payment:', error);
            showMessage(error.message || 'Failed to verify payment. Please try again.', 'error');
            if (verifyBtn) {
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = '<i class="fa-solid fa-check"></i> Verify Payment';
            }
        }
    }

    function setupBankTransferModal() {
        const bankModal = document.getElementById('bankTransferModal');
        const closeBankModal = document.getElementById('closeBankModal');
        const cancelBankBtn = document.getElementById('cancelBankBtn');
        const submitBankBtn = document.getElementById('submitBankTransferBtn');

        if (closeBankModal) {
            closeBankModal.addEventListener('click', () => closeBankTransferModalFunc());
        }
        if (cancelBankBtn) {
            cancelBankBtn.addEventListener('click', () => closeBankTransferModalFunc());
        }
        if (bankModal) {
            bankModal.addEventListener('click', (e) => {
                if (e.target === bankModal) {
                    closeBankTransferModalFunc();
                }
            });
        }

        if (submitBankBtn) {
            submitBankBtn.addEventListener('click', submitBankTransfer);
        }

        const transferDateInput = document.getElementById('transferDate');
        if (transferDateInput) {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            transferDateInput.value = now.toISOString().slice(0, 16);
        }
    }

    function showBankTransferModal() {
        const modal = document.getElementById('bankTransferModal');
        if (!modal) return;

        const total = checkoutData.total;
        document.getElementById('bankAmount').textContent = `$${total.toFixed(2)}`;

        document.getElementById('bankTransferForm').reset();
        const transferDateInput = document.getElementById('transferDate');
        if (transferDateInput) {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            transferDateInput.value = now.toISOString().slice(0, 16);
        }

        document.getElementById('bankTransferStatus').style.display = 'none';

        ['bankName', 'bankAccountNumber', 'bankAccountName', 'transferReference', 'transferDate'].forEach(id => {
            const errorEl = document.getElementById(id + 'Error');
            if (errorEl) errorEl.textContent = '';
        });

        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeBankTransferModalFunc() {
        const modal = document.getElementById('bankTransferModal');
        if (modal) {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = 'auto';
        }
    }

    async function submitBankTransfer() {
        const bankName = document.getElementById('bankName').value.trim();
        const bankAccountNumber = document.getElementById('bankAccountNumber').value.trim();
        const bankAccountName = document.getElementById('bankAccountName').value.trim();
        const transferReference = document.getElementById('transferReference').value.trim();
        const transferDate = document.getElementById('transferDate').value;

        let isValid = true;
        if (!bankName) {
            document.getElementById('bankNameError').textContent = 'Bank name is required';
            isValid = false;
        }
        if (!bankAccountNumber) {
            document.getElementById('bankAccountNumberError').textContent = 'Account number is required';
            isValid = false;
        }
        if (!bankAccountName) {
            document.getElementById('bankAccountNameError').textContent = 'Account holder name is required';
            isValid = false;
        }
        if (!transferReference) {
            document.getElementById('transferReferenceError').textContent = 'Transfer reference is required';
            isValid = false;
        }
        if (!transferDate) {
            document.getElementById('transferDateError').textContent = 'Transfer date is required';
            isValid = false;
        }

        if (!isValid) return;

        const submitBtn = document.getElementById('submitBankTransferBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
        }

        try {
            if (!currentOrderId) {
                const orderResult = await createOrderForPayment();
                if (!orderResult.success) {
                    throw new Error(orderResult.message || 'Failed to create order');
                }
                currentOrderId = orderResult.order_id;
            }

            const response = await fetch('api/create_bank_transfer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    order_id: currentOrderId,
                    amount: checkoutData.total,
                    bank_name: bankName,
                    bank_account_number: bankAccountNumber,
                    bank_account_name: bankAccountName,
                    transfer_reference: transferReference,
                    transfer_date: transferDate
                })
            });

            const responseText = await response.text();
            if (!response.ok) {
                let errorData;
                try {
                    errorData = JSON.parse(responseText);
                } catch (e) {
                    errorData = { message: responseText || 'Server error occurred' };
                }
                throw new Error(errorData.message || 'Failed to submit bank transfer');
            }

            const result = JSON.parse(responseText);

            if (result.success) {
                if (result.merchant_bank_details) {
                    document.getElementById('merchantBankName').textContent = result.merchant_bank_details.bank_name;
                    document.getElementById('merchantAccountNumber').textContent = result.merchant_bank_details.account_number;
                    document.getElementById('merchantAccountName').textContent = result.merchant_bank_details.account_name;
                    document.getElementById('merchantSwiftCode').textContent = result.merchant_bank_details.swift_code;
                }

                const statusDiv = document.getElementById('bankTransferStatus');
                statusDiv.style.display = 'block';
                statusDiv.style.background = '#d4edda';
                statusDiv.style.color = '#155724';
                statusDiv.innerHTML = `
                    <i class="fa-solid fa-check-circle"></i> 
                    <p><strong>${result.message}</strong></p>
                    <p style="font-size: 0.9rem; margin-top: 0.5rem;">
                        Transaction Reference: <strong>${result.transaction_reference}</strong>
                    </p>
                    <p style="font-size: 0.85rem; margin-top: 0.5rem; color: #666;">
                        Your payment will be verified within 24 hours. You will receive a notification once verified.
                    </p>
                `;

                setTimeout(() => {
                    closeBankTransferModalFunc();
                    closeCheckoutModalFunc();
                    showOrderConfirmation(currentOrderId);
                }, 3000);
            } else {
                throw new Error(result.message || 'Failed to submit bank transfer');
            }
        } catch (error) {
            console.error('Error submitting bank transfer:', error);
            showMessage(error.message || 'Failed to submit bank transfer. Please try again.', 'error');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-check"></i> Submit Transfer Details';
            }
        }
    }

    async function createOrderForPayment() {
        const orderItems = checkoutData.cartItems.map(item => ({
            product_id: item.id,
            quantity: item.quantity,
            unit_price: item.price
        }));

        const deliveryAddress = `${checkoutData.shipping.fullName}\n${checkoutData.shipping.contactNumber}\n${checkoutData.shipping.deliveryAddress}`;

        const response = await fetch('api/create_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                cart_items: orderItems,
                delivery_address: deliveryAddress,
                payment_method: checkoutData.payment.method
            })
        });

        const responseText = await response.text();
        if (!response.ok) {
            let errorData;
            try {
                errorData = JSON.parse(responseText);
            } catch (e) {
                errorData = { message: responseText || 'Server error occurred' };
            }
            return { success: false, message: errorData.message || 'Failed to create order' };
        }

        const result = JSON.parse(responseText);

        if (result.success) {
            let orderId = null;
            if (result.orders && result.orders.length > 0) {
                orderId = result.orders[0].order_id;
            }

            return { success: true, order_id: orderId };
        } else {
            return { success: false, message: result.message || 'Failed to create order' };
        }
    }

    function showOrderConfirmation(orderId) {
        const orderNumberDisplay = document.getElementById('orderNumberDisplay');
        if (orderNumberDisplay) {
            orderNumberDisplay.textContent = `#${orderId}`;
        }

        const confirmationModal = document.getElementById('orderConfirmationModal');
        if (confirmationModal) {
            confirmationModal.setAttribute('aria-hidden', 'false');
            confirmationModal.classList.add('show');
        }

        if (typeof fetchProducts === 'function') {
            fetchProducts();
        }
    }

    // Setup password modal and logout
    setupPasswordModal();
    setupLogout();

    // Setup payment modals
    setupPaymentModals();

    // Initialize
    setupCheckoutListeners();
    fetchProducts();
}


// Setup password modal
function setupPasswordModal() {
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
        closePasswordModal.addEventListener('click', () => closePasswordModalFunc());
    }
    if (cancelPasswordBtn) {
        cancelPasswordBtn.addEventListener('click', () => closePasswordModalFunc());
    }

    if (changePasswordModal) {
        changePasswordModal.addEventListener('click', function (e) {
            if (e.target === changePasswordModal) {
                closePasswordModalFunc();
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
                    closePasswordModalFunc();
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

function closePasswordModalFunc() {
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

// Setup logout
function setupLogout() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
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
        });
    }
}

// Add modal styles
const style = document.createElement('style');
style.textContent = `
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
        max-width: 700px;
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
`;
document.head.appendChild(style);

