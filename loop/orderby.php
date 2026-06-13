<?php
/**
 * Show options for ordering
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/orderby.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     9.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$id_suffix = wp_unique_id();

?>
<form class="woocommerce-ordering" method="get">
	
		<label for="woocommerce-orderby-<?php echo esc_attr( $id_suffix ); ?>"><?php echo esc_html__( 'Sort by:', 'woocommerce' ); ?></label>
	
	<div class="dropdown-container">
		<div
			class="dropdown-selected"
			<?php if ( $use_label ) : ?>
				id="woocommerce-orderby-<?php echo esc_attr( $id_suffix ); ?>"
			<?php else : ?>
				aria-label="<?php esc_attr_e( 'Shop order', 'woocommerce' ); ?>"
			<?php endif; ?>
			role="combobox"
			aria-haspopup="listbox"
			aria-expanded="false"
			tabindex="0"
		>
			<span class="selected-text">
				<?php
				
				$selected_name = isset( $catalog_orderby_options[$orderby] ) ? esc_html( $catalog_orderby_options[$orderby] ) : esc_html__( 'Select an option', 'woocommerce' );
				echo $selected_name;
				?>
			</span>
			<i class="icofont-rounded-down"></i>
		</div>
		<ul class="orderby dropdown-list" role="listbox" hidden>
			<?php foreach ( $catalog_orderby_options as $id => $name ) : ?>
				<li
					role="option"
					data-value="<?php echo esc_attr( $id ); ?>"
					<?php if ( $orderby === $id ) : ?>
						aria-selected="true"
						class="selected"
					<?php else : ?>
						aria-selected="false"
					<?php endif; ?>
				>
					<?php echo esc_html( $name ); ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<input type="hidden" name="orderby" value="<?php echo esc_attr( $orderby ); ?>" />
	<input type="hidden" name="paged" value="1" />
	<?php wc_query_string_form_fields( null, array( 'orderby', 'submit', 'paged', 'product-page' ) ); ?>
</form>
<style>
.woocommerce-ordering .dropdown-container {
	position: relative;
	width: fit-content;
}
.woocommerce-ordering .dropdown-selected {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding:5px 10px;
	/*border: 1px solid #ccc;*/
	background: #fff;
	cursor: pointer;
	border-radius: 4px 4px 0 0;
	gap:5px;
}
.woocommerce-ordering .dropdown-selected:focus {
	/*outline: 2px solid #007cba;*/
}
.woocommerce-ordering .dropdown-selected .selected-text {
	flex-grow: 1;
	font-size:1.5rem;
	font-weight: 600;
}
.woocommerce-ordering .dropdown-selected .icofont-rounded-down {
	font-size: 1.3rem;
}
.woocommerce-ordering .dropdown-list {
	position: absolute;
	top: 100%;
	left: 0;
	width: 100%;
	border:1px solid #231f206e;
	background: #fff;
	list-style: none;
	margin: 0;
	padding: 0;
	z-index: 10;
	border-radius: 4px;
	/*max-height: 200px;*/
	overflow-y: auto;
}
.woocommerce-ordering .dropdown-list[hidden] {
	display: none;
}
.woocommerce-ordering .dropdown-list li {
	padding: 0 10px;
	cursor: pointer;
	font-size: 1.5rem;
}
.woocommerce-ordering .dropdown-list li:hover,
.woocommerce-ordering .dropdown-list li:focus {
	background: #E3E9FF;
}
.woocommerce-ordering .dropdown-list li.selected {
	background: #E3E9FF;
}

.woocommerce-ordering .dropdown-selected[aria-expanded="false"]{
    border:1px solid transparent; 
}

.woocommerce-ordering .dropdown-selected[aria-expanded="true"]{
    border-left: 1px solid #231f206e;
    border-right: 1px solid #231f206e;
    border-top: 1px solid #231f206e;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
	const dropdownContainers = document.querySelectorAll('.woocommerce-ordering .dropdown-container');
	dropdownContainers.forEach(container => {
		const selected = container.querySelector('.dropdown-selected');
		const selectedText = selected.querySelector('.selected-text');
		const list = container.querySelector('.dropdown-list');
		const hiddenInput = container.closest('form').querySelector('input[name="orderby"]');

		
		selected.addEventListener('click', () => {
			const isHidden = list.hasAttribute('hidden');
			list.toggleAttribute('hidden', !isHidden);
			selected.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
		});

		
		selected.addEventListener('keydown', (e) => {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				list.toggleAttribute('hidden');
				selected.setAttribute('aria-expanded', list.hasAttribute('hidden') ? 'false' : 'true');
			}
		});

		
		list.addEventListener('click', (e) => {
			const li = e.target.closest('li');
			if (li) {
				const value = li.getAttribute('data-value');
				const text = li.textContent.trim();
				hiddenInput.value = value;
				selectedText.textContent = text;
				list.querySelectorAll('li').forEach(item => {
					item.setAttribute('aria-selected', 'false');
					item.classList.remove('selected');
				});
				li.setAttribute('aria-selected', 'true');
				li.classList.add('selected');
				list.setAttribute('hidden', '');
				selected.setAttribute('aria-expanded', 'false');
				container.closest('form').submit();
			}
		});

		
		document.addEventListener('click', (e) => {
			if (!container.contains(e.target)) {
				list.setAttribute('hidden', '');
				selected.setAttribute('aria-expanded', 'false');
			}
		});
	});
});
</script>