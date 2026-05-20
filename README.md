<div align="center">

<img src="public/images/MAin Logo.png" alt="Gobaad Bank Logo" width="100" />

# Gobaad Bank Management System

**A full-stack, multi-branch banking platform built with Laravel 12, Vue 3, and Inertia.js**

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat&logo=laravel&logoColor=white)
![Vue](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=flat&logo=vue.js&logoColor=white)
![Inertia](https://img.shields.io/badge/Inertia.js-latest-9553E9?style=flat)
![Tailwind](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=flat&logo=tailwind-css&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?style=flat&logo=mysql&logoColor=white)

*Final Year Project — University*

</div>

---


## Overview

The Gobaad Bank Management System is a complete banking operations platform covering:

- **Staff Portal** — Teller operations, approvals, customer care, compliance, accounting, branch management
- **HQ Administration** — Super Admin dashboard, global analytics, branch and staff management, RBAC
- **Customer Self-Service Portal** — Account balances, transaction history, deposits, withdrawals, transfers, notifications
- **Document Generation** — Transaction receipts, account statements, branch performance reports (PDF)

---

## Technology Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 12 (PHP 8.3) |
| Frontend | Vue 3 (Composition API) |
| SPA Bridge | Inertia.js |
| Styling | Tailwind CSS 3 |
| Database | MySQL 8 (InnoDB) |
| Authentication | Laravel Session Guards (dual — staff + customer) |
| State Management | Pinia |
| Charts | ApexCharts (vue3-apexcharts) |
| PDF Generation | barryvdh/laravel-dompdf |
| Email | Laravel Mail via Gmail SMTP |
| Build Tool | Vite |

---

## Features

### Staff Portal
- **Teller Operations** — Cash deposit, withdrawal, inter-account transfer with transaction limit enforcement
- **Approval Queue** — Multi-level approval escalation (Teller → Supervisor → Branch Manager)
- **Cash Count** — Daily denomination breakdown with supervisor approval
- **Cash Allocation** — Vault-to-teller cash distribution with ledger entries
- **Customer Registration** — Full KYC document collection and account opening
- **Compliance & AML** — KYC verification queue, transaction monitoring, SAR filing
- **General Ledger** — Double-entry ledger, trial balance, GL detail view
- **Branch Reports** — KPI cards, daily trend charts, top tellers, largest transactions (printable PDF)
- **Branch Settings** — SWIFT code, working hours, contact info

### Security
- **Two-Factor Authentication** — 6-digit OTP via email on every login (expires in **3 minutes**)
- **Role-Based Access Control** — 11 roles, 50+ granular permissions, database-driven
- **Session Timeout** — Staff: warn at 8 min, logout at 10 min | Customer: warn at 3 min, logout at 5 min
- **Immutable Ledger** — No UPDATE or DELETE ever runs on `ledger_entries`
- **Audit Trail** — Every staff action logged with IP address and timestamp
- **Account Freeze** — Full freeze/unfreeze log with staff member and reason
- **Dark / Light Mode** — System-wide theme toggle, preference saved in localStorage

### Customer Portal
- Dashboard with account balances and recent transactions
- Full transaction history with filters
- Self-service deposit, withdrawal, and transfer requests
- Notification inbox with unread badge
- Profile management and password change

---

## Requirements

- PHP >= 8.2
- Composer
- Node.js >= 18 & npm
- MySQL 8
- Git

---

## Installation

### 1. Clone the repository

```bash
git clone <repository-url>
cd BankSystem
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install Node dependencies

```bash
npm install
```

### 4. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database and mail credentials:

```env
DB_DATABASE=BankSystem
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD="your_gmail_app_password"
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="Gobaad Bank"
```

> **Gmail App Password:** Enable 2FA on your Google account, then generate an App Password at [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords). Use that as `MAIL_PASSWORD` — not your regular Gmail password.

### 5. Create the database

```sql
CREATE DATABASE BankSystem;
```

### 6. Run migrations and seed

```bash
php artisan migrate
php artisan db:seed
```

### 7. Storage symlink

```bash
php artisan storage:link
```

### 8. Build frontend assets

```bash
npm run build
```

### 9. Start the server

```bash
php artisan serve
```

Visit **http://localhost:8000**

---

## Default Login Credentials

> **Change all passwords immediately after first login.**

### Staff Portal — `/login`

| Role | Staff ID | Password |
|------|----------|----------|
| Super Admin | `ADM-001` | `password` |
| Branch Manager | `BM-001` | `password` |
| Bank Teller | `TLR-001` | `password` |
| Customer Care | `CCO-001` | `password` |
| Compliance Officer | `COM-001` | `password` |

### Customer Portal — `/customer/login`

Customers log in with their **phone number** and the temporary password sent to their email after KYC approval.

> **2FA is required for all logins.** A 6-digit OTP will be sent to the registered email address and expires in 3 minutes.

---

## Project Structure

```
BankSystem/
├── app/
│   ├── Http/
│   │   ├── Controllers/         # Inertia page controllers
│   │   └── Middleware/          # Auth, RBAC, BranchScope, AuditLogger
│   ├── Models/                  # Eloquent models (OtpCode, Staff, Customer, etc.)
│   └── Services/
│       └── LedgerService.php    # Double-entry ledger engine
├── database/
│   ├── migrations/              # 23 migration files (run in dependency order)
│   └── seeders/                 # Roles, permissions, branches, Super Admin
├── public/
│   └── images/                  # Bank logos — git-tracked, always available
├── resources/
│   ├── css/
│   │   └── app.css              # Tailwind base + dark/light mode CSS overrides
│   ├── js/
│   │   ├── composables/
│   │   │   └── useTheme.js      # Dark/light mode toggle with localStorage
│   │   ├── Layouts/
│   │   │   ├── AuthenticatedLayout.vue   # Staff portal layout + idle timer
│   │   │   └── CustomerLayout.vue        # Customer portal layout + idle timer
│   │   └── Pages/               # All Vue page components by module
│   └── views/
│       ├── app.blade.php        # Root Inertia HTML template
│       ├── receipts/            # Transaction receipt Blade/dompdf templates
│       └── statements/          # Account statement Blade/dompdf template
└── routes/
    └── web.php                  # All 160 application routes
```

---

## Key Configuration

| Setting | Value | Where |
|---------|-------|--------|
| Server session lifetime | 10 minutes | `.env` → `SESSION_LIFETIME` |
| Staff idle warning | 8 minutes | `AuthenticatedLayout.vue` |
| Staff auto-logout | 10 minutes | `AuthenticatedLayout.vue` |
| Customer idle warning | 3 minutes | `CustomerLayout.vue` |
| Customer auto-logout | 5 minutes | `CustomerLayout.vue` |
| OTP expiry (server) | 3 minutes | `app/Models/OtpCode.php` |
| OTP countdown (frontend) | 3 minutes | `Pages/Auth/Verify2fa.vue` |
| bcrypt rounds | 12 | `.env` → `BCRYPT_ROUNDS` |

---

## Database Overview

The system uses **23 MySQL tables** with a double-entry General Ledger at its core.

```
branches · roles · permissions · role_permissions · staff · customers
account_types · accounts · transactions · ledger_entries · pending_approvals
inter_branch_clearing · loans · loan_repayments · kyc_documents · notifications
staff_audit_log · account_freeze_logs · cash_allocations · cash_counts
suspicious_activity_reports · otp_codes · cache
```

> `ledger_entries` is **append-only** — no UPDATE or DELETE is ever executed on this table. Corrections are made through reversal entries only.

---

## Staff Roles

| Role | Tier | Txn Limit |
|------|------|-----------|
| Super Admin | System | Unlimited |
| System Admin | System | None (infra only) |
| Branch Manager | Branch | Unlimited (own branch) |
| Operational Manager | Branch | High |
| Teller Supervisor | Branch | Above teller |
| Bank Teller | Front-line | $5,000 / txn |
| Customer Care Officer | Front-line | N/A |
| Loan Officer | Specialist | Approve up to $50K |
| Compliance Officer | Specialist | N/A (cross-branch read) |
| Internal Auditor | Oversight | N/A (read-only all) |
| Vault Cashier | Branch | Vault operations |

---

## Common Commands

```bash
# Clear all caches after .env changes
php artisan config:clear && php artisan cache:clear && php artisan view:clear

# Rebuild frontend
npm run build

# Watch for changes (development)
npm run dev

# Fresh migration with seed (WARNING: wipes all data)
php artisan migrate:fresh --seed

# List all routes
php artisan route:list

# Open PHP REPL
php artisan tinker
```

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Logo not showing | Files are in `public/images/` — no symlink needed. Run `php artisan storage:link` if KYC documents are missing. |
| Mail / OTP not sending | Use a Gmail **App Password**, not your regular password. Generate at myaccount.google.com/apppasswords |
| Session expires immediately | Check `SESSION_LIFETIME` in `.env`, then run `php artisan config:clear` |
| 403 Forbidden | The staff role lacks the required permission. Check HQ Admin → Roles & Permissions. |
| Migrations fail | Ensure MySQL is running and the database exists: `CREATE DATABASE BankSystem;` |
| Blank page after clone | Run `composer install`, `npm install`, `npm run build`, `php artisan key:generate` |

---

## License

This project was developed as a **Final Year University Project**.
For academic and educational use only — not licensed for commercial deployment.

---

<div align="center">
  <br/>
  <sub>Built with ❤️ by <strong>Abdalla Firin Saed</strong> · <strong>Sakariye Aadan Mohamed</strong> · <strong>Anas Abdijibar Faarah</strong> · <strong>Naima Said Hirsi</strong></sub>
  <br/>
  <sub><em>Gobaad Bank Management System — Final Year Project</em></sub>
</div>
