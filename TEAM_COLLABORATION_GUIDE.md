# MSEC SMART STOCK — Team Collaboration & Work Division Guide
### Architecture: Multi-PC Distributed Development (Lead Muhammad + 2 Junior Developers)
**Institution:** Mohamed Sathak Engineering College (MSEC), Kilakarai  
**Project Lead:** Muhammad Shakil (System Architect & Full-Stack Lead)  
**Repository Architecture:** Modular PHP 8.2 + Portable SQLite / MariaDB + Shared CSS Design System  

---

## 🎯 1. Work Division Strategy (The 3-Person Breakdown)

To avoid merge conflicts and ensure everyone's work progresses in parallel across different PCs, the system is strictly decoupled into **isolated module packages**:

```
                               ┌────────────────────────────────────────────────────────┐
                               │             TEAM LEADER (Muhammad Shakil)              │
                               │  - Core Engine, Auth, Security & Approval Engine       │
                               │  - Global Responsive Design System & CSS Tokens        │
                               │  - Requisitions, Indents & Multi-Tier Approvals        │
                               │  - Procurement, Vendors, POs & Material GRN           │
                               │  - Master Inventory, Central Stores & Distribution     │
                               │  - NAAC/NBA Reports, Physical Verification & Merges    │
                               │  (MUHAMMAD SHAKIL HANDLES ALL OF THE ABOVE ALONE)      │
                               └───────────────────────────┬────────────────────────────┘
                                                           │
                            ┌──────────────────────────────┴──────────────────────────────┐
                            ▼                                                             ▼
        ┌───────────────────────────────────────┐             ┌───────────────────────────────────────┐
        │       JUNIOR 1 (Colleague A)          │             │       JUNIOR 2 (Colleague B)          │
        │   Module: IT Asset Tracking &         │             │   Module: Stock Movement &            │
        │   Hardware Maintenance                │             │   Inter-Department Transfers          │
        │                                       │             │                                       │
        │   Files:                              │             │   Files:                              │
        │   - models/Asset.php                  │             │   - models/Movement.php               │
        │   - views/assets/index.php            │             │   - views/movement/index.php          │
        │                                       │             │                                       │
        │   Branch:                             │             │   Branch:                             │
        │   feature/asset-management            │             │   feature/stock-movement              │
        └───────────────────────────────────────┘             └───────────────────────────────────────┘
```

---

## 🔒 2. File Ownership Boundaries (Zero Merge Conflict Rule)

The foundation of seamless multi-PC collaboration is **strict file isolation**. Each person works exclusively on their assigned model and view folder:

| Team Member | Branch Name | Permitted Files to Edit | STRICTLY FORBIDDEN (Off-Limits) |
|:---|:---|:---|:---|
| **Lead (Muhammad Shakil)** | `main` | `core/`, `config/`, `public/index.php`, `public/css/style.css`, `views/layout/`, `views/inventory/`, `views/requests/`, `views/procurement/`, `views/distribution/`, `views/reports/`, `views/verification/`, `views/admin/` | — *(Muhammad maintains and develops all remaining modules alone)* |
| **Junior 1 (Colleague A)** | `feature/asset-management` | `models/Asset.php`<br>`views/assets/index.php` | DO NOT touch `core/`, `config/`, `public/css/`, `layout/`, or other view folders. |
| **Junior 2 (Colleague B)** | `feature/stock-movement` | `models/Movement.php`<br>`views/movement/index.php` | DO NOT touch `core/`, `config/`, `public/css/`, `layout/`, or other view folders. |

> [!IMPORTANT]
> **The Golden Rule:** When Junior 1 and Junior 2 edit **only their assigned files**, Git merge conflicts are mathematically **ZERO (0%)**. Muhammad can merge both branches into `main` automatically without any code collisions.

---

## 🚀 3. Step-by-Step Multi-PC Setup Guide (Git / GitHub)

### Step 3.1: Lead Pushes Code to GitHub (One-Time Setup by Muhammad)
Run these commands in your project terminal:
```powershell
cd C:\Users\mohds\.gemini\antigravity-ide\scratch\msec-smart-stock

# 1. Initialize Git & commit baseline
git add .
git commit -m "feat: complete MSEC Smart Stock baseline with 3-person team guide"

# 2. Push to GitHub main branch
git branch -M main
git push -u origin main
```

---

### Step 3.2: How Junior 1 Sets Up Their PC (IT Asset Tracking)
Junior 1 opens terminal/command prompt on their computer:
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

### Step 3.3: How Junior 2 Sets Up Their PC (Stock Movement & Transfers)
Junior 2 opens terminal/command prompt on their computer:
```bash
# 1. Clone repository
git clone https://github.com/tech-shakil/msec-smart-stock.git
cd msec-smart-stock

# 2. Create and switch to their own isolated feature branch
git checkout -b feature/stock-movement

# 3. Start local development server (No MySQL required! SQLite works immediately)
run.bat
# (Or: php -S localhost:8000 -t public)

# 4. Open in browser: http://localhost:8000
```

---

## 💻 4. Junior 1 Work Package: IT Asset Tracking & Maintenance

* **Assigned To:** Junior 1 (Colleague A)
* **Branch Name:** `feature/asset-management`
* **Assigned Files (ONLY touch these):**
  - [`models/Asset.php`](file:///c:/Users/mohds/.gemini/antigravity-ide/scratch/msec-smart-stock/models/Asset.php)
  - [`views/assets/index.php`](file:///c:/Users/mohds/.gemini/antigravity-ide/scratch/msec-smart-stock/views/assets/index.php)

### 🔑 Roles & Rights for Asset Management:
| Role / Persona | Permitted Rights | What to Show in UI | What to HIDE (Off-Limits) |
|:---|:---|:---|:---|
| **Central Storekeeper** (`storekeeper`) & **Dept Coordinator** (`coord_cse`) | `create_asset`<br>`report_damage` | • **"+ Register New Asset"** header button & registration modal<br>• Full Asset catalog with MAC, IP, AMC<br>• **"Log Issue"** maintenance button | None (Full section access) |
| **Lab In-Charge** (`lab_ai`) & **Technician / Faculty** (`lab_mech`) | `report_damage` | • Asset catalog for their lab/department<br>• **"Log Issue"** fault reporting button & modal | ❌ **HIDE** "+ Register New Asset" button & registration modal |
| **Principal / Director / Auditor / Management** (`principal`, `director`, `auditor`) | Read-Only Governance | • Asset catalog, search filter by serial/MAC/IP<br>• Warranty/AMC status badges | ❌ **HIDE** "+ Register New Asset" button<br>❌ **HIDE** "Log Issue" action buttons (display "View Only" badge instead) |

### 🎯 Junior 1 Action Items:
1. **Guard Creation:** Wrap "+ Register New Asset" button and `newAssetModal` inside `<?php if (Auth::can('create_asset')): ?> ... <?php endif; ?>`.
2. **Guard Issue Logging:** Wrap "Log Issue" action button and `serviceModal` inside `<?php if (Auth::can('report_damage')): ?> ... <?php endif; ?>`.
3. **Hardware Spec Tracking:** Ensure inputs capture Serial Number, MAC Address, IP Address, Configuration (CPU/RAM/SSD), AMC Details, and Warranty Expiry.
4. **How Junior 1 Submits:**
   ```bash
   git add models/Asset.php views/assets/index.php
   git commit -m "feat(assets): enforce RBAC visibility and IT hardware spec register"
   git push origin feature/asset-management
   ```

---

## 🔄 5. Junior 2 Work Package: Stock Movement & Inter-Department Transfers

* **Assigned To:** Junior 2 (Colleague B)
* **Branch Name:** `feature/stock-movement`
* **Assigned Files (ONLY touch these):**
  - [`models/Movement.php`](file:///c:/Users/mohds/.gemini/antigravity-ide/scratch/msec-smart-stock/models/Movement.php)
  - [`views/movement/index.php`](file:///c:/Users/mohds/.gemini/antigravity-ide/scratch/msec-smart-stock/views/movement/index.php)

### 🔑 Roles & Rights for Stock Movement:
| Role / Persona | Permitted Rights | What to Show in UI | What to HIDE (Off-Limits) |
|:---|:---|:---|:---|
| **Dept Coordinator / Lab In-Charge / HOD** (`coord_cse`, `lab_ai`, `hod_cse`) | `create_transfer`<br>`advance_transfer` | • **"+ Initiate Inter-Dept Transfer"** button & modal<br>• Department movement register<br>• Releasing approval & receiving acknowledgment buttons | ❌ Central store internal movement controls |
| **Central Storekeeper** (`storekeeper`) | `create_transfer`<br>`advance_transfer` | • Full institutional transfer register<br>• Central Store &rarr; Dept transfers<br>• **"Dispatch"** transit progression buttons | None |
| **Principal / Director / Auditor** (`principal`, `director`, `auditor`) | Read-Only Audit & Traceability | • Complete movement audit register<br>• Transit lifecycle timeline (Submitted &rarr; Approved &rarr; Dispatched &rarr; In Transit &rarr; Acknowledged)<br>• Transfer justification notes | ❌ **HIDE** "+ Initiate Inter-Dept Transfer" button<br>❌ **HIDE** Action links (`Approve`, `Dispatch`, `Acknowledge`) — display status badges only |

### 🎯 Junior 2 Action Items:
1. **Guard Initiation:** Wrap "+ Initiate Inter-Dept Transfer" button and `newTransferModal` inside `<?php if (Auth::can('create_transfer')): ?> ... <?php endif; ?>`.
2. **Guard Status Progression:** Wrap lifecycle action links (`Approve`, `Dispatch`, `Acknowledge & Close`) inside `<?php if (Auth::can('advance_transfer')): ?> ... <?php endif; ?>`.
3. **Stock Balance Synchronization:** When status reaches `Acknowledged`, ensure source department stock decreases and destination department stock increases automatically.
4. **How Junior 2 Submits:**
   ```bash
   git add models/Movement.php views/movement/index.php
   git commit -m "feat(movement): enforce RBAC transfer lifecycle and stock balance sync"
   git push origin feature/stock-movement
   ```

---

## 🎨 6. UI & Design Consistency Rules (For Both Juniors)

To ensure the application looks 100% unified, professional, and responsive without anyone breaking the styles:

> [!WARNING]
> **Strict Rule:** **DO NOT** create custom CSS files or add inline `<style>` tags with hardcoded fonts or ad-hoc colors. Use the pre-built design system tokens already defined in `public/css/style.css`:

### Standard UI Elements to Use:
| Component | How Juniors Should Write It in HTML/PHP |
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

## 🛡️ 7. How Lead (Muhammad) Merges Their Work Safely

When Junior 1 and Junior 2 submit their Pull Requests on GitHub:

1. **Review on GitHub:** Open each PR and inspect `Files Changed`. Verify Junior 1 only touched `models/Asset.php` and `views/assets/`, and Junior 2 only touched `models/Movement.php` and `views/movement/`.
2. **Merge on GitHub:** Click **Merge Pull Request** &rarr; **Confirm Merge**.
3. **Pull Down to Lead PC:**
   ```powershell
   cd C:\Users\mohds\.gemini\antigravity-ide\scratch\msec-smart-stock
   git checkout main
   git pull origin main
   ```
4. **Test Live:** Open `http://localhost:8000` — all modules will now run together in harmony with zero merge conflicts!

---

## 📋 8. Quick Handout Text to Send to Junior 1 & Junior 2

Copy and send this exact message to your 2 colleagues via WhatsApp or Email:

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
     • Handles core architecture, database, security, responsive UI engine, requisitions, procurement, distribution, reports, and final PR merges alone.

4. UI Guideline:
   Use existing CSS classes (.card, .btn-primary, .btn-secondary, .data-table, .form-control, .badge) from public/css/style.css so all pages remain 100% unified and mobile-responsive.

5. When done:
   Push to your feature branch and create a Pull Request on GitHub. Do NOT push directly to main.
```
