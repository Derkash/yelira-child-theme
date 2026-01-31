/**
 * Yelira - Main JavaScript
 * @version 3.0.0
 * Style Neyssa Shop
 */

(function($) {
    'use strict';

    const YELIRA = {
        init: function() {
            this.mobileMenu();
            this.searchBar();
            this.stickyHeader();
            this.backToTop();
            this.ajaxAddToCart();
            this.quantityButtons();
            this.productHover();
        },

        /**
         * Menu Mobile (Burger)
         */
        mobileMenu: function() {
            const burger = document.getElementById('yelira-burger');
            const nav = document.getElementById('yelira-nav');
            const overlay = document.getElementById('yelira-overlay');

            if (!burger || !nav) return;

            burger.addEventListener('click', () => {
                burger.classList.toggle('active');
                nav.classList.toggle('active');
                overlay.classList.toggle('active');
                document.body.style.overflow = nav.classList.contains('active') ? 'hidden' : '';
            });

            overlay.addEventListener('click', () => {
                burger.classList.remove('active');
                nav.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            });

            // Fermer le menu quand on clique sur un lien
            const navLinks = nav.querySelectorAll('a');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 768) {
                        burger.classList.remove('active');
                        nav.classList.remove('active');
                        overlay.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            });
        },

        /**
         * Barre de recherche
         */
        searchBar: function() {
            const searchToggle = document.getElementById('yelira-search-toggle');
            const searchBar = document.getElementById('yelira-search-bar');
            const searchClose = document.getElementById('yelira-search-close');

            if (!searchToggle || !searchBar) return;

            searchToggle.addEventListener('click', () => {
                searchBar.classList.add('active');
                const input = searchBar.querySelector('input[type="search"]');
                if (input) input.focus();
            });

            if (searchClose) {
                searchClose.addEventListener('click', () => {
                    searchBar.classList.remove('active');
                });
            }

            // Fermer avec Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && searchBar.classList.contains('active')) {
                    searchBar.classList.remove('active');
                }
            });
        },

        /**
         * Header sticky avec effet show/hide au scroll
         */
        stickyHeader: function() {
            const header = document.getElementById('yelira-header');
            if (!header) return;

            let lastScroll = 0;

            window.addEventListener('scroll', () => {
                const currentScroll = window.scrollY;

                if (currentScroll > 150) {
                    if (currentScroll > lastScroll) {
                        // Scroll down - hide promo bar effet
                        header.style.transform = 'translateY(-40px)';
                    } else {
                        // Scroll up - show all
                        header.style.transform = 'translateY(0)';
                    }
                } else {
                    header.style.transform = 'translateY(0)';
                }

                lastScroll = currentScroll;
            });
        },

        /**
         * Bouton retour en haut
         */
        backToTop: function() {
            const btn = document.createElement('button');
            btn.className = 'back-to-top';
            btn.innerHTML = '↑';
            btn.setAttribute('aria-label', 'Retour en haut');
            document.body.appendChild(btn);

            window.addEventListener('scroll', () => {
                if (window.scrollY > 500) {
                    btn.classList.add('is-visible');
                } else {
                    btn.classList.remove('is-visible');
                }
            });

            btn.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        },

        /**
         * Notification après ajout au panier AJAX
         */
        ajaxAddToCart: function() {
            $(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {
                YELIRA.showNotification('Produit ajouté au panier !', 'success');
            });
        },

        /**
         * Afficher une notification
         */
        showNotification: function(message, type = 'success') {
            const existing = document.querySelector('.yelira-notification');
            if (existing) existing.remove();

            const notification = document.createElement('div');
            notification.className = 'yelira-notification';
            notification.innerHTML = `
                <span class="notification-icon">✓</span>
                <span class="notification-message">${message}</span>
            `;
            document.body.appendChild(notification);

            setTimeout(() => notification.classList.add('is-visible'), 10);

            setTimeout(() => {
                notification.classList.remove('is-visible');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        },

        /**
         * Boutons +/- pour la quantité
         */
        quantityButtons: function() {
            $(document).on('click', '.qty-btn', function() {
                const btn = $(this);
                const input = btn.siblings('input.qty');
                const currentVal = parseInt(input.val()) || 1;
                const min = parseInt(input.attr('min')) || 1;
                const max = parseInt(input.attr('max')) || 999;

                if (btn.hasClass('qty-minus')) {
                    if (currentVal > min) {
                        input.val(currentVal - 1).trigger('change');
                    }
                } else if (btn.hasClass('qty-plus')) {
                    if (currentVal < max) {
                        input.val(currentVal + 1).trigger('change');
                    }
                }
            });

            // Wrapper les inputs quantité avec les boutons
            $('.quantity').each(function() {
                const $this = $(this);
                if ($this.find('.qty-btn').length === 0) {
                    $this.find('input.qty').before('<button type="button" class="qty-btn qty-minus">−</button>');
                    $this.find('input.qty').after('<button type="button" class="qty-btn qty-plus">+</button>');
                }
            });
        },

        /**
         * Effet hover sur les produits (image secondaire)
         */
        productHover: function() {
            const products = document.querySelectorAll('.product');

            products.forEach(product => {
                const primaryImg = product.querySelector('.wp-post-image');
                const secondaryImg = product.querySelector('.secondary-image');

                if (primaryImg && secondaryImg) {
                    product.addEventListener('mouseenter', () => {
                        primaryImg.style.opacity = '0';
                        secondaryImg.style.opacity = '1';
                    });

                    product.addEventListener('mouseleave', () => {
                        primaryImg.style.opacity = '1';
                        secondaryImg.style.opacity = '0';
                    });
                }
            });
        }
    };

    /**
     * Initialisation au chargement du DOM
     */
    $(document).ready(function() {
        YELIRA.init();
    });

    /**
     * Réinitialiser après AJAX
     */
    $(document).ajaxComplete(function() {
        YELIRA.productHover();
        YELIRA.quantityButtons();
    });

})(jQuery);
