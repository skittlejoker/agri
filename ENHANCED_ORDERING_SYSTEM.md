# Enhanced Ordering System Documentation

## Overview
The ordering system has been enhanced with a comprehensive workflow including payment methods, shipping tracking, delivery time estimates, and review functionality.

## New Features

### 1. **Payment System**
- **E-Wallet Payment**: Buyers can pay immediately when placing an order
- **Cash on Delivery (COD)**: Payment is collected upon delivery
- Payment status is tracked (Unpaid/Paid)
- COD orders automatically mark as paid when delivered

### 2. **Shipping Workflow**
- **To Ship**: Order is ready to be shipped by farmer
- **Shipped**: Order has been shipped (includes delivery countdown timer)
- **Delivered**: Order has been delivered to buyer

### 3. **Delivery Tracking**
- **Delivery Address**: Required field when placing order
- **Distance Calculation**: Automatically calculated (simplified algorithm - can be enhanced with geocoding)
- **Estimated Delivery Time**: Calculated based on distance (minutes)
- **Live Countdown**: Shows remaining time when order is shipped (updates every minute)

### 4. **Review System**
- Buyers can review products after delivery
- 5-star rating system
- Optional review comments
- Reviews are displayed on completed orders

## Setup Instructions

### Step 1: Run Database Migration
Before using the new features, you must update your database schema:

**Option 1: Using PHP Script (Recommended)**
1. Navigate to: `http://localhost/E-commerce/agriculture-marketplace/migrations/enhance_orders_table.php`
2. The script will automatically add all new columns and indexes

**Option 2: Using SQL Script**
1. Open `migrations/enhance_orders_table.sql`
2. Run the SQL commands in your MySQL database

### Step 2: Verify Installation
After running the migration, check that the following columns exist in the `orders` table:
- `payment_method`
- `payment_status`
- `shipping_status`
- `delivery_address`
- `delivery_distance`
- `estimated_delivery_time`
- `shipped_at`
- `delivered_at`
- `review_rating`
- `review_comment`

## Usage Guide

### For Buyers

#### Placing an Order
1. Add products to cart
2. Click "Place Order" button
3. **Checkout Modal** will appear:
   - Select payment method (E-Wallet or Cash on Delivery)
   - Enter delivery address (required)
   - Click "Place Order"

#### Viewing Orders
Orders now display:
- **Payment Status**: Unpaid (red) or Paid (green) badge
- **Payment Method**: E-Wallet or Cash on Delivery
- **Shipping Status**: 
  - To Ship (yellow) - Waiting for farmer to ship
  - Shipped (green) - Shows countdown timer (e.g., "2h 30m remaining")
  - Delivered (green) - Order received
- **Delivery Address**: Your delivery location
- **Distance**: Distance from farmer to your location (in km)

#### Reviewing Products
After an order is delivered:
1. You'll see a "Write Review" button
2. Click it to open the review modal
3. Select rating (1-5 stars)
4. Write optional comment
5. Submit review

### For Farmers

#### Managing Orders
1. View orders in "Orders" section
2. Update shipping status:
   - **To Ship** → **Shipped**: When you send the order (sets shipping time)
   - **Shipped** → **Delivered**: When order reaches buyer
3. Cash on Delivery orders automatically update payment status to "Paid" when marked as delivered

## API Endpoints

### New Endpoints

1. **update_payment_status.php** (POST)
   - Updates payment status for buyers
   - Parameters: `order_id`, `payment_status` ('paid' or 'unpaid')

2. **submit_review.php** (POST)
   - Submits review for delivered orders
   - Parameters: `order_id`, `rating` (1-5), `comment` (optional)

### Updated Endpoints

1. **create_order.php**
   - Now accepts: `payment_method`, `delivery_address`
   - Returns enhanced order information

2. **get_orders.php**
   - Returns all new fields if migration is complete
   - Backwards compatible with old schema

3. **update_order_status.php**
   - Now supports `shipping_status` parameter
   - Automatically updates payment for COD when delivered

## Technical Details

### Distance Calculation
Currently uses a simplified algorithm based on address length. In production, you should:
- Use geocoding service (Google Maps API, OpenStreetMap, etc.)
- Store latitude/longitude for buyers and farmers
- Calculate actual distance using Haversine formula

### Delivery Time Estimation
- Formula: Minimum 30 minutes + (3 minutes per km)
- Can be customized in `create_order.php`

### Countdown Timer
- Updates every 60 seconds
- Shows time remaining based on `shipped_at` + `estimated_delivery_time`
- Automatically stops when order is delivered

## Database Schema Changes

### New Columns in `orders` table:
```sql
payment_method ENUM('ewallet', 'cash_on_delivery')
payment_status ENUM('unpaid', 'paid')
shipping_status ENUM('to_ship', 'shipped', 'delivered')
delivery_address TEXT
delivery_distance DECIMAL(10,2)
estimated_delivery_time INT (minutes)
shipped_at DATETIME
delivered_at DATETIME
review_rating INT (1-5)
review_comment TEXT
```

### New Indexes:
- `idx_payment_status` on `payment_status`
- `idx_shipping_status` on `shipping_status`
- `idx_shipped_at` on `shipped_at`

## Troubleshooting

### Migration Issues
- If migration fails, check MySQL error logs
- Ensure you have proper permissions
- Make sure `orders` table exists first

### Order Display Issues
- Check browser console for JavaScript errors
- Verify API responses in Network tab
- Ensure migration completed successfully

### Countdown Timer Not Working
- Check that `shipped_at` and `estimated_delivery_time` are set
- Verify JavaScript is enabled
- Check browser console for errors

## Future Enhancements

Potential improvements:
1. Real geocoding and distance calculation
2. Email notifications for order status changes
3. Delivery tracking with map integration
4. E-wallet integration (payment gateway)
5. Multiple delivery addresses per user
6. Delivery history and analytics

## Support

For issues or questions:
1. Check error logs in PHP error_log
2. Verify database schema matches expected structure
3. Test API endpoints directly
4. Review browser console for frontend errors



