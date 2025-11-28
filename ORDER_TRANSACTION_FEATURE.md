# Order and Transaction System

This document explains the new order and transaction features added to the agriculture marketplace.

## Features Implemented

### 1. **Quantity Synchronization**
- When farmers update product quantities/stock, the buyer dashboard automatically updates to reflect the current stock
- Cart quantities are automatically adjusted if available stock decreases below the cart quantity
- Product list refreshes every 30 seconds to show the latest stock availability

### 2. **Order Placement**
- Buyers can place orders directly from their cart
- When an order is placed:
  - A transaction record is created in the database
  - Product stock is automatically reduced
  - Cart is cleared after successful order placement

### 3. **Order Management**
- **For Buyers:**
  - View all their orders in the "My Orders" section
  - See order status (pending, confirmed, completed, cancelled)
  - View order details including product, quantity, price, and farmer name

- **For Farmers:**
  - View all orders received from buyers in the "Orders" section
  - Update order status using a dropdown menu
  - When an order is cancelled, stock is automatically restored

## Database Schema

### Orders Table
The `orders` table stores transaction information:
- `id`: Unique order ID
- `buyer_id`: Buyer who placed the order
- `farmer_id`: Farmer who owns the product
- `product_id`: Product being ordered
- `quantity`: Quantity ordered
- `unit_price`: Price per unit at time of order
- `total_price`: Total order value
- `status`: Order status (pending, confirmed, completed, cancelled)
- `created_at`: Order creation timestamp
- `updated_at`: Last update timestamp

## Setup Instructions

1. **Create the orders table:**
   ```bash
   php setup_orders_table.php
   ```
   Or manually run the SQL from `migrations/create_orders_table.sql`

2. **That's it!** The system is ready to use.

## API Endpoints

### Create Order
- **Endpoint:** `api/create_order.php`
- **Method:** POST
- **Access:** Buyers only
- **Body:** JSON with `cart_items` array
  ```json
  {
    "cart_items": [
      {
        "product_id": 1,
        "quantity": 5
      }
    ]
  }
  ```

### Get Orders
- **Endpoint:** `api/get_orders.php`
- **Method:** GET
- **Access:** Both buyers and farmers
- **Response:** List of orders filtered by user type

### Update Order Status
- **Endpoint:** `api/update_order_status.php`
- **Method:** POST
- **Access:** Farmers only
- **Body:** JSON with `order_id` and `status`
  ```json
  {
    "order_id": 1,
    "status": "confirmed"
  }
  ```

## How It Works

### Buyer Flow:
1. Buyer browses products and adds items to cart
2. Buyer clicks "Place Order" button in cart
3. System validates stock availability
4. Order is created and stock is reduced
5. Cart is cleared
6. Order appears in "My Orders" section

### Farmer Flow:
1. Farmer updates product stock (quantity) in their dashboard
2. Buyers see updated stock immediately (refreshes every 30 seconds)
3. When a buyer places an order, farmer receives notification in "Orders" section
4. Farmer can update order status (pending → confirmed → completed)
5. If order is cancelled, stock is automatically restored

### Stock Management:
- Stock is reduced when an order is placed
- Stock is restored when an order is cancelled
- Buyers cannot add more items to cart than available stock
- Cart automatically adjusts if stock decreases while items are in cart

## User Interface Changes

### Buyer Dashboard:
- Added "Place Order" button in cart section
- Added "My Orders" section with order history
- Added navigation link to "My Orders"

### Farmer Dashboard:
- Added "Orders" section showing received orders
- Added dropdown to change order status
- Added navigation link to "Orders"

## Notes

- The orders table is automatically created when the first order is placed if it doesn't exist
- Order prices are stored at the time of order (prevents price changes from affecting existing orders)
- Stock updates are immediate and synchronized across all buyer views
- Order status updates trigger automatic stock restoration for cancelled orders


