<?php
/**
 * Product Configurator — Static layout (Step 4)
 *
 * Loaded by fhs_render_configurator() via wc_get_template() when
 * fhs_configurator_is_active() returns true for the current product.
 *
 * Fires on the fhs_inside_product_main_container hook, which is defined in
 * content-single-product.php and fires after .single-product-layout-wrap
 * closes. The configurator therefore renders full-width below the
 * image + summary row, matching the variation card grid position.
 *
 * Available variables (passed from fhs_render_configurator via wc_get_template):
 *   $configurator_product  WC_Product  The base product for this configurator.
 *   $sections              array[]     Output of fhs_configurator_get_sections().
 *                                      Each element:
 *                                        'key'            string  e.g. 'machine_packages'
 *                                        'label'          string  Human-readable heading
 *                                        'selection_type' string  'single' or 'multiple'
 *                                        'products'       array[] fhs_configurator_get_product_data()
 *                                                                 Each: id, name, sku, image_url
 *
 * Step 4 renders:
 *   - Section navigation tabs (one tab per non-empty section)
 *   - Product cards per section (image, name, SKU)
 *   - selection_type exposed as data-selection-type on the section panel
 *
 * Step 4 does NOT render:
 *   - Pricing (Step 5)
 *   - Selection / Add to Configuration (Step 7)
 *   - Your Configuration panel (Step 6)
 *   - Any JavaScript
 *   - Any inline CSS
 *
 * @package FHS_WOO
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// ── G. TEMPLATE ENTRY TRACE ───────────────────────────────────────────────────
$_fhs_trace_log = function( $msg ) {
	$dir  = wp_upload_dir();
	$file = $dir['basedir'] . '/fhs-config-trace.log';
	file_put_contents( $file, '[' . date('H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND | LOCK_EX );
};
$_fhs_trace_log( 'G. TEMPLATE ENTERED: configurator-template.php' );
$_fhs_trace_log( 'G. isset($sections): '   . ( isset( $sections )            ? 'YES' : 'NO' ) );
$_fhs_trace_log( 'G. is_array($sections): ' . ( isset( $sections ) && is_array( $sections ) ? 'YES' : 'NO' ) );
$_fhs_trace_log( 'G. count($sections): '   . ( isset( $sections ) && is_array( $sections ) ? count( $sections ) : 'N/A' ) );
$_fhs_trace_log( 'G. isset($configurator_product): ' . ( isset( $configurator_product ) ? 'YES' : 'NO' ) );

// Nothing to show — fhs_configurator_get_sections() already filtered empties,
// but guard here too so the wrapper never renders when $sections is empty.
if ( empty( $sections ) ) {
	$_fhs_trace_log( 'H. EARLY RETURN — $sections is empty' );
	return;
}

// First section key — used to mark the first tab/panel active by default.
$first_key = $sections[0]['key'];
$_fhs_trace_log( 'G. PAST empty check — rendering HTML, first_key=' . $first_key );
?>

<div class="fhs-configurator"
	data-product-id="<?php echo absint( $configurator_product->get_id() ); ?>">

	<!-- ── Section navigation tabs ──────────────────────────────────────── -->
	<nav class="fhs-configurator__tabs" role="tablist">
		<?php foreach ( $sections as $section ) : ?>
			<button
				class="fhs-configurator__tab<?php echo $section['key'] === $first_key ? ' fhs-configurator__tab--active' : ''; ?>"
				role="tab"
				data-section="<?php echo esc_attr( $section['key'] ); ?>"
				aria-selected="<?php echo $section['key'] === $first_key ? 'true' : 'false'; ?>"
				aria-controls="fhs-conf-panel-<?php echo esc_attr( $section['key'] ); ?>"
			>
				<?php echo esc_html( $section['label'] ); ?>
			</button>
		<?php endforeach; ?>
	</nav>

	<!-- ── Section panels ───────────────────────────────────────────────── -->
	<?php foreach ( $sections as $section ) :
		$is_active = $section['key'] === $first_key;
	?>
		<div
			class="fhs-configurator__panel<?php echo $is_active ? ' fhs-configurator__panel--active' : ''; ?>"
			id="fhs-conf-panel-<?php echo esc_attr( $section['key'] ); ?>"
			role="tabpanel"
			data-section="<?php echo esc_attr( $section['key'] ); ?>"
			data-selection-type="<?php echo esc_attr( $section['selection_type'] ); ?>"
		>

			<!-- Panel heading -->
			<h3 class="fhs-configurator__panel-heading">
				<?php echo esc_html( $section['label'] ); ?>
				<?php if ( 'multiple' === $section['selection_type'] ) : ?>
					<span class="fhs-configurator__selection-hint">
						(<?php esc_html_e( 'Select one or more', 'woocommerce' ); ?>)
					</span>
				<?php else : ?>
					<span class="fhs-configurator__selection-hint">
						(<?php esc_html_e( 'Select one', 'woocommerce' ); ?>)
					</span>
				<?php endif; ?>
			</h3>

			<!-- Product card grid for this section -->
			<div class="fhs-configurator__product-grid">

				<?php foreach ( $section['products'] as $product_data ) : ?>

					<div
						class="fhs-configurator__product-card"
						data-product-id="<?php echo absint( $product_data['id'] ); ?>"
					>

						<!-- Card image -->
						<div class="fhs-configurator__card-img-wrap">
							<img
								src="<?php echo esc_url( $product_data['image_url'] ); ?>"
								alt="<?php echo esc_attr( $product_data['name'] ); ?>"
								class="fhs-configurator__card-img"
								loading="lazy"
							/>
						</div>

						<!-- Card body -->
						<div class="fhs-configurator__card-body">

							<h4 class="fhs-configurator__card-name">
								<?php echo esc_html( $product_data['name'] ); ?>
							</h4>

							<?php if ( ! empty( $product_data['sku'] ) ) : ?>
								<p class="fhs-configurator__card-sku">
									<?php esc_html_e( 'SKU', 'woocommerce' ); ?>:
									<span><?php echo esc_html( $product_data['sku'] ); ?></span>
								</p>
							<?php endif; ?>

							<!-- Pricing placeholder — implemented in Step 5 -->

							<!-- Selection / Add to Configuration — implemented in Step 7 -->

						</div><!-- /.fhs-configurator__card-body -->

					</div><!-- /.fhs-configurator__product-card -->

				<?php endforeach; ?>

			</div><!-- /.fhs-configurator__product-grid -->

		</div><!-- /.fhs-configurator__panel -->

	<?php endforeach; ?>

	<!-- ── Your Configuration panel — implemented in Step 6 ─────────────── -->

</div><!-- /.fhs-configurator -->

<?php $_fhs_trace_log( 'G. TEMPLATE END — HTML fully output' ); ?>
