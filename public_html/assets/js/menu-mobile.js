document.addEventListener('DOMContentLoaded', function () {
    const hamburger = document.getElementById('hamburger');
    const nav = document.querySelector('.header__nav');
    const links = document.querySelectorAll('.nav__link');

    if (!hamburger || !nav) return;

    function fecharMenu() {
        hamburger.classList.remove('active');
        nav.classList.remove('active');
        hamburger.setAttribute('aria-expanded', 'false');
    }

    function abrirMenu() {
        hamburger.classList.add('active');
        nav.classList.add('active');
        hamburger.setAttribute('aria-expanded', 'true');
    }

    hamburger.setAttribute('aria-expanded', 'false');
    hamburger.setAttribute('aria-controls', 'nav-menu');

    hamburger.addEventListener('click', function () {
        const isOpen = nav.classList.contains('active');
        isOpen ? fecharMenu() : abrirMenu();
    });

    // Fechar ao clicar em um link
    links.forEach(function (link) {
        link.addEventListener('click', fecharMenu);
    });

    // Fechar ao clicar fora do menu
    document.addEventListener('click', function (e) {
        if (!hamburger.contains(e.target) && !nav.contains(e.target)) {
            fecharMenu();
        }
    });

    // Fechar ao redimensionar para desktop
    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            fecharMenu();
        }
    });
});
