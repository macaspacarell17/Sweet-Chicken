# Sweet Chicken — Web-Based Restaurant Management System with Role-Based Access Control

> Guagua National Colleges, Inc. · Sta. Filomena, Guagua, Pampanga
> Research project, August 2026

A centralized, browser-accessible restaurant management platform for **Sweet Chicken Restaurant** (Purok 2, San Pablo, Guagua, Pampanga). It replaces the restaurant's current mobile-only Point-of-Sale application (Peddlr) with a single web system that gives four types of users — **Administrator, Cashier, Kitchen Staff, and Customer** — their own dashboard and permissions, so each user sees and does only what their role requires.

---

## 1. Background and Purpose

Sweet Chicken Restaurant sells flavored fried chicken meals, barkada feast combos, beverages, desserts, and add-ons to dine-in and take-out customers. Its current POS, Peddlr, has several limitations:

| Limitation of the current system | How this system addresses it |
| --- | --- |
| Slow online-order processing and sales-report synchronization during peak hours | All modules share one MySQL database and refresh automatically (see [Real-Time Synchronization](#5-real-time-synchronization)) |
| Mobile-only; no desktop or laptop access | Web-based; runs in a browser on desktop, laptop, tablet, and mobile devices with no installation |
| No real-time channel between cashier and kitchen; orders are relayed on paper | Kitchen module displays incoming orders and status updates without manual refreshing |
| **No role-based access control** — owner, cashier, and kitchen helper see and can change the same data | Role-Based Access Control (RBAC) enforced on the server for every page and API call |
| No audit logging — no way to tell who processed a transaction or changed a record | Audit-log module: every login, order, menu change, and account change is recorded with the user, time, and IP address |

**Primary objective:** develop a centralized, secure, web-based platform that improves operational efficiency, transaction accuracy, cashier–kitchen communication, cross-device accessibility, and the security of restaurant and customer information.

---

## 2. Modules and User Roles

RBAC theory (Ferraiolo & Kuhn, 1992) governs the system: each role carries only the permissions its responsibilities require.

| Role | Dashboard | What the role can do | What the role cannot do |
| --- | --- | --- | --- |
| **Administrator** | `admin_dashboard.php` | Dashboard and sales reports; menu and pricing management; create, edit, deactivate, and reactivate Cashier and Kitchen Staff accounts; monitor customer accounts and login activity; monitor all orders (dine-in, take-out, online); review and export audit logs | — (full access) |
| **Cashier** | `cashier_dashboard.php` | Point-of-sale for walk-in orders; handle the queue of online orders (confirm, then complete when ready); accept cash and GCash payments; issue receipts; view sales reports | Create employee accounts; change menu prices; change access-control settings |
| **Kitchen Staff** | `kitchen_dashboard.php` | View incoming orders with their customizations; mark confirmed orders as **Ready** | See sales, pricing, or account management |
| **Customer** | `customer_dashboard.php` | The self-service **"DIY POS"**: browse the menu, customize a dine-in or take-out order, pay by cash or GCash, and track the order's live status | See other customers' data or any internal report |

Customers register with an email address verified by a one-time password (OTP). Employee accounts (Cashier and Kitchen Staff) are created by the Administrator. Any user can reset a forgotten password through an emailed OTP. A deactivated account is signed out immediately and cannot log in until the Administrator reactivates it.

---

## 3. Features

- **Role-based authentication and authorization** — session-based login; every dashboard and API action checks the user's role.
- **Customer self-service ordering ("DIY POS")** — menu browsing, meal customization (chicken flavor, rice, beverage, add-ons), dine-in or take-out, cash or GCash payment (GCash requires a reference number), live order tracking.
- **Point-of-Sale (Cashier)** — walk-in order entry, table number for dine-in, cash/GCash payment, receipts.
- **Online-order management** — incoming customer orders are confirmed by the Cashier before reaching the Kitchen.
- **Kitchen order display** — real-time display of orders to prepare, including customizations, with status updates.
- **Menu management** — Administrator adds, edits, and removes items, prices, and availability.
- **Sales reporting** — sales are recorded when an order is completed; daily, weekly, and monthly views for owners and cashiers.
- **Employee account management** — Administrator registers Cashier and Kitchen Staff accounts, edits their details, and deactivates or reactivates them. Changes take effect immediately, even for users who are already logged in.
- **Audit logging** — records logins, logouts, failed logins, customer registrations, password resets, order placement and status changes, payment updates, menu changes, and employee-account changes, each with the user, timestamp, and IP address. Only the Administrator can view and export the log (CSV).
- **Real-time synchronization** — Customer, Cashier, Kitchen, and Administrator modules reflect the same data without manual page refreshing.

### Menu categories

Barkada Meals · Flavored Chicken Combo · Soda · Dessert · Add-Ons · Juice
Chicken flavors offered: Honey Garlic, Sweet Chili, Buffalo, Garlic Parmesan, Salted Egg, Original, Yangnyeom.

---

## 4. Order Workflow

```
Customer (DIY POS) → place_order.php ──► Order: Pending
                                              │  Cashier confirms
                                              ▼
                                         Preparing ──► Kitchen marks Ready
                                                              │
                                                              ▼
                                         Ready ──► Cashier completes ──► Completed ──► recorded in Sales ──► Administrator reports
```

| Status | Meaning | Who sets it |
| --- | --- | --- |
| `Pending` | Order received from a customer, awaiting confirmation | System (on customer order) |
| `Preparing` | Confirmed; visible on the Kitchen display | Cashier (walk-in orders start here) |
| `Ready` | Food is prepared and waiting for pickup or serving | Kitchen Staff |
| `Completed` | Order handed over; sale recorded | Cashier |
| `Cancelled` | Order cancelled | Cashier / Administrator |

Transition rules are enforced in `api.php` (for example, Kitchen can only move an order from `Preparing` to `Ready`; Cashier can only complete an order that is `Ready`).

**Walk-in orders** entered at the Cashier are saved directly with status `Preparing` and appear on the Kitchen display immediately.

**Menu changes** made by the Administrator are saved to MySQL; the Customer dashboard picks them up automatically.

---

## 5. Real-Time Synchronization

All modules read and write the same MySQL database through `api.php`. Dashboards poll the server so changes appear without a manual refresh:

| Dashboard | Refresh interval |
| --- | --- |
| Kitchen — order list | every 1 second |
| Cashier — order board | every 1 second |
| Customer — order tracker (while open) | every 1 second |
| Administrator — data sync | every 5 seconds |
| Customer — menu availability and prices | every 10 seconds |

---

## 6. Technology Stack

| Layer | Technology |
| --- | --- |
| Front end | HTML, CSS, JavaScript (Chart.js for charts, Font Awesome icons, Google Fonts) |
| Back end | PHP (developed on 8.0) with `mysqli` |
| Database | MySQL / MariaDB (`sweet_chicken_db`) |
| Email (OTP) | PHPMailer over SMTP |
| Local environment | XAMPP (Apache + MySQL) |
| Design | Wireframes and prototypes in Figma |
| Methodology | Agile SDLC; Descriptive-Developmental research design |

---

## 7. Project Structure

```
SWEET_CHICKEN/
├── index.php                  Landing page
├── loginpage.php              Login, registration with email OTP, password reset
├── logout.php                 Ends the session
├── admin_dashboard.php        Administrator module
├── cashier_dashboard.php      Cashier module (POS + online-order queue)
├── kitchen_dashboard.php      Kitchen module
├── customer_dashboard.php     Customer module ("DIY POS")
├── api.php                    Shared backend API (orders, menu, sales, users, metrics)
├── place_order.php            Customer order submission (validates menu and stock)
├── auth.php                   Account-status check (deactivated accounts are signed out)
├── audit_log.php              createAuditLog() helper
├── get_audit_logs.php         Administrator-only audit-log feed (JSON)
├── generate_admin.php         Utility: generates a password hash for the admin account
├── config/database.php        Database connection
├── database/
│   ├── original_database.sql  Base schema
│   ├── connected_update.sql   Migration: product codes and order customer information
│   └── paper_alignment_update.sql  Migration: table number, Cash/GCash only, audit_logs, account status
├── css/ js/ images/           Per-page styles, scripts (incl. js/session_guard.js), and assets
├── PHPMailer/                 Email library for OTP messages
└── diypos.php                 Early standalone prototype (see Implementation Status)
```

---

## 8. Installation and Setup

**Requirements:** XAMPP (Apache and MySQL/MariaDB, PHP 8.0 or later) and a modern browser. An internet connection is required (the system is web-based).

1. Copy the project folder into `C:\xampp\htdocs\` (so it is served at `http://localhost/SWEET_CHICKEN/`). Make sure `images/` and `PHPMailer/` sit beside the PHP files.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open phpMyAdmin and set up the database, using **one** of these options:
   - **Option A (current data):** import `sweet_chicken_db.sql` (the current database export).
   - **Option B (fresh install):** import `database/original_database.sql`, then run `database/connected_update.sql` **once**.
4. In both cases, run `database/paper_alignment_update.sql` (it can safely be run more than once). It adds the dine-in `table_number` column, restricts payment methods to Cash and GCash, creates the `audit_logs` table, and adds the account `status` column. It uses MariaDB syntax, which is what XAMPP provides.
5. Check `config/database.php` (default: host `localhost`, user `root`, empty password, database `sweet_chicken_db`).
6. Configure the SMTP account used for OTP emails in `loginpage.php` (`sendOtpEmail()`), using an app password for your own mail account.
7. Open `http://localhost/SWEET_CHICKEN/loginpage.php`.

**Default Administrator account:** `admin@sweetchicken.com` — the password is `Admin123!` only if the hash in the `users` table was generated with `generate_admin.php`. **Change the password immediately after first login** and create the Cashier and Kitchen Staff accounts from the Administrator dashboard.

---

## 9. Implementation Status

This section records where the system still differs from the research paper, so the README does not overstate what is built.

**Aligned with the paper in this version:** delivery has been removed (Dine-In and Take-Out only); payment methods are Cash and GCash only (enforced in the code and the database); audit logging is implemented end to end; employee accounts can be edited and deactivated, and the change is saved and enforced; the dine-in `table_number` column is part of the migration.

**Remaining differences:**

| # | Area | Paper requirement | Current code | Suggested action |
| --- | --- | --- | --- | --- |
| 1 | Kitchen status flow | Received → Preparing → Ready | The Cashier confirms (`Pending` → `Preparing`); the Kitchen sees only `Preparing` orders and marks them `Ready` | Either describe the confirmation step in the paper's workflow, or let the Kitchen see `Pending` orders and start preparation |
| 2 | Cashier payment options | Cashier accepts cash and GCash | The walk-in payment dropdown lists Cash only (the GCash QR code logic exists but cannot be selected) | Add GCash to the dropdown in `cashier_dashboard.php` |
| 3 | Sales reports | Daily, weekly, and monthly reports | Cashier has daily/weekly views; Administrator has a monthly chart and a CSV export of all completed sales. The export button also shows a misleading "PDF/CSV" alert | Add period selection to the Administrator export and correct the alert |
| 4 | Access-control settings | Administrator manages access-control settings | The permission matrix on the Administrator dashboard is display-only and is not saved; actual permissions are enforced in code by role | Remove the matrix or persist it |
| 5 | Password reset by Administrator | "Reset passwords according to restaurant policy" | Users reset their own password through an emailed OTP; there is no Administrator-initiated reset | Acceptable for the current scope, or add an Administrator reset action |
| 6 | `diypos.php` | The Customer module *is* the "DIY POS" | A separate early prototype with a hard-coded menu and prices that differ from the database; not linked from any page | Remove it, or connect it to the database |
| 7 | Inventory | Limited to marking items available/unavailable | A per-item stock count is decremented on orders; the `inventory` table is unused | Acceptable; the unused table can be removed |
| 8 | Legacy `orders.address` column | No delivery | The column is kept so old rows are not lost, but the application no longer reads or writes it | Drop it after confirming no historical data is needed |
| 9 | Credentials and error messages | Secure handling of data | SMTP credentials are written directly in `loginpage.php`; `api.php` returns raw error text on unexpected failures; the admin hash in `original_database.sql` may not match the documented password | Move secrets to a configuration file kept out of source control, rotate the mail password, return generic error messages, and regenerate the admin hash with `generate_admin.php` |

---

## 10. Scope and Delimitation

**In scope:** customer ordering, order management, kitchen order display, sales reporting, menu management, employee account management, and RBAC for Sweet Chicken Restaurant, accessible from desktop, laptop, tablet, and mobile devices.

**Out of scope (per the study's delimitation):**

- Use by other restaurants without further customization
- Offline operation — a stable internet connection is required
- Payment methods other than **cash and GCash** (no credit/debit cards or other e-wallets)
- **Delivery service**, GPS rider tracking, and rider assignment
- Inventory beyond marking items available/unavailable (no supplier management, forecasting, or automated purchasing)
- Accounting, payroll, and broader financial-management modules
- Advanced security such as biometric authentication, AI-based fraud detection, intrusion detection, or blockchain record-keeping

---

## 11. Glossary

| Term | Meaning in this system |
| --- | --- |
| **RBAC** | Role-Based Access Control: permissions are assigned by role (Administrator, Cashier, Kitchen Staff, Customer), not granted uniformly |
| **DIY POS** | The Customer module: a smartphone-based self-service ordering interface |
| **Audit log** | A record of user actions (logins, order processing, menu updates, account changes) kept for accountability |
| **Real-time synchronization** | Order and transaction data are reflected across all modules without manual refreshing |
| **Peddlr** | The restaurant's current mobile-only POS application, which this system replaces |

---

## 12. Researchers

Tanedo, Romeo Jr. C. · Aga-in, Byron Kharl · Doncillo, Martin · Macaspac, Arell · Magno, Julian Naomi · Santos, Ranz Kerby · Supremido, Lois Lane · Valencia, Murfin Jello

*Guagua National Colleges, Inc. — August 2026*
