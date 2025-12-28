document.addEventListener('DOMContentLoaded', () => {
    const dropdown = document.getElementById('notificationsDropdown');
    const notifBtn = document.getElementById('notificationsButton');
    const badge = document.querySelector('.badge-count');
    const list = document.getElementById('notificationsList');
    const viewAll = document.getElementById('viewAllNotifications');
    const markAll = document.getElementById('markAllReadBtn');
    const clearAllBtn = document.getElementById('clearAllNotifsBtn');

    // Ensure API path includes project folder when hosted under a subpath
    const API = '/E-commerce-shoes/admin/process/notifications_api.php';

    if (!dropdown || !notifBtn || !list) return;

    /* ----------------------------------------
      Helpers
      ------------------------------------- */

    const hideBadge = () => {
        if (badge) badge.style.display = 'none';
    };

    const updateBadge = (delta = 0) => {
        if (!badge || !badge.textContent) return;

        let count = parseInt(badge.textContent, 10) || 0;
        count = Math.max(0, count + delta);

        if (count === 0) {
            hideBadge();
        } else {
            badge.style.display = 'flex';
            badge.textContent = count > 99 ? '99+' : String(count);
        }
    };

    const clearUI = () => {
        hideBadge();
        list.innerHTML =
            '<p class="py-6 text-center text-sm text-gray-500">No notifications</p>';
    };

    const post = async (url) => {
        try {
            const res = await fetch(url, { method: 'POST', credentials: 'same-origin' });
            return res;
        } catch (err) {
            console.error(url, err);
            return null;
        }
    };

    /* ----------------------------------------
     Dropdown Toggle
      ------------------------------------- */

    notifBtn.addEventListener('click', async (e) => {
        e.stopPropagation();
        dropdown.classList.toggle('hidden');

        if (!dropdown.classList.contains('hidden')) {
            await post(`${API}?action=mark_all_read`);
            hideBadge();

            list.querySelectorAll('.notif-item').forEach(el =>
                el.classList.remove('bg-indigo-50')
            );
        }
    });

    /* ----------------------------------------
      Mark All / View All / Clear All
     ------------------------------------- */

    markAll?.addEventListener('click', async () => {
        await post(`${API}?action=mark_all_read`);
        clearUI();
    });

    viewAll?.addEventListener('click', async (e) => {
        e.preventDefault();
        const res = await post(`${API}?action=mark_all_read`);
        if (res && (res.ok || res.status === 204)) {
            // Navigate after server-side marks all read so count won't reappear
            window.location.href = viewAll.href;
        } else {
            // fallback: still navigate but warn in console
            console.warn('Failed to mark notifications read before navigation', res);
            window.location.href = viewAll.href;
        }
    });

    clearAllBtn?.addEventListener('click', async (e) => {
        e.preventDefault();
        await post(`${API}?action=delete_all`);
        clearUI();
    });

    /* ----------------------------------------
     Notification List (Event Delegation)
    / * ------------------------------------- */

    list.addEventListener('click', async (e) => {
        const clearBtn = e.target.closest('.notif-clear');
        const item = e.target.closest('.notif-item');

        // Delete notification
        if (clearBtn) {
            e.preventDefault();
            const id = clearBtn.dataset.id;
            if (!id) return;

            await post(`${API}?action=delete&id=${encodeURIComponent(id)}`);
            clearBtn.closest('.notif-row')?.remove();
            updateBadge(-1);
        }

        // Mark single notification as read
        if (item && !clearBtn) {
            e.preventDefault();
            const id = item.dataset.id;
            if (!id) return;

            await post(`${API}?action=mark_read&id=${encodeURIComponent(id)}`);
            item.closest('.notif-row')?.remove();
            updateBadge(-1);
        }

        if (list.querySelectorAll('.notif-item').length === 0) {
            clearUI();
        }
    });

    /* ----------------------------------------
      Close on Outside Click
     ------------------------------------- */

    document.addEventListener('click', () => {
        dropdown.classList.add('hidden');
    });
});
