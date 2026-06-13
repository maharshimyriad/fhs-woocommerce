<?php
/**
 * Single Template for Pipe Fitting Sheet CPT
 */
get_header();
?>

<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

<div class="pipe-fitting-wrapper single-custom-container">

    <!-- Breadcrumb -->
    <div class="custom-breadcrumbs">
        <a href="<?php echo home_url(); ?>">Home</a> >
        <a href="<?php echo get_post_type_archive_link('pipe-fitting-sheet'); ?>">
            Pipe Fitting Sheets
        </a> >
        <span><?php the_title(); ?></span>
    </div>

    <div class="pipe-fitting-container">

        <!-- LEFT IMAGE -->
        <div class="pipe-fitting-image">
            <?php 
            $image = get_field('product_image');

            if ( $image ) {

                // If Image Return Format = Array
                if ( is_array($image) ) {
                    echo '<img src="'. esc_url($image['url']) .'" alt="'. esc_attr($image['alt']) .'">';
                } 
                // If Return Format = ID
                else {
                    echo wp_get_attachment_image($image, 'large');
                }
            }
            ?>
        </div>

        <!-- RIGHT CONTENT -->
        <div class="pipe-fitting-content">
            
            <h1 class="pipe-fitting-title">
                <?php 
                $acf_title = get_field('product_title');
                echo $acf_title ? esc_html($acf_title) : get_the_title();
                ?>
            </h1>

            <div class="pipe-fitting-description">
                <?php 
                $description = get_field('product_description');
                if($description){
                    echo $description;
                } else {
                    the_content(); // fallback to WP editor
                }
                ?>
            </div>

        </div>

    </div>

</div>

<?php endwhile; endif; ?>

<?php get_footer(); ?>
