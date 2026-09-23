# MSEC SMART STOCK — Team Collaboration & Work Division Guide
### Architecture: Multi-PC Distributed Development (Lead + 2 Team Members)
**Institution:** Mohamed Sathak Engineering College (MSEC), Kilakarai  
**Project Lead:** Muhammad  
**Repository Architecture:** Modular PHP 8.2 + Portable SQLite + Shared CSS Design System  

---

## 🎯 1. Work Division Strategy (The 3-Person Breakdown)

To avoid conflicts and ensure everyone's work merges seamlessly across different PCs, the work is strictly decoupled into **isolated modules**:

```
                               ┌────────────────────────────────────────────────┐
                               │   TEAM LEADER (Muhammad)                       │
                               │   - Core Architecture, Auth & Approval Engine │
                               │   - Global Responsive Design System & CSS      │
                               │   - Inventory Master, Requisitions & Vouchers  │
                               │   - Procurement, Verification & Final Merges   │
                               └───────────────────────┬────────────────────────┘
                                                       │
                           ┌───────────────────────────┴───────────────────────────┐
                           ▼                                                       ▼
       ┌───────────────────────────────────────┐               ┌───────────────────────────────────────┐
       │   PERSON 1 (Junior / Colleague A)     │               │   PERSON 2 (Junior / Colleague B)     │
       │   Module: IT Asset Tracking           │               │   Module: Stock Movement & Transfers  │
       │   Files:                              │               │   Files:                              │
       │   - models/Asset.php                  │               │   - models/Movement.php               │
       │   - views/assets/index.php            │               │   - views/movement/index.php          │
       └───────────────────────────────────────┘               └───────────────────────────────────────┘
```

---

## 🔒 2. File Ownership Boundaries (Zero Merge Conflict Rule)

The secret to seamless multi-PC collaboration is **strict file isolation**:

| Team Member | Permitted Files to Edit | STRICTLY FORBIDDEN (Off-Limits) |
|:---|:---|:---|
| **Lead (You)** | `core/`, `config/`, `public/index.php`, `public/css/style.css`, `views/layout/`, `views/inventory/`, `views/requests/`, `views/distribution/`, `views/reports/` | — |
| **Person 1** | `models/Asset.php`<br>`views/assets/index.php` | DO NOT touch `core/`, `config/`, `public/css/`, `layout/`, or other view folders. |
| **Person 2** | `models/Movement.php`<br>`views/movement/index.php` | DO NOT touch `core/`, `config/`, `public/css/`, `layout/`, or other view folders. |

> [!IMPORTANT]
> **The Golden Rule:** When Person 1 and Person 2 only touch their assigned `models/` file and `views/` folder, **Git merge conflicts are mathematically ZERO (0%)**.

---

## 🚀 3. Step-by-Step Multi-PC Setup Guide (Git / GitHub)

### Step 3.1: Lead Pushes Code to GitHub (One-Time Setup by Lead)
Run these commands in your project terminal:
```powershell
cd C:\Users\mohds\.gemini\antigravity-ide\scratch\msec-smart-stock

# 1. Initialize Git
git init

# 2. Add all files & commit
git add .
git commit -m "Initial baseline: MSEC Smart Stock with RBAC and Responsive Design"

# 3. Create a Private Repository on GitHub (e.g., msec-smart-stock)
# 4. Link and push to main branch
git branch -M main
git remote add origin https://github.com/tech-shakil/msec-smart-stock.git
git push -u origin main
```

---

### Step 3.2: How Person 1 Sets Up Their PC
Person 1 opens terminal/command prompt on their computer:
```bash
# 1. Clone repository
git clone https://github.com/tech-shakil/msec-smart-stock.git
cd msec-smart-stock

# 2. Create and switch to their own isolated feature branch
git checkout -b feature/asset-management

# 3. Start local development server (No MySQL required! SQLite works immediately)
run.bat
# (Or: php -S localhost:8000 -t public)

# 4. Open in browser: http://localhost:8000
```

---

### Step 3.3: How Person 2 Sets Up Their PC
Person 2 opens terminal/command prompt on their computer:
```bash
# 1. Clone repository
git clone https://github.com/tech-shakil/msec-smart-stock.git
cd msec-smart-stock

# 2. Create and switch to their own isolated feature branch
git checkout -b feature/stock-movement

# 3. Start local development server
run.bat
# (Or: php -S localhost:8000 -t public)

# 4. Open in browser: http://localhost:8000
```

---

## 💻 4. Person 1 Work Package: IT Asset Tracking & Maintenance

* **Assigned To:** Person 1 (Junior A)
* **Branch Name:** `feature/asset-management`
* **Assigned Files:**
  - [`models/Asset.php`](file:///c:/Users/mohds/.gemini/antigravity-ide/scratch/msec-smart-stock/models/Asset.php)
  - [`views/assets/index.php`](file:///c:/Users/mohds/.gemini/antigravity-ide/scratch/msec-smart-stock/views/assets/index.php)

### 🔑 Roles & Rights for Asset Management:
| Role / Persona | Permitted Rights | What to Show in UI | What to HIDE (Off-Limits) |
|:---|:---|:---|:---|
| **Central Storekeeper** (`storekeeper`) & **Dept Coordinator** (`coord_cse`) | `create_asset`<br>`report_damage` | • **"+ Register New Asset"** header button & registration modal<br>• Full Asset catalog with MAC, IP, AMC<br>• **"Log Issue"** maintenance button | None (Full section access) |
| **Lab In-Charge** (`lab_ai`) & **Technician / Faculty** (`lab_mech`) | `report_damage` | • Asset catalog for their lab/department<br>• **"Log Issue"** fault reporting button & modal | ❌ **HIDE** "+ Register New Asset" button & registration modal |
| **Principal / Director / Auditor / Management** (`principal`, `director`, `auditor`) | Read-Only Governance | • Asset catalog, search filter by serial/MAC/IP<br>• Warranty/AMC status badges | ❌ **HIDE** "+ Register New Asset" button<br>❌ **HIDE** "Log Issue" action buttons (display "View Only" badge instead) |

### 🎯 "Only Show What is Necessary" Rules for Person 1:
1. **Guard Creation:** Wrap "+ Register New Asset" button and `newAssetModal` inside `<?php if (Auth::can('create_asset')): ?> ... <?php endif; ?>`.
2. **Guard Issue Logging:** Wrap "Log Issue" action button and `serviceModal` inside `<?php if (Auth::can('report_damage')): ?> ... <?php endif; ?>`.
3. **Clean Display for Reviewers:** When an auditor or principal logs in, they only see clean data tables and status pills without any mutation buttons.
4. **How Person 1 Submits Their Work:**
   ```bash
   git add models/Asset.php views/assets/index.php
   git commit -m "feat(assets): enforce RBAC visibility and IT hardware spec register"
   git push origin feature/asset-management
   ```
   *Then Person 1 creates a **Pull Request (PR)** on GitHub targeting the `main` branch.*

---

## 🔄 5. Person 2 Work Package: Stock Movement & Inter-Department Transfers

* **Assigned To:** Person 2 (Junior B)
* **Branch Name:** `feature/stock-movement`
* **Assigned Files:**
  - [`models/Movement.php`](file:///c:/Users/mohds/.gemini/antigravity-ide/scratch/msec-smart-stock/models/Movement.php)
  - [`views/movement/index.php`](file:///c:/Users/mohds/.gemini/antigravity-ide/scratch/msec-smart-stock/views/movement/index.php)

### 🔑 Roles & Rights for Stock Movement & Transfers:
| Role / Persona | Permitted Rights | What to Show in UI | What to HIDE (Off-Limits) |
|:---|:---|:---|:---|
| **Dept Coordinator / Lab In-Charge / HOD** (`coord_cse`, `lab_ai`, `hod_cse`) | `create_transfer`<br>`advance_transfer` | • **"+ Initiate Inter-Dept Transfer"** button & modal<br>• Department movement register<br>• Releasing approval & receiving acknowledgment buttons | ❌ Central store internal movement controls |
| **Central Storekeeper** (`storekeeper`) | `create_transfer`<br>`advance_transfer` | • Full institutional transfer register<br>• Central Store &rarr; Dept transfers<br>• **"Dispatch"** transit progression buttons | None |
| **Principal / Director / Auditor** (`principal`, `director`, `auditor`) | Read-Only Audit & Traceability | • Complete movement audit register<br>• Transit lifecycle timeline (Submitted &rarr; Approved &rarr; Dispatched &rarr; In Transit &rarr; Acknowledged)<br>• Transfer justification notes | ❌ **HIDE** "+ Initiate Inter-Dept Transfer" button<br>❌ **HIDE** Action links (`Approve`, `Dispatch`, `Acknowledge`) — display status badges only |

### 🎯 "Only Show What is Necessary" Rules for Person 2:
1. **Guard Initiation:** Wrap "+ Initiate Inter-Dept Transfer" button and `newTransferModal` inside `<?php if (Auth::can('create_transfer')): ?> ... <?php endif; ?>`.
2. **Guard Status Progression:** Wrap lifecycle action links (`Approve`, `Dispatch`, `Acknowledge & Close`) inside `<?php if (Auth::can('advance_transfer')): ?> ... <?php endif; ?>`. For viewers, show only informational badges (`Awaiting Approval`, `In Transit`, `Completed`).
3. **Stock Balance Synchronization:** When status reaches `Acknowledged`, ensure source department stock decreases and destination department stock increases automatically.
4. **How Person 2 Submits Their Work:**
   ```bash
   git add models/Movement.php views/movement/index.php
   git commit -m "feat(movement): enforce RBAC transfer lifecycle and stock balance sync"
   git push origin feature/stock-movement
   ```
   *Then Person 2 creates a **Pull Request (PR)** on GitHub targeting the `main` branch.*

---

## 🎨 6. UI & Design Consistency Rules (For Person 1 & 2)

To ensure the application looks 100% unified, professional, and responsive without anyone breaking the styles:

> [!WARNING]
> **Strict Rule:** **DO NOT** create custom CSS files or add inline `<style>` tags with hardcoded fonts or weird colors. Use the pre-built design system tokens already defined in `public/css/style.css`:

### Standard UI Elements to Use:
| Component | How Person 1 & 2 Should Write It in HTML/PHP |
|:---|:---|
| **Primary Button** | `<button class="btn btn-primary">+ Action Name</button>` |
| **Secondary Button**| `<button class="btn btn-secondary">Cancel</button>` |
| **Card Container** | `<div class="card"><div class="card-header"><div class="card-title">Title</div></div><div class="card-body">Content</div></div>` |
| **Data Table** | `<div class="data-table-container"><table class="data-table">...</table></div>` |
| **Status Badge** | `<span class="badge badge-success">Approved</span>` / `badge-warning` / `badge-danger` |
| **Scrollable Tabs** | `<div class="tabs-nav-scroll"><a class="btn btn-primary btn-sm">Tab 1</a></div>` |
| **Form Inputs** | `<div class="form-group"><label class="form-label">Field Name</label><input type="text" class="form-control"></div>` |
| **Modal Dialog** | Use standard `<div id="myModal" class="modal-backdrop"><div class="modal-dialog">...</div></div>` |

---

## 🛡️ 7. How Lead (You) Merges Their Work Safely

When Person 1 and Person 2 submit their Pull Requests on GitHub:

1. **Review on GitHub:** Open the PR and check `Files Changed`. Verify Person 1 only modified `models/Asset.php` and `views/assets/index.php`, and Person 2 only modified `models/Movement.php` and `views/movement/index.php`.
2. **Merge on GitHub:** Click **Merge Pull Request** &rarr; **Confirm Merge**.
3. **Pull Down to Lead PC:**
   ```powershell
   cd C:\Users\mohds\.gemini\antigravity-ide\scratch\msec-smart-stock
   git checkout main
   git pull origin main
   ```
4. **Test Live:** Open `http://localhost:8000` — both modules will now run together in your main branch seamlessly!

---

## 📋 8. Quick Handout Text to Send to Person 1 & Person 2

You can copy and send this exact message to your 2 colleagues on WhatsApp / Email:

```text
Hi Team! Here is our project setup, work allocation, and roles & rights specification for MSEC SMART STOCK:

1. Clone Repo:
   git clone https://github.com/tech-shakil/msec-smart-stock.git
   cd msec-smart-stock

2. Run Locally (Zero configuration! SQLite is pre-seeded):
   Double-click run.bat (or run: php -S localhost:8000 -t public)
   Open browser at: http://localhost:8000
   (Use the "🎭 Demo Role" switcher at top-right to test any role instantly)

3. Work Allocation, Assigned Roles & Rights:

   ► COLLEAGUE 1 (IT Asset Tracking & Maintenance):
     • Branch: git checkout -b feature/asset-management
     • Files to touch: ONLY models/Asset.php and views/assets/index.php
     • Roles & Rights for your section:
       - Store Keeper (storekeeper) & Dept Coordinator (coord_cse): Full rights (can register new assets with + Register button, report damage, view all specs).
       - Lab In-Charge (lab_ai) & Faculty/Tech (lab_mech): Can view lab assets and report damaged equipment with "Log Issue" button.
       - Principal / Director / Auditor (principal, auditor): Read-only view (CANNOT see register or report buttons, only data table and "View Only" badge).
     • Rule: Only show what is necessary! Wrap buttons and modals with if (Auth::can('create_asset')) and if (Auth::can('report_damage')).

   ► COLLEAGUE 2 (Stock Movement & Inter-Dept Transfers):
     • Branch: git checkout -b feature/stock-movement
     • Files to touch: ONLY models/Movement.php and views/movement/index.php
     • Roles & Rights for your section:
       - Dept Coordinator (coord_cse) & Lab In-Charge (lab_ai) & HOD (hod_cse): Can initiate transfer (+ Initiate Transfer button) and advance their stage.
       - Central Storekeeper (storekeeper): Can initiate, dispatch, and monitor all campus transfers.
       - Principal / Director / Auditor (principal, auditor): Read-only audit trail (CANNOT see initiate or action buttons; show only status badges).
     • Rule: Only show what is necessary! Wrap "+ Initiate Transfer" and action links with if (Auth::can('create_transfer')) and if (Auth::can('advance_transfer')).

   ► LEAD (Muhammad Shakil):
     • Handles core, routing, database, security, and remaining modules.

4. UI Guideline:
   Use existing CSS classes (.card, .btn-primary, .btn-secondary, .data-table, .form-control, .badge) from public/css/style.css so all pages remain 100% unified and mobile-responsive.

5. When done:
   Push to your feature branch and create a Pull Request on GitHub. Do NOT push directly to main.
```
