# FHS WooCommerce — Project Context

This document describes the full architecture, file inventory, and change history
for the FHS WooCommerce template override repository.

---

## Repository Overview

**Repo path (local):** `C:\Users\admin\Desktop\fhs-woo\fhs-woocommerce`  
**Live deployment path:** `/home/fhsbfeaturedcom/public_html/wp-content/themes/astra-child/woocommerce/`  
**Purpose:** WooCommerce template overrides for the FHS Poly child theme (Astra-based).

This repo contains **only** the `woocommerce/` subfolder of the child theme.
It is not a standalone plugin. The child theme `functions.php` (on the live server)
includes this code via:

```php
require_once get_stylesheet_directory() . '/woocommerce/fhs-tier-pricing-init.php';
require_once get_stylesheet_directory() . '/woocommerce/fhs-configurator.php';
```

---

## Environment

| Item | Value |
|---|---|
| Theme | Astra (parent) + astra-child |
| Astra version | ≥ 3.9.2 (confirmed — uses `load_theme_side_woocommerce_strcture()`) |
| ACF | Active. Fields managed in WordPress admin, NOT registered in PHP |
| Tier Pricing plugin | `Woo_Tier_Pricing_Table` (custom plugin) |
| `html { font-size }` | **10px** — therefore `1rem = 10px`, `1.6rem = 16px` |

---

## Critical Astra Behaviour

Astra ≥ 3.9.2 **replaces** all default `woocommerce_single_product_summary` callbacks
with a single callback `single_product_content_structure()` at priority 10 (added on
the `wp` action). That method calls `woocommerce_template_single_add_to_cart()`
as a **direct PHP function call** inside a `switch/case` block — not through the hook.

**`remove_action()` on `woocommerce_single_product_summary` does not work here.**

The correct suppression point is the `astra_woo_single_product_structure` filter,
which Astra applies to its structure array before iterating it. Removing `'add_cart'`
from that array prevents the direct call from ever happening.

---

## File Inventory

### Pre-existing files (not modified by this project)

| File | Purpose |
|---|---|
| `single-product.php` | Top-level single product page template. Calls `wc_get_template_part('content','single-product')` inside a `while(have_posts())` loop. |
| `content-single-product.php` | **Key layout template.** Defines the product HTML structure including the custom `fhs_inside_product_main_container` hook. See layout section below. |
| `single-product/add-to-cart/simple.php` | Add-to-cart UI for simple products. Custom: price (Level A / tier logic), qty stepper, ATC button, wishlist/quote shortcodes, Features accordion, Delivery Timeframes, enquiry modal. |
| `single-product/add-to-cart/variable.php` | **Router only.** Checks ACF field `category_variation_template` on product categories. Routes to `variation-card-grid-template.php` (true) or `default-variable-template.php` (false). |
| `single-product/add-to-cart/default-variable-template.php` | Standard WooCommerce variation dropdowns + `single_variation_wrap`. Used for variable products without card grid. |
| `single-product/add-to-cart/variation-card-grid-template.php` | Card-per-variation layout for variable products in card-grid categories. Two-phase render: summary content fires at priority 30, card grid registers on `fhs_inside_product_main_container`. |
| `single-product/add-to-cart/variation-add-to-cart-button.php` | Overrides WooCommerce's variation ATC button inside `default-variable-template.php`. Checks ACF `product_poa` field — if true, hides ATC (Price On Application). |
| `single-product/add-to-cart/variation-cards.css` | Styles for the variation card grid. Enqueued inside `variation-card-grid-template.php`. |
| `single-product/price.php` | Suppresses the default price display (outputs nothing — custom price logic is inside add-to-cart templates). |
| `single-product/short-description.php` | Renders stock status indicator + product short description. |
| `single-product/title.php` | Renders product title + SKU. Includes inline JS to update SKU on variation change. |
| `single-product/tabs/tabs.php` | Custom product tabs with Product Specs, Description, Video, Downloads, FAQ panels. Uses ACF fields. |
| `fhs-tier-pricing-init.php` | Initialises Level A tier pricing display. Loads `admin-level-pricing-display.php`. |
| `admin-level-pricing-display.php` | Helper functions for Level A pricing. Provides `fhs_get_level_a_regular_price()`, `fhs_get_level_a_sale_price()`, `fhs_get_level_a_pricing()`. Adds admin metabox and product list column. |
| `single-product/delivery-enquiry-handler.php` | AJAX handler for delivery time enquiry modal. Creates DB table, sends email, registers `wp_ajax_delivery_time_enquiry`. |
| `checkout/form-shipping.php` | Custom checkout shipping form. Uses `WC()->session->get('fhs_fulfilment_method')`. |
| Various other files | Cart, checkout, myaccount, order, loop, wishlist, email templates. Not modified by this project. |

### New files created by this project

| File | Purpose |
|---|---|
| `fhs-configurator.php` | All PHP for the product configurator feature: activation guard, Astra suppression, section/product data helpers, template renderer. 383 lines. |
| `single-product/add-to-cart/configurator-template.php` | HTML template for the configurator two-column layout. 285 lines. |
| `single-product/add-to-cart/configurator.js` | Client-side selection behaviour: tabs, radio/checkbox, Select All, state reader, custom event. 336 lines. Vanilla JS, no jQuery. |
| `single-product/add-to-cart/configurator.css` | All styles for the configurator. 617 lines. |

---

## content-single-product.php — Layout Structure

This is the most important structural file. It defines the DOM hierarchy that
everything else renders inside.

```
<div id="product-{id}" class="product ...">                          ← WC product classes
  <div class="single-product-content-container product-main-container">

    <div class="single-product-layout-wrap">                         ← image + summary row
      <!-- woocommerce_before_single_product_summary -->
      <!-- product gallery (div.images, floated left 48%) -->

      <div class="summary entry-summary">                            ← summary column (floated right 48%)
        <!-- woocommerce_single_product_summary -->
        <!-- title (priority 5) -->
        <!-- price (priority 10) — outputs nothing, suppressed -->
        <!-- excerpt/short-description (priority 20) -->
        <!-- add_to_cart (priority 30) — suppressed for configurator -->
        <!-- meta SKU/categories (priority 40) -->
      </div>
    </div><!-- /.single-product-layout-wrap -->

    <!-- fhs_inside_product_main_container -->
    <!-- Configurator and variation card grid render HERE -->
    <!-- Full width, after the floated image+summary row -->

  </div><!-- /.single-product-content-container -->

  <!-- woocommerce_after_single_product_summary -->
  <!-- Product tabs, upsells, related products -->
</div>
```

**Key facts:**
- `div.images` and `div.summary` are floated at 48% each by WooCommerce layout CSS.
- The configurator renders after `.single-product-layout-wrap` closes, still inside
  `.single-product-content-container`. It uses `clear: both` to escape the float context.
- `fhs_inside_product_main_container` is a **custom hook** defined in this file.
  It does not exist in WooCommerce core or Astra. It was added specifically to allow
  full-width content injection below the image/summary row.

---

## ACF Fields Used

### Product-level fields

| Field name | Type | Purpose |
|---|---|---|
| `enable_product_configurator` | True/False | Activates configurator for this product |
| `product_features` | Text/WYSIWYG | Features accordion content |
| `product_poa` | True/False | Price On Application — hides ATC in variation templates |

### ACF Group: `configurator_options`

This is an ACF **Group** field. Sub-fields must be read as:
```php
$group = get_field('configurator_options', $product_id);
$group['machine_packages']  // NOT get_field('machine_packages', $product_id)
```

| Sub-field | Type | Notes |
|---|---|---|
| `machine_packages` | Relationship (returns post IDs) | Always single-select. No separate type field. |
| `liner_sets` | Relationship (returns post IDs) | |
| `liner_sets_selection_type` | Select (`single`/`multiple`) | Default: `multiple` |
| `replacement_parts` | Relationship (returns post IDs) | |
| `replacement_parts_selection_type` | Select (`single`/`multiple`) | Default: `multiple` |
| `accessories` | Relationship (returns post IDs) | |
| `accessories_selection_type` | Select (`single`/`multiple`) | Default: `multiple` |
| `data_logging` | Relationship (returns post IDs) | |
| `data_logging_selection_type` | Select (`single`/`multiple`) | Default: `multiple` |
| `consumables` | Relationship (returns post IDs) | |
| `consumables_selection_type` | Select (`single`/`multiple`) | Default: `multiple` |
| `tooling_extras` | Relationship (returns post IDs) | |
| `tooling_extras_selection_type` | Select (`single`/`multiple`) | Default: `multiple` |

### Category-level fields

| Field name | Applied to | Purpose |
|---|---|---|
| `category_variation_template` | `product_cat` term | True → use variation card grid for products in this category |

---

## Pricing Logic (existing, in simple.php and variation templates)

All existing pricing logic follows this pattern:

```
is_user_logged_in()?
  └── current_user_can('manage_woocommerce')?
        ├── YES (admin): show Level A pricing
        │     post_meta: _LevelA_tiered_price_regular_price
        │     post_meta: _LevelA_tiered_price_sale_price
        │     Helper functions: fhs_get_level_a_regular_price(), fhs_get_level_a_sale_price()
        │     (defined in admin-level-pricing-display.php)
        └── NO (regular logged-in user): show tier pricing
              $product->get_price() / get_price_html()
              (Tier Pricing Table Premium plugin modifies these values)
Guest: show login prompt only — no price, no ATC
```

**Pricing is not yet implemented in the configurator** (planned as a separate step).

---

## The Product Configurator Feature

### Activation condition

The configurator activates if and only if **both** conditions are true:

1. `$product->is_type('simple')` — only simple products
2. `get_field('enable_product_configurator', $product->get_id())` — ACF field is truthy

Variable products, even if the ACF field is accidentally enabled, fall through
completely to their existing template behaviour.

### Execution flow

```
URL: /product/[configurator-product-slug]/

single-product.php
  └─ wc_get_template_part('content', 'single-product')
        └─ content-single-product.php
              ├─ woocommerce_before_single_product_summary
              │     └─ product gallery
              │
              ├─ woocommerce_single_product_summary (Astra's single_product_content_structure)
              │     ├─ title (priority 5)
              │     ├─ price (priority 10) — outputs nothing
              │     ├─ short description (priority 20)
              │     ├─ [add_cart REMOVED by fhs_configurator_filter_astra_structure]
              │     └─ meta/SKU (priority 40) — still renders
              │
              └─ fhs_inside_product_main_container
                    └─ fhs_render_configurator()
                          ├─ fhs_configurator_is_active() → true
                          ├─ fhs_configurator_get_sections($product_id)
                          │     └─ get_field('configurator_options', $product_id)
                          │           └─ validates each relationship product
                          │           └─ skips non-simple / invalid products
                          │           └─ skips empty sections
                          │           └─ returns array of populated sections
                          └─ wc_get_template('configurator-template.php', [...])
                                └─ renders two-column layout
                                └─ enqueues configurator.css + configurator.js
```

### Suppression mechanism

`fhs_configurator_filter_astra_structure()` is registered on:
```php
add_filter('astra_woo_single_product_structure', 'fhs_configurator_filter_astra_structure');
```
It removes `'add_cart'` from Astra's structure array, preventing
`woocommerce_template_single_add_to_cart()` from being called for configurator products.
On all other products, it returns the structure array completely unchanged.

---

## fhs-configurator.php — Function Reference

| Function | Description |
|---|---|
| `fhs_configurator_is_active($product)` | Single activation guard. Accepts WC_Product or int. Returns bool. |
| `fhs_configurator_filter_astra_structure($structure)` | Filter on `astra_woo_single_product_structure`. Removes `'add_cart'` when configurator is active. |
| `fhs_configurator_get_product_data($product_id)` | Returns `[id, name, sku, image_url]` for one validated simple product. Returns `[]` for invalid/non-simple. |
| `fhs_configurator_get_sections($product_id)` | Reads `configurator_options` ACF group. Returns array of section arrays. Skips empty sections and invalid products. |
| `fhs_render_configurator()` | Hooked to `fhs_inside_product_main_container`. Calls `fhs_configurator_get_sections()` and loads `configurator-template.php` via `wc_get_template()`. |

**Hooks registered:**
```php
add_filter('astra_woo_single_product_structure', 'fhs_configurator_filter_astra_structure');
add_action('fhs_inside_product_main_container',  'fhs_render_configurator');
```

---

## configurator-template.php — Template Structure

**Variables received from `fhs_render_configurator()`:**
- `$configurator_product` — WC_Product object
- `$sections` — array of section arrays from `fhs_configurator_get_sections()`

**HTML structure:**
```html
<div class="fhs-configurator product-main-container" data-product-id="...">
  <div class="fhs-configurator__layout">           ← CSS grid: 1fr / 380px

    <div class="fhs-configurator__left">
      <div class="fhs-configurator__intro-bar">    ← "Choose from sections..." bar
      <nav class="fhs-configurator__tabs">         ← one button per section
        <button data-section-key="..." class="... is-active">

      <div class="fhs-configurator__panel is-active" data-section-key="..." data-selection-type="...">
        <div class="fhs-configurator__panel-header">
          <h3>Section Label <span.optional-badge> <span.info-icon>
          <button.fhs-configurator__select-all>    ← multiple sections only

        <div class="fhs-configurator__grid--machine | --standard">
          <label class="fhs-configurator__card --machine | --standard"
                 data-product-id="..." data-section-key="...">
            <input type="radio|checkbox" class="fhs-configurator__card-input"
                   name="fhs_configurator_{key}" data-product-id="..." data-section-key="...">
            <div.fhs-configurator__card-img-wrap>
            <div.fhs-configurator__card-body>
              <p.fhs-configurator__card-name>
              <p.fhs-configurator__card-sku>
              <!-- Price — Step 5 -->

    <div class="fhs-configurator__right">          ← Your Configuration panel (Step 6)
```

**Input naming strategy:**
- Single sections (including machine_packages): `type="radio"` with `name="fhs_configurator_{key}"`
- Multiple sections: `type="checkbox"` with `name="fhs_configurator_{key}[]"`
- Each single-selection section has its own independent radio group name
- Input `value` = WooCommerce product ID (integer)

---

## configurator.js — JavaScript API

**No dependencies.** Plain vanilla JS wrapped in an IIFE. Loaded in footer.

### Public API

```js
window.fhsConfigurator.getSelections()
// Returns: { machine_packages: [], liner_sets: [40053, 40025], ... }
```

### Custom event

```js
// Dispatched from .fhs-configurator after any selection change (not on tab switch)
wrapper.dispatchEvent(new CustomEvent('fhs:configurator:change', {
  bubbles: true,
  detail: {
    selections: {
      machine_packages:  [],
      liner_sets:        [40053, 40025],
      replacement_parts: [30603],
      accessories:       [],
      data_logging:      [],
      consumables:       [],
      tooling_extras:    [],
    }
  }
}));
```

### Internal functions

| Function | Description |
|---|---|
| `init()` | Entry point. Finds all `.fhs-configurator` elements and initialises each. |
| `initConfigurator(wrapper)` | Wires tab clicks, change listener, Select All buttons for one instance. |
| `switchTab(wrapper, tabs, panels, targetKey)` | Activates matching tab/panel by `data-section-key`. Does not reset selections. |
| `syncSectionSelectedState(wrapper, sectionKey)` | Sets/removes `.is-selected` on every card in a section to match `input.checked`. |
| `handleSelectAll(wrapper, sectionKey, btn)` | Toggles all checkboxes in section. Calls sync + updateButton + dispatch. |
| `updateSelectAllButton(wrapper, sectionKey)` | Sets button text to `"Select all"` or `"Deselect all"` based on checked state. |
| `getConfiguratorSelections(wrapper)` | Reads all checked inputs grouped by section key. Returns state object. |
| `dispatchChangeEvent(wrapper)` | Fires `fhs:configurator:change` with full state in `event.detail.selections`. |
| `getSectionInputs(wrapper, sectionKey)` | Helper. Returns all `.fhs-configurator__card-input` for a section. |

---

## configurator.css — Key Sections

| Section | Classes | Notes |
|---|---|---|
| Outer wrapper | `.fhs-configurator` | `clear: both` to escape float context. `width: 100%`. Light grey bg. |
| Two-column grid | `.fhs-configurator__layout` | `grid-template-columns: minmax(0,1fr) 380px`. `padding: 0 20px`. |
| Left column | `.fhs-configurator__left` | `min-width: 0` |
| Right column | `.fhs-configurator__right` | `position: sticky; top: 80px`. Placeholder until Step 6. |
| Intro bar | `.fhs-configurator__intro-bar` | Number badge + instruction text + help link |
| Tab bar | `.fhs-configurator__tabs` | Flex row, wraps on mobile. Active state: `.is-active` (blue underline). |
| Section panel | `.fhs-configurator__panel` | White card, `display:none` when `[hidden]`. Active: `is-active`. |
| Machine grid | `.fhs-configurator__grid--machine` | `repeat(auto-fill, minmax(180px, 1fr))` |
| Standard grid | `.fhs-configurator__grid--standard` | `repeat(auto-fill, minmax(130px, 1fr))` |
| Hidden input | `.fhs-configurator__card-input` | Visually hidden via clip/position absolute. Keeps accessibility. |
| Selected card | `.fhs-configurator__card.is-selected` | Blue border + light blue bg + box-shadow ring |
| Font sizing | All `rem` values | Scaled for `html { font-size: 10px }`. `1.6rem = 16px`. |

**Responsive breakpoints:**
- `≤ 1024px` — columns stack, sidebar becomes static
- `≤ 900px` — machine grid 3 cols, standard grid `minmax(110px,1fr)`
- `≤ 700px` — tabs scroll horizontally, layout padding reduced
- `≤ 480px` — machine grid 2 cols, standard grid 3 cols fixed

---

## What Is NOT Yet Implemented

| Feature | Planned step |
|---|---|
| Pricing display on configurator cards | Step 5 |
| "Your Configuration" right panel | Step 6 |
| Add All to Cart | Step 7 |
| PHP/WC session persistence | Step 6/7 |
| AJAX endpoints for cart | Step 7 |
| Machine Package replaces base product in panel | Step 6 |
| Features accordion + Delivery Timeframes on configurator products | Separate decision |
| `woocommerce_template_single_meta` suppression | Open decision |

---

## Products Completely Unaffected

The following product types and templates have **zero changes** from this project:

- Normal simple products (no `enable_product_configurator`) → `simple.php` unchanged
- Variable products → `variable.php`, `default-variable-template.php`, `variation-add-to-cart-button.php` unchanged
- Card-grid variable products → `variation-card-grid-template.php` unchanged
- POA products → `product_poa` logic unchanged
- All non-single-product pages — configurator assets not enqueued

---

## Change History Summary

| Step | Change | Files |
|---|---|---|
| Investigation | Read all existing templates, traced execution flow, identified Astra direct-call bypass | No code changes |
| Step 2 | Created `fhs-configurator.php` with `fhs_configurator_is_active()` and `fhs_configurator_filter_astra_structure()`. Fixed: initial `remove_action` approach did not work; replaced with `astra_woo_single_product_structure` filter after discovering Astra calls ATC directly. | `fhs-configurator.php` (new) |
| Step 3 | Added `fhs_render_configurator()` to `fhs-configurator.php`, hooked to `fhs_inside_product_main_container`. Initially output placeholder div. | `fhs-configurator.php` |
| Step 4 | Added `fhs_configurator_get_product_data()` and `fhs_configurator_get_sections()`. Created `configurator-template.php` with static section/card render. Fixed: initial implementation called `get_field('sub_field', $id)` directly — this returns NULL for ACF Group sub-fields. Fixed to read entire group once with `get_field('configurator_options', $id)` then access keys from that array. | `fhs-configurator.php`, `configurator-template.php` (new) |
| Step 4 correction | Changed selection_type fallback from `'single'` to `'multiple'` to match ACF field default. | `fhs-configurator.php` |
| Step 4 layout | Created `configurator.css`. Two-column layout, tab bar, machine package cards, standard grid cards. Font sizes scaled for 10px base. | `configurator.css` (new) |
| Layout fix | Fixed excessive vertical gap (float context from `.single-product-layout-wrap`): added `clear: both` to `.fhs-configurator`. Fixed narrow width: added `width: 100%; box-sizing: border-box`. Adjusted grid column sizes. | `configurator.css` |
| Selection step | Replaced fake `<span>` radio/checkbox indicators with real `<input type="radio|checkbox">` wrapped in `<label>`. Entire card is clickable via `<label for="...">`. Added `configurator.js` with tab switching, selection sync, Select All/Deselect All, `getConfiguratorSelections()`, `fhs:configurator:change` event. Added JS enqueue to template. Added `.is-selected`, `.is-active`, `.fhs-configurator__card-input` CSS rules. | `configurator-template.php`, `configurator.js` (new), `configurator.css` |
