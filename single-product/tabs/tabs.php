<?php
/**
 * Single Product Tabs
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/tabs/tabs.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter tabs and allow third parties to add their own.
 *
 * Each tab is an array containing title, callback, and priority.
 *
 * @see woocommerce_default_product_tabs()
 */
$product_tabs = apply_filters( 'woocommerce_product_tabs', array() );

if ( ! empty( $product_tabs ) ) : ?>

	<div class="woocommerce-tabs wc-tabs-wrapper">
		<ul class="tabs wc-tabs" role="tablist">
			<?php foreach ( $product_tabs as $key => $product_tab ) : ?>
				<li class="<?php echo esc_attr( $key ); ?>_tab" id="tab-title-<?php echo esc_attr( $key ); ?>">
					<a href="#tab-<?php echo esc_attr( $key ); ?>" role="tab" aria-controls="tab-<?php echo esc_attr( $key ); ?>">
						<?php echo wp_kses_post( apply_filters( 'woocommerce_product_' . $key . '_tab_title', $product_tab['title'], $key ) ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php foreach ( $product_tabs as $key => $product_tab ) : ?>
			<div class="woocommerce-Tabs-panel woocommerce-Tabs-panel--<?php echo esc_attr( $key ); ?> panel entry-content wc-tab" id="tab-<?php echo esc_attr( $key ); ?>" role="tabpanel" aria-labelledby="tab-title-<?php echo esc_attr( $key ); ?>">
				<?php
				if ( isset( $product_tab['callback'] ) ) {
					call_user_func( $product_tab['callback'], $key, $product_tab );
				}
				?>
			</div>
		<?php endforeach; ?>
		<?php do_action( 'woocommerce_product_after_tabs' ); ?>
	</div>

<?php endif; ?>


<?php
$post_id = isset($post_id) ? $post_id : get_the_ID();
$show_specs = get_field('showhide_product_specifications', $post_id);
$tabs_class = 'tabs-main-container ' . ($show_specs ? 'show-tabs' : 'hide-tabs');
?>
<div class="<?php echo esc_attr($tabs_class); ?>">

	

    <div class="custom-ms-tab mobile-hidden product-main-container">
        <div class="custom-ms-nav-item" data-tab="custom-ms-specs">
            <div><i class="icofont icofont-file-alt"></i></div>Product Specifications
        </div>
        <div class="custom-ms-nav-item" data-tab="custom-ms-desc">
            <div><i class="icofont icofont-file-alt"></i></div>Product Description
        </div>
        <div class="custom-ms-nav-item" data-tab="custom-ms-video">
            <div><i class="icofont-ui-video-chat"></i></div>Product Video
        </div>
        <div class="custom-ms-nav-item" data-tab="custom-ms-downloads">
            <div><i class="icofont-download"></i></div>Documents & Downloads
        </div>
        <div class="custom-ms-nav-item" data-tab="custom-ms-faq">
            <div><i class="icofont-question-circle"></i></div>Product FAQ
        </div>
    </div>

    <div class="custom-ms-tab mobile product-main-container">
        <select class="custom-ms-dropdown" onchange="changeTab(this)">
            <option data-tab="custom-ms-specs" selected>Product Specifications</option>
            <option data-tab="custom-ms-desc">Product Description</option>
            <option data-tab="custom-ms-video">Product Video</option>
            <option data-tab="custom-ms-downloads">Documents & Downloads</option>
            <option data-tab="custom-ms-faq">Product FAQ</option>
        </select>
    </div>

    <div class="custom-ms-container product-main-container">
 <div class="custom-ms-specs parent-tab-container" id="custom-ms-specs">
    
<!--         <tbody>
            <?php if( have_rows('product_specs') ): ?>
                <?php while( have_rows('product_specs') ): the_row(); 
                    $key   = get_sub_field('specs_key');
                    $value = get_sub_field('specs_value');
                ?>
                    <tr>
                        <td style="border-bottom: 1px solid #ddd; border-right: none; font-weight: bold; padding-top: 10px;">
                            <?php echo esc_html($key); ?>:
                        </td>
                        <td style="border-bottom: 1px solid #ddd; border-right: none; text-align: right;">
                            <?php echo esc_html($value) ?: '-'; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2" style="text-align: center; padding: 15px;">No specifications available.</td>
                </tr>
            <?php endif; ?>
        </tbody> -->
		<div class="product-hide-spec">
  <summary class="product-specs__summary">
    <i class="fa-solid fa-file-lines"></i>
    Product Specifications
  </summary>

  <div class="product-specs__content">
<!-- 	  <div class="spec-row">
      <span>Product Specs</span>
      <span><?php echo get_field('product_specifications') ?: '-'; ?></span>
    </div> -->
    <div class="spec-row">
      <span>Sizes</span>
      <span><?php echo get_field('product_specs_size') ?: '-'; ?></span>
    </div>

    <div class="spec-row">
      <span>SDR Range</span>
      <span><?php echo get_field('product_specs_sdr_range') ?: '-'; ?></span>
    </div>

    <div class="spec-row">
      <span>Pressure Rating</span>
      <span><?php echo get_field('product_specs_pressure_rating') ?: '-'; ?></span>
    </div>
<div class="spec-row">
            <span>SKU:</span>
            <span>
                <?php echo esc_html( get_post_meta(get_the_ID(), '_sku', true) ?: '-' ); ?>
            </span>
        </div>
    <div class="spec-row">
      <span>Width</span>
      <span><?php echo get_field('product_specs_width') ?: '-'; ?></span>
    </div>

    <div class="spec-row">
      <span>Height</span>
      <span><?php echo get_field('product_specs_height') ?: '-'; ?></span>
    </div>

    <div class="spec-row">
      <span>Weight</span>
      <span><?php echo get_field('product_specs_weight') ?: '-'; ?></span>
    </div>
  </div>
</div>
<style>/* Wrapper */
.product-hide-spec {
  width: 100%;
  max-width: 100%;
  background: #fff;
  border-radius: 4px;
  border: 1px solid #e5e7eb;
/*   overflow: hidden; */
}

/* Summary (Header bar) */
.product-specs__summary {
  list-style: none;
  cursor: pointer;
  background: #0b4f6c; /* dark blue */
  color: #fff;
  padding: 14px 20px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 10px;
}

.product-specs__summary::-webkit-details-marker {
  display: none;
}

.product-specs__summary i {
  font-size: 16px;
}

/* Content */
.product-specs__content {
  padding: 0;
}

/* Rows */
.spec-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 20px;
  border-bottom: 1px solid #eef0f2;
  font-size: 14px;
}

.spec-row:last-child {
  border-bottom: none;
}

/* Left text */
.spec-row span:first-child {
  font-weight: 600;
  color: #1f2937;
}

/* Right value */
.spec-row span:last-child {
  color: #374151;
}

/* Optional hover effect */
.spec-row:hover {
  background: #f9fafb;
}

/* Open animation (optional) */
details[open] .product-specs__content {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-3px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
</div>


<!-- <div id="custom-ms-desc" class="custom-ms-desc parent-tab-container" style="text-align: center;padding: 10px 45px;">
    <?php echo apply_filters('woocommerce_short_description', get_post_field('post_excerpt', $post_id)); ?>
</div>
 --><?php
global $post, $product;

// Make sure $product is set
if ( ! $product ) {
    $product = wc_get_product( $post->ID );
}

// Get the full product description (from main editor)
$product_description = $product->get_description();
?>

<div id="custom-ms-desc" class="custom-ms-desc parent-tab-container" style="text-align: center; padding: 10px 45px;">
    <?php 
    // Output description safely
    echo wp_kses_post( $product_description ); 
    ?>
</div>


        <!-- Product Video -->
        <div class="custom-ms-video parent-tab-container" id="custom-ms-video">
            <span class="video-iframe">
				<?php
            $video_embed = get_field('product_video_1');
            if ($video_embed) {
                echo $video_embed;
            } else {
                echo 'Product video not available.';
            }
            ?>
			</span>
        </div>

        <!-- Product Downloads -->
        <div class="custom-ms-downloads parent-tab-container" id="custom-ms-downloads">
            <?php if (have_rows('download_file_product')) : ?>
                <div id="custom-downloads-grid" class="custom-downloads-grid">
                    <?php
                    while (have_rows('download_file_product')) : the_row();
                        $file_name = get_sub_field('file_product_name');
                        $file = get_sub_field('file_to_download');
                        $file_url = wp_get_attachment_url($file);

                        if ($file_url) : ?>
                            <ul class="download-tab">
                                <li class="pdf-icon">
                                    <a href="<?php echo esc_url($file_url); ?>" download>
                                        <img src="https://fhs.myriadsolutionz.com/wp-content/uploads/2025/04/Group-21869.svg" alt="PDF Icon">
                                    </a>
                                </li>
                                <span class="download-des">
                                    <a href="<?php echo esc_url($file_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($file_name); ?></a>
                                    <h5>
                                        <a href="<?php echo esc_url($file_url); ?>" target="_blank" rel="noopener noreferrer" class="view-doc">View Document</a>
                                        <span> | </span>
                                        <a href="<?php echo esc_url($file_url); ?>" class="view-doc" download="<?php echo esc_html($file_name);?>">Download</a>
                                    </h5>
                                </span>
                            </ul>
                        <?php endif;
                    endwhile;
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product FAQ -->
  <div class="custom-ms-faq parent-tab-container" id="custom-ms-faq">
    <?php if (have_rows('product_faq')) : ?>
        <div class="faq-accordion">
            <div class="faq-columns">
                <div class="left-column-faq">
                    <?php 
                    $counter = 1;
                    while (have_rows('product_faq')) : the_row();
                        $question = get_sub_field('question');
                        $answer = get_sub_field('answer');
                        if ($counter % 2 !== 0) : ?>
                            <div class="faq-item">
                                <button class="faq-question">
                                    <ul>
                                        <span class="faq-icon">+</span>
                                        <span><?php echo esc_html($question); ?></span>
                                    </ul>
                                </button>
                                <div class="faq-answer">
                                    <p><?php echo wp_kses_post($answer); ?></p>
                                </div>
                            </div>
                        <?php endif;
                        $counter++;
                    endwhile; ?>
                </div>
                <div class="right-column-faq">
                    <?php 
                    $counter = 1;
                    while (have_rows('product_faq')) : the_row();
                        $question = get_sub_field('question');
                        $answer = get_sub_field('answer');
                        if ($counter % 2 === 0) : ?>
                            <div class="faq-item">
                                <button class="faq-question">
                                    <ul>
                                        <span class="faq-icon">+</span>
                                        <span><?php echo esc_html($question); ?></span>
                                    </ul>
                                </button>
                                <div class="faq-answer">
                                    <p><?php echo wp_kses_post($answer); ?></p>
                                </div>
                            </div>
                        <?php endif;
                        $counter++;
                    endwhile; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
    </div>
	</div>

    <div style="margin-top:60px;"><?php echo do_shortcode('[elementor-template id="21142"]'); ?></div>
    <div class="about-us-container"><?php echo do_shortcode('[elementor-template id="14294"]'); ?></div>
    <div><?php echo do_shortcode('[elementor-template id="13889"]'); ?></div>


