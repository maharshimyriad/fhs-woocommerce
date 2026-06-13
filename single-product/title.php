<?php
/**
 * Single Product title
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/title.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://woocommerce.com/document/template-structure/
 * @package    WooCommerce\Templates
 * @version    1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<div class="product-title-wrapper">
    <?php
    global $product;
    the_title('<h1 class="product_title entry-title">','</h1>');
//     echo '<span id="dynamic-sku" class="product-sku"><strong>SKU : </strong>' . esc_html($product->get_sku()) . '</span>';
    echo '<span id="dynamic-sku" class="product-sku">
        <strong>SKU : </strong>
        <span class="sku-value">' . esc_html( $product->get_sku() ) . '</span>
      </span>';

    ?>
</div>

<?php if ( is_product() ) : ?>

<script>
jQuery(function($){

    var $form = $('form.variations_form');
    var $skuValue = $('.sku-value');
    var defaultSku = '<?php echo esc_js( $product->get_sku() ); ?>';

    // When variation is selected
    $form.on('found_variation', function(event, variation){
        if (variation && variation.sku) {
            $skuValue.text(variation.sku);
        } else {
            $skuValue.text(defaultSku);
        }
    });

    // When variations are reset
    $form.on('reset_data', function(){
        $skuValue.text(defaultSku);
    });

});
</script>

<?php endif; ?>

