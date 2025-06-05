// Dark Mode Handler
class DarkModeManager {
    constructor() {
        this.init();
    }

    init() {
        // Check for saved theme preference or default to light mode
        const savedTheme = localStorage.getItem('theme') || 'light';
        this.setTheme(savedTheme);
        this.createToggleButton();
        this.bindEvents();
    }

    setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        this.updateToggleButton(theme);
    }

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
    }

    createToggleButton() {
        const button = document.createElement('button');
        button.className = 'dark-mode-toggle';
        button.id = 'darkModeToggle';
        button.setAttribute('aria-label', 'Toggle dark mode');
        button.innerHTML = '<i class="bx bx-moon"></i>';
        document.body.appendChild(button);
    }

    updateToggleButton(theme) {
        const button = document.getElementById('darkModeToggle');
        if (button) {
            if (theme === 'dark') {
                button.innerHTML = '<i class="bx bx-sun"></i>';
                button.setAttribute('aria-label', 'Switch to light mode');
            } else {
                button.innerHTML = '<i class="bx bx-moon"></i>';
                button.setAttribute('aria-label', 'Switch to dark mode');
            }
        }
    }

    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('#darkModeToggle')) {
                this.toggleTheme();
            }
        });

        // Keyboard shortcut: Ctrl + Shift + D
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                this.toggleTheme();
            }
        });
    }
}

// Initialize dark mode when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new DarkModeManager();
});