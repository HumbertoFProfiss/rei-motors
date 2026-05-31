(function () {
    const STORAGE_KEY = 'rei-motors-theme';
    const DARK = 'dark';
    const LIGHT = 'light';

    function getPreferred() {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) return saved;
        return window.matchMedia('(prefers-color-scheme: light)').matches ? LIGHT : DARK;
    }

    function apply(theme) {
        if (theme === LIGHT) {
            document.documentElement.setAttribute('data-theme', 'light');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }

        const btn = document.getElementById('themeToggle');
        if (btn) {
            btn.querySelector('.theme-toggle__icon').textContent = theme === LIGHT ? '🌙' : '☀️';
            btn.setAttribute('aria-label', theme === LIGHT ? 'Ativar modo escuro' : 'Ativar modo claro');
        }

        localStorage.setItem(STORAGE_KEY, theme);
    }

    // Aplicar tema antes do paint para evitar flash
    apply(getPreferred());

    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('themeToggle');
        if (!btn) return;

        // Re-aplicar para atualizar o ícone após o DOM carregar
        apply(getPreferred());

        btn.addEventListener('click', function () {
            const current = localStorage.getItem(STORAGE_KEY) || DARK;
            apply(current === DARK ? LIGHT : DARK);
        });
    });
})();
