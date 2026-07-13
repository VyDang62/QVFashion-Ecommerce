# Fullstack Ecommerce

## Description

An e-commerce and business intelligence platform tailored for the fashion retail sector, built using the Modern Monolith architecture. This platform unifies digital storefront operations with complex back-office warehouse accounting and enables four main distinct workflows or implementations:

1. **Customers** browse product categories, manage customized variants (size, color, material), check out via automated shipping tiers, and perform secure online bank transactions.
2. **Sales Staff** manage order status processing, coordinate marketing voucher matrices, update homepage banners, and handle content moderation.
3. **Warehouse Managers** manage internal supplier logistics, create dynamic goods receipts, and review automated low-stock safety thresholds.
4. **Admins** maintain overriding system configuration control, design granular role-based permissions, and review comprehensive business audit trials.

### Features:

* **Laravel 11** provides the robust monolithic backend server and business logic environment for this application.
* **Inertia.js** operates as the communication layer, enabling server-driven data delivery without the need for client-side API routing.
* **Vue.js 3** powers the reactive user interface, providing a smooth, fast Single Page Application (SPA) user experience.
* **PostgreSQL Subqueries & Aggregations** handle heavy time-series reporting and multi-batch Cost of Goods Sold (COGS) tracking directly at the database layer.
* **Custom Route Middleware** enforces granular Role-Based Access Control (RBAC) to protect secure endpoints and sensitive financial registry data downloads.
* **Third-Party API Integrations** include the **VNPay Gateway** for real-time secure banking transactions and **Giao Hàng Nhanh (GHN)** for dynamic distance-based shipping fee computations.

## Install
`npm install` in the project root will install dependencies

## Database Seed
Before running the application, make sure your `.env` file is properly configured with your PostgreSQL credentials. Then, execute the following artisan commands to structure your tables and populate the core business data:

## Installation & Setup Guide

Follow these steps to clone the repository, install all required dependencies, and initialize the application environment.

### 1. Clone the Repository
```bash
git clone [https://github.com/VyDang62/QVFashion-Ecommerce.git](https://github.com/VyDang62/QVFashion-Ecommerce.git)
cd qvfashion
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### 2. Database setup

```bash
php artisan migrate
php artisan db:seed
```

### 3. Start development
```bash
npm run dev
php artisan queue:work
```
