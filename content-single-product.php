<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

/**
 * Hook: woocommerce_before_single_product.
 * @hooked woocommerce_output_all_notices - 10
 */
do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form();
	return;
}

$fhs_has_configurator = function_exists( 'fhs_configurator_is_active' ) && fhs_configurator_is_active( $product );
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>

	<div class="single-product-content-container product-main-container<?php echo $fhs_has_configurator ? ' fhs-configurator-product-layout' : ''; ?>">

		<?php if ( $fhs_has_configurator ) : ?>
			<div class="fhs-configurator-product-main">
		<?php endif; ?>

			<!-- Image + summary row -->
			<div class="single-product-layout-wrap">
				<?php
				/**
				 * Hook: woocommerce_before_single_product_summary.
				 * @hooked woocommerce_show_product_sale_flash - 10
				 * @hooked woocommerce_show_product_images - 20
				 */
				do_action( 'woocommerce_before_single_product_summary' );
				?>

				<div class="summary entry-summary">
					<?php
					/**
					 * Hook: woocommerce_single_product_summary.
					 * @hooked woocommerce_template_single_title - 5
					 * @hooked woocommerce_template_single_rating - 10
					 * @hooked woocommerce_template_single_price - 10
					 * @hooked woocommerce_template_single_excerpt - 20
					 * @hooked woocommerce_template_single_add_to_cart - 30
					 * @hooked woocommerce_template_single_meta - 40
					 * @hooked woocommerce_template_single_sharing - 50
					 * @hooked WC_Structured_Data::generate_product_data() - 60
					 */
					do_action( 'woocommerce_single_product_summary' );
					?>
				</div>
			</div><!-- /.single-product-layout-wrap -->

		<?php if ( $fhs_has_configurator ) : ?>
			</div><!-- /.fhs-configurator-product-main -->

			<!--
				Configurator inline row:
				Left  → .fhs-configurator  (selection table, full remaining width)
				Right → .fhs-configurator-sidebar  (Your Configuration panel, fixed 360px)
				Both stretch to the same height via align-items: stretch.
			-->
			<div class="fhs-configurator-inline-row">

				<?php
				/**
				 * Hook: fhs_inside_product_main_container
				 * → fhs_render_configurator() outputs .fhs-configurator here.
				 */
				do_action( 'fhs_inside_product_main_container' );
				?>

				<?php
				/**
				 * Hook: fhs_configurator_sidebar_area
				 * → fhs_render_configurator_sidebar() outputs <aside.fhs-configurator-sidebar> here.
				 */
				do_action( 'fhs_configurator_sidebar_area' );
				?>

			</div><!-- /.fhs-configurator-inline-row -->

		<?php else : ?>
			<?php
			/**
			 * Non-configurator products: variation card grid etc. still fire here.
			 */
			do_action( 'fhs_inside_product_main_container' );
			?>
		<?php endif; ?>

	</div><!-- /.single-product-content-container -->

	<?php
	/**
	 * Hook: woocommerce_after_single_product_summary.
	 * @hooked woocommerce_output_product_data_tabs - 10
	 * @hooked woocommerce_upsell_display - 15
	 * @hooked woocommerce_output_related_products - 20
	 */
	do_action( 'woocommerce_after_single_product_summary' );
	?>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
