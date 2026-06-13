<?php
/**
 * My Account Navigation Template
 */
if (!defined('ABSPATH')) {
    exit;
}

$items = wc_get_account_menu_items();
$sub_items = array(
    'orders-invoices' => array(
        'orders' => __('My Orders', 'astra-child'),
        'invoices' => __('My Invoices', 'astra-child'),
    ),
);

// Define IcoFont icons for each menu item and submenu item
$icons = array(
    'edit-account' => 'icofont-ui-user',
    'cart' => 'icofont-shopping-cart',
    'orders-invoices' => 'icofont-copy-invert',
    'my-quotes' => 'icofont-files-stack', 
    'wishlist' => 'icofont-heart',
    'edit-address' => 'icofont-location-pin',
    'customer-logout' => 'customer-logout',
    // Submenu items for orders-invoices
    'orders' => 'icofont-box',
    'invoices' => 'icofont-file-alt',
);
?>


<!-- Wrapper for the toggle button and navigation -->
<div class="my-account-navigation-wrapper">
    <!-- Hamburger toggle button (visible on mobile) -->
    <button type="button" class="menu-toggle" aria-expanded="false" aria-controls="my-account-navigation">
        <span class="hamburger-icon"></span>
        <span class="screen-reader-text"><?php esc_html_e('Toggle Menu', 'astra-child'); ?></span>
    </button>

    <!-- Navigation menu -->
    <nav class="woocommerce-MyAccount-navigation" id="my-account-navigation" aria-label="Account pages">
        <ul>
            
            <?php foreach ($items as $endpoint => $label) : ?>
                <li class="<?php echo wc_get_account_menu_item_classes($endpoint); ?>">
                    <a href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>">
                        <!-- Use IcoFont icon instead of span -->
                        <?php if ($icons[$endpoint] == 'customer-logout') : ?>
                            <img class="custom-logout" src="https://fhs.com.au/wp-content/uploads/2025/03/logout.svg" />
                        <?php else : ?>
                            <i class="<?php echo esc_attr($icons[$endpoint] ?? 'icofont-circle'); ?>"></i>
                        <?php endif; ?>
                        <?php echo esc_html($label); ?>
                        <?php if ($endpoint === 'orders-invoices' && in_array('is-active', explode(' ', wc_get_account_menu_item_classes($endpoint)))) : ?>
                            <span class="nav-chevron"></span>
                        <?php endif; ?>
                    </a>
                    <?php if (isset($sub_items[$endpoint])) : ?>
                        <ul class="submenu">
                            <?php foreach ($sub_items[$endpoint] as $sub_endpoint => $sub_label) : ?>
                                <li class="<?php echo wc_get_account_menu_item_classes($sub_endpoint); ?>">
                                    <a href="<?php echo esc_url(wc_get_account_endpoint_url($sub_endpoint)); ?>">
                                        <!-- Use IcoFont icon for submenu -->
                                        <i class="<?php echo esc_attr($icons[$sub_endpoint] ?? 'icofont-circle'); ?>"></i>
                                        <?php echo esc_html($sub_label); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
		
    </nav>
</div>

