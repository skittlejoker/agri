# GitHub Pages Deployment Guide

This guide will help you deploy the AgriMarket website to GitHub Pages.

## Quick Start

### Step 1: Prepare Your Repository

1. Make sure all your files are in the `agriculture-marketplace` directory
2. Ensure you have the following structure:
   ```
   agriculture-marketplace/
   ├── index.html
   ├── style.css
   ├── script.js
   ├── help-assistant.js
   ├── uploads/
   │   └── field.jpeg
   └── .github/
       └── workflows/
           └── deploy.yml
   ```

### Step 2: Initialize Git (if not done)

```bash
cd agriculture-marketplace
git init
git add .
git commit -m "Initial commit for GitHub Pages"
```

### Step 3: Create GitHub Repository

1. Go to [GitHub](https://github.com) and sign in
2. Click the **+** icon → **New repository**
3. Repository name: `Agri_Market.io` (or your preferred name)
4. Description: "Agriculture Marketplace - Connecting Farmers & Buyers"
5. Set to **Public** (required for free GitHub Pages)
6. **DO NOT** initialize with README, .gitignore, or license
7. Click **Create repository**

### Step 4: Push to GitHub

```bash
git remote add origin https://github.com/YOUR_USERNAME/Agri_Market.io.git
git branch -M main
git push -u origin main
```

Replace `YOUR_USERNAME` with your actual GitHub username.

### Step 5: Enable GitHub Pages

#### Option A: Using GitHub Actions (Recommended)

1. Go to your repository on GitHub
2. Click **Settings** → **Pages**
3. Under **Source**, select **GitHub Actions**
4. The workflow will automatically run and deploy your site
5. Wait 2-3 minutes for deployment to complete
6. Your site will be available at: `https://YOUR_USERNAME.github.io/Agri_Market.io/`

#### Option B: Using Branch Deployment

1. Go to **Settings** → **Pages**
2. Under **Source**, select **Deploy from a branch**
3. Branch: **main**
4. Folder: **/ (root)**
5. Click **Save**
6. Your site will be available at: `https://YOUR_USERNAME.github.io/Agri_Market.io/`

### Step 6: Verify Deployment

1. Visit your site URL
2. Check that all images load correctly
3. Test navigation links
4. Verify responsive design on mobile

## Troubleshooting

### Images Not Loading

If images don't load:
- Check that `uploads/field.jpeg` exists
- Verify image paths in `style.css` are relative (not absolute)
- Ensure images are committed to the repository

### 404 Errors

- Make sure `index.html` is in the root directory
- Check that file names match exactly (case-sensitive)
- Verify all linked files exist in the repository

### Styling Issues

- Clear browser cache (Ctrl+F5 or Cmd+Shift+R)
- Check browser console for errors
- Verify CSS file is linked correctly in HTML

### Deployment Not Working

- Check GitHub Actions tab for errors
- Verify `.github/workflows/deploy.yml` exists
- Ensure repository is set to Public
- Check repository Settings → Pages for configuration

## Custom Domain (Optional)

To use a custom domain:

1. Go to **Settings** → **Pages**
2. Under **Custom domain**, enter your domain
3. Add a `CNAME` file in the root with your domain name
4. Configure DNS records with your domain provider

## Updating Your Site

After making changes:

```bash
git add .
git commit -m "Update website content"
git push origin main
```

GitHub Pages will automatically rebuild and deploy your changes (usually within 1-2 minutes).

## File Structure for GitHub Pages

```
agriculture-marketplace/
├── index.html              # Main landing page (required)
├── style.css              # Styles
├── script.js              # JavaScript
├── help-assistant.js      # Chatbot
├── login.html             # Login page
├── register.html          # Registration page
├── buyer_dashboard.html   # Buyer dashboard
├── farmer_dashboard.html   # Farmer dashboard
├── uploads/               # Images and assets
│   ├── field.jpeg
│   └── products/
├── .github/
│   └── workflows/
│       └── deploy.yml     # Deployment workflow
├── .gitignore             # Git ignore rules
├── README.md              # Project documentation
└── DEPLOYMENT.md          # This file
```

## Important Notes

- **Static Site Only**: This deployment is for the static frontend only
- **No PHP/Backend**: PHP files and API endpoints won't work on GitHub Pages
- **No Database**: Database connections won't work on GitHub Pages
- **Public Repository**: Free GitHub Pages requires public repositories

## Support

If you encounter issues:
1. Check the GitHub Actions logs
2. Review browser console for errors
3. Verify all file paths are correct
4. Ensure all assets are committed to the repository

---

**Happy Deploying! 🚀**

