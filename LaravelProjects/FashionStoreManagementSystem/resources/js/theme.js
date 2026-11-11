const STORAGE_KEY = 'theme';

function getStoredTheme() {
    try {
        return localStorage.getItem(STORAGE_KEY);
    } catch {
        return null;
    }
}

function getPreferredTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function currentTheme() {
    return getStoredTheme() || getPreferredTheme();
}

function updateToggleIcon(theme) {
    const button = document.getElementById('themeToggle');
    if (!button) return;
    const icon = button.querySelector('i');
    if (icon) icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    const label = theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
    button.setAttribute('title', label);
    button.setAttribute('aria-label', label);
}

function applyTheme(theme, { persist = true, announce = true } = {}) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    if (persist) {
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch {
            /* ignore */
        }
    }
    updateToggleIcon(theme);
    if (announce) {
        document.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
    }
}

applyTheme(currentTheme(), { persist: false, announce: false });
updateToggleIcon(currentTheme());

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
    if (!getStoredTheme()) {
        applyTheme(event.matches ? 'dark' : 'light', { persist: false });
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('themeToggle');
    if (button) {
        button.addEventListener('click', () => {
            applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
        });
    }
});

window.Theme = { current: currentTheme, apply: applyTheme };
