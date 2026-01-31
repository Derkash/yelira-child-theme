/**
 * Yelira - Main JavaScript
 * @version 2.0.0
 */

(function($) {
    'use strict';

    /**
     * Configuration
     */
    const YELIRA = {
        init: function() {
            this.promoBar();
            this.backToTop();
            this.productHover();
            this.ajaxAddToCart();
            this.quantityButtons();
            this.stickyHeader();
        },

        /**
         * Bandeau promo défilant
         */
        promoBar: function() {
            const promoBar = document.querySelector('.yelira-promo-bar');
            if (!promoBar) return;

            // Pause on hover
            const inner = promoBar.querySelector('.yelira-promo-bar-inner');
            if (inner) {
                promoBar.addEventListener('mouseenter', () => {
                    inner.style.animationPlayState = 'paused';
                });
                promoBar.addEventListener('mouseleave', () => {
                    inner.style.animationPlayState = 'running';
                });
            }
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

            // Afficher/masquer le bouton
            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) {
                    btn.classList.add('is-visible');
                } else {
                    btn.classList.remove('is-visible');
                }
            });

            // Scroll vers le haut
            btn.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
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
            // Supprimer les notifications existantes
            const existing = document.querySelector('.yelira-notification');
            if (existing) existing.remove();

            // Créer la notification
            const notification = document.createElement('div');
            notification.className = 'yelira-notification';
            notification.innerHTML = `
                <span class="notification-icon">✓</span>
                <span class="notification-message">${message}</span>
            `;
            document.body.appendChild(notification);

            // Afficher avec animation
            setTimeout(() => notification.classList.add('is-visible'), 10);

            // Masquer après 3 secondes
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
         * Header sticky
         */
        stickyHeader: function() {
            const header = document.querySelector('.site-header');
            if (!header) return;

            let lastScroll = 0;
            const headerHeight = header.offsetHeight;

            window.addEventListener('scroll', () => {
                const currentScroll = window.scrollY;

                if (currentScroll > headerHeight) {
                    header.classList.add('is-sticky');

                    if (currentScroll > lastScroll) {
                        // Scroll vers le bas
                        header.classList.add('is-hidden');
                    } else {
                        // Scroll vers le haut
                        header.classList.remove('is-hidden');
                    }
                } else {
                    header.classList.remove('is-sticky', 'is-hidden');
                }

                lastScroll = currentScroll;
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
     * Réinitialiser après AJAX (ex: filtres de produits)
     */
    $(document).ajaxComplete(function() {
        YELIRA.productHover();
        YELIRA.quantityButtons();
    });

})(jQuery);
