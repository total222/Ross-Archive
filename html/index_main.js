 document.addEventListener('scroll', function() {
            const bioSection = document.querySelector('.brief_biography');
            const bioImg = document.querySelector('.brief_bio_img');
            if (!bioSection || !bioImg) return;

            const rect = bioSection.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            // Solo aplicar si la sección está visible
            if (rect.top < windowHeight && rect.bottom > 0) {
                // Calcular el porcentaje de scroll dentro de la sección
                const scrollPercent = Math.min(Math.max((windowHeight - rect.top) / (rect.height + windowHeight), 0), 1);
                // Parallax solo para la imagen
                // La imagen se mueve hacia arriba y escala, cubriendo explícitamente el texto
                const translateY = scrollPercent * -180; // mucho más movimiento
                const scale = 1.2 + scrollPercent * 0.20; // mucho más escala
                bioImg.style.transform = `translateY(${translateY}px) scale(${scale})`;
            } else {
                bioImg.style.transform = '';
            }
        });

    // Hover dinámico para blog_link y foro_link que afecta blog_hero y foro_hero
    document.addEventListener('DOMContentLoaded', function() {
        const blogLink = document.querySelector('.blog_link');
        const blogHero = document.querySelector('.blog_hero');
        const foroLink = document.querySelector('.foro_link');
        const foroHero = document.querySelector('.foro_hero');

        if (blogLink && blogHero) {
            blogLink.addEventListener('mouseenter', function() {
                blogHero.style.backgroundColor = 'black';
                blogHero.style.color = 'white';
            });
            blogLink.addEventListener('mouseleave', function() {
                blogHero.style.backgroundColor = '';
                blogHero.style.color = '';
            });
        }
        if (foroLink && foroHero) {
            foroLink.addEventListener('mouseenter', function() {
                foroHero.style.backgroundColor = 'black';
                foroHero.style.color = 'white';
            });
            foroLink.addEventListener('mouseleave', function() {
                foroHero.style.backgroundColor = '';
                foroHero.style.color = '';
            });
        }
    });

    // Toggle menú hamburguesa para móvil
    document.addEventListener('DOMContentLoaded', function() {
        const hamburgerButton = document.querySelector('.hamburger');
        const overlay = document.getElementById('mobile-menu');
        const overlayLinks = overlay ? overlay.querySelectorAll('a') : [];
        const body = document.body;

        if (!hamburgerButton || !overlay) return;

        const closeMenu = () => {
            hamburgerButton.classList.remove('is-active');
            overlay.classList.remove('open');
            overlay.setAttribute('hidden', '');
            hamburgerButton.setAttribute('aria-expanded', 'false');
            body.style.overflow = '';
        };

        const openMenu = () => {
            overlay.removeAttribute('hidden');
            // Forzar reflow para que la transición ocurra al añadir .open
            // eslint-disable-next-line @typescript-eslint/no-unused-expressions
            overlay.offsetHeight;
            overlay.classList.add('open');
            hamburgerButton.classList.add('is-active');
            hamburgerButton.setAttribute('aria-expanded', 'true');
            body.style.overflow = 'hidden';
        };

        hamburgerButton.addEventListener('click', () => {
            const isOpen = overlay.classList.contains('open');
            if (isOpen) closeMenu(); else openMenu();
        });

        // Cerrar al hacer click en un enlace
        overlayLinks.forEach(link => link.addEventListener('click', closeMenu));

        // Cerrar con Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeMenu();
        });
    });