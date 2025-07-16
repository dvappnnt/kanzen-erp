<p align="center">
  <img src="https://laravel.com/img/logomark.min.svg" alt="Laravel" width="100" />
  <img src="https://upload.wikimedia.org/wikipedia/commons/9/95/Vue.js_Logo_2.svg" alt="Vue.js" width="100" />
</p>

<h1 align="center">Laravue ERP</h1>

<p align="center">
  <strong>Laravel 12 + Vue 3 + Inertia.js</strong><br>
  A powerful, modern ERP system built for business efficiency and scalability.
</p>

---

## ✨ Overview

**Laravue ERP** is a feature-rich, modular Enterprise Resource Planning (ERP) system built with Laravel and Vue. It helps businesses manage their operations — from inventory, purchasing, and sales to supplier and customer relations — within a unified and intuitive platform.

Designed with flexibility, security, and scalability in mind, this project serves as an ideal foundation for business management systems, custom ERP solutions, and SaaS products.

---

## ⚙️ Tech Stack

### 🔴 Laravel 12 (Backend)

- Robust backend framework with MVC architecture
- Jetstream + Inertia for seamless SPA authentication
- Sanctum for secure API authentication
- Spatie Permissions for role-based access control
- Laravel Queues and Scheduler for task automation

### 🟢 Vue 3 + Inertia.js (Frontend)

- Composition API with reactive state management
- Tailwind CSS for fast and responsive UI development
- Inertia.js to bridge Laravel backend and Vue frontend
- Dynamic and reusable UI components (forms, modals, tables)

---

## 🏢 ERP Core Modules

- 📦 **Inventory Management**  
- 🛒 **Purchase Orders and Supplier Management**  
- 🏷️ **Product and Category Management**  
- 👥 **Customer Relationship Management (CRM)**  
- 📊 **Reporting and Analytics**  
- 🔒 **Role-Based Access Control and Permissions**  
- 🧩 **Extensible for future custom modules**

---

## 📄 Key Features

- ✅ Modular and scalable architecture
- ✅ Dynamic form and table components
- ✅ Secure authentication and authorization system
- ✅ Mobile-responsive and fast-loading UI
- ✅ Optimized for both internal and external integrations
- ✅ Clean codebase ready for production deployments

---

## 👨‍💻 Developed by

**Ian Ichiro Beguas**  
Full-Stack Web Developer | Full Stack Web Developer 
🌐 [https://edeesonopina.vercel.app](https://porfolio-ichiro.vercel.app)

**Edeeson Opina**  
Full-Stack Web Developer | Full Stack Web Developer 
🌐 [https://edeesonopina.vercel.app](https://edeesonopina.vercel.app)

---

## 📝 License

This project is open-source and may be used for educational or commercial purposes. Attribution is appreciated.

---

## 📦 Installation & Setup

```bash
# Clone the repository
git clone https://github.com/EdeesonOpina/laravue-erp.git
cd laravue-erp

# Install backend dependencies
composer install

# Install frontend dependencies
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run database migrations and seeders
php artisan migrate --seed

# Start development servers
npm run dev
npm run build
php artisan serve

