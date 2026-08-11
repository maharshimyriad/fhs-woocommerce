<?php

/**
 * Product Configurator — Main left-column layout
 *
 * Loaded by fhs_render_configurator() via wc_get_template() when
 * fhs_configurator_is_active() returns true for the current product.
 *
 * Fires on fhs_inside_product_main_container — after .single-product-layout-wrap
 * closes — so the configurator main area renders below the image + summary row
 * inside the left column of the configurator-enabled product layout.
 *
 * Available variables (passed via wc_get_template):
 *   $configurator_product  WC_Product  Base product for this configurator.
 *   $sections              array[]     Output of fhs_configurator_get_sections().
 *     Each section:
 *       'key'            string   e.g. 'machine_packages'
 *       'label'          string   Human-readable heading
 *       'selection_type' string   'single' | 'multiple'
 *       'products'       array[]  Each: id, name, sku, image_url, price_html,
 *                                 price_value, price_display
 *
 * @package FHS_WOO
 * @version 3.1.0
 */

defined('ABSPATH') || exit;

if (empty($sections)) {
	return;
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

$section_labels = array();
$product_map    = array();
$base_product   = array(
	'id'            => absint($configurator_product->get_id()),
	'section_key'   => 'base_product',
	'section_label' => __('Base Product', 'woocommerce'),
	'name'          => $configurator_product->get_name(),
	'sku'           => $configurator_product->get_sku(),
	'image_url'     => wp_get_attachment_image_url($configurator_product->get_image_id(), 'woocommerce_thumbnail'),
	'price_html'    => '',
	'price_value'   => 0,
	'price_display' => '',
);

$base_product_data = fhs_configurator_get_product_data($configurator_product->get_id());
if (! empty($base_product_data)) {
	$base_product['image_url']     = $base_product_data['image_url'];
	$base_product['price_html']    = isset($base_product_data['price_html']) ? $base_product_data['price_html'] : '';
	$base_product['price_value']   = isset($base_product_data['price_value']) ? (float) $base_product_data['price_value'] : 0;
	$base_product['price_display'] = isset($base_product_data['price_display']) ? $base_product_data['price_display'] : '';
}

foreach ($sections as $section) {
	$section_labels[$section['key']] = $section['label'];

	foreach ($section['products'] as $product_data) {
		$product_map[(string) $product_data['id']] = array(
			'id'            => absint($product_data['id']),
			'section_key'   => $section['key'],
			'section_label' => $section['label'],
			'name'          => $product_data['name'],
			'sku'           => $product_data['sku'],
			'image_url'     => $product_data['image_url'],
			'price_html'    => isset($product_data['price_html']) ? $product_data['price_html'] : '',
			'price_value'   => isset($product_data['price_value']) ? (float) $product_data['price_value'] : 0,
			'price_display' => isset($product_data['price_display']) ? $product_data['price_display'] : '',
		);
	}
}
?>

<div class="fhs-configurator"
	data-product-id="<?php echo absint($configurator_product->get_id()); ?>"
	data-section-labels="<?php echo esc_attr(wp_json_encode($section_labels)); ?>"
	data-product-map="<?php echo esc_attr(wp_json_encode($product_map)); ?>"
	data-base-product="<?php echo esc_attr(wp_json_encode($base_product)); ?>"
	data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
	data-cart-url="<?php echo esc_url(wc_get_cart_url()); ?>"
	data-cart-nonce="<?php echo esc_attr(wp_create_nonce('fhs_configurator_add_all_to_cart')); ?>">

	<div class="fhs-configurator__main">

		<!-- Intro header bar -->
		<div class="fhs-configurator__intro-bar">
			<span class="fhs-configurator__intro-text">
				<?php esc_html_e('Choose from any of the sections below (all optional)', 'woocommerce'); ?>
			</span>
			<span class="fhs-configurator__intro-help">
				<?php esc_html_e('Need help?', 'woocommerce'); ?>
				<a href="/contact" class="fhs-configurator__intro-help-link">
					<?php esc_html_e('Contact our team', 'woocommerce'); ?>
				</a>
			</span>
		</div>

		<!-- ── Tab navigation ───────────────────────────────────────── -->
		<nav class="fhs-configurator__tabs" role="tablist"
			aria-label="<?php esc_attr_e('Configurator sections', 'woocommerce'); ?>">
			<?php foreach ($sections as $section) :
				$is_first   = $section['key'] === $first_key;
				$icon_class = isset($section_icons[$section['key']]) ? $section_icons[$section['key']] : 'icofont-box';
			?>
				<button
					class="fhs-configurator__tab<?php echo $is_first ? ' is-active' : ''; ?>"
					role="tab"
					data-section-key="<?php echo esc_attr($section['key']); ?>"
					aria-selected="<?php echo $is_first ? 'true' : 'false'; ?>"
					aria-controls="fhs-conf-panel-<?php echo esc_attr($section['key']); ?>"
					id="fhs-conf-tab-<?php echo esc_attr($section['key']); ?>"
					type="button">
					<i class="icofont <?php echo esc_attr($icon_class); ?>" aria-hidden="true"></i>
					<?php echo esc_html($section['label']); ?>
				</button>
			<?php endforeach; ?>
		</nav>

		<!-- ── Section panels ───────────────────────────────────────── -->
		<?php foreach ($sections as $section) :
			$is_first    = $section['key'] === $first_key;
			$is_machine  = $section['key'] === 'machine_packages';
			$is_multiple = $section['selection_type'] === 'multiple';
			$input_name  = 'fhs_configurator_' . $section['key'];
		?>
			<div
				class="fhs-configurator__panel<?php echo $is_first ? ' is-active' : ''; ?>"
				id="fhs-conf-panel-<?php echo esc_attr($section['key']); ?>"
				role="tabpanel"
				aria-labelledby="fhs-conf-tab-<?php echo esc_attr($section['key']); ?>"
				data-section-key="<?php echo esc_attr($section['key']); ?>"
				data-selection-type="<?php echo esc_attr($section['selection_type']); ?>"
				<?php if (! $is_first) : ?>hidden<?php endif; ?>>

				<div class="fhs-configurator__panel-header">
					<h3 class="fhs-configurator__panel-heading">
						<?php echo esc_html($section['label']); ?>
						<span class="fhs-configurator__optional-badge">
							<?php esc_html_e('optional', 'woocommerce'); ?>
						</span>
					</h3>

					<?php if ($is_multiple) : ?>
						<button
							type="button"
							class="fhs-configurator__select-all"
							data-section-key="<?php echo esc_attr($section['key']); ?>"
							aria-label="<?php
										printf(
											esc_attr__('Select all %s', 'woocommerce'),
											esc_attr($section['label'])
										);
										?>"><?php esc_html_e('Select all', 'woocommerce'); ?></button>
					<?php endif; ?>
				</div><!-- /.fhs-configurator__panel-header -->

				<div class="fhs-configurator__grid<?php echo $is_machine ? ' fhs-configurator__grid--machine' : ' fhs-configurator__grid--standard'; ?>">
					<?php
					// For the machine packages section, prepend the base product as a
					// "Standard" card. It is NOT pre-selected — the user must actively choose it.
					if ($is_machine) :
						$base_input_id = 'fhs-conf-input-' . $section['key'] . '-' . absint($base_product['id']);
					?>
						<label
							class="fhs-configurator__card fhs-configurator__card--machine fhs-configurator__card--base-product"
							data-product-id="<?php echo absint($base_product['id']); ?>"
							data-section-key="<?php echo esc_attr($section['key']); ?>"
							for="<?php echo esc_attr($base_input_id); ?>">

							<input
								type="radio"
								id="<?php echo esc_attr($base_input_id); ?>"
								name="<?php echo esc_attr($input_name); ?>"
								value="<?php echo absint($base_product['id']); ?>"
								class="fhs-configurator__card-input"
								data-product-id="<?php echo absint($base_product['id']); ?>"
								data-section-key="<?php echo esc_attr($section['key']); ?>" />

							<span class="fhs-configurator__standard-badge">
								<?php esc_html_e('Standard', 'woocommerce'); ?>
							</span>

							<div class="fhs-configurator__card-img-wrap">
								<?php if (! empty($base_product['image_url'])) : ?>
									<img
										src="<?php echo esc_url($base_product['image_url']); ?>"
										alt="<?php echo esc_attr($base_product['name']); ?>"
										class="fhs-configurator__card-img"
										loading="lazy" />
								<?php endif; ?>
							</div>

							<div class="fhs-configurator__card-body">
								<p class="fhs-configurator__card-name">
									<?php echo esc_html($base_product['name']); ?>
								</p>
								<?php if (! empty($base_product['sku'])) : ?>
									<p class="fhs-configurator__card-sku">
										<?php echo esc_html($base_product['sku']); ?>
									</p>
								<?php endif; ?>
								<?php if (! empty($base_product['price_html'])) : ?>
									<div class="fhs-configurator__card-price"><?php echo wp_kses_post($base_product['price_html']); ?></div>
								<?php endif; ?>
							</div>
						</label><!-- /.fhs-configurator__card--base-product -->
					<?php endif; ?>

					<?php foreach ($section['products'] as $product_data) :
						$product_id = absint($product_data['id']);
						$input_type = $is_machine || ! $is_multiple ? 'radio' : 'checkbox';
						$input_id   = 'fhs-conf-input-' . $section['key'] . '-' . $product_id;
					?>
						<label
							class="fhs-configurator__card<?php echo $is_machine ? ' fhs-configurator__card--machine' : ' fhs-configurator__card--standard'; ?>"
							data-product-id="<?php echo $product_id; ?>"
							data-section-key="<?php echo esc_attr($section['key']); ?>"
							for="<?php echo esc_attr($input_id); ?>">
							<?php if ('radio' === $input_type) : ?>
								<input
									type="radio"
									id="<?php echo esc_attr($input_id); ?>"
									name="<?php echo esc_attr($input_name); ?>"
									value="<?php echo $product_id; ?>"
									class="fhs-configurator__card-input"
									data-product-id="<?php echo $product_id; ?>"
									data-section-key="<?php echo esc_attr($section['key']); ?>" />
							<?php else : ?>
								<input
									type="checkbox"
									id="<?php echo esc_attr($input_id); ?>"
									name="<?php echo esc_attr($input_name); ?>[]"
									value="<?php echo $product_id; ?>"
									class="fhs-configurator__card-input"
									data-product-id="<?php echo $product_id; ?>"
									data-section-key="<?php echo esc_attr($section['key']); ?>" />
							<?php endif; ?>

							<div class="fhs-configurator__card-img-wrap">
								<img
									src="<?php echo esc_url($product_data['image_url']); ?>"
									alt="<?php echo esc_attr($product_data['name']); ?>"
									class="fhs-configurator__card-img"
									loading="lazy" />
							</div>

							<div class="fhs-configurator__card-body">
								<p class="fhs-configurator__card-name">
									<?php echo esc_html($product_data['name']); ?>
								</p>
								<?php if (! empty($product_data['sku'])) : ?>
									<p class="fhs-configurator__card-sku">
										<?php echo esc_html($product_data['sku']); ?>
									</p>
								<?php endif; ?>
								<?php if (! empty($product_data['price_html'])) : ?>
									<div class="fhs-configurator__card-price"><?php echo wp_kses_post($product_data['price_html']); ?></div>
								<?php endif; ?>
							</div>
						</label><!-- /.fhs-configurator__card -->
					<?php endforeach; ?>
				</div><!-- /.fhs-configurator__grid -->

				<div class="fhs-configurator__panel-actions">
					<button
						type="button"
						class="fhs-configurator__commit-section"
						data-section-key="<?php echo esc_attr($section['key']); ?>">
						<?php esc_html_e('Add to Configuration', 'woocommerce'); ?>
					</button>
				</div>
			</div><!-- /.fhs-configurator__panel -->
		<?php endforeach; ?>

	</div><!-- /.fhs-configurator__main -->

</div><!-- /.fhs-configurator -->