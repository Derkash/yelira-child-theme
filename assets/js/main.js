/**
 * Yelira - Main JavaScript
 * @version 4.0.0
 * Luxe E-commerce Experience
 */

(function($) {
    'use strict';

    const YELIRA = {
        // Configuration
        config: {
            headerScrollThreshold: 100,
            animationDuration: 600,
            observerThreshold: 0.1
        },

        /**
         * Initialize all modules
         */
        init: function() {
            this.mobileMenu();
            this.searchBar();
            this.stickyHeader();
            this.backToTop();
            this.ajaxAddToCart();
            this.quantityButtons();
            this.productHover();
            this.smoothScrollLinks();
            this.lazyLoadImages();
            this.animateOnScroll();
        },

        /**
         * Mobile Menu (Burger) with enhanced animations
         */
        mobileMenu: function() {
            const burger = document.getElementById('yelira-burger');
            const nav = document.getElementById('yelira-nav');
            const overlay = document.getElementById('yelira-overlay');

            if (!burger || !nav) return;

            const toggleMenu = (open) => {
                burger.classList.toggle('active', open);
                nav.classList.toggle('active', open);
                if (overlay) overlay.classList.toggle('active', open);
                document.body.style.overflow = open ? 'hidden' : '';

                // Animate menu items
                if (open) {
                    const items = nav.querySelectorAll('.yelira-nav-item');
                    items.forEach((item, index) => {
                        item.style.opacity = '0';
                        item.style.transform = 'translateX(-20px)';
                        setTimeout(() => {
                            item.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                            item.style.opacity = '1';
                            item.style.transform = 'translateX(0)';
                        }, 100 + (index * 50));
                    });
                }
            };

            burger.addEventListener('click', () => {
                const isOpen = !nav.classList.contains('active');
                toggleMenu(isOpen);
            });

            if (overlay) {
                overlay.addEventListener('click', () => toggleMenu(false));
            }

            // Close on link click (mobile)
            const navLinks = nav.querySelectorAll('a');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 768) {
                        toggleMenu(false);
                    }
                });
            });

            // Close on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && nav.classList.contains('active')) {
                    toggleMenu(false);
                }
            });
        },

        /**
         * Search Bar with focus trap
         */
        searchBar: function() {
            const searchToggle = document.getElementById('yelira-search-toggle');
            const searchBar = document.getElementById('yelira-search-bar');
            const searchClose = document.getElementById('yelira-search-close');
            const searchInput = searchBar?.querySelector('input[type="search"]');

            if (!searchToggle || !searchBar) return;

            const openSearch = () => {
                searchBar.classList.add('active');
                if (searchInput) {
                    setTimeout(() => searchInput.focus(), 300);
                }
            };

            const closeSearch = () => {
                searchBar.classList.remove('active');
                if (searchInput) searchInput.blur();
            };

            searchToggle.addEventListener('click', openSearch);

            if (searchClose) {
                searchClose.addEventListener('click', closeSearch);
            }

            // Close with Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && searchBar.classList.contains('active')) {
                    closeSearch();
                }
            });

            // Close on click outside
            searchBar.addEventListener('click', (e) => {
                if (e.target === searchBar) {
                    closeSearch();
                }
            });
        },

        /**
         * Sticky Header with smart show/hide
         */
        stickyHeader: function() {
            const header = document.getElementById('yelira-header');
            const promoBar = document.getElementById('yelira-promo-bar');
            if (!header) return;

            let lastScroll = 0;
            let ticking = false;

            const updateHeader = () => {
                const currentScroll = window.scrollY;

                // Add scrolled class for shadow
                header.classList.toggle('scrolled', currentScroll > 20);

                // Smart hide/show on scroll direction
                if (currentScroll > this.config.headerScrollThreshold) {
                    if (currentScroll > lastScroll && currentScroll > 200) {
                        // Scrolling down - hide promo bar
                        if (promoBar) {
                            header.style.transform = 'translateY(-38px)';
                        }
                    } else {
                        // Scrolling up - show all
                        header.style.transform = 'translateY(0)';
                    }
                } else {
                    header.style.transform = 'translateY(0)';
                }

                lastScroll = currentScroll;
                ticking = false;
            };

            window.addEventListener('scroll', () => {
                if (!ticking) {
                    requestAnimationFrame(updateHeader);
                    ticking = true;
                }
            }, { passive: true });
        },

        /**
         * Back to Top Button with smooth animation
         */
        backToTop: function() {
            // Create button with SVG icon
            const btn = document.createElement('button');
            btn.className = 'back-to-top';
            btn.setAttribute('aria-label', 'Retour en haut');
            btn.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="18 15 12 9 6 15"></polyline>
                </svg>
            `;
            document.body.appendChild(btn);

            let ticking = false;

            const updateVisibility = () => {
                btn.classList.toggle('is-visible', window.scrollY > 500);
                ticking = false;
            };

            window.addEventListener('scroll', () => {
                if (!ticking) {
                    requestAnimationFrame(updateVisibility);
                    ticking = true;
                }
            }, { passive: true });

            btn.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        },

        /**
         * AJAX Add to Cart with elegant notification
         */
        ajaxAddToCart: function() {
            $(document.body).on('added_to_cart', (event, fragments, cart_hash, $button) => {
                this.showNotification('Produit ajouté au panier', 'success');

                // Animate cart icon
                const cartIcon = document.querySelector('.yelira-cart');
                if (cartIcon) {
                    cartIcon.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        cartIcon.style.transform = 'scale(1)';
                    }, 200);
                }
            });

            // Handle adding state
            $(document.body).on('adding_to_cart', ($button, data) => {
                $button.addClass('loading');
            });
        },

        /**
         * Show notification toast
         */
        showNotification: function(message, type = 'success') {
            // Remove existing
            const existing = document.querySelector('.yelira-notification');
            if (existing) existing.remove();

            const icons = {
                success: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>`,
                error: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`,
                info: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`
            };

            const notification = document.createElement('div');
            notification.className = `yelira-notification yelira-notification--${type}`;
            notification.innerHTML = `
                <span class="notification-icon">${icons[type] || icons.info}</span>
                <span class="notification-message">${message}</span>
            `;

            document.body.appendChild(notification);

            // Trigger animation
            requestAnimationFrame(() => {
                notification.classList.add('is-visible');
            });

            // Auto-dismiss
            setTimeout(() => {
                notification.classList.remove('is-visible');
                setTimeout(() => notification.remove(), 400);
            }, 3500);
        },

        /**
         * Quantity Buttons (+/-)
         */
        quantityButtons: function() {
            const wrapInputs = () => {
                document.querySelectorAll('.quantity:not(.qty-wrapped)').forEach(container => {
                    const input = container.querySelector('input.qty');
                    if (!input || container.querySelector('.qty-btn')) return;

                    container.classList.add('qty-wrapped');

                    const minus = document.createElement('button');
                    minus.type = 'button';
                    minus.className = 'qty-btn qty-minus';
                    minus.textContent = '−';
                    minus.setAttribute('aria-label', 'Diminuer la quantité');

                    const plus = document.createElement('button');
                    plus.type = 'button';
                    plus.className = 'qty-btn qty-plus';
                    plus.textContent = '+';
                    plus.setAttribute('aria-label', 'Augmenter la quantité');

                    input.before(minus);
                    input.after(plus);
                });
            };

            // Initial wrap
            wrapInputs();

            // Handle clicks
            $(document).on('click', '.qty-btn', function() {
                const btn = $(this);
                const input = btn.siblings('input.qty');
                const currentVal = parseInt(input.val()) || 1;
                const min = parseInt(input.attr('min')) || 1;
                const max = parseInt(input.attr('max')) || 9999;

                let newVal = currentVal;

                if (btn.hasClass('qty-minus') && currentVal > min) {
                    newVal = currentVal - 1;
                } else if (btn.hasClass('qty-plus') && currentVal < max) {
                    newVal = currentVal + 1;
                }

                if (newVal !== currentVal) {
                    input.val(newVal).trigger('change');
                }
            });

            // Re-wrap after AJAX
            $(document).ajaxComplete(wrapInputs);
        },

        /**
         * Product Image Hover Effect
         */
        productHover: function() {
            const products = document.querySelectorAll('.product');

            products.forEach(product => {
                const primaryImg = product.querySelector('.wp-post-image');
                const secondaryImg = product.querySelector('.secondary-image');

                if (!primaryImg || !secondaryImg) return;

                // Preload secondary image
                const preloadImg = new Image();
                preloadImg.src = secondaryImg.src;

                product.addEventListener('mouseenter', () => {
                    primaryImg.style.opacity = '0';
                    secondaryImg.style.opacity = '1';
                });

                product.addEventListener('mouseleave', () => {
                    primaryImg.style.opacity = '1';
                    secondaryImg.style.opacity = '0';
                });
            });
        },

        /**
         * Smooth Scroll for Anchor Links
         */
        smoothScrollLinks: function() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;

                    const target = document.querySelector(targetId);
                    if (target) {
                        e.preventDefault();
                        const headerOffset = 150;
                        const elementPosition = target.getBoundingClientRect().top;
                        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        },

        /**
         * Lazy Load Images with Intersection Observer
         */
        lazyLoadImages: function() {
            if (!('IntersectionObserver' in window)) return;

            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }
                        if (img.dataset.srcset) {
                            img.srcset = img.dataset.srcset;
                            img.removeAttribute('data-srcset');
                        }
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            document.querySelectorAll('img[data-src], img[data-srcset]').forEach(img => {
                imageObserver.observe(img);
            });
        },

        /**
         * Animate Elements on Scroll
         */
        animateOnScroll: function() {
            if (!('IntersectionObserver' in window)) return;

            const animatedElements = document.querySelectorAll('.yelira-section, .yelira-category-card, .yelira-collection-card');

            if (animatedElements.length === 0) return;

            const animateObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                        animateObserver.unobserve(entry.target);
                    }
                });
            }, {
                threshold: this.config.observerThreshold,
                rootMargin: '0px 0px -50px 0px'
            });

            animatedElements.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = `opacity ${this.config.animationDuration}ms ease, transform ${this.config.animationDuration}ms ease`;
                animateObserver.observe(el);
            });
        }
    };

    /**
     * Initialize on DOM Ready
     */
    $(document).ready(() => {
        YELIRA.init();
    });

    /**
     * Re-initialize after AJAX updates
     */
    $(document).ajaxComplete(() => {
        YELIRA.productHover();
        YELIRA.lazyLoadImages();
    });

    /**
     * Expose YELIRA globally for debugging
     */
    window.YELIRA = YELIRA;

})(jQuery);
