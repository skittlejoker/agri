# How to Fix "Method not Allowed" Error

## Problem
The error occurs because your browser is caching the old JavaScript file that was using GET instead of POST.

## Solutions

### Solution 1: Hard Refresh the Page (Recommended)
Press **Ctrl + F5** (or **Ctrl + Shift + R**) on your keyboard while on the register page.

This forces the browser to reload all files from the server, ignoring the cache.

### Solution 2: Clear Browser Cache
1. Open your browser settings
2. Find "Clear browsing data" or "Clear cache"
3. Select "Cached images and files"
4. Click "Clear data"
5. Refresh the page

### Solution 3: Open in Incognito/Private Mode
1. Press **Ctrl + Shift + N** (Chrome) or **Ctrl + Shift + P** (Firefox)
2. Navigate to the registration page
3. Try registering again

## What Was Fixed
✅ Updated `script-php.js` to use POST method for registration
✅ Updated `script-php.js` to use POST method for login
✅ Added cache-busting parameters to HTML files

## Test the Fix
1. Hard refresh the registration page (Ctrl + F5)
2. Fill in the registration form
3. Click "Register"
4. You should now be successfully registered!

