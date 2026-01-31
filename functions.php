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

    // Google Fonts
    wp_enqueue_style(
        'yelira-fonts',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap',
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
 * Ajouter du CSS inline pour s'assurer que le bandeau s'affiche correctement
 */
function yelira_inline_critical_css() {
    ?>
    <style id="yelira-critical-css">
        /* Bandeau promo - Critical CSS */
        .yelira-promo-bar {
            background-color: #1a1a1a !important;
            color: #ffffff !important;
            height: 40px !important;
            display: flex !important;
            align-items: center !important;
            overflow: hidden !important;
            position: relative !important;
            z-index: 9999 !important;
            width: 100% !important;
        }
        .yelira-promo-bar-inner {
            display: flex !important;
            animation: yelira-scroll 30s linear infinite !important;
            white-space: nowrap !important;
        }
        .yelira-promo-bar-text {
            display: inline-flex !important;
            align-items: center !important;
            padding: 0 50px !important;
            font-size: 12px !important;
            font-weight: 500 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            color: #ffffff !important;
        }
        .yelira-promo-bar-text::after {
            content: '✦' !important;
            margin-left: 50px !important;
            color: #c9a962 !important;
        }
        @keyframes yelira-scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* Override Blocksy pour les cartes produits */
        .woocommerce ul.products li.product {
            text-align: left !important;
        }
        .woocommerce ul.products li.product .yelira-product-category {
            display: block !important;
            font-size: 10px !important;
            font-weight: 500 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            color: #999999 !important;
            margin: 15px 0 5px 0 !important;
        }
        .woocommerce ul.products li.product .yelira-product-sku {
            display: block !important;
            font-size: 10px !important;
            color: #999999 !important;
            margin-bottom: 8px !important;
            font-family: monospace !important;
        }
        .woocommerce ul.products li.product .yelira-product-excerpt {
            font-size: 12px !important;
            color: #666666 !important;
            line-height: 1.5 !important;
            margin: 10px 0 !important;
            display: -webkit-box !important;
            -webkit-line-clamp: 2 !important;
            -webkit-box-orient: vertical !important;
            overflow: hidden !important;
        }

        /* Badges */
        .yelira-badges {
            position: absolute !important;
            top: 10px !important;
            left: 10px !important;
            z-index: 5 !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 5px !important;
        }
        .badge-new {
            background-color: #c9a962 !important;
            color: #1a1a1a !important;
            font-size: 10px !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            padding: 5px 10px !important;
        }
        .badge-sale {
            background-color: #e53935 !important;
            color: #ffffff !important;
            font-size: 10px !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            padding: 5px 10px !important;
        }
        .badge-low-stock {
            background-color: #ff9800 !important;
            color: #ffffff !important;
            font-size: 10px !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            padding: 5px 10px !important;
        }
    </style>
    <?php
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
 * HEADER - PERSONNALISATION
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
 * Ajouter les icônes de réseaux sociaux dans le header
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
 * Afficher le footer personnalisé
 */
function yelira_custom_footer() {
    ?>
    <footer class="yelira-footer">
        <div class="yelira-footer-top">
            <div class="yelira-container">
                <div class="yelira-footer-grid">
                    <!-- Colonne 1 - À propos -->
                    <div class="yelira-footer-col">
                        <h4>À propos de Yelira</h4>
                        <p>Yelira est votre boutique de référence pour la mode modeste et élégante. Nous sélectionnons avec soin des vêtements de qualité qui allient pudeur et tendance.</p>
                        <div class="yelira-footer-social">
                            <?php yelira_social_icons(); ?>
                        </div>
                    </div>

                    <!-- Colonne 2 - Catégories -->
                    <div class="yelira-footer-col">
                        <h4>Nos Collections</h4>
                        <ul class="yelira-footer-links">
                            <li><a href="/categorie-produit/abayas/">Abayas</a></li>
                            <li><a href="/categorie-produit/hijabs/">Hijabs</a></li>
                            <li><a href="/categorie-produit/jilbabs/">Jilbabs</a></li>
                            <li><a href="/categorie-produit/khimar/">Khimar</a></li>
                            <li><a href="/categorie-produit/robes/">Robes</a></li>
                            <li><a href="/categorie-produit/pret-a-porter/">Prêt-à-porter</a></li>
                        </ul>
                    </div>

                    <!-- Colonne 3 - Informations -->
                    <div class="yelira-footer-col">
                        <h4>Informations</h4>
                        <ul class="yelira-footer-links">
                            <li><a href="/a-propos/">À propos</a></li>
                            <li><a href="/livraison/">Livraison</a></li>
                            <li><a href="/retours-echanges/">Retours & Échanges</a></li>
                            <li><a href="/mentions-legales/">Mentions légales</a></li>
                            <li><a href="/cgv/">CGV</a></li>
                            <li><a href="/contact/">Contact</a></li>
                        </ul>
                    </div>

                    <!-- Colonne 4 - Contact -->
                    <div class="yelira-footer-col">
                        <h4>Besoin d'aide ?</h4>
                        <ul class="yelira-footer-contact">
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                <a href="mailto:contact@yelira.fr">contact@yelira.fr</a>
                            </li>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                                <span>Service client 7j/7</span>
                            </li>
                        </ul>
                        <div class="yelira-footer-payments">
                            <h5>Paiement sécurisé</h5>
                            <div class="yelira-payment-icons">
                                <span class="payment-icon">💳 CB</span>
                                <span class="payment-icon">Visa</span>
                                <span class="payment-icon">Mastercard</span>
                                <span class="payment-icon">PayPal</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="yelira-footer-bottom">
            <div class="yelira-container">
                <p>&copy; <?php echo date('Y'); ?> Yelira - Mode Modeste. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
    <?php
}
add_action('wp_footer', 'yelira_custom_footer', 5);

/**
 * Ajouter CSS pour le footer personnalisé
 */
function yelira_footer_styles() {
    ?>
    <style id="yelira-footer-css">
        /* Footer Yelira */
        .yelira-footer {
            background-color: #1a1a1a;
            color: #ffffff;
            margin-top: 60px;
        }
        .yelira-footer-top {
            padding: 60px 0 40px;
        }
        .yelira-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .yelira-footer-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
        }
        .yelira-footer-col h4 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #c9a962;
        }
        .yelira-footer-col p {
            font-size: 14px;
            line-height: 1.7;
            color: #aaaaaa;
        }
        .yelira-footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .yelira-footer-links li {
            margin-bottom: 10px;
        }
        .yelira-footer-links a {
            color: #aaaaaa;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        .yelira-footer-links a:hover {
            color: #c9a962;
        }
        .yelira-footer-contact {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .yelira-footer-contact li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #aaaaaa;
        }
        .yelira-footer-contact svg {
            color: #c9a962;
            flex-shrink: 0;
        }
        .yelira-footer-contact a {
            color: #aaaaaa;
            text-decoration: none;
        }
        .yelira-footer-contact a:hover {
            color: #c9a962;
        }
        .yelira-footer-social {
            margin-top: 20px;
        }
        .yelira-social-icons {
            display: flex;
            gap: 15px;
        }
        .yelira-social-icons a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background-color: #2a2a2a;
            border-radius: 50%;
            color: #ffffff;
            transition: all 0.3s ease;
        }
        .yelira-social-icons a:hover {
            background-color: #c9a962;
            color: #1a1a1a;
        }
        .yelira-footer-payments {
            margin-top: 25px;
        }
        .yelira-footer-payments h5 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888888;
            margin-bottom: 10px;
        }
        .yelira-payment-icons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .payment-icon {
            background-color: #2a2a2a;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
            color: #aaaaaa;
        }
        .yelira-footer-bottom {
            border-top: 1px solid #2a2a2a;
            padding: 20px 0;
            text-align: center;
        }
        .yelira-footer-bottom p {
            margin: 0;
            font-size: 13px;
            color: #666666;
        }

        /* Responsive Footer */
        @media (max-width: 992px) {
            .yelira-footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 576px) {
            .yelira-footer-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .yelira-footer-social,
            .yelira-social-icons,
            .yelira-payment-icons {
                justify-content: center;
            }
            .yelira-footer-contact li {
                justify-content: center;
            }
        }

        /* Cacher le footer par défaut de Blocksy */
        footer.ct-footer,
        .site-footer:not(.yelira-footer) {
            display: none !important;
        }
    </style>
    <?php
}
add_action('wp_head', 'yelira_footer_styles', 999);
