document.addEventListener('DOMContentLoaded', function () {

    // ===== HEADER SCROLL =====
    // Adiciona sombra no header ao rolar a página
    const header = document.querySelector('.header');
    if (header) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 50) {
                header.style.boxShadow = '0 2px 20px rgba(0,0,0,0.3)';
            } else {
                header.style.boxShadow = 'none';
            }
        }, { passive: true });
    }

    // ===== SMOOTH SCROLL para links âncora =====
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;

            const alvo = document.querySelector(href);
            if (!alvo) return;

            e.preventDefault();

            const headerHeight = header ? header.offsetHeight : 0;
            const topo = alvo.getBoundingClientRect().top + window.scrollY - headerHeight - 16;

            window.scrollTo({ top: topo, behavior: 'smooth' });
        });
    });

    // ===== ANIMAÇÃO DE ENTRADA (Intersection Observer) =====
    const observerConfig = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, observerConfig);

    // Animar cards e seções ao entrar na tela
    document.querySelectorAll('.card__veiculo, .diferencial__card, .sobre__content').forEach(function (el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });

    // ===== LAZY LOADING de imagens =====
    if ('loading' in HTMLImageElement.prototype) {
        document.querySelectorAll('img:not([loading])').forEach(function (img) {
            img.setAttribute('loading', 'lazy');
        });
    }

    // ===== HERO CAROUSEL =====
    var slides = document.querySelectorAll('.hero__slide');
    if (slides.length > 1) {
        var atual = 0;
        setInterval(function () {
            slides[atual].classList.remove('ativo');
            atual = (atual + 1) % slides.length;
            slides[atual].classList.add('ativo');
        }, 5000);
    }

    // ===== FEEDBACK VISUAL em links WhatsApp =====
    document.querySelectorAll('a[href*="wa.me"]').forEach(function (link) {
        link.addEventListener('click', function () {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => { this.style.transform = ''; }, 200);
        });
    });

});
