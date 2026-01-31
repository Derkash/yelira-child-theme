<?php
/**
 * Yelira Child Theme - Functions
 *
 * @package Yelira
 * @version 2.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Définir les constantes du thème
 */
define('YELIRA_VERSION', '2.0.0');
define('YELIRA_DIR', get_stylesheet_directory());
define('YELIRA_URI', get_stylesheet_directory_uri());

/**
 * Charger les styles du thème parent et enfant
 */
function yelira_enqueue_styles() {
    // Style du thème parent
    wp_enqueue_style(
        'blocksy-style',
        get_template_directory_uri() . '/style.css',
        array(),
        YELIRA_VERSION
    );

    // Style du thème enfant
    wp_enqueue_style(
        'yelira-style',
        get_stylesheet_uri(),
        array('blocksy-style'),
        YELIRA_VERSION
    );

    // Google Fonts - Playfair Display + DM Sans
    wp_enqueue_style(
        'yelira-fonts',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap',
        array(),
        null
    );
}
add_action('wp_enqueue_scripts', 'yelira_enqueue_styles');

/**
 * Charger les scripts du thème
 */
function yelira_enqueue_scripts() {
    wp_enqueue_script(
        'yelira-scripts',
        YELIRA_URI . '/assets/js/main.js',
        array('jquery'),
        YELIRA_VERSION,
        true
    );

    // Localize script pour AJAX
    wp_localize_script('yelira-scripts', 'yeliraAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('yelira_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'yelira_enqueue_scripts');

/**
 * Ajouter le support des fonctionnalités
 */
function yelira_theme_setup() {
    // Support WooCommerce
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

    // Images personnalisées
    add_image_size('yelira-product-card', 400, 600, true); // Ratio 2:3
    add_image_size('yelira-product-large', 800, 1200, true);
    add_image_size('yelira-category', 600, 400, true);
}
add_action('after_setup_theme', 'yelira_theme_setup');

/**
 * ============================================================================
 * WOOCOMMERCE - PERSONNALISATION DES CARTES PRODUITS
 * ============================================================================
 */

/**
 * Afficher la catégorie du produit avant le titre
 */
function yelira_show_product_category() {
    global $product;
    $categories = get_the_terms($product->get_id(), 'product_cat');

    if ($categories && !is_wp_error($categories)) {
        $category = $categories[0];
        echo '<span class="yelira-product-category">' . esc_html($category->name) . '</span>';
    }
}
add_action('woocommerce_before_shop_loop_item_title', 'yelira_show_product_category', 15);

/**
 * Afficher le SKU du produit
 */
function yelira_show_product_sku() {
    global $product;
    $sku = $product->get_sku();

    if ($sku) {
        echo '<span class="yelira-product-sku">Réf: ' . esc_html($sku) . '</span>';
    }
}
add_action('woocommerce_after_shop_loop_item_title', 'yelira_show_product_sku', 5);

/**
 * Afficher la description courte du produit
 */
function yelira_show_product_excerpt() {
    global $product;
    $excerpt = $product->get_short_description();

    if ($excerpt) {
        echo '<div class="yelira-product-excerpt">' . wp_trim_words($excerpt, 15) . '</div>';
    }
}
add_action('woocommerce_after_shop_loop_item_title', 'yelira_show_product_excerpt', 15);

/**
 * ============================================================================
 * WOOCOMMERCE - BADGES PERSONNALISÉS
 * ============================================================================
 */

/**
 * Ajouter des badges personnalisés (Nouveau, Stock faible)
 */
function yelira_custom_badges() {
    global $product;

    echo '<div class="yelira-badges">';

    // Badge "Nouveau" (produits de moins de 30 jours)
    $post_date = get_the_date('Y-m-d', $product->get_id());
    $days_ago = (time() - strtotime($post_date)) / DAY_IN_SECONDS;

    if ($days_ago < 30) {
        echo '<span class="badge-new">Nouveau</span>';
    }

    // Badge "Soldes" (si en promotion)
    if ($product->is_on_sale()) {
        $regular_price = (float) $product->get_regular_price();
        $sale_price = (float) $product->get_sale_price();

        if ($regular_price > 0) {
            $percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
            echo '<span class="badge-sale">-' . $percentage . '%</span>';
        }
    }

    // Badge "Derniers articles" (stock faible)
    if ($product->managing_stock() && $product->get_stock_quantity() <= 5 && $product->get_stock_quantity() > 0) {
        echo '<span class="badge-low-stock">Derniers articles</span>';
    }

    echo '</div>';
}
add_action('woocommerce_before_shop_loop_item_title', 'yelira_custom_badges', 5);

/**
 * Supprimer le badge "Promo" par défaut de WooCommerce
 */
remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10);

/**
 * ============================================================================
 * WOOCOMMERCE - IMAGE SECONDAIRE AU HOVER
 * ============================================================================
 */

/**
 * Ajouter l'image secondaire pour le hover
 */
function yelira_secondary_product_image() {
    global $product;

    $attachment_ids = $product->get_gallery_image_ids();

    if ($attachment_ids && isset($attachment_ids[0])) {
        echo wp_get_attachment_image(
            $attachment_ids[0],
            'woocommerce_thumbnail',
            false,
            array('class' => 'secondary-image')
        );
    }
}
add_action('woocommerce_before_shop_loop_item_title', 'yelira_secondary_product_image', 11);

/**
 * ============================================================================
 * WOOCOMMERCE - PERSONNALISATION PANIER
 * ============================================================================
 */

/**
 * Changer le texte du bouton "Ajouter au panier"
 */
function yelira_add_to_cart_text($text, $product) {
    if ($product->is_type('simple')) {
        return 'Ajouter au panier';
    }
    if ($product->is_type('variable')) {
        return 'Voir les options';
    }
    return $text;
}
add_filter('woocommerce_product_add_to_cart_text', 'yelira_add_to_cart_text', 10, 2);

/**
 * Notification AJAX après ajout au panier
 */
function yelira_add_to_cart_fragments($fragments) {
    ob_start();
    ?>
    <span class="yelira-cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
    <?php
    $fragments['.yelira-cart-count'] = ob_get_clean();

    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'yelira_add_to_cart_fragments');

/**
 * ============================================================================
 * BANDEAU PROMO DÉFILANT
 * ============================================================================
 */

/**
 * Afficher le bandeau promo
 */
function yelira_promo_bar() {
    $messages = array(
        'Livraison OFFERTE dès 49€ d\'achat',
        'Paiement sécurisé 100%',
        'Retours gratuits sous 30 jours',
        'Service client disponible 7j/7'
    );

    // Permettre la personnalisation via les options
    $messages = apply_filters('yelira_promo_messages', $messages);

    if (empty($messages)) {
        return;
    }
    ?>
    <div class="yelira-promo-bar" id="yelira-promo-bar">
        <div class="yelira-promo-bar-inner">
            <?php foreach ($messages as $message) : ?>
                <span class="yelira-promo-bar-text"><?php echo esc_html($message); ?></span>
            <?php endforeach; ?>
            <?php foreach ($messages as $message) : ?>
                <span class="yelira-promo-bar-text"><?php echo esc_html($message); ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
// Utiliser wp_body_open qui est plus universel
add_action('wp_body_open', 'yelira_promo_bar', 1);

/**
 * Critical CSS is now handled entirely in style.css
 * This function is kept for backwards compatibility but no longer outputs inline styles
 */
function yelira_inline_critical_css() {
    // All critical CSS has been moved to the main stylesheet for better caching
}
add_action('wp_head', 'yelira_inline_critical_css', 999);

/**
 * ============================================================================
 * SEO & PERFORMANCES
 * ============================================================================
 */

/**
 * Ajouter le schema markup pour les produits
 */
function yelira_product_schema() {
    if (!is_product()) {
        return;
    }

    global $product;

    $schema = array(
        '@context' => 'https://schema.org/',
        '@type' => 'Product',
        'name' => $product->get_name(),
        'description' => wp_strip_all_tags($product->get_short_description()),
        'sku' => $product->get_sku(),
        'brand' => array(
            '@type' => 'Brand',
            'name' => 'Yelira'
        ),
        'offers' => array(
            '@type' => 'Offer',
            'url' => get_permalink($product->get_id()),
            'priceCurrency' => get_woocommerce_currency(),
            'price' => $product->get_price(),
            'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
        )
    );

    // Ajouter l'image
    $image_id = $product->get_image_id();
    if ($image_id) {
        $schema['image'] = wp_get_attachment_url($image_id);
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
}
add_action('wp_head', 'yelira_product_schema');

/**
 * Lazy loading natif pour les images
 */
function yelira_lazy_loading($attr, $attachment, $size) {
    $attr['loading'] = 'lazy';
    return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'yelira_lazy_loading', 10, 3);

/**
 * ============================================================================
 * UTILITAIRES
 * ============================================================================
 */

/**
 * Shortcode pour afficher les produits en vedette
 * Usage: [yelira_featured_products limit="8"]
 */
function yelira_featured_products_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 8,
        'columns' => 4
    ), $atts);

    return do_shortcode('[products limit="' . $atts['limit'] . '" columns="' . $atts['columns'] . '" visibility="featured"]');
}
add_shortcode('yelira_featured_products', 'yelira_featured_products_shortcode');

/**
 * Shortcode pour afficher les nouveautés
 * Usage: [yelira_new_products limit="8"]
 */
function yelira_new_products_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 8,
        'columns' => 4
    ), $atts);

    return do_shortcode('[products limit="' . $atts['limit'] . '" columns="' . $atts['columns'] . '" orderby="date" order="DESC"]');
}
add_shortcode('yelira_new_products', 'yelira_new_products_shortcode');

/**
 * Shortcode pour afficher les promotions
 * Usage: [yelira_sale_products limit="8"]
 */
function yelira_sale_products_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 8,
        'columns' => 4
    ), $atts);

    return do_shortcode('[products limit="' . $atts['limit'] . '" columns="' . $atts['columns'] . '" on_sale="true"]');
}
add_shortcode('yelira_sale_products', 'yelira_sale_products_shortcode');

/**
 * ============================================================================
 * ADMIN - PERSONNALISATION
 * ============================================================================
 */

/**
 * Ajouter le logo personnalisé dans l'admin
 */
function yelira_admin_logo() {
    echo '<style>
        #wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon:before {
            content: "Y";
            font-family: "Inter", sans-serif;
            font-weight: bold;
        }
    </style>';
}
add_action('admin_head', 'yelira_admin_logo');

/**
 * Message de bienvenue personnalisé dans le dashboard
 */
function yelira_dashboard_welcome($translated_text, $text, $domain) {
    if ($text === 'Welcome to WordPress!' && is_admin()) {
        return 'Bienvenue sur Yelira !';
    }
    return $translated_text;
}
add_filter('gettext', 'yelira_dashboard_welcome', 20, 3);

/**
 * ============================================================================
 * HEADER - PERSONNALISATION (Style Neyssa Shop)
 * ============================================================================
 */

/**
 * Enregistrer les menus de navigation
 */
function yelira_register_menus() {
    register_nav_menus(array(
        'yelira-main-menu' => 'Menu Principal Yelira',
        'yelira-footer-menu' => 'Menu Footer Yelira',
        'yelira-categories-menu' => 'Menu Catégories Yelira'
    ));
}
add_action('after_setup_theme', 'yelira_register_menus');

/**
 * Header personnalisé style Neyssa Shop
 */
function yelira_custom_header() {
    ?>
    <!-- Header Principal -->
    <header class="yelira-header" id="yelira-header">
        <div class="yelira-header-main">
            <div class="yelira-container">
                <div class="yelira-header-inner">
                    <!-- Burger Menu Mobile -->
                    <button class="yelira-burger" id="yelira-burger" aria-label="Menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>

                    <!-- Logo -->
                    <div class="yelira-logo">
                        <a href="<?php echo home_url(); ?>">
                            <span class="yelira-logo-text">YELIRA</span>
                        </a>
                    </div>

                    <!-- Actions (Recherche, Compte, Panier) -->
                    <div class="yelira-header-actions">
                        <button class="yelira-search-toggle" id="yelira-search-toggle" aria-label="Rechercher">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        </button>
                        <a href="<?php echo wc_get_page_permalink('myaccount'); ?>" class="yelira-account" aria-label="Mon compte">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </a>
                        <a href="<?php echo wc_get_cart_url(); ?>" class="yelira-cart" aria-label="Panier">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            <span class="yelira-cart-count"><?php echo WC()->cart ? WC()->cart->get_cart_contents_count() : '0'; ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Catégories (Bandeau Horizontal) -->
        <nav class="yelira-nav-categories" id="yelira-nav">
            <div class="yelira-container">
                <ul class="yelira-nav-list">
                    <li class="yelira-nav-item highlight"><a href="/categorie-produit/soldes/">SOLDES</a></li>
                    <li class="yelira-nav-item highlight"><a href="/categorie-produit/nouveautes/">NOUVEAUTÉS</a></li>
                    <li class="yelira-nav-item"><a href="/categorie-produit/abayas/">ABAYAS</a></li>
                    <li class="yelira-nav-item"><a href="/categorie-produit/hijabs/">HIJABS</a></li>
                    <li class="yelira-nav-item"><a href="/categorie-produit/jilbabs/">JILBABS</a></li>
                    <li class="yelira-nav-item"><a href="/categorie-produit/khimar/">KHIMAR</a></li>
                    <li class="yelira-nav-item"><a href="/categorie-produit/robes/">ROBES</a></li>
                    <li class="yelira-nav-item"><a href="/categorie-produit/burkini/">BURKINI</a></li>
                    <li class="yelira-nav-item"><a href="/categorie-produit/pret-a-porter/">PRÊT-À-PORTER</a></li>
                    <li class="yelira-nav-item"><a href="/categorie-produit/homme/">HOMME</a></li>
                </ul>
            </div>
        </nav>

        <!-- Barre de recherche -->
        <div class="yelira-search-bar" id="yelira-search-bar">
            <div class="yelira-container">
                <form role="search" method="get" action="<?php echo home_url('/'); ?>">
                    <input type="search" name="s" placeholder="Rechercher un produit..." autocomplete="off">
                    <input type="hidden" name="post_type" value="product">
                    <button type="submit" aria-label="Rechercher">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    </button>
                </form>
                <button class="yelira-search-close" id="yelira-search-close" aria-label="Fermer">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Overlay pour menu mobile -->
    <div class="yelira-overlay" id="yelira-overlay"></div>
    <?php
}
add_action('wp_body_open', 'yelira_custom_header', 2);

/**
 * Ajouter les icônes de réseaux sociaux
 */
function yelira_social_icons() {
    ?>
    <div class="yelira-social-icons">
        <a href="https://instagram.com/yelira.fr" target="_blank" rel="noopener" aria-label="Instagram">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
        </a>
        <a href="https://facebook.com/yelira.fr" target="_blank" rel="noopener" aria-label="Facebook">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/></svg>
        </a>
        <a href="https://tiktok.com/@yelira.fr" target="_blank" rel="noopener" aria-label="TikTok">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z"/></svg>
        </a>
    </div>
    <?php
}

/**
 * Cacher le header par défaut de Blocksy
 */
function yelira_hide_default_header() {
    ?>
    <style>
        header.ct-header,
        .site-header:not(.yelira-header) {
            display: none !important;
        }
    </style>
    <?php
}
add_action('wp_head', 'yelira_hide_default_header', 1);

/**
 * ============================================================================
 * FOOTER - PERSONNALISATION
 * ============================================================================
 */

/**
 * Enregistrer les zones de widgets pour le footer
 */
function yelira_register_sidebars() {
    register_sidebar(array(
        'name' => 'Footer - Colonne 1',
        'id' => 'yelira-footer-1',
        'description' => 'Widgets pour la première colonne du footer (À propos)',
        'before_widget' => '<div class="footer-widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h4 class="footer-widget-title">',
        'after_title' => '</h4>'
    ));

    register_sidebar(array(
        'name' => 'Footer - Colonne 2',
        'id' => 'yelira-footer-2',
        'description' => 'Widgets pour la deuxième colonne du footer (Catégories)',
        'before_widget' => '<div class="footer-widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h4 class="footer-widget-title">',
        'after_title' => '</h4>'
    ));

    register_sidebar(array(
        'name' => 'Footer - Colonne 3',
        'id' => 'yelira-footer-3',
        'description' => 'Widgets pour la troisième colonne du footer (Informations)',
        'before_widget' => '<div class="footer-widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h4 class="footer-widget-title">',
        'after_title' => '</h4>'
    ));

    register_sidebar(array(
        'name' => 'Footer - Colonne 4',
        'id' => 'yelira-footer-4',
        'description' => 'Widgets pour la quatrième colonne du footer (Contact/Newsletter)',
        'before_widget' => '<div class="footer-widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h4 class="footer-widget-title">',
        'after_title' => '</h4>'
    ));
}
add_action('widgets_init', 'yelira_register_sidebars');

/**
 * Afficher le footer personnalisé - Design Luxe
 */
function yelira_custom_footer() {
    ?>
    <footer class="yelira-footer">
        <!-- Newsletter Section -->
        <div class="yelira-footer-newsletter">
            <div class="yelira-container">
                <h3>Rejoignez la communauté Yelira</h3>
                <p>Inscrivez-vous pour recevoir nos nouveautés et offres exclusives</p>
                <form class="yelira-newsletter-form" action="#" method="post">
                    <input type="email" name="email" placeholder="Votre adresse email" required>
                    <button type="submit">S'inscrire</button>
                </form>
            </div>
        </div>

        <div class="yelira-footer-top">
            <div class="yelira-container">
                <div class="yelira-footer-grid">
                    <!-- Colonne 1 - À propos -->
                    <div class="yelira-footer-col">
                        <div class="yelira-footer-brand">YELIRA</div>
                        <p>Votre destination pour la mode modeste et élégante. Nous sélectionnons avec soin des pièces raffinées qui célèbrent la pudeur sans compromis sur le style.</p>
                        <div class="yelira-footer-social">
                            <?php yelira_social_icons(); ?>
                        </div>
                    </div>

                    <!-- Colonne 2 - Collections -->
                    <div class="yelira-footer-col">
                        <h4>Collections</h4>
                        <ul class="yelira-footer-links">
                            <li><a href="/categorie-produit/nouveautes/">Nouveautés</a></li>
                            <li><a href="/categorie-produit/abayas/">Abayas</a></li>
                            <li><a href="/categorie-produit/hijabs/">Hijabs</a></li>
                            <li><a href="/categorie-produit/jilbabs/">Jilbabs</a></li>
                            <li><a href="/categorie-produit/khimar/">Khimar</a></li>
                            <li><a href="/categorie-produit/robes/">Robes</a></li>
                        </ul>
                    </div>

                    <!-- Colonne 3 - Aide -->
                    <div class="yelira-footer-col">
                        <h4>Aide & Info</h4>
                        <ul class="yelira-footer-links">
                            <li><a href="/livraison/">Livraison</a></li>
                            <li><a href="/retours-echanges/">Retours & Échanges</a></li>
                            <li><a href="/guide-des-tailles/">Guide des tailles</a></li>
                            <li><a href="/faq/">FAQ</a></li>
                            <li><a href="/contact/">Contact</a></li>
                        </ul>
                    </div>

                    <!-- Colonne 4 - Contact -->
                    <div class="yelira-footer-col">
                        <h4>Contact</h4>
                        <ul class="yelira-footer-contact">
                            <li>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                <a href="mailto:contact@yelira.fr">contact@yelira.fr</a>
                            </li>
                            <li>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span>Service client 7j/7</span>
                            </li>
                        </ul>
                        <div class="yelira-footer-payments">
                            <h5>Paiement sécurisé</h5>
                            <div class="yelira-payment-icons">
                                <span class="payment-icon">Visa</span>
                                <span class="payment-icon">Mastercard</span>
                                <span class="payment-icon">PayPal</span>
                                <span class="payment-icon">CB</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="yelira-footer-bottom">
            <div class="yelira-container">
                <p>&copy; <?php echo date('Y'); ?> Yelira. Tous droits réservés.</p>
                <div class="yelira-footer-legal">
                    <a href="/mentions-legales/">Mentions légales</a>
                    <a href="/cgv/">CGV</a>
                    <a href="/confidentialite/">Confidentialité</a>
                </div>
            </div>
        </div>
    </footer>
    <?php
}
add_action('wp_footer', 'yelira_custom_footer', 5);

/**
 * Footer styles are now in the main stylesheet
 * This function is kept for backwards compatibility
 */
function yelira_footer_styles() {
    // All footer CSS has been moved to style.css
}
add_action('wp_head', 'yelira_footer_styles', 999);
