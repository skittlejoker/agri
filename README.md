# AgriMarket - Agriculture Marketplace

A modern, responsive e-commerce platform connecting local farmers with buyers for sustainable agriculture.

🌐 **Live Demo**: [https://skittlejoker.github.io/Agri_Market.io/](https://skittlejoker.github.io/Agri_Market.io/)

## 🚀 Features

- **Direct Farm-to-Buyer Connection**: Connect directly with local farmers
- **Fresh Local Produce**: Browse and purchase fresh, locally-sourced products
- **Farmer Dashboard**: Manage products, orders, and inventory
- **Buyer Dashboard**: Shop, track orders, and manage cart
- **Secure Transactions**: Safe and reliable payment processing
- **Responsive Design**: Works seamlessly on all devices
- **Interactive Chatbot**: AI-powered help assistant

## 📁 Project Structure

```
agriculture-marketplace/
├── index.html              # Main landing page
├── style.css              # Main stylesheet
├── script.js              # Main JavaScript
├── help-assistant.js      # Chatbot functionality
├── login.html             # Login page
├── register.html          # Registration page
├── buyer_dashboard.html   # Buyer dashboard
├── farmer_dashboard.html   # Farmer dashboard
├── uploads/               # Image assets
└── .github/
    └── workflows/
        └── deploy.yml     # GitHub Pages deployment
```

## 🛠️ Deployment to GitHub Pages

### Prerequisites

- A GitHub account
- A repository named `Agri_Market.io` (or your preferred name)

### Deployment Steps

1. **Initialize Git Repository** (if not already done):
   ```bash
   cd agriculture-marketplace
   git init
   git add .
   git commit -m "Initial commit"
   ```

2. **Create GitHub Repository**:
   - Go to GitHub and create a new repository
   - Name it `Agri_Market.io` (or your preferred name)
   - Do NOT initialize with README, .gitignore, or license

3. **Connect and Push**:
   ```bash
   git remote add origin https://github.com/YOUR_USERNAME/Agri_Market.io.git
   git branch -M main
   git push -u origin main
   ```

4. **Enable GitHub Pages**:
   - Go to your repository on GitHub
   - Click on **Settings** → **Pages**
   - Under **Source**, select **GitHub Actions**
   - The workflow will automatically deploy on every push to `main` branch

5. **Access Your Site**:
   - Your site will be available at: `https://YOUR_USERNAME.github.io/Agri_Market.io/`
   - It may take a few minutes for the first deployment to complete

### Manual Deployment

If you prefer manual deployment:

1. Go to **Settings** → **Pages**
2. Under **Source**, select **Deploy from a branch**
3. Select **main** branch and **/ (root)** folder
4. Click **Save**

## 🔧 Local Development

To run the site locally:

1. **Using a simple HTTP server**:
   ```bash
   # Python 3
   python -m http.server 8000
   
   # Node.js (if you have http-server installed)
   npx http-server
   
   # PHP
   php -S localhost:8000
   ```

2. Open your browser and navigate to `http://localhost:8000`

## 📝 Notes

- This is a **static site** version for GitHub Pages
- PHP backend features (API, database) are not included in this deployment
- For full functionality, you'll need to deploy the PHP backend separately
- All images and assets should be in the `uploads/` directory

## 🎨 Customization

- **Colors**: Edit CSS variables in `style.css` (lines 1-18)
- **Content**: Edit `index.html` for landing page content
- **Styling**: Modify `style.css` for design changes

## 📧 Contact

- **Email**: agrimarketplace.nexus@gmail.com
- **Phone**: +63 912 345 6789
- **Address**: College of Information and Computer Studies, Gordon College, Olongapo City, Philippines

## 📄 License

This project is part of an academic project for Gordon College.

## 🙏 Acknowledgments

- Font Awesome for icons
- Google Fonts (Poppins) for typography
- All the farmers and buyers using the platform

---

**Made with ❤️ for sustainable agriculture**

