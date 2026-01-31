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
