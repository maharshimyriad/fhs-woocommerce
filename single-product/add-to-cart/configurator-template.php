<?php
/**
 * Product Configurator — Two-column layout
 *
 * Loaded by fhs_render_configurator() via wc_get_template() when
 * fhs_configurator_is_active() returns true for the current product.
 *
 * Fires on fhs_inside_product_main_container — after .single-product-layout-wrap
 * closes — so the configurator renders full-width below the image + summary row.
 *
 * Available variables (passed via wc_get_template):
 *   $configurator_product  WC_Product  Base product for this configurator.
 *   $sections              array[]     Output of fhs_configurator_get_sections().
 *     Each section:
 *       'key'            string   e.g. 'machine_packages'
 *       'label'          string   Human-readable heading
 *       'selection_type' string   'single' | 'multiple'
 *       'products'       array[]  Each: id, name, sku, image_url
 *
 * This step renders:
 *   - Full two-column outer layout (left options, right panel placeholder)
 *   - Section intro header bar
 *   - Tab navigation with icofont icons
 *   - Machine Packages: large radio-style cards
 *   - All other sections: compact checkbox-style grid cards
 *   - "Select all" link for multiple-selection sections
 *   - Pricing placeholder comments (Step 5)
 *   - Selection / Add to Configuration placeholder comments (Step 7)
 *   - Your Configuration panel placeholder (Step 6)
 *
 * @package FHS_WOO
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $sections ) ) {
	return;
}

// Enqueue configurator stylesheet once.
if ( ! wp_style_is( 'fhs-configurator', 'enqueued' ) ) {
	wp_enqueue_style(
		'fhs-configurator',
		get_stylesheet_directory_uri() . '/woocommerce/single-product/add-to-cart/configurator.css',
		array( 'woocommerce-general' ),
		'1.0.0'
	);
}

$first_key = $sections[0]['key'];

// Icon map per section key — uses icofont classes already loaded in the theme.
$section_icons = array(
	'machine_packages'  => 'icofont-industries-4',
	'liner_sets'        => 'icofont-loop',
	'replacement_parts' => 'icofont-tools-alt-2',
	'accessories'       => 'icofont-box',
	'data_logging'      => 'icofont-chart-bar-graph',
	'consumables'       => 'icofont-recycle',
	'tooling_extras'    => 'icofont-wrench',
);
?>

<div class="fhs-configurator product-main-container"
	data-product-id="<?php echo absint( $configurator_product->get_id() ); ?>">

	<!-- ════════════════════════════════════════════════════════════════════
	     Outer two-column layout
	     LEFT  = section tabs + product cards
	     RIGHT = Your Configuration panel (Step 6)
	     ════════════════════════════════════════════════════════════════════ -->
	<div class="fhs-configurator__layout">

		<!-- ── LEFT COLUMN ─────────────────────────────────────────────── -->
		<div class="fhs-configurator__left">

			<!-- Intro header bar -->
			<div class="fhs-configurator__intro-bar">
				<span class="fhs-configurator__intro-number">1</span>
				<span class="fhs-configurator__intro-text">
					<?php esc_html_e( 'Choose from any of the sections below (all optional)', 'woocommerce' ); ?>
				</span>
				<span class="fhs-configurator__intro-help">
					<?php esc_html_e( 'Need help?', 'woocommerce' ); ?>
					<a href="/contact" class="fhs-configurator__intro-help-link">
						<?php esc_html_e( 'Contact our team', 'woocommerce' ); ?>
					</a>
				</span>
			</div>

			<!-- ── Tab navigation ───────────────────────────────────────── -->
			<nav class="fhs-configurator__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Configurator sections', 'woocommerce' ); ?>">
				<?php foreach ( $sections as $section ) :
					$is_active  = $section['key'] === $first_key;
					$icon_class = $section_icons[ $section['key'] ] ?? 'icofont-box';
				?>
					<button
						class="fhs-configurator__tab<?php echo $is_active ? ' fhs-configurator__tab--active' : ''; ?>"
						role="tab"
						data-section="<?php echo esc_attr( $section['key'] ); ?>"
						aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
						aria-controls="fhs-conf-panel-<?php echo esc_attr( $section['key'] ); ?>"
						id="fhs-conf-tab-<?php echo esc_attr( $section['key'] ); ?>"
					>
						<i class="icofont <?php echo esc_attr( $icon_class ); ?>" aria-hidden="true"></i>
						<?php echo esc_html( $section['label'] ); ?>
					</button>
				<?php endforeach; ?>
			</nav>

			<!-- ── Section panels ───────────────────────────────────────── -->
			<?php foreach ( $sections as $section ) :
				$is_active      = $section['key'] === $first_key;
				$is_machine     = $section['key'] === 'machine_packages';
				$is_multiple    = $section['selection_type'] === 'multiple';
				$panel_class    = 'fhs-configurator__panel';
				$panel_class   .= $is_active    ? ' fhs-configurator__panel--active'  : '';
				$panel_class   .= $is_machine   ? ' fhs-configurator__panel--machine' : '';
			?>
				<div
					class="<?php echo esc_attr( $panel_class ); ?>"
					id="fhs-conf-panel-<?php echo esc_attr( $section['key'] ); ?>"
					role="tabpanel"
					aria-labelledby="fhs-conf-tab-<?php echo esc_attr( $section['key'] ); ?>"
					data-section="<?php echo esc_attr( $section['key'] ); ?>"
					data-selection-type="<?php echo esc_attr( $section['selection_type'] ); ?>"
					<?php if ( ! $is_active ) : ?>hidden<?php endif; ?>
				>

					<!-- Panel heading row -->
					<div class="fhs-configurator__panel-header">
						<h3 class="fhs-configurator__panel-heading">
							<?php echo esc_html( $section['label'] ); ?>
							<span class="fhs-configurator__optional-badge">
								<?php esc_html_e( 'optional', 'woocommerce' ); ?>
							</span>
							<span class="fhs-configurator__info-icon" aria-label="<?php esc_attr_e( 'More info', 'woocommerce' ); ?>">
								<i class="icofont-info-circle" aria-hidden="true"></i>
							</span>
						</h3>

						<?php if ( $is_multiple ) : ?>
							<!-- Select All — wired up in Step 7 -->
							<button
								type="button"
								class="fhs-configurator__select-all"
								data-section="<?php echo esc_attr( $section['key'] ); ?>"
								aria-label="<?php printf( esc_attr__( 'Select all %s', 'woocommerce' ), esc_attr( $section['label'] ) ); ?>"
							>
								<?php esc_html_e( 'Select all', 'woocommerce' ); ?>
							</button>
						<?php endif; ?>
					</div><!-- /.fhs-configurator__panel-header -->

					<!-- ── Product card grid ──────────────────────────── -->
					<div class="fhs-configurator__grid<?php echo $is_machine ? ' fhs-configurator__grid--machine' : ' fhs-configurator__grid--standard'; ?>">

						<?php foreach ( $section['products'] as $product_data ) : ?>

							<div
								class="fhs-configurator__card<?php echo $is_machine ? ' fhs-configurator__card--machine' : ' fhs-configurator__card--standard'; ?>"
								data-product-id="<?php echo absint( $product_data['id'] ); ?>"
								data-section="<?php echo esc_attr( $section['key'] ); ?>"
							>

								<?php if ( $is_machine ) : ?>
									<!-- Machine Package card — large, radio-style -->

									<!-- Selection indicator — interactive in Step 7 -->
									<div class="fhs-configurator__card-select-indicator" aria-hidden="true">
										<span class="fhs-configurator__radio"></span>
									</div>

									<div class="fhs-configurator__card-img-wrap">
										<img
											src="<?php echo esc_url( $product_data['image_url'] ); ?>"
											alt="<?php echo esc_attr( $product_data['name'] ); ?>"
											class="fhs-configurator__card-img"
											loading="lazy"
										/>
									</div>

									<div class="fhs-configurator__card-body">
										<p class="fhs-configurator__card-name">
											<?php echo esc_html( $product_data['name'] ); ?>
										</p>
										<?php if ( ! empty( $product_data['sku'] ) ) : ?>
											<p class="fhs-configurator__card-sku">
												<?php echo esc_html( $product_data['sku'] ); ?>
											</p>
										<?php endif; ?>
										<!-- Price — Step 5 -->
									</div>

								<?php else : ?>
									<!-- Standard card — compact, checkbox-style -->

									<!-- Selection indicator — interactive in Step 7 -->
									<div class="fhs-configurator__card-select-indicator" aria-hidden="true">
										<span class="fhs-configurator__checkbox"></span>
									</div>

									<div class="fhs-configurator__card-img-wrap">
										<img
											src="<?php echo esc_url( $product_data['image_url'] ); ?>"
											alt="<?php echo esc_attr( $product_data['name'] ); ?>"
											class="fhs-configurator__card-img"
											loading="lazy"
										/>
									</div>

									<div class="fhs-configurator__card-body">
										<p class="fhs-configurator__card-name">
											<?php echo esc_html( $product_data['name'] ); ?>
										</p>
										<?php if ( ! empty( $product_data['sku'] ) ) : ?>
											<p class="fhs-configurator__card-sku">
												<?php echo esc_html( $product_data['sku'] ); ?>
											</p>
										<?php endif; ?>
										<!-- Price — Step 5 -->
									</div>

								<?php endif; ?>

							</div><!-- /.fhs-configurator__card -->

						<?php endforeach; ?>

					</div><!-- /.fhs-configurator__grid -->

					<!-- Add to Configuration button — Step 7 -->

				</div><!-- /.fhs-configurator__panel -->

			<?php endforeach; ?>

		</div><!-- /.fhs-configurator__left -->

		<!-- ── RIGHT COLUMN — Your Configuration (Step 6) ──────────────── -->
		<div class="fhs-configurator__right">
			<!-- Your Configuration panel rendered in Step 6 -->
		</div>

	</div><!-- /.fhs-configurator__layout -->

</div><!-- /.fhs-configurator -->
