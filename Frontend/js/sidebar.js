/**
 * Sidebar.js - Minimalist Collapsible & Mobile Drawer Navigation
 * Handles desktop collapse/expand, localStorage persistence, and mobile drawer interactions.
 */

(function () {
    'use strict';

    const STORAGE_KEY = 'sidebar_collapsed';

    function initSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        const toggleBtn = document.getElementById('sidebar-toggle-btn');
        const closeBtn = document.getElementById('sidebar-close-btn');
        const backdrop = document.getElementById('sidebar-backdrop');

        // 1. Restore desktop collapse state from localStorage
        const isCollapsed = localStorage.getItem(STORAGE_KEY) === 'true';
        if (window.innerWidth > 1024 && isCollapsed) {
            setCollapsed(sidebar, true, false);
        } else {
            setCollapsed(sidebar, false, false);
        }

        // 2. Desktop Toggle Click
        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const currentlyCollapsed = sidebar.classList.contains('collapsed');
                const nextState = !currentlyCollapsed;
                setCollapsed(sidebar, nextState, true);
            });
        }

        // 3. Mobile Close Click
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                closeMobileDrawer(sidebar, backdrop);
            });
        }

        // 4. Backdrop Click
        if (backdrop) {
            backdrop.addEventListener('click', () => {
                closeMobileDrawer(sidebar, backdrop);
            });
        }

        // 5. ESC Key Closes Mobile Drawer
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
                closeMobileDrawer(sidebar, backdrop);
            }
        });

        // 6. Inject Mobile Menu Button into Header if not already present
        ensureMobileTrigger(sidebar, backdrop);

        // 7. Auto close mobile drawer on window resize to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024 && sidebar.classList.contains('mobile-open')) {
                closeMobileDrawer(sidebar, backdrop);
            }
        });
    }

    /**
     * Set sidebar collapsed/expanded state
     */
    function setCollapsed(sidebar, collapsed, saveStorage) {
        if (collapsed) {
            sidebar.classList.add('collapsed');
            document.documentElement.classList.add('sidebar-is-collapsed');
        } else {
            sidebar.classList.remove('collapsed');
            document.documentElement.classList.remove('sidebar-is-collapsed');
        }

        const toggleBtn = document.getElementById('sidebar-toggle-btn');
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            const icon = toggleBtn.querySelector('.material-symbols-outlined');
            if (icon) {
                icon.textContent = collapsed ? 'chevron_right' : 'menu_open';
            }
        }

        if (saveStorage) {
            localStorage.setItem(STORAGE_KEY, collapsed ? 'true' : 'false');
        }

        // Kích hoạt lại resize để Chart.js tự căn chỉnh kích thước biểu đồ mượt mà
        setTimeout(() => {
            window.dispatchEvent(new Event('resize'));
        }, 220);
    }

    /**
     * Open mobile drawer
     */
    function openMobileDrawer(sidebar, backdrop) {
        sidebar.classList.add('mobile-open');
        if (backdrop) backdrop.classList.add('active');
        document.body.classList.add('sidebar-drawer-active');
    }

    /**
     * Close mobile drawer
     */
    function closeMobileDrawer(sidebar, backdrop) {
        sidebar.classList.remove('mobile-open');
        if (backdrop) backdrop.classList.remove('active');
        document.body.classList.remove('sidebar-drawer-active');
    }

    /**
     * Ensure a mobile menu hamburger button exists in the current page header
     */
    function ensureMobileTrigger(sidebar, backdrop) {
        if (document.querySelector('.sidebar-mobile-trigger')) return;

        const headerLeft = document.querySelector('.header-left') || 
                           document.querySelector('.notif-page-header') ||
                           document.querySelector('.header');

        if (!headerLeft) return;

        const triggerBtn = document.createElement('button');
        triggerBtn.type = 'button';
        triggerBtn.className = 'sidebar-mobile-trigger';
        triggerBtn.setAttribute('aria-label', 'Mở menu điều hướng');
        triggerBtn.title = 'Mở menu';
        triggerBtn.innerHTML = '<span class="material-symbols-outlined">menu</span>';

        triggerBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openMobileDrawer(sidebar, backdrop);
        });

        headerLeft.insertBefore(triggerBtn, headerLeft.firstChild);
    }

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebar);
    } else {
        initSidebar();
    }
})();
