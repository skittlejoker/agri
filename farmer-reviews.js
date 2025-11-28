// Load reviews for the farmer
function loadReviews() {
    const reviewsContainer = document.getElementById('reviewsContainer');
    const averageRatingEl = document.getElementById('averageRating');
    const totalReviewsEl = document.getElementById('totalReviews');
    const ratingStarsEl = document.getElementById('ratingStars');

    if (!reviewsContainer) return;

    // Show loading state
    reviewsContainer.innerHTML = `
        <div style="text-align: center; padding: 2rem;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size: 2rem; color: #28a745;"></i>
            <p style="margin-top: 1rem; color: #666;">Loading reviews...</p>
        </div>
    `;

    // Fetch reviews from PHP backend
    fetch('api/get_farmer_reviews.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const reviews = data.reviews || [];
                const averageRating = data.average_rating || 0;
                const totalReviews = data.total || 0;

                // Update summary
                if (averageRatingEl) {
                    averageRatingEl.textContent = averageRating.toFixed(1);
                }
                if (totalReviewsEl) {
                    totalReviewsEl.textContent = totalReviews;
                }
                if (ratingStarsEl) {
                    ratingStarsEl.innerHTML = renderStars(averageRating, true);
                }

                if (reviews.length === 0) {
                    reviewsContainer.innerHTML = `
                        <div style="text-align: center; padding: 3rem; color: #666;">
                            <i class="fa-solid fa-comment-dots" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: #ccc;"></i>
                            <h3 style="color: #333;">No reviews yet</h3>
                            <p style="margin: 1rem 0;">Reviews from buyers will appear here once they submit them.</p>
                        </div>
                    `;
                    return;
                }

                // Display reviews
                reviewsContainer.innerHTML = reviews.map(review => `
                    <div style="background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            ${review.product_image ? `
                            <div style="flex-shrink: 0;">
                                <img src="${review.product_image}" alt="${review.product_name}" 
                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                            </div>
                            ` : ''}
                            <div style="flex: 1; min-width: 200px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                                    <div>
                                        <h4 style="margin: 0; color: #333; font-size: 1.1rem;">${escapeHtml(review.product_name)}</h4>
                                        <p style="margin: 0.25rem 0 0 0; color: #666; font-size: 0.9rem;">
                                            <i class="fa-solid fa-user"></i> ${escapeHtml(review.buyer_name)}
                                        </p>
                                    </div>
                                    <div style="text-align: right;">
                                        ${renderStars(review.rating, false)}
                                        <div style="font-size: 0.85rem; color: #999; margin-top: 0.25rem;">
                                            ${new Date(review.review_date).toLocaleDateString()}
                                        </div>
                                    </div>
                                </div>
                                ${review.comment ? `
                                <div style="margin-top: 0.75rem; padding: 0.75rem; background: #f8f9fa; border-radius: 6px; border-left: 3px solid #28a745;">
                                    <p style="margin: 0; color: #333; line-height: 1.6;">${escapeHtml(review.comment)}</p>
                                </div>
                                ` : ''}
                                <div style="margin-top: 0.75rem; font-size: 0.85rem; color: #666;">
                                    <span>Order #${review.order_id}</span>
                                    <span style="margin: 0 0.5rem;">•</span>
                                    <span>Quantity: ${review.quantity}</span>
                                    <span style="margin: 0 0.5rem;">•</span>
                                    <span>Total: ₱${review.total_price.toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');

            } else {
                reviewsContainer.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: #dc3545;">
                        <i class="fa-solid fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>Error loading reviews: ${data.message || 'Unknown error'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            reviewsContainer.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: #dc3545;">
                    <i class="fa-solid fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Network error. Please try again.</p>
                </div>
            `;
        });
}

// Render star rating
function renderStars(rating, showNumber = false) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);

    let starsHTML = '';
    for (let i = 0; i < fullStars; i++) {
        starsHTML += '<i class="fa-solid fa-star" style="color: #ffc107;"></i>';
    }
    if (hasHalfStar) {
        starsHTML += '<i class="fa-solid fa-star-half-stroke" style="color: #ffc107;"></i>';
    }
    for (let i = 0; i < emptyStars; i++) {
        starsHTML += '<i class="fa-regular fa-star" style="color: #ddd;"></i>';
    }
    if (showNumber && rating > 0) {
        starsHTML += `<span style="margin-left: 0.5rem; color: #666; font-size: 0.9rem;">(${rating.toFixed(1)})</span>`;
    }
    return starsHTML;
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadReviews();
    
    // Setup logout
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to logout?')) {
                // Use fetch to call API logout, then redirect
                fetch('api/logout.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    // Redirect to login page
                    window.location.href = 'login.html';
                })
                .catch(error => {
                    console.error('Logout error:', error);
                    // Redirect anyway
                    window.location.href = 'login.html';
                });
            }
        });
    }

    // Setup change password modal (if available)
    if (typeof setupChangePasswordModal === 'function') {
        setupChangePasswordModal();
    }
});

