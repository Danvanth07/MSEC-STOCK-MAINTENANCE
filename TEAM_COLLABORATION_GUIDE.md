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
git remote add origin https://github.com/<YOUR_GITHUB_USERNAME>/msec-smart-stock.git
git push -u origin main
```

---

### Step 3.2: How Person 1 Sets Up Their PC
Person 1 opens terminal/command prompt on their computer:
```bash
# 1. Clone repository
git clone https://github.com/<YOUR_GITHUB_USERNAME>/msec-smart-stock.git
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
git clone https://github.com/<YOUR_GITHUB_USERNAME>/msec-smart-stock.git
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

### Deliverables & Tasks for Person 1:
1. **Asset Registration Modal Enhancement:**
   - Ensure the modal captures technical hardware specs: Serial Number, MAC Address, IP Address, Processor (CPU), RAM, SSD/HDD, Operating System, Purchase Date, Cost, and AMC details.
2. **Status Management & Filter Tabs:**
   - Test filtering by status pills: `Available`, `In Use`, `Under Repair`, `Condemned`.
3. **Equipment Fault & Repair Reporting:**
   - Test reporting damage on an asset which changes status to `Under Repair` and logs maintenance notes.
4. **How Person 1 Submits Their Work:**
   ```bash
   git add models/Asset.php views/assets/index.php
   git commit -m "feat(assets): completed IT asset tracking and maintenance logging"
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

### Deliverables & Tasks for Person 2:
1. **CSE &rarr; ECE 5 Keyboards Transfer Scenario (Section 13):**
   - Verify the full lifecycle: `Submitted` &rarr; `Approved` &rarr; `Dispatched` &rarr; `In Transit` &rarr; `Acknowledged`.
2. **Stock Balance Synchronization:**
   - Confirm that when the receiving department acknowledges the transfer, the source department's balance decreases and receiving department's balance increases.
3. **Transfer Initiation Modal:**
   - Verify selecting source department, destination department, items, and quantity.
4. **How Person 2 Submits Their Work:**
   ```bash
   git add models/Movement.php views/movement/index.php
   git commit -m "feat(movement): completed inter-department transfer workflow"
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
Hi Team! Here is our project setup for MSEC SMART STOCK:

1. Clone Repo:
   git clone https://github.com/<YOUR_GITHUB_USERNAME>/msec-smart-stock.git
   cd msec-smart-stock

2. Run Locally (Zero configuration! No MySQL required):
   Double-click run.bat (or run: php -S localhost:8000 -t public)
   Open browser at: http://localhost:8000

3. Work Allocation:
   - Colleague 1: Checkout branch 'feature/asset-management'. Work ONLY on models/Asset.php and views/assets/index.php.
   - Colleague 2: Checkout branch 'feature/stock-movement'. Work ONLY on models/Movement.php and views/movement/index.php.
   - Lead (Muhammad) will handle core, routing, database, and remaining modules.

4. UI Guideline:
   Use the existing CSS classes (.card, .btn-primary, .data-table, .form-control) from public/css/style.css so all pages look identical and mobile-responsive.

5. When done:
   Push to your branch and create a Pull Request on GitHub. Do not push directly to main.
```
