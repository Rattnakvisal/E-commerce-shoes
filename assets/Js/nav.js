// DOM Elements
const mobileMenuButton = document.getElementById('mobileMenuButton');
const closeMobileMenu = document.getElementById('closeMobileMenu');
const mobileSidebar = document.getElementById('mobileSidebar');
const mobileOverlay = document.getElementById('mobileOverlay');
const mobileSearchButton = document.getElementById('mobileSearchButton');
const mobileSearchBar = document.getElementById('mobileSearchBar');

// Dropdown Elements
const notificationsButton = document.getElementById('notificationsButton');
const notificationsDropdown = document.getElementById('notificationsDropdown');
const messagesButton = document.getElementById('messagesButton');
const messagesDropdown = document.getElementById('messagesDropdown');
const quickAddButton = document.getElementById('quickAddButton');
const quickAddDropdown = document.getElementById('quickAddDropdown');
const adminDropdownButton = document.getElementById('adminDropdownButton');
const adminDropdownMenu = document.getElementById('adminDropdownMenu');

// Close all dropdowns
function closeAllDropdowns() {
    if (notificationsDropdown && notificationsDropdown.classList) notificationsDropdown.classList.add('hidden');
    if (messagesDropdown && messagesDropdown.classList) messagesDropdown.classList.add('hidden');
    if (quickAddDropdown && quickAddDropdown.classList) quickAddDropdown.classList.add('hidden');
    if (adminDropdownMenu && adminDropdownMenu.classList) adminDropdownMenu.classList.add('hidden');
}

// Toggle mobile menu (guarded)
if (mobileMenuButton && mobileSidebar && mobileOverlay) {
    mobileMenuButton.addEventListener('click', () => {
        mobileSidebar.classList.remove('-translate-x-full');
        mobileOverlay.classList.remove('hidden');
    });

    if (closeMobileMenu) {
        closeMobileMenu.addEventListener('click', () => {
            mobileSidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
        });
    }

    mobileOverlay.addEventListener('click', () => {
        mobileSidebar.classList.add('-translate-x-full');
        mobileOverlay.classList.add('hidden');
    });
}

// Toggle mobile search (guarded)
if (mobileSearchButton && mobileSearchBar) {
    mobileSearchButton.addEventListener('click', () => {
        mobileSearchBar.classList.toggle('hidden');
    });
}

// Dropdown toggles (guarded)
if (notificationsButton && notificationsDropdown) {
    notificationsButton.addEventListener('click', (e) => {
        e.stopPropagation();
        const isHidden = notificationsDropdown.classList.contains('hidden');
        closeAllDropdowns();
        if (isHidden) {
            notificationsDropdown.classList.remove('hidden');
        }
    });
}

if (messagesButton && messagesDropdown) {
    messagesButton.addEventListener('click', (e) => {
        e.stopPropagation();
        const isHidden = messagesDropdown.classList.contains('hidden');
        closeAllDropdowns();
        if (isHidden) {
            messagesDropdown.classList.remove('hidden');
        }
    });
}

if (quickAddButton && quickAddDropdown) {
    quickAddButton.addEventListener('click', (e) => {
        e.stopPropagation();
        const isHidden = quickAddDropdown.classList.contains('hidden');
        closeAllDropdowns();
        if (isHidden) {
            quickAddDropdown.classList.remove('hidden');
        }
    });
}

if (adminDropdownButton && adminDropdownMenu) {
    adminDropdownButton.addEventListener('click', (e) => {
        e.stopPropagation();
        const isHidden = adminDropdownMenu.classList.contains('hidden');
        closeAllDropdowns();
        if (isHidden) {
            adminDropdownMenu.classList.remove('hidden');
        }
    });
}

// Close dropdowns when clicking outside
document.addEventListener('click', (e) => {
    if (notificationsButton && notificationsDropdown) {
        if (!notificationsButton.contains(e.target) && !notificationsDropdown.contains(e.target)) {
            notificationsDropdown.classList.add('hidden');
        }
    }
    if (messagesButton && messagesDropdown) {
        if (!messagesButton.contains(e.target) && !messagesDropdown.contains(e.target)) {
            messagesDropdown.classList.add('hidden');
        }
    }
    if (quickAddButton && quickAddDropdown) {
        if (!quickAddButton.contains(e.target) && !quickAddDropdown.contains(e.target)) {
            quickAddDropdown.classList.add('hidden');
        }
    }
    if (adminDropdownButton && adminDropdownMenu) {
        if (!adminDropdownButton.contains(e.target) && !adminDropdownMenu.contains(e.target)) {
            adminDropdownMenu.classList.add('hidden');
        }
    }
});

// Toggle dark mode (example)
function toggleDarkMode() {
    document.body.classList.toggle('dark');
    const isDark = document.body.classList.contains('dark');
    localStorage.setItem('darkMode', isDark);

    // Show toast notification
    showToast(isDark ? 'Dark mode enabled' : 'Light mode enabled');
}

// Toast notification function
function showToast(message, type = 'success') {
    // Create toast if it doesn't exist
    let toast = document.getElementById('custom-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'custom-toast';
        toast.className = `fixed bottom-4 right-4 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white px-6 py-3 rounded-lg shadow-xl transform transition-all duration-300 translate-y-full`;
        document.body.appendChild(toast);
    }

    toast.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} mr-2"></i>
            <span>${message}</span>
        </div>
    `;

    toast.classList.remove('translate-y-full');

    setTimeout(() => {
        toast.classList.add('translate-y-full');
    }, 3000);
}

// Initialize dark mode from localStorage
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark');
}

// Close dropdowns on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeAllDropdowns();
    }
});
