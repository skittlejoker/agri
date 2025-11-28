// Help Assistant System - Context-aware help for farmers and buyers with Chat Support

// Help content based on page context
const helpContent = {
    // Farmer Pages
    'farmer-dashboard': {
        title: 'Farmer Dashboard Help',
        sections: [
            {
                icon: 'fa-solid fa-chart-line',
                title: 'Dashboard Overview',
                content: 'Your dashboard provides quick access to all your farming activities. Use the Quick Actions cards to navigate to different sections.'
            },
            {
                icon: 'fa-solid fa-box',
                title: 'My Products',
                content: 'Manage your product listings, add new products, update prices, and track inventory. You can add products with prices per kilogram (kg) and optionally per gram (g).'
            },
            {
                icon: 'fa-solid fa-shopping-cart',
                title: 'Orders',
                content: 'View and manage orders from buyers. Update order status: Pending → Confirmed → Shipped → Completed. You can also cancel orders if needed.'
            },
            {
                icon: 'fa-solid fa-star',
                title: 'Reviews',
                content: 'See what buyers are saying about your products. View ratings and comments to improve your offerings.'
            }
        ]
    },
    'farmer-products': {
        title: 'Product Management Help',
        sections: [
            {
                icon: 'fa-solid fa-plus',
                title: 'Adding Products',
                content: 'Click "Add Product" to create a new listing. Fill in: Product Name (required), Price per Kilogram (required), Price per Gram (optional), Stock Quantity, Description, and Image URL.'
            },
            {
                icon: 'fa-solid fa-peso-sign',
                title: 'Pricing',
                content: 'Set prices in Philippine Peso (₱). You must enter a price per kilogram. Optionally add a price per gram - this will be displayed as a sub-price on your product cards.'
            },
            {
                icon: 'fa-solid fa-boxes-stacked',
                title: 'Stock Management',
                content: 'Update stock quantities directly on product cards. Stock is automatically reduced when orders are placed and restored if orders are cancelled.'
            },
            {
                icon: 'fa-solid fa-image',
                title: 'Product Images',
                content: 'Add product images by providing an image URL. Make sure the URL is publicly accessible. Images will be displayed on product cards.'
            },
            {
                icon: 'fa-solid fa-edit',
                title: 'Editing Products',
                content: 'Click the edit button on any product card to modify its details. Update prices, stock, description, or image as needed.'
            },
            {
                icon: 'fa-solid fa-trash',
                title: 'Deleting Products',
                content: 'Click the delete button to remove a product from your listings. This action cannot be undone.'
            }
        ]
    },
    'farmer-orders': {
        title: 'Order Management Help',
        sections: [
            {
                icon: 'fa-solid fa-list',
                title: 'Orders to Ship',
                content: 'View all pending and confirmed orders that need to be shipped. Update the status as you process each order.'
            },
            {
                icon: 'fa-solid fa-truck',
                title: 'Order Status',
                content: 'Use the dropdown to change order status: Pending → Confirmed → Shipped → Completed. Only completed orders can be reviewed by buyers.'
            },
            {
                icon: 'fa-solid fa-check-circle',
                title: 'Shipped Orders',
                content: 'Track orders that have been shipped. Once marked as "Shipped", buyers will see tracking information.'
            },
            {
                icon: 'fa-solid fa-times-circle',
                title: 'Cancelling Orders',
                content: 'You can cancel orders if needed. Cancelled orders will have their stock automatically restored to your inventory.'
            }
        ]
    },
    'farmer-reviews': {
        title: 'Reviews Help',
        sections: [
            {
                icon: 'fa-solid fa-star',
                title: 'Viewing Reviews',
                content: 'See all reviews from buyers who purchased your products. Reviews include ratings (1-5 stars) and comments.'
            },
            {
                icon: 'fa-solid fa-chart-bar',
                title: 'Average Rating',
                content: 'Your average rating is calculated from all reviews. This helps you understand buyer satisfaction with your products.'
            },
            {
                icon: 'fa-solid fa-comment',
                title: 'Review Details',
                content: 'Each review shows the product name, buyer name, rating, comment, order details, and review date.'
            }
        ]
    },
    // Buyer Pages
    'buyer-dashboard': {
        title: 'Buyer Dashboard Help',
        sections: [
            {
                icon: 'fa-solid fa-store',
                title: 'Shop',
                content: 'Browse and purchase fresh agricultural products from local farmers. Add items to your cart and checkout when ready.'
            },
            {
                icon: 'fa-solid fa-shopping-bag',
                title: 'My Orders',
                content: 'Track all your orders, view order history, and see order status updates from farmers.'
            },
            {
                icon: 'fa-solid fa-cart-shopping',
                title: 'Shopping Cart',
                content: 'Add products to your cart and manage quantities before checkout. Cart items are saved automatically.'
            }
        ]
    },
    'buyer-shop': {
        title: 'Shopping Help',
        sections: [
            {
                icon: 'fa-solid fa-search',
                title: 'Finding Products',
                content: 'Browse products by category or use the search function. Each product shows price per kilogram (kg) and optionally per gram (g).'
            },
            {
                icon: 'fa-solid fa-cart-plus',
                title: 'Adding to Cart',
                content: 'Click "Add" or use the +/- buttons to adjust quantity. You cannot add more items than available stock.'
            },
            {
                icon: 'fa-solid fa-peso-sign',
                title: 'Pricing',
                content: 'Products show prices in Philippine Peso (₱). Both kilogram and gram prices are displayed when available.'
            },
            {
                icon: 'fa-solid fa-box',
                title: 'Stock Availability',
                content: 'Stock levels are shown on each product. Green badge = in stock, Yellow = low stock, Red = out of stock.'
            },
            {
                icon: 'fa-solid fa-credit-card',
                title: 'Checkout',
                content: 'Click "Buy Now" to proceed to checkout. You can pay via GCash, Bank Transfer, or Cash on Delivery.'
            }
        ]
    },
    'buyer-orders': {
        title: 'My Orders Help',
        sections: [
            {
                icon: 'fa-solid fa-filter',
                title: 'Order Filters',
                content: 'Use tabs to filter orders: All, Unpaid, To Ship, Shipped, To Review, Returns. This helps you find specific orders quickly.'
            },
            {
                icon: 'fa-solid fa-clock',
                title: 'Order Status',
                content: 'Track your order progress: Pending → Confirmed → Shipped → Completed. Status updates are provided by the farmer.'
            },
            {
                icon: 'fa-solid fa-star',
                title: 'Writing Reviews',
                content: 'After an order is completed, you can write a review. Rate the product (1-5 stars) and add a comment about your experience.'
            },
            {
                icon: 'fa-solid fa-truck',
                title: 'Tracking Orders',
                content: 'Once an order is shipped, you\'ll see delivery tracking information and estimated delivery time.'
            },
            {
                icon: 'fa-solid fa-credit-card',
                title: 'Payment',
                content: 'View payment status for each order. Unpaid orders will show payment options and instructions.'
            }
        ]
    }
};

// FAQ Database - Common questions and answers
const faqDatabase = {
    // General Questions
    general: [
        {
            keywords: ['login', 'sign in', 'account', 'password', 'forgot password'],
            question: 'How do I login or reset my password?',
            answer: 'Use your registered email and password to login. If you forgot your password, contact the administrator or use the "Change Password" option in your account settings.'
        },
        {
            keywords: ['logout', 'sign out', 'exit'],
            question: 'How do I logout?',
            answer: 'Click the "Logout" button in the top right corner of the navigation bar to safely sign out of your account.'
        },
        {
            keywords: ['contact', 'support', 'help', 'assistance', 'problem', 'issue'],
            question: 'How can I get support?',
            answer: 'You can use this help assistant chat to ask questions. For urgent issues, contact the platform administrator directly.'
        }
    ],
    // Farmer Questions
    farmer: [
        {
            keywords: ['add product', 'create product', 'new product', 'list product'],
            question: 'How do I add a new product?',
            answer: 'Go to "My Products" page and click the "Add Product" button. Fill in the product name, price per kilogram (required), optional price per gram, stock quantity, description, and image URL. Click "Add Product" to save.'
        },
        {
            keywords: ['edit product', 'update product', 'modify product', 'change product'],
            question: 'How do I edit a product?',
            answer: 'On the "My Products" page, click the "Edit" button on any product card. Modify the details you want to change and click "Update Product" to save changes.'
        },
        {
            keywords: ['delete product', 'remove product'],
            question: 'How do I delete a product?',
            answer: 'Click the "Delete" button on the product card you want to remove. Note: This action cannot be undone, and the product will be permanently removed from your listings.'
        },
        {
            keywords: ['price', 'pricing', 'set price', 'change price', 'peso', 'php'],
            question: 'How do I set product prices?',
            answer: 'You must set a price per kilogram (₱/kg). You can optionally add a price per gram (₱/g). Both prices will be displayed on your product cards. Prices are in Philippine Peso (₱).'
        },
        {
            keywords: ['stock', 'inventory', 'quantity', 'update stock'],
            question: 'How do I manage stock?',
            answer: 'You can update stock quantities directly on product cards using the stock input field. Stock is automatically reduced when orders are placed and restored if orders are cancelled.'
        },
        {
            keywords: ['order', 'orders', 'view orders', 'manage orders'],
            question: 'How do I manage orders?',
            answer: 'Go to the "Orders" page to see all orders. Use the status dropdown to update order status: Pending → Confirmed → Shipped → Completed. You can also cancel orders if needed.'
        },
        {
            keywords: ['shipped', 'ship', 'shipping', 'delivery'],
            question: 'How do I mark an order as shipped?',
            answer: 'On the "Orders" page, select "Shipped" from the status dropdown for the order. This will notify the buyer that their order is on the way.'
        },
        {
            keywords: ['review', 'reviews', 'see reviews', 'view reviews', 'rating'],
            question: 'How do I see buyer reviews?',
            answer: 'Go to the "Reviews" page to see all reviews from buyers. You can see ratings, comments, and which products were reviewed.'
        },
        {
            keywords: ['about this page', 'what is this page', 'page information', 'this page', 'what does this page do', 'page purpose'],
            question: 'What is this page about?',
            answer: 'This is your farmer dashboard where you can manage your agricultural products, view and process orders from buyers, and see reviews. Use the navigation bar to access different sections: Dashboard (overview), My Products (manage listings), Orders (process orders), and Reviews (view buyer feedback).'
        },
        {
            keywords: ['dashboard', 'home', 'main page', 'overview', 'what is dashboard'],
            question: 'What is the dashboard?',
            answer: 'The dashboard is your main page showing quick access to all features: My Products, Orders, and Reviews. Use the navigation bar to switch between sections.'
        },
        {
            keywords: ['navigation', 'menu', 'nav bar', 'navigation bar', 'how to navigate'],
            question: 'How do I navigate the platform?',
            answer: 'Use the navigation bar at the top of the page. For farmers: Dashboard, My Products, Orders, Reviews, Change Password, Help, and Logout. Click on any link to go to that section.'
        }
    ],
    // Buyer Questions
    buyer: [
        {
            keywords: ['shop', 'browse', 'products', 'find products', 'search'],
            question: 'How do I find products?',
            answer: 'Go to the "Shop" page to browse all available products. You can search by product name or browse by category. Each product shows price per kg and optionally per gram.'
        },
        {
            keywords: ['add to cart', 'cart', 'shopping cart', 'add item'],
            question: 'How do I add products to cart?',
            answer: 'On the Shop page, click "Add" or use the +/- buttons to adjust quantity, then click "Add to Cart". You cannot add more than the available stock.'
        },
        {
            keywords: ['checkout', 'buy', 'purchase', 'order', 'place order'],
            question: 'How do I checkout?',
            answer: 'Click "Buy Now" on any product or go to your cart and proceed to checkout. Fill in your shipping address and select a payment method (GCash, Bank Transfer, or Cash on Delivery).'
        },
        {
            keywords: ['payment', 'pay', 'gcash', 'bank', 'cod', 'cash on delivery'],
            question: 'What payment methods are available?',
            answer: 'You can pay via GCash, Bank Transfer, or Cash on Delivery (COD). For COD, payment is made when the order is delivered.'
        },
        {
            keywords: ['track order', 'order status', 'where is my order', 'order tracking'],
            question: 'How do I track my order?',
            answer: 'Go to "My Orders" page to see all your orders. The status will show: Pending → Confirmed → Shipped → Completed. Once shipped, you\'ll see delivery tracking information.'
        },
        {
            keywords: ['review', 'write review', 'rate', 'rating', 'comment'],
            question: 'How do I write a review?',
            answer: 'After an order is completed, go to "My Orders" and find the completed order. Click "Write Review" to rate the product (1-5 stars) and add a comment about your experience.'
        },
        {
            keywords: ['cancel order', 'cancel', 'return'],
            question: 'Can I cancel an order?',
            answer: 'You can request to cancel an order if it hasn\'t been shipped yet. Contact the farmer or use the order management options on the "My Orders" page.'
        },
        {
            keywords: ['about this page', 'what is this page', 'page information', 'this page', 'what does this page do', 'page purpose'],
            question: 'What is this page about?',
            answer: 'This is your buyer dashboard where you can browse and purchase agricultural products, manage your orders, and write reviews. Use the navigation bar to access: Dashboard (overview), Shop (browse products), My Orders (track purchases), and Cart (view items).'
        },
        {
            keywords: ['dashboard', 'home', 'main page', 'overview', 'what is dashboard'],
            question: 'What is the dashboard?',
            answer: 'The dashboard is your main page showing quick access to Shop, Cart, and My Orders. Use the navigation bar to switch between sections.'
        },
        {
            keywords: ['navigation', 'menu', 'nav bar', 'navigation bar', 'how to navigate'],
            question: 'How do I navigate the platform?',
            answer: 'Use the navigation bar at the top of the page. For buyers: Dashboard, Shop, My Orders, Change Password, Help, and Logout. Click on any link to go to that section.'
        }
    ]
};

// Get current page context
function getPageContext() {
    const body = document.body;
    const pageAttr = body.getAttribute('data-page');
    return pageAttr || 'default';
}

// Get user type (farmer or buyer)
function getUserType() {
    const context = getPageContext();
    if (context.startsWith('farmer-')) return 'farmer';
    if (context.startsWith('buyer-')) return 'buyer';
    return 'general';
}

// Handle greetings and casual interactions
function handleGreeting(query) {
    const queryLower = query.toLowerCase().trim();
    const greetings = ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening', 'greetings', 'greeting'];
    const thanks = ['thanks', 'thank you', 'thank', 'ty', 'appreciate', 'appreciated'];
    const bye = ['bye', 'goodbye', 'see you', 'later', 'exit', 'quit'];
    const help = ['help', 'what can you do', 'what do you do', 'capabilities', 'features'];

    if (greetings.some(g => queryLower.includes(g))) {
        const userType = getUserType();
        const context = getPageContext();
        let contextInfo = '';

        if (userType === 'farmer') {
            if (context === 'farmer-products') {
                contextInfo = 'I see you\'re on the Products page. I can help you with adding, editing, or managing your products.';
            } else if (context === 'farmer-orders') {
                contextInfo = 'I see you\'re managing orders. I can help you with shipping, tracking, or updating order statuses.';
            } else if (context === 'farmer-reviews') {
                contextInfo = 'I see you\'re viewing reviews. I can help you understand and respond to buyer feedback.';
            } else {
                contextInfo = 'I can help you with managing your products, orders, and reviews.';
            }
        } else {
            if (context === 'buyer-shop') {
                contextInfo = 'I see you\'re shopping. I can help you find products, add items to cart, or learn about payment options.';
            } else if (context === 'buyer-orders') {
                contextInfo = 'I see you\'re checking your orders. I can help you track orders, write reviews, or manage your purchases.';
            } else {
                contextInfo = 'I can help you with shopping, placing orders, and tracking your purchases.';
            }
        }

        return {
            answer: `Hello! 👋 Welcome to AgriMarket Help Assistant.\n\n${contextInfo}\n\nWhat would you like to know?`,
            isGreeting: true,
            showSuggestions: true
        };
    }

    if (thanks.some(t => queryLower.includes(t))) {
        const hasHistory = conversationContext.askedQuestions.length > 0;
        const followUp = hasHistory
            ? 'Is there anything else you\'d like to know about?'
            : 'Feel free to ask me anything about using the platform!';

        return {
            answer: `You're welcome! 😊 ${followUp}`,
            isGreeting: true,
            showSuggestions: true
        };
    }

    if (bye.some(b => queryLower.includes(b))) {
        return {
            answer: "Goodbye! 👋 Feel free to come back anytime if you need help. Have a great day!",
            isGreeting: true,
            showSuggestions: false
        };
    }

    if (help.some(h => queryLower.includes(h))) {
        const userType = getUserType();
        let capabilities = '';

        if (userType === 'farmer') {
            capabilities = '• Add and manage products\n• Set prices (per kg and per gram)\n• Manage inventory and stock\n• Process and ship orders\n• View buyer reviews\n• Track sales and performance';
        } else {
            capabilities = '• Browse and search products\n• Add items to cart\n• Place orders\n• Track order status\n• Write product reviews\n• Manage payments';
        }

        return {
            answer: `I can help you with various aspects of the platform! 💡\n\n${capabilities}\n\nWhat would you like to know more about?`,
            isGreeting: true,
            showSuggestions: true
        };
    }

    return null;
}

// Get suggested questions based on user type and context
function getSuggestedQuestions(relatedToTopic = null) {
    const userType = getUserType();
    const context = getPageContext();

    // If related to a topic, provide follow-up questions
    if (relatedToTopic) {
        if (relatedToTopic.includes('product')) {
            if (userType === 'farmer') {
                return [
                    'How do I edit a product?',
                    'How do I manage stock?',
                    'How do I delete a product?'
                ];
            } else {
                return [
                    'How do I add products to cart?',
                    'What payment methods are available?',
                    'How do I checkout?'
                ];
            }
        } else if (relatedToTopic.includes('order')) {
            if (userType === 'farmer') {
                return [
                    'How do I mark an order as shipped?',
                    'How do I cancel an order?',
                    'How do I see order details?'
                ];
            } else {
                return [
                    'How do I track my order?',
                    'How do I write a review?',
                    'Can I cancel an order?'
                ];
            }
        } else if (relatedToTopic.includes('review')) {
            return [
                'How do I see more reviews?',
                'How do I respond to reviews?'
            ];
        }
    }

    // Default suggestions based on page
    if (userType === 'farmer') {
        if (context === 'farmer-products') {
            return [
                'How do I add a new product?',
                'How do I set product prices?',
                'How do I manage stock?',
                'How do I edit a product?'
            ];
        } else if (context === 'farmer-orders') {
            return [
                'How do I manage orders?',
                'How do I mark an order as shipped?',
                'How do I cancel an order?'
            ];
        } else if (context === 'farmer-reviews') {
            return [
                'How do I see buyer reviews?',
                'How do I improve my ratings?'
            ];
        } else {
            return [
                'How do I add a new product?',
                'How do I manage orders?',
                'How do I see buyer reviews?'
            ];
        }
    } else if (userType === 'buyer') {
        if (context === 'buyer-shop') {
            return [
                'How do I find products?',
                'How do I add products to cart?',
                'What payment methods are available?'
            ];
        } else if (context === 'buyer-orders') {
            return [
                'How do I track my order?',
                'How do I write a review?',
                'Can I cancel an order?'
            ];
        } else {
            return [
                'How do I find products?',
                'How do I checkout?',
                'How do I track my order?'
            ];
        }
    }

    return [
        'How do I login?',
        'How do I get support?'
    ];
}

// Get action buttons based on topic
function getActionButtons(topic, question) {
    const userType = getUserType();
    const context = getPageContext();
    const actions = [];

    if (!topic || !question) return actions;

    const topicLower = topic.toLowerCase();
    const questionLower = question.toLowerCase();

    // Product-related actions
    if (topicLower.includes('product') || questionLower.includes('product')) {
        if (userType === 'farmer' && context !== 'farmer-products') {
            actions.push({
                text: 'Go to My Products',
                icon: 'fa-solid fa-box',
                action: () => {
                    window.location.href = 'farmer_products.html';
                }
            });
        } else if (userType === 'buyer' && context !== 'buyer-shop') {
            actions.push({
                text: 'Go to Shop',
                icon: 'fa-solid fa-store',
                action: () => {
                    window.location.href = 'buyer_shop.html';
                }
            });
        }
    }

    // Order-related actions
    if (topicLower.includes('order') || questionLower.includes('order')) {
        if (userType === 'farmer' && context !== 'farmer-orders') {
            actions.push({
                text: 'Go to Orders',
                icon: 'fa-solid fa-shopping-cart',
                action: () => {
                    window.location.href = 'farmer_orders.html';
                }
            });
        } else if (userType === 'buyer' && context !== 'buyer-orders') {
            actions.push({
                text: 'Go to My Orders',
                icon: 'fa-solid fa-list',
                action: () => {
                    window.location.href = 'buyer_orders.html';
                }
            });
        }
    }

    // Review-related actions
    if (topicLower.includes('review') || questionLower.includes('review')) {
        if (userType === 'farmer' && context !== 'farmer-reviews') {
            actions.push({
                text: 'Go to Reviews',
                icon: 'fa-solid fa-star',
                action: () => {
                    window.location.href = 'farmer_reviews.html';
                }
            });
        }
    }

    return actions;
}

// Semantic synonyms and related terms for better matching
const semanticMap = {
    'product': ['item', 'goods', 'listing', 'merchandise', 'commodity', 'crop', 'produce', 'harvest'],
    'order': ['purchase', 'transaction', 'buy', 'sale', 'deal'],
    'price': ['cost', 'amount', 'fee', 'charge', 'rate', 'value'],
    'stock': ['inventory', 'quantity', 'available', 'supply', 'amount', 'count'],
    'add': ['create', 'new', 'post', 'upload', 'register', 'list', 'insert'],
    'edit': ['update', 'modify', 'change', 'alter', 'revise', 'adjust'],
    'delete': ['remove', 'erase', 'clear', 'drop', 'eliminate'],
    'ship': ['deliver', 'send', 'dispatch', 'transport', 'mail'],
    'review': ['rating', 'feedback', 'comment', 'opinion', 'evaluation', 'testimonial'],
    'cart': ['basket', 'bag', 'shopping cart', 'checkout'],
    'payment': ['pay', 'paying', 'paid', 'transaction', 'billing', 'checkout'],
    'track': ['trace', 'follow', 'monitor', 'locate', 'find'],
    'cancel': ['stop', 'abort', 'terminate', 'void', 'withdraw'],
    'search': ['find', 'look', 'browse', 'discover', 'locate'],
    'account': ['profile', 'settings', 'user', 'account settings'],
    'password': ['pass', 'pwd', 'security', 'login'],
    'help': ['assist', 'support', 'guide', 'aid', 'information']
};

// Extract intent from query
function extractIntent(query) {
    const queryLower = query.toLowerCase().trim();
    const intents = {
        action: null,
        topic: null,
        questionType: null
    };

    // Question type detection
    if (queryLower.match(/^(how|what|where|when|why|can|do|is|are|will)/)) {
        intents.questionType = 'howto';
    } else if (queryLower.match(/^(what|which|tell me about|explain)/)) {
        intents.questionType = 'information';
    } else if (queryLower.match(/^(can|may|could|is it possible|am i able)/)) {
        intents.questionType = 'permission';
    }

    // Action detection
    const actions = ['add', 'create', 'edit', 'update', 'delete', 'remove', 'view', 'see', 'find', 'search', 'track', 'cancel', 'ship', 'pay', 'buy', 'purchase', 'checkout'];
    for (const action of actions) {
        if (queryLower.includes(action)) {
            intents.action = action;
            break;
        }
    }

    // Topic detection
    const topics = ['product', 'order', 'price', 'stock', 'review', 'cart', 'payment', 'account', 'password', 'dashboard', 'shop'];
    for (const topic of topics) {
        if (queryLower.includes(topic)) {
            intents.topic = topic;
            break;
        }
    }

    return intents;
}

// Expand query with synonyms
function expandQuery(query) {
    const queryLower = query.toLowerCase();
    const expanded = [queryLower];

    // Add synonyms
    for (const [term, synonyms] of Object.entries(semanticMap)) {
        if (queryLower.includes(term)) {
            synonyms.forEach(syn => {
                const expandedQuery = queryLower.replace(term, syn);
                expanded.push(expandedQuery);
            });
        }
    }

    return expanded;
}

// Calculate similarity score between two strings
function calculateSimilarity(str1, str2) {
    const longer = str1.length > str2.length ? str1 : str2;
    const shorter = str1.length > str2.length ? str2 : str1;

    if (longer.length === 0) return 1.0;

    // Exact match
    if (str1 === str2) return 1.0;

    // Contains match
    if (longer.includes(shorter)) return 0.8;

    // Word overlap
    const words1 = str1.split(/\s+/);
    const words2 = str2.split(/\s+/);
    const commonWords = words1.filter(w => words2.includes(w));
    if (commonWords.length > 0) {
        return 0.5 + (commonWords.length / Math.max(words1.length, words2.length)) * 0.3;
    }

    // Character similarity (simple Levenshtein-like)
    let matches = 0;
    for (let i = 0; i < shorter.length; i++) {
        if (longer.includes(shorter[i])) matches++;
    }
    return matches / longer.length * 0.3;
}

// Find answer in FAQ database with intelligent matching
function findAnswer(query) {
    const userType = getUserType();
    const queryLower = query.toLowerCase().trim();

    // Check for greetings first
    const greetingResponse = handleGreeting(query);
    if (greetingResponse) {
        return greetingResponse;
    }

    // Extract intent
    const intent = extractIntent(query);

    // Search in user-specific FAQs first
    const userFaqs = faqDatabase[userType] || [];
    const generalFaqs = faqDatabase.general || [];
    const allFaqs = [...userFaqs, ...generalFaqs];

    // Handle "about this page" queries intelligently
    if (queryLower.includes('about this page') || queryLower.includes('what is this page') ||
        queryLower.includes('this page') || queryLower.includes('page information')) {
        const context = getPageContext();

        // Try to find page-specific FAQ first
        const pageSpecificFaq = allFaqs.find(faq =>
            faq.keywords.some(kw => kw.includes('about this page') || kw.includes('this page'))
        );

        if (pageSpecificFaq) {
            return {
                question: pageSpecificFaq.question,
                answer: pageSpecificFaq.answer,
                found: true,
                showSuggestions: true
            };
        }
    }

    // Expand query with synonyms
    const expandedQueries = expandQuery(query);

    // Score-based matching
    let bestMatch = null;
    let bestScore = 0;
    const matches = [];

    for (const faq of allFaqs) {
        let score = 0;
        let matchedKeywords = [];

        // Check each keyword
        for (const keyword of faq.keywords) {
            const keywordLower = keyword.toLowerCase();

            // Exact phrase match (highest priority)
            for (const expandedQuery of expandedQueries) {
                if (expandedQuery.includes(keywordLower) || keywordLower.includes(expandedQuery)) {
                    score += 15;
                    matchedKeywords.push(keyword);
                    break;
                }
            }

            // Word-by-word matching
            const keywordWords = keywordLower.split(/\s+/);
            const queryWords = queryLower.split(/\s+/).filter(w => w.length > 2);

            let wordMatches = 0;
            for (const kw of keywordWords) {
                for (const qw of queryWords) {
                    if (qw.includes(kw) || kw.includes(qw)) {
                        score += 5;
                        wordMatches++;
                        break;
                    }
                }
            }

            if (wordMatches === keywordWords.length && wordMatches > 0) {
                matchedKeywords.push(keyword);
            }

            // Semantic similarity
            const similarity = calculateSimilarity(queryLower, keywordLower);
            if (similarity > 0.5) {
                score += similarity * 10;
            }
        }

        // Intent-based scoring
        if (intent.topic) {
            const questionLower = faq.question.toLowerCase();
            const answerLower = faq.answer.toLowerCase();
            if (questionLower.includes(intent.topic) || answerLower.includes(intent.topic)) {
                score += 8;
            }
        }

        if (intent.action) {
            const questionLower = faq.question.toLowerCase();
            if (questionLower.includes(intent.action)) {
                score += 8;
            }
        }

        // Question text matching
        const questionLower = faq.question.toLowerCase();
        const queryWords = queryLower.split(/\s+/).filter(w => w.length > 2);
        let questionMatches = 0;
        for (const qw of queryWords) {
            if (questionLower.includes(qw)) {
                questionMatches++;
                score += 3;
            }
        }

        // Answer text matching (lower priority)
        const answerLower = faq.answer.toLowerCase();
        for (const qw of queryWords) {
            if (answerLower.includes(qw)) {
                score += 1;
            }
        }

        // Context bonus (if on relevant page)
        const context = getPageContext();
        if (intent.topic) {
            if ((intent.topic === 'product' && context === 'farmer-products') ||
                (intent.topic === 'order' && (context === 'farmer-orders' || context === 'buyer-orders')) ||
                (intent.topic === 'review' && context === 'farmer-reviews')) {
                score += 5;
            }
        }

        if (score > 0) {
            matches.push({
                faq: faq,
                score: score,
                matchedKeywords: matchedKeywords
            });

            if (score > bestScore) {
                bestScore = score;
                bestMatch = {
                    question: faq.question,
                    answer: faq.answer,
                    found: true,
                    showSuggestions: true,
                    score: score
                };
            }
        }
    }

    // If we found a good match (score >= 10), return it
    if (bestMatch && bestScore >= 10) {
        return bestMatch;
    }

    // Try fuzzy matching for common question patterns
    const commonPatterns = {
        'how do i': ['how can i', 'how to', 'how does', 'how do', 'steps to', 'way to'],
        'what is': ['what are', 'what does', 'what do', 'tell me about', 'explain'],
        'where can i': ['where do i', 'where is', 'where to', 'location'],
        'when can i': ['when do i', 'when will', 'when is'],
        'can i': ['may i', 'is it possible', 'am i able', 'allowed to']
    };

    for (const [pattern, variations] of Object.entries(commonPatterns)) {
        if (variations.some(v => queryLower.includes(v)) || queryLower.startsWith(pattern)) {
            const remainingQuery = queryLower.replace(new RegExp(`(${variations.join('|')}|${pattern})`, 'gi'), '').trim();

            if (remainingQuery.length > 2) {
                // Try to find FAQ based on remaining words
                for (const faq of allFaqs) {
                    for (const keyword of faq.keywords) {
                        if (remainingQuery.includes(keyword.toLowerCase()) || keyword.toLowerCase().includes(remainingQuery)) {
                            return {
                                question: faq.question,
                                answer: faq.answer,
                                found: true,
                                showSuggestions: true
                            };
                        }
                    }
                }
            }
        }
    }

    // If no good match, provide intelligent fallback with context-aware suggestions
    const context = getPageContext();
    let helpfulInfo = '';
    let suggestedActions = [];

    if (userType === 'farmer') {
        if (context === 'farmer-products') {
            helpfulInfo = 'I can help you with adding products, setting prices, managing stock, editing products, deleting products, or adding product images.';
            suggestedActions = ['How do I add a new product?', 'How do I set product prices?', 'How do I manage stock?'];
        } else if (context === 'farmer-orders') {
            helpfulInfo = 'I can help you with managing orders, marking orders as shipped, confirming orders, cancelling orders, or tracking order status.';
            suggestedActions = ['How do I manage orders?', 'How do I mark an order as shipped?', 'How do I cancel an order?'];
        } else if (context === 'farmer-reviews') {
            helpfulInfo = 'I can help you with viewing reviews, understanding ratings, improving your product ratings, or responding to buyer feedback.';
            suggestedActions = ['How do I see buyer reviews?', 'How do I improve my ratings?'];
        } else {
            helpfulInfo = 'I can help you with products, orders, reviews, pricing, stock management, account settings, or dashboard navigation.';
            suggestedActions = ['How do I add a new product?', 'How do I manage orders?', 'How do I see buyer reviews?'];
        }
    } else {
        if (context === 'buyer-shop') {
            helpfulInfo = 'I can help you with finding products, adding to cart, checkout, payment methods, product information, or stock availability.';
            suggestedActions = ['How do I find products?', 'How do I add products to cart?', 'What payment methods are available?'];
        } else if (context === 'buyer-orders') {
            helpfulInfo = 'I can help you with tracking orders, writing reviews, cancelling orders, understanding order statuses, or payment information.';
            suggestedActions = ['How do I track my order?', 'How do I write a review?', 'Can I cancel an order?'];
        } else {
            helpfulInfo = 'I can help you with shopping, orders, payments, reviews, account settings, or dashboard navigation.';
            suggestedActions = ['How do I find products?', 'How do I checkout?', 'How do I track my order?'];
        }
    }

    // Try to extract what the user might be asking about
    const queryWords = queryLower.split(/\s+/).filter(w => w.length > 2);
    const relevantTopics = [];

    for (const word of queryWords) {
        for (const [topic, synonyms] of Object.entries(semanticMap)) {
            if (word.includes(topic) || synonyms.some(s => word.includes(s))) {
                relevantTopics.push(topic);
            }
        }
    }

    let personalizedResponse = `I'm not entirely sure what you're asking about, but ${helpfulInfo}\n\n`;

    if (relevantTopics.length > 0) {
        personalizedResponse += `Based on your question, you might be asking about: ${relevantTopics.join(', ')}.\n\n`;
    }

    personalizedResponse += `Try asking:\n`;
    suggestedActions.forEach((action, idx) => {
        personalizedResponse += `${idx + 1}. "${action}"\n`;
    });
    personalizedResponse += `\nOr browse the "Help Topics" tab for detailed guides.`;

    return {
        question: null,
        answer: personalizedResponse,
        found: false,
        showSuggestions: true
    };
}

// Chat functionality with conversation context
let chatMessages = [];
let conversationContext = {
    lastTopic: null,
    userType: null,
    currentPage: null,
    askedQuestions: []
};

function addChatMessage(message, isUser = true, suggestions = null, actions = null) {
    chatMessages.push({
        message: message,
        isUser: isUser,
        timestamp: new Date(),
        suggestions: suggestions || null,
        showSuggestions: suggestions !== null && suggestions.length > 0,
        actions: actions || null,
        showActions: actions !== null && actions.length > 0
    });
    renderChatMessages();
}

// Update conversation context
function updateContext(query, result) {
    conversationContext.userType = getUserType();
    conversationContext.currentPage = getPageContext();

    if (result.found && result.question) {
        conversationContext.lastTopic = result.question;
    }

    conversationContext.askedQuestions.push({
        query: query,
        topic: result.question || query,
        timestamp: new Date()
    });

    // Keep only last 5 questions
    if (conversationContext.askedQuestions.length > 5) {
        conversationContext.askedQuestions.shift();
    }
}

function renderChatMessages() {
    const chatMessagesEl = document.getElementById('chatMessages');
    if (!chatMessagesEl) return;

    let html = '';

    chatMessages.forEach((msg, index) => {
        const time = msg.timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        if (msg.isUser) {
            html += `
                <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
                    <div style="background: #28a745; color: white; padding: 0.75rem 1rem; border-radius: 18px 18px 4px 18px; max-width: 70%; word-wrap: break-word; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="font-size: 0.9rem; margin-bottom: 0.25rem; white-space: pre-wrap;">${escapeHtml(msg.message)}</div>
                        <div style="font-size: 0.75rem; opacity: 0.8;">${time}</div>
                    </div>
                </div>
            `;
        } else {
            html += `
                <div style="display: flex; justify-content: flex-start; margin-bottom: 1rem;">
                    <div style="background: #f0f0f0; color: #333; padding: 0.75rem 1rem; border-radius: 18px 18px 18px 4px; max-width: 70%; word-wrap: break-word; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="font-size: 0.9rem; margin-bottom: 0.25rem; white-space: pre-wrap; line-height: 1.5;">${escapeHtml(msg.message)}</div>
                        <div style="font-size: 0.75rem; opacity: 0.6;">${time}</div>
                    </div>
                </div>
            `;

            // Add action buttons after assistant messages (if enabled)
            if (msg.showActions && msg.actions && msg.actions.length > 0) {
                html += `
                    <div style="margin-bottom: 1rem; padding-left: 0.5rem;">
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            ${msg.actions.map((action, idx) => {
                    const actionId = `actionBtn_${index}_${idx}`;
                    return `
                                <button id="${actionId}" 
                                    style="background: #28a745; color: white; border: none; padding: 0.5rem 1rem; border-radius: 20px; cursor: pointer; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s; font-weight: 500;"
                                    onmouseover="this.style.background='#218838'; this.style.transform='translateY(-2px)';"
                                    onmouseout="this.style.background='#28a745'; this.style.transform='translateY(0)';">
                                    <i class="${action.icon}"></i>
                                    ${escapeHtml(action.text)}
                                </button>
                            `;
                }).join('')}
                        </div>
                    </div>
                `;
            }

            // Add suggested questions after assistant messages (if enabled)
            if (msg.showSuggestions && msg.suggestions && msg.suggestions.length > 0) {
                html += `
                    <div style="margin-bottom: 1rem;">
                        <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.5rem; padding-left: 0.5rem; font-weight: 500;">
                            <i class="fa-solid fa-lightbulb" style="color: #ffc107;"></i> You might also ask:
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            ${msg.suggestions.map(suggestion => {
                    const escaped = escapeHtml(suggestion).replace(/'/g, "\\'");
                    return `
                                <button onclick="askSuggestedQuestion('${escaped}');" 
                                    style="background: white; border: 1px solid #28a745; color: #28a745; padding: 0.5rem 1rem; border-radius: 20px; cursor: pointer; font-size: 0.85rem; text-align: left; transition: all 0.2s;"
                                    onmouseover="this.style.background='#28a745'; this.style.color='white'; this.style.transform='translateX(5px)';"
                                    onmouseout="this.style.background='white'; this.style.color='#28a745'; this.style.transform='translateX(0)';">
                                    ${escapeHtml(suggestion)}
                                </button>
                            `;
                }).join('')}
                        </div>
                    </div>
                `;
            }
        }
    });

    chatMessagesEl.innerHTML = html;

    // Attach action button handlers
    chatMessages.forEach((msg, index) => {
        if (msg.showActions && msg.actions) {
            msg.actions.forEach((action, idx) => {
                const actionId = `actionBtn_${index}_${idx}`;
                const actionBtn = document.getElementById(actionId);
                if (actionBtn) {
                    actionBtn.addEventListener('click', action.action);
                }
            });
        }
    });

    // Scroll to bottom
    chatMessagesEl.scrollTop = chatMessagesEl.scrollHeight;
}

// Handle suggested question click
function askSuggestedQuestion(question) {
    const chatInput = document.getElementById('chatInput');
    if (chatInput) {
        chatInput.value = question;
        handleChatSubmit();
    }
}

function handleChatSubmit() {
    const chatInput = document.getElementById('chatInput');
    if (!chatInput) return;

    const query = chatInput.value.trim();
    if (!query) return;

    // Hide welcome message when user sends first message
    const welcomeMsg = document.getElementById('welcomeMessage');
    if (welcomeMsg) welcomeMsg.style.display = 'none';

    // Add user message
    addChatMessage(query, true);

    // Clear input
    chatInput.value = '';

    // Show typing indicator
    const typingIndicator = document.createElement('div');
    typingIndicator.id = 'typingIndicator';
    typingIndicator.innerHTML = `
        <div style="display: flex; justify-content: flex-start; margin-bottom: 1rem;">
            <div style="background: #f0f0f0; color: #333; padding: 0.75rem 1rem; border-radius: 18px 18px 18px 4px;">
                <i class="fa-solid fa-circle-notch fa-spin"></i> Thinking...
            </div>
        </div>
    `;
    const chatMessagesEl = document.getElementById('chatMessages');
    if (chatMessagesEl) {
        chatMessagesEl.appendChild(typingIndicator);
        chatMessagesEl.scrollTop = chatMessagesEl.scrollHeight;
    }

    // Simulate thinking delay, then respond
    setTimeout(() => {
        const typingEl = document.getElementById('typingIndicator');
        if (typingEl) typingEl.remove();

        const result = findAnswer(query);
        let response = result.answer;

        // Update conversation context
        updateContext(query, result);

        // Format response with better structure
        if (result.question && result.found && !result.isGreeting) {
            response = `📌 ${result.question}\n\n${result.answer}`;
        } else if (!result.found && !result.isGreeting) {
            // For unmatched queries, add helpful context
            const context = getPageContext();
            const userType = getUserType();

            // Try to predict what user might need based on conversation history
            if (conversationContext.askedQuestions.length > 0) {
                const lastQuestion = conversationContext.askedQuestions[conversationContext.askedQuestions.length - 1];
                if (lastQuestion.topic) {
                    response += `\n\n💡 Based on your previous question about "${lastQuestion.topic}", you might also want to know about related topics.`;
                }
            }
        }

        // Get context-aware suggestions with intelligent prediction
        const relatedTopic = result.question || conversationContext.lastTopic;
        let suggestions = null;

        if (result.showSuggestions && !result.isGreeting) {
            suggestions = getSuggestedQuestions(relatedTopic).slice(0, 3);

            // Add intelligent predictions based on query intent
            const intent = extractIntent(query);
            if (intent.topic && !suggestions.some(s => s.toLowerCase().includes(intent.topic))) {
                // Add topic-specific suggestions
                const topicSuggestions = getSuggestedQuestions(intent.topic);
                if (topicSuggestions.length > 0) {
                    suggestions = [topicSuggestions[0], ...suggestions].slice(0, 3);
                }
            }
        }

        // Get action buttons based on topic
        const actions = (!result.isGreeting && result.found)
            ? getActionButtons(relatedTopic, result.question || query)
            : null;

        addChatMessage(response, false, suggestions, actions);
    }, 800);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Show help modal with tabs
function showHelpModal() {
    const context = getPageContext();
    const content = helpContent[context] || helpContent['farmer-dashboard']; // Default fallback

    // Create or get modal
    let modal = document.getElementById('helpModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'helpModal';
        modal.className = 'modal';
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 900px; max-height: 90vh; display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 2px solid #e0e0e0;">
                    <h2 style="margin: 0; color: #333; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-circle-question" style="color: #28a745;"></i>
                        <span id="helpModalTitle">${content.title}</span>
                    </h2>
                    <button id="closeHelpModal" class="icon-btn" style="background: transparent; border: none; font-size: 1.5rem; color: #666; cursor: pointer; padding: 0.5rem; border-radius: 50%; transition: all 0.2s;" aria-label="Close">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                
                <!-- Tabs -->
                <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; border-bottom: 2px solid #e0e0e0;">
                    <button id="helpTopicsTab" class="help-tab active" style="flex: 1; padding: 0.75rem 1rem; background: #28a745; color: white; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 600; transition: all 0.2s;">
                        <i class="fa-solid fa-book"></i> Help Topics
                    </button>
                    <button id="chatTab" class="help-tab" style="flex: 1; padding: 0.75rem 1rem; background: #e0e0e0; color: #666; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 600; transition: all 0.2s;">
                        <i class="fa-solid fa-comments"></i> Chat Assistant
                    </button>
                </div>
                
                <!-- Tab Content -->
                <div style="flex: 1; overflow-y: auto; min-height: 300px;">
                    <!-- Help Topics Content -->
                    <div id="helpTopicsContent" style="display: block;">
                        <div id="helpModalContent"></div>
                    </div>
                    
                    <!-- Chat Content -->
                    <div id="chatContent" style="display: none; height: 100%; flex-direction: column;">
                        <div id="chatMessages" style="flex: 1; overflow-y: auto; padding: 1rem; background: #fafafa; border-radius: 8px; margin-bottom: 1rem; min-height: 300px; max-height: 400px;">
                            <div id="welcomeMessage" style="text-align: center; color: #666; padding: 2rem;">
                                <i class="fa-solid fa-robot" style="font-size: 3rem; color: #28a745; margin-bottom: 1rem;"></i>
                                <p style="margin: 0; font-size: 1.1rem; font-weight: 600;">Hello! 👋 I'm your help assistant.</p>
                                <p style="margin: 0.5rem 0 1rem 0; font-size: 0.95rem; color: #666;">Ask me anything about using the platform, or try one of these quick questions:</p>
                                <div id="quickQuestions" style="display: flex; flex-direction: column; gap: 0.5rem; max-width: 400px; margin: 0 auto;">
                                    ${getSuggestedQuestions().slice(0, 3).map(q => {
            const escaped = escapeHtml(q).replace(/'/g, "\\'");
            return `
                                        <button onclick="askSuggestedQuestion('${escaped}');" 
                                            style="background: white; border: 2px solid #28a745; color: #28a745; padding: 0.75rem 1.5rem; border-radius: 25px; cursor: pointer; font-size: 0.9rem; transition: all 0.2s; font-weight: 500;"
                                            onmouseover="this.style.background='#28a745'; this.style.color='white'; this.style.transform='translateY(-2px)';"
                                            onmouseout="this.style.background='white'; this.style.color='#28a745'; this.style.transform='translateY(0)';">
                                            ${escapeHtml(q)}
                                        </button>
                                    `;
        }).join('')}
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="text" id="chatInput" placeholder="Type your question here..." style="flex: 1; padding: 0.75rem 1rem; border: 2px solid #e0e0e0; border-radius: 25px; font-size: 0.95rem; outline: none; transition: border-color 0.2s;" onkeypress="if(event.key === 'Enter') handleChatSubmit();" onfocus="this.style.borderColor='#28a745'; this.style.boxShadow='0 0 0 3px rgba(40,167,69,0.1)';" onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none';">
                            <button onclick="handleChatSubmit();" style="padding: 0.75rem 1.5rem; background: #28a745; color: white; border: none; border-radius: 25px; cursor: pointer; font-weight: 600; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='#218838'; this.style.transform='scale(1.05)';" onmouseout="this.style.background='#28a745'; this.style.transform='scale(1)';">
                                <i class="fa-solid fa-paper-plane"></i> Send
                            </button>
                            <button onclick="clearChat();" style="padding: 0.75rem; background: #f0f0f0; color: #666; border: none; border-radius: 50%; cursor: pointer; transition: all 0.2s; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;" title="Clear chat" onmouseover="this.style.background='#e0e0e0';" onmouseout="this.style.background='#f0f0f0';">
                                <i class="fa-solid fa-rotate-left"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // Setup close handlers
        const closeBtn = document.getElementById('closeHelpModal');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeHelpModal);
        }

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeHelpModal();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                closeHelpModal();
            }
        });

        // Tab switching
        const helpTopicsTab = document.getElementById('helpTopicsTab');
        const chatTab = document.getElementById('chatTab');
        const helpTopicsContent = document.getElementById('helpTopicsContent');
        const chatContent = document.getElementById('chatContent');

        if (helpTopicsTab && chatTab) {
            helpTopicsTab.addEventListener('click', () => {
                helpTopicsTab.classList.add('active');
                helpTopicsTab.style.background = '#28a745';
                helpTopicsTab.style.color = 'white';
                chatTab.classList.remove('active');
                chatTab.style.background = '#e0e0e0';
                chatTab.style.color = '#666';
                if (helpTopicsContent) helpTopicsContent.style.display = 'block';
                if (chatContent) chatContent.style.display = 'none';
            });

            chatTab.addEventListener('click', () => {
                chatTab.classList.add('active');
                chatTab.style.background = '#28a745';
                chatTab.style.color = 'white';
                helpTopicsTab.classList.remove('active');
                helpTopicsTab.style.background = '#e0e0e0';
                helpTopicsTab.style.color = '#666';
                if (helpTopicsContent) helpTopicsContent.style.display = 'none';
                if (chatContent) chatContent.style.display = 'flex';

                // Hide welcome message if chat has messages
                if (chatMessages.length > 0) {
                    const welcomeMsg = document.getElementById('welcomeMessage');
                    if (welcomeMsg) welcomeMsg.style.display = 'none';
                }

                // Focus on chat input
                const chatInput = document.getElementById('chatInput');
                if (chatInput) chatInput.focus();
            });
        }
    }

    // Update content
    const titleEl = document.getElementById('helpModalTitle');
    const contentEl = document.getElementById('helpModalContent');

    if (titleEl) titleEl.textContent = content.title;
    if (contentEl) {
        contentEl.innerHTML = content.sections.map(section => `
            <div style="background: #f8f9fa; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; border-left: 4px solid #28a745;">
                <div style="display: flex; align-items: flex-start; gap: 1rem;">
                    <div style="flex-shrink: 0; width: 40px; height: 40px; background: #28a745; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;">
                        <i class="${section.icon}"></i>
                    </div>
                    <div style="flex: 1;">
                        <h3 style="margin: 0 0 0.5rem 0; color: #333; font-size: 1.1rem;">${section.title}</h3>
                        <p style="margin: 0; color: #666; line-height: 1.6;">${section.content}</p>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Reset chat when opening modal (but keep context)
    chatMessages = [];
    conversationContext.currentPage = getPageContext();
    conversationContext.userType = getUserType();

    const chatMessagesEl = document.getElementById('chatMessages');
    if (chatMessagesEl) {
        const suggestedQuestions = getSuggestedQuestions().slice(0, 3);
        const userType = getUserType();
        const context = getPageContext();

        let welcomeText = 'Hello! 👋 I\'m your help assistant.';
        let contextHint = 'Ask me anything about using the platform, or try one of these quick questions:';

        if (userType === 'farmer') {
            if (context === 'farmer-products') {
                contextHint = 'I can help you with product management. Try asking about adding products, setting prices, or managing stock.';
            } else if (context === 'farmer-orders') {
                contextHint = 'I can help you with order management. Try asking about shipping orders, updating statuses, or handling cancellations.';
            } else if (context === 'farmer-reviews') {
                contextHint = 'I can help you understand buyer reviews and improve your ratings.';
            }
        } else {
            if (context === 'buyer-shop') {
                contextHint = 'I can help you with shopping. Try asking about finding products, adding to cart, or payment methods.';
            } else if (context === 'buyer-orders') {
                contextHint = 'I can help you with your orders. Try asking about tracking, reviews, or order management.';
            }
        }

        chatMessagesEl.innerHTML = `
            <div id="welcomeMessage" style="text-align: center; color: #666; padding: 2rem;">
                <i class="fa-solid fa-robot" style="font-size: 3rem; color: #28a745; margin-bottom: 1rem;"></i>
                <p style="margin: 0; font-size: 1.1rem; font-weight: 600;">${welcomeText}</p>
                <p style="margin: 0.5rem 0 1rem 0; font-size: 0.95rem; color: #666;">${contextHint}</p>
                <div id="quickQuestions" style="display: flex; flex-direction: column; gap: 0.5rem; max-width: 400px; margin: 0 auto;">
                    ${suggestedQuestions.map(q => {
            const escaped = escapeHtml(q).replace(/'/g, "\\'");
            return `
                        <button onclick="askSuggestedQuestion('${escaped}');" 
                            style="background: white; border: 2px solid #28a745; color: #28a745; padding: 0.75rem 1.5rem; border-radius: 25px; cursor: pointer; font-size: 0.9rem; transition: all 0.2s; font-weight: 500;"
                            onmouseover="this.style.background='#28a745'; this.style.color='white'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(40,167,69,0.3)';"
                            onmouseout="this.style.background='white'; this.style.color='#28a745'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                            ${escapeHtml(q)}
                        </button>
                    `;
        }).join('')}
                </div>
            </div>
        `;
    }

    // Show modal
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

// Close help modal
function closeHelpModal() {
    const modal = document.getElementById('helpModal');
    if (modal) {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = 'auto';
    }
}

// Clear chat conversation
function clearChat() {
    if (confirm('Are you sure you want to clear the chat history?')) {
        chatMessages = [];
        conversationContext.askedQuestions = [];
        const chatMessagesEl = document.getElementById('chatMessages');
        if (chatMessagesEl) {
            const suggestedQuestions = getSuggestedQuestions().slice(0, 3);
            const userType = getUserType();
            const context = getPageContext();

            let welcomeText = 'Hello! 👋 I\'m your help assistant.';
            let contextHint = 'Ask me anything about using the platform, or try one of these quick questions:';

            if (userType === 'farmer') {
                if (context === 'farmer-products') {
                    contextHint = 'I can help you with product management. Try asking about adding products, setting prices, or managing stock.';
                } else if (context === 'farmer-orders') {
                    contextHint = 'I can help you with order management. Try asking about shipping orders, updating statuses, or handling cancellations.';
                } else if (context === 'farmer-reviews') {
                    contextHint = 'I can help you understand buyer reviews and improve your ratings.';
                }
            } else {
                if (context === 'buyer-shop') {
                    contextHint = 'I can help you with shopping. Try asking about finding products, adding to cart, or payment methods.';
                } else if (context === 'buyer-orders') {
                    contextHint = 'I can help you with your orders. Try asking about tracking, reviews, or order management.';
                }
            }

            chatMessagesEl.innerHTML = `
                <div id="welcomeMessage" style="text-align: center; color: #666; padding: 2rem;">
                    <i class="fa-solid fa-robot" style="font-size: 3rem; color: #28a745; margin-bottom: 1rem;"></i>
                    <p style="margin: 0; font-size: 1.1rem; font-weight: 600;">${welcomeText}</p>
                    <p style="margin: 0.5rem 0 1rem 0; font-size: 0.95rem; color: #666;">${contextHint}</p>
                    <div id="quickQuestions" style="display: flex; flex-direction: column; gap: 0.5rem; max-width: 400px; margin: 0 auto;">
                        ${suggestedQuestions.map(q => {
                const escaped = escapeHtml(q).replace(/'/g, "\\'");
                return `
                            <button onclick="askSuggestedQuestion('${escaped}');" 
                                style="background: white; border: 2px solid #28a745; color: #28a745; padding: 0.75rem 1.5rem; border-radius: 25px; cursor: pointer; font-size: 0.9rem; transition: all 0.2s; font-weight: 500;"
                                onmouseover="this.style.background='#28a745'; this.style.color='white'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(40,167,69,0.3)';"
                                onmouseout="this.style.background='white'; this.style.color='#28a745'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                                ${escapeHtml(q)}
                            </button>
                        `;
            }).join('')}
                    </div>
                </div>
            `;
        }
    }
}

// Make functions globally available
window.showHelpModal = showHelpModal;
window.closeHelpModal = closeHelpModal;
window.handleChatSubmit = handleChatSubmit;
window.askSuggestedQuestion = askSuggestedQuestion;
window.clearChat = clearChat;

// Initialize help button on page load
document.addEventListener('DOMContentLoaded', function () {
    // Add click handler to existing help button if it exists
    const existingHelpBtn = document.getElementById('helpBtn');
    if (existingHelpBtn && !existingHelpBtn.hasAttribute('data-help-initialized')) {
        existingHelpBtn.setAttribute('data-help-initialized', 'true');
        existingHelpBtn.addEventListener('click', function (e) {
            e.preventDefault();
            showHelpModal();
        });
    }

    // Add help button to navigation if it doesn't exist
    const nav = document.querySelector('.nav');
    if (nav && !document.getElementById('helpBtn')) {
        const helpBtn = document.createElement('button');
        helpBtn.id = 'helpBtn';
        helpBtn.className = 'btn btn-ghost';
        helpBtn.style.marginLeft = '0.5rem';
        helpBtn.style.display = 'inline-flex';
        helpBtn.style.alignItems = 'center';
        helpBtn.style.gap = '0.5rem';
        helpBtn.innerHTML = '<i class="fa-solid fa-circle-question"></i><span>Help</span>';
        helpBtn.title = 'Get Help (Press F1)';
        helpBtn.setAttribute('data-help-initialized', 'true');
        helpBtn.addEventListener('click', function (e) {
            e.preventDefault();
            showHelpModal();
        });

        // Insert before logout button
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            nav.insertBefore(helpBtn, logoutBtn);
        } else {
            nav.appendChild(helpBtn);
        }
    }

    // Add keyboard shortcut (F1) to open help
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F1') {
            e.preventDefault();
            showHelpModal();
        }
    });
});
