<?php
/**
 * Product Configurator — Main left-column layout
 *
 * @package FHS_WOO
 * @version 3.2.0
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $sections ) ) {
	return;
}

$first_key = $sections[0]['key'];

$section_icons = array(
	'machine_packages'  => 'fa-solid fa-box',
	'liner_sets'        => 'icofont-loop',
	'replacement_parts' => 'icofont-tools-alt-2',
	'accessories'       => 'icofont-box',
	'data_logging'      => 'icofont-chart-bar-graph',
	'consumables'       => 'icofont-recycle',
	'tooling_extras'    => 'icofont-wrench',
);

$section_tooltips = array(
	'machine_packages'  => __( 'Add Machine Packages', 'woocommerce' ),
	'liner_sets'        => __( 'Add Liner Sets', 'woocommerce' ),
	'replacement_parts' => __( 'Add Replacement Parts', 'woocommerce' ),
	'accessories'       => __( 'Add Accessories', 'woocommerce' ),
	'data_logging'      => __( 'Add Data Logging', 'woocommerce' ),
	'consumables'       => __( 'Add Consumables', 'woocommerce' ),
	'tooling_extras'    => __( 'Add Tooling & Extras', 'woocommerce' ),
);

$section_labels = array();
$product_map    = array();

$base_product = array(
	'id'            => absint( $configurator_product->get_id() ),
	'section_key'   => 'base_product',
	'section_label' => __( 'Base Product', 'woocommerce' ),
	'name'          => $configurator_product->get_name(),
	'sku'           => $configurator_product->get_sku(),
	'image_url'     => wp_get_attachment_image_url( $configurator_product->get_image_id(), 'woocommerce_thumbnail' ),
	'price_html'    => '',
	'price_value'   => 0,
	'price_display' => '',
	'permalink'     => get_permalink( $configurator_product->get_id() ),
);

$base_product_data = fhs_configurator_get_product_data( $configurator_product->get_id() );
if ( ! empty( $base_product_data ) ) {
	$base_product['image_url']     = $base_product_data['image_url'];
	$base_product['price_html']    = $base_product_data['price_html']    ?? '';
	$base_product['price_value']   = isset( $base_product_data['price_value'] ) ? (float) $base_product_data['price_value'] : 0;
	$base_product['price_display'] = $base_product_data['price_display'] ?? '';
}

foreach ( $sections as $section ) {
	$section_labels[ $section['key'] ] = $section['label'];
	foreach ( $section['products'] as $product_data ) {
		$product_map[ (string) $product_data['id'] ] = array(
			'id'            => absint( $product_data['id'] ),
			'section_key'   => $section['key'],
			'section_label' => $section['label'],
			'name'          => $product_data['name'],
			'sku'           => $product_data['sku'],
			'image_url'     => $product_data['image_url'],
			'price_html'    => $product_data['price_html']    ?? '',
			'price_value'   => isset( $product_data['price_value'] ) ? (float) $product_data['price_value'] : 0,
			'price_display' => $product_data['price_display'] ?? '',
			'permalink'     => get_permalink( absint( $product_data['id'] ) ),
		);
	}
}
?>

<div class="fhs-configurator"
	data-product-id="<?php echo absint( $configurator_product->get_id() ); ?>"
	data-section-labels="<?php echo esc_attr( wp_json_encode( $section_labels ) ); ?>"
	data-product-map="<?php echo esc_attr( wp_json_encode( $product_map ) ); ?>"
	data-base-product="<?php echo esc_attr( wp_json_encode( $base_product ) ); ?>"
	data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
	data-cart-url="<?php echo esc_url( wc_get_cart_url() ); ?>"
	data-cart-nonce="<?php echo esc_attr( wp_create_nonce( 'fhs_configurator_add_all_to_cart' ) ); ?>">

	<div class="fhs-configurator__main">

		<!-- Intro header bar -->
		<div class="fhs-configurator__intro-bar">
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

		<!-- Mobile section dropdown -->
		<div class="fhs-configurator__tab-dropdown" aria-label="<?php esc_attr_e( 'Select section', 'woocommerce' ); ?>">
			<button type="button" class="fhs-configurator__tab-dropdown-trigger" aria-haspopup="listbox" aria-expanded="false">
				<span class="fhs-configurator__tab-dropdown-icon">
					<i class="icofont <?php echo esc_attr( $section_icons[ $first_key ] ?? 'icofont-box' ); ?>" aria-hidden="true"></i>
				</span>
				<span class="fhs-configurator__tab-dropdown-label"><?php echo esc_html( $sections[0]['label'] ); ?></span>
				<svg class="fhs-configurator__tab-dropdown-chevron" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
					<path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
			<ul class="fhs-configurator__tab-dropdown-menu" role="listbox">
				<?php foreach ( $sections as $section ) :
					$icon_class = $section_icons[ $section['key'] ] ?? 'icofont-box';
					$is_first   = $section['key'] === $first_key;
				?>
					<li class="fhs-configurator__tab-dropdown-item<?php echo $is_first ? ' is-active' : ''; ?>"
						role="option"
						aria-selected="<?php echo $is_first ? 'true' : 'false'; ?>"
						data-section-key="<?php echo esc_attr( $section['key'] ); ?>"
						data-icon-class="<?php echo esc_attr( $icon_class ); ?>">
						<i class="icofont <?php echo esc_attr( $icon_class ); ?>" aria-hidden="true"></i>
						<?php echo esc_html( $section['label'] ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<!-- Tab navigation — sticky within the configurator -->
		<nav class="fhs-configurator__tabs fhs-configurator__tabs--sticky" role="tablist"
			aria-label="<?php esc_attr_e( 'Configurator sections', 'woocommerce' ); ?>">
			<?php foreach ( $sections as $section ) :
				$is_first   = $section['key'] === $first_key;
				$icon_class = $section_icons[ $section['key'] ] ?? 'icofont-box';
			?>
				<button
					class="fhs-configurator__tab<?php echo $is_first ? ' is-active' : ''; ?>"
					role="tab"
					data-section-key="<?php echo esc_attr( $section['key'] ); ?>"
					aria-selected="<?php echo $is_first ? 'true' : 'false'; ?>"
					aria-controls="fhs-conf-panel-<?php echo esc_attr( $section['key'] ); ?>"
					id="fhs-conf-tab-<?php echo esc_attr( $section['key'] ); ?>"
					type="button">
					<i class="icofont <?php echo esc_attr( $icon_class ); ?>" aria-hidden="true"></i>
					<?php echo esc_html( $section['label'] ); ?>
				</button>
			<?php endforeach; ?>
		</nav>

		<!-- Section panels -->
		<?php foreach ( $sections as $section ) :
			$is_first    = $section['key'] === $first_key;
			$is_machine  = $section['key'] === 'machine_packages';
			$is_multiple = $section['selection_type'] === 'multiple';
			$input_name  = 'fhs_configurator_' . $section['key'];
			$cols        = 4; // grid columns — used to calculate 1-row cutoff
		?>
			<div
				class="fhs-configurator__panel<?php echo $is_first ? ' is-active' : ''; ?>"
				id="fhs-conf-panel-<?php echo esc_attr( $section['key'] ); ?>"
				role="tabpanel"
				aria-labelledby="fhs-conf-tab-<?php echo esc_attr( $section['key'] ); ?>"
				data-section-key="<?php echo esc_attr( $section['key'] ); ?>"
				data-selection-type="<?php echo esc_attr( $section['selection_type'] ); ?>"
				<?php if ( ! $is_first ) : ?>hidden<?php endif; ?>>

				<!-- Panel heading -->
				<div class="fhs-configurator__panel-header">
					<h3 class="fhs-configurator__panel-heading">
						<?php echo esc_html( $section['label'] ); ?>
						<span class="fhs-configurator__optional-badge">
							<?php esc_html_e( '(optional)', 'woocommerce' ); ?>
						</span>
						<?php if ( ! empty( $section_tooltips[ $section['key'] ] ) ) : ?>
							<span class="fhs-configurator__tooltip" aria-label="<?php echo esc_attr( $section_tooltips[ $section['key'] ] ); ?>" role="tooltip" tabindex="0">
								<i class="icofont icofont-question-circle fhs-configurator__tooltip-icon" aria-hidden="true"></i>
								<span class="fhs-configurator__tooltip-bubble" role="presentation">
									<?php echo esc_html( $section_tooltips[ $section['key'] ] ); ?>
								</span>
							</span>
						<?php endif; ?>
						<span class="fhs-configurator__section-status" data-section-status="<?php echo esc_attr( $section['key'] ); ?>" aria-live="polite"></span>
					</h3>
					<?php if ( $is_multiple ) : ?>
						<button type="button" class="fhs-configurator__select-all"
							data-section-key="<?php echo esc_attr( $section['key'] ); ?>"
							aria-label="<?php printf( esc_attr__( 'Select all %s', 'woocommerce' ), esc_attr( $section['label'] ) ); ?>">
							<?php esc_html_e( 'Select all', 'woocommerce' ); ?>
						</button>
					<?php endif; ?>
				</div>

				<!--
				  Grid wrapper — data-cols tells JS how many cards per row.
				  Cards beyond the first row are hidden by default; a "View more"
				  button reveals them.
				-->
				<div class="fhs-configurator__grid-wrap"
					data-cols="<?php echo esc_attr( $cols ); ?>"
					data-section-key="<?php echo esc_attr( $section['key'] ); ?>">

					<div class="fhs-configurator__grid<?php echo $is_machine ? ' fhs-configurator__grid--machine' : ' fhs-configurator__grid--standard'; ?>">

						<?php
						// Base product card for machine packages.
						if ( $is_machine ) :
							$base_input_id = 'fhs-conf-input-' . $section['key'] . '-' . absint( $base_product['id'] );
						?>
							<label
								class="fhs-configurator__card fhs-configurator__card--machine fhs-configurator__card--base-product"
								data-product-id="<?php echo absint( $base_product['id'] ); ?>"
								data-section-key="<?php echo esc_attr( $section['key'] ); ?>"
								for="<?php echo esc_attr( $base_input_id ); ?>">

								<input type="radio"
									id="<?php echo esc_attr( $base_input_id ); ?>"
									name="<?php echo esc_attr( $input_name ); ?>"
									value="<?php echo absint( $base_product['id'] ); ?>"
									class="fhs-configurator__card-input"
									data-product-id="<?php echo absint( $base_product['id'] ); ?>"
									data-section-key="<?php echo esc_attr( $section['key'] ); ?>" />

								<span class="fhs-configurator__standard-badge">
									<?php esc_html_e( 'Standard', 'woocommerce' ); ?>
								</span>

								<div class="fhs-configurator__card-img-wrap">
									<?php if ( ! empty( $base_product['image_url'] ) ) : ?>
										<img src="<?php echo esc_url( $base_product['image_url'] ); ?>"
											alt="<?php echo esc_attr( $base_product['name'] ); ?>"
											class="fhs-configurator__card-img" loading="lazy" />
									<?php endif; ?>
								</div>

								<div class="fhs-configurator__card-body">
									<?php if ( ! empty( $base_product['permalink'] ) ) : ?>
										<a class="fhs-configurator__card-name-link"
											href="<?php echo esc_url( $base_product['permalink'] ); ?>"
											target="_blank" rel="noopener"
											onclick="event.stopPropagation();">
											<p class="fhs-configurator__card-name"><?php echo esc_html( $base_product['name'] ); ?></p>
										</a>
									<?php else : ?>
										<p class="fhs-configurator__card-name"><?php echo esc_html( $base_product['name'] ); ?></p>
									<?php endif; ?>

									<?php if ( ! empty( $base_product['sku'] ) ) : ?>
										<p class="fhs-configurator__card-sku"><?php echo esc_html( $base_product['sku'] ); ?></p>
									<?php endif; ?>

									<?php if ( ! empty( $base_product['price_html'] ) ) : ?>
										<div class="fhs-configurator__card-price"><?php echo wp_kses_post( $base_product['price_html'] ); ?></div>
									<?php endif; ?>

									<!-- Quantity stepper for base product card -->
									<div class="fhs-configurator__card-qty-wrap">
										<span role="button" tabindex="0" class="fhs-conf-qty-minus" aria-label="<?php esc_attr_e( 'Decrease', 'woocommerce' ); ?>">−</span>
										<input type="number"
											class="fhs-conf-qty-input"
											value="1" min="1"
											data-product-id="<?php echo absint( $base_product['id'] ); ?>"
											aria-label="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>" />
										<span role="button" tabindex="0" class="fhs-conf-qty-plus" aria-label="<?php esc_attr_e( 'Increase', 'woocommerce' ); ?>">+</span>
									</div>
								</div>
							</label>
						<?php endif; ?>

						<?php foreach ( $section['products'] as $product_data ) :
							$product_id    = absint( $product_data['id'] );
							$input_type    = ( $is_machine || ! $is_multiple ) ? 'radio' : 'checkbox';
							$input_id      = 'fhs-conf-input-' . $section['key'] . '-' . $product_id;
							$product_link  = get_permalink( $product_id );
						?>
							<label
								class="fhs-configurator__card<?php echo $is_machine ? ' fhs-configurator__card--machine' : ' fhs-configurator__card--standard'; ?>"
								data-product-id="<?php echo $product_id; ?>"
								data-section-key="<?php echo esc_attr( $section['key'] ); ?>"
								for="<?php echo esc_attr( $input_id ); ?>">

								<?php if ( 'radio' === $input_type ) : ?>
									<input type="radio"
										id="<?php echo esc_attr( $input_id ); ?>"
										name="<?php echo esc_attr( $input_name ); ?>"
										value="<?php echo $product_id; ?>"
										class="fhs-configurator__card-input"
										data-product-id="<?php echo $product_id; ?>"
										data-section-key="<?php echo esc_attr( $section['key'] ); ?>" />
								<?php else : ?>
									<input type="checkbox"
										id="<?php echo esc_attr( $input_id ); ?>"
										name="<?php echo esc_attr( $input_name ); ?>[]"
										value="<?php echo $product_id; ?>"
										class="fhs-configurator__card-input"
										data-product-id="<?php echo $product_id; ?>"
										data-section-key="<?php echo esc_attr( $section['key'] ); ?>" />
								<?php endif; ?>

								<div class="fhs-configurator__card-img-wrap">
									<img src="<?php echo esc_url( $product_data['image_url'] ); ?>"
										alt="<?php echo esc_attr( $product_data['name'] ); ?>"
										class="fhs-configurator__card-img" loading="lazy" />
								</div>

								<div class="fhs-configurator__card-body">
									<?php if ( $product_link ) : ?>
										<a class="fhs-configurator__card-name-link"
											href="<?php echo esc_url( $product_link ); ?>"
											target="_blank" rel="noopener"
											onclick="event.stopPropagation();">
											<p class="fhs-configurator__card-name"><?php echo esc_html( $product_data['name'] ); ?></p>
										</a>
									<?php else : ?>
										<p class="fhs-configurator__card-name"><?php echo esc_html( $product_data['name'] ); ?></p>
									<?php endif; ?>

									<?php if ( ! empty( $product_data['sku'] ) ) : ?>
										<p class="fhs-configurator__card-sku"><?php echo esc_html( $product_data['sku'] ); ?></p>
									<?php endif; ?>

									<?php if ( ! empty( $product_data['price_html'] ) ) : ?>
										<div class="fhs-configurator__card-price"><?php echo wp_kses_post( $product_data['price_html'] ); ?></div>
									<?php endif; ?>

									<!-- Quantity stepper -->
									<div class="fhs-configurator__card-qty-wrap">
										<span role="button" tabindex="0" class="fhs-conf-qty-minus" aria-label="<?php esc_attr_e( 'Decrease', 'woocommerce' ); ?>">−</span>
										<input type="number"
											class="fhs-conf-qty-input"
											value="1" min="1"
											data-product-id="<?php echo $product_id; ?>"
											aria-label="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>" />
										<span role="button" tabindex="0" class="fhs-conf-qty-plus" aria-label="<?php esc_attr_e( 'Increase', 'woocommerce' ); ?>">+</span>
									</div>
								</div>
							</label>
						<?php endforeach; ?>

					</div><!-- /.fhs-configurator__grid -->

					<!-- View more toggle — rendered by JS after grid init -->
					<div class="fhs-configurator__view-more" data-section-key="<?php echo esc_attr( $section['key'] ); ?>" style="display:none;">
						<button type="button" class="fhs-configurator__view-more-btn" aria-expanded="false">
							<span class="fhs-configurator__view-more-label"><?php esc_html_e( 'View more', 'woocommerce' ); ?></span>
							<svg class="fhs-configurator__view-more-arrow" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
								<path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</button>
					</div>

				</div><!-- /.fhs-configurator__grid-wrap -->

			</div><!-- /.fhs-configurator__panel -->
		<?php endforeach; ?>

	</div><!-- /.fhs-configurator__main -->

</div><!-- /.fhs-configurator -->
