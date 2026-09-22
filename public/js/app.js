/**
 * MSEC SMART STOCK - Dynamic Portal Scripts
 * Responsive Mobile Navigation, Table Search Filters & Modal Controls
 */

document.addEventListener('DOMContentLoaded', () => {
    // Quick search filter for data tables
    const searchInputs = document.querySelectorAll('.table-search-input');
    searchInputs.forEach(input => {
        input.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const targetTableId = e.target.getAttribute('data-table');
            const table = document.getElementById(targetTableId);
            if (!table) return;

            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });

    // Auto-dismiss alert notifications after 5 seconds
    const alerts = document.querySelectorAll('.alert-auto-dismiss');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });

    // Close modal or drawer on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeSidebar();
            document.querySelectorAll('.modal-backdrop').forEach(modal => {
                modal.style.display = 'none';
            });
        }
    });

    // Auto-close sidebar on window resize to desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1024) {
            closeSidebar();
        }
    });
});

// Mobile Sidebar Drawer Controls
function toggleSidebar() {
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar) return;

    if (sidebar.classList.contains('open')) {
        closeSidebar();
    } else {
        openSidebar();
    }
}

function openSidebar() {
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (sidebar) sidebar.classList.add('open');
    if (backdrop) backdrop.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (sidebar) sidebar.classList.remove('open');
    if (backdrop) backdrop.classList.remove('active');
    document.body.style.overflow = '';
}

// Modal toggle helpers with backdrop click
function openModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) {
        el.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) {
        el.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Global modal backdrop click-to-close
window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.style.display = 'none';
        document.body.style.overflow = '';
    }
});
