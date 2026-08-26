# FHS WooCommerce Child Theme Refactor Analysis

## Objective

Refactor the monolithic `functions.php` in the Astra child theme into a clean, maintainable architecture while preserving existing functionality, endpoints, metadata, and frontend output.

This document covers:

1. Full feature inventory
2. Classification by responsibility
3. Target architecture
4. Migration priority and rollout plan
5. Risk areas
6. Removal candidates

---

# 1. Executive Summary

The current `fhs-woocommerce/functions.php` file contains a mix of:

- Astra child theme setup
- frontend assets
- WooCommerce archive/product/cart/checkout/account customizations
- business-critical account and invoice logic
- MYOB-related customer/payment term behavior
- AJAX handlers
- Gravity Forms integration
- admin user metadata logic
- experimental/debug code
- inline CSS/JS blocks

The file has exceeded a maintainable scope and should be split into:

- lightweight child-theme display concerns
- business logic plugins
- WooCommerce feature plugins

The most important architectural conclusion is:

> Business logic must move out of the theme.

That includes invoices, MYOB term logic, account endpoints, quote integration, customer business metadata, checkout payment rules, PO number/file handling, and cart workflow behavior.

---

# 2. Current File Characteristics

## Observed patterns

- Global functions mixed with anonymous callbacks
- Hooks registered throughout the file without grouping
- Business logic embedded directly in template/UI callbacks
- Duplicate and overlapping hooks
- Hardcoded URLs and IDs
- Inline JavaScript and CSS embedded in PHP
- Debug/testing code left in production
- Rewrite rules flushed on every request

## Architectural concerns

- Hard to test
- Hard to reason about hook execution order
- High regression risk when changing any section
- Theme change would break critical business features
- Several concerns are tightly coupled only because they live in one file

---

# 3. Full Feature Inventory

## 3.1 Theme Features

These are primarily presentation or theme bootstrap concerns.

### 3.1.1 Theme bootstrap

**Code areas identified:**
- `define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0' );`
- `child_enqueue_styles()`
- `register_menus()`

**Purpose:**
- Child theme version constant
- Enqueue child stylesheet
- Register primary and secondary menus

**Recommended destination:**
- Stay in child theme

**Suggested file locations:**
- `inc/setup/theme-setup.php`
- `inc/assets/enqueue.php`
- `inc/setup/menus.php`

---

### 3.1.2 Tracking / head output

**Code areas identified:**
- anonymous `wp_head` callback injecting Google tag script

**Purpose:**
- Adds Google tracking snippet to frontend head

**Recommended destination:**
- Stay in child theme

**Refactor note:**
- Replace hardcoded GA ID with a constant or option

**Suggested file location:**
- `inc/assets/tracking.php`

---

### 3.1.3 Theme/general frontend assets

**Code areas identified:**
- `enqueue_jquery_ui()`
- `load_custom_assets()`
- `enqueue_splidejs_scripts()`

**Purpose:**
- Load global CSS/JS
- Load jQuery UI
- Load Splide
- Load custom JS for tabs, modal address handling, cart interactions
- Localize AJAX/config values to scripts

**Recommended destination:**
- General display assets stay in theme
- Cart/checkout/product-specific assets should move with the plugin functionality they support

**Suggested file location:**
- `inc/assets/enqueue.php`

**Important note:**
The current code deregisters WordPress core jQuery and replaces it with CDN jQuery. This is risky and should be reviewed carefully during implementation.

---

### 3.1.4 Navigation behavior

**Code areas identified:**
- `exclude_products_from_secondary_menu()`
- `include_products_in_menu()`

**Purpose:**
- Modify visible menu items based on item classes and custom args

**Recommended destination:**
- Stay in child theme

**Suggested file location:**
- `inc/theme/navigation.php`

---

### 3.1.5 Astra/blog/search presentation

**Code areas identified:**
- `astra_blog_post_thumbnail_and_title_order()`
- `custom_astra_read_more_button()`
- `display_author_first_name()`
- breadcrumb replacement for uncategorized blog path

**Purpose:**
- Reorder Astra archive blocks
- Add custom CTA/read more behavior in search
- Show author first name instead of full display name
- Improve blog breadcrumb labeling

**Recommended destination:**
- Stay in child theme

**Suggested file locations:**
- `inc/theme/blog.php`
- `inc/theme/search.php`
- `inc/theme/breadcrumbs.php`

---

### 3.1.6 Category / archive UI

**Code areas identified:**
- `custom_brand_breadcrumb()`
- removal of default WooCommerce breadcrumb/header actions
- `custom_banner_with_breadcrumbs()`
- custom `woocommerce_template_loop_category_title()`
- `add_custom_section_to_category_page()`
- `add_category_type_to_body_class()`

**Purpose:**
- Customize category/brand archive breadcrumbs
- Add archive hero/banner image
- Inject term icon/title markup
- Load Elementor sections on category pages
- Add body classes for category type

**Recommended destination:**
- Stay in child theme

**Suggested file location:**
- `inc/woocommerce/archive-ui.php`

**Refactor notes:**
- Replace hardcoded fallback image URL with configurable value
- Replace hardcoded Elementor template IDs with constants/options/mapping

---

### 3.1.7 Wishlist/account presentation helpers

**Code areas identified:**
- `custom_login_register_button()`
- `add_navbar_in_wishlist()`
- `custom_redirect_after_wishlist_delete()`

**Purpose:**
- Login/register shortcode button
- Add account navigation inside wishlist page
- Redirect after wishlist deletion

**Recommended destination:**
- Login/register button: stay in theme
- Wishlist wrapper/nav injection: stay in theme
- Redirect can remain in theme unless wishlist becomes part of a formal account portal plugin

**Suggested file location:**
- `inc/theme/wishlist.php`
- `inc/theme/account-ui.php`

---

## 3.2 WooCommerce Core Customizations

These are WooCommerce-specific features that should be grouped by responsibility.

### 3.2.1 Thank-you/payment status customization

**Code areas identified:**
- `show_pending_payment_error()`

**Purpose:**
- Show error notice on thank-you page for pending orders
- Remove thank-you text filters

**Recommended destination:**
- Move to `fhs-checkout`

**Reason:**
- Checkout/payment/order status behavior should not live in theme

---

### 3.2.2 Catalog/archive behavior

**Code areas identified:**
- `hide_subcategory_products_on_parent()`
- `remove_subcategory_products()`
- `custom_woocommerce_catalog_orderby()`
- `custom_woocommerce_orderby_logic()`
- `custom_catalog_orderby_options()`
- `set_products_per_page()`
- `woocommerce_ajax_variation_threshold` filter
- availability text customization

**Purpose:**
- Control category displays
- Control sorting labels/options
- Search ordering adjustments
- Pagination count
- Variation threshold changes
- Availability text formatting

**Recommended destination:**
- Move to `fhs-products`

**Reason:**
- These are WooCommerce catalog/product behaviors, not Astra theme setup

---

### 3.2.3 Search behavior

**Code areas identified:**
- search post type modification in `pre_get_posts`
- `custom_search_orderby_product_price_desc()`
- `posts_where` SKU search extension

**Purpose:**
- Include products/pages in search
- Order search results with product-first logic
- Match product SKU in search

**Recommended destination:**
- Move to `fhs-products`

**Risk note:**
- The `posts_where` filter is broad and should be narrowed during refactor

---

### 3.2.4 Account navigation and endpoint behavior

**Code areas identified:**
- `custom_account_menu_items_order()`
- `custom_register_my_account_endpoints()`
- query var for `my-quotes`
- endpoint rendering callbacks

**Endpoints found:**
- `orders`
- `invoices`
- `my-quotes`
- `cart`

**Recommended destination:**
- Menu ordering/UI can remain in theme if purely visual
- Endpoint registration and content should move into plugins

**Plugin mapping:**
- `invoices` → `fhs-core`
- `my-quotes` → `fhs-core`
- `cart` → `fhs-cart`

---

### 3.2.5 Cart behavior

**Code areas identified:**
- `cart_content()`
- `custom_cart_url()`
- `custom_add_cart_endpoint()`
- redirect `/cart/` to `/my-account/cart/`
- `update_cart_item_quantity_callback()`
- `custom_cart_price_details()`
- `cart_section_header_and_filter()`
- `merge_duplicated_products_in_cart()`
- fee cleanup hook removing `Shipping Amount`

**Purpose:**
- Replace default cart flow with account/cart endpoint flow
- AJAX quantity update
- Custom totals panel
- Product search UI in cart
- Merge duplicate products in cart
- Remove unwanted fee line

**Recommended destination:**
- Move to `fhs-cart`

---

### 3.2.6 Product page enhancements

**Code areas identified:**
- `show_acf_product_download()`
- `add_custom_variation_tabs()`
- `render_complete_kit_shortcode()`

**Purpose:**
- Product download CTA
- Attribute tab UI for variable products
- Complete-your-kit/optional-extras merchandising block

**Recommended destination:**
- Move to `fhs-products`

---

## 3.3 Business Critical Features (Must Become Plugins)

These features should survive a theme change.

### 3.3.1 Invoice system

**Code areas identified:**
- `invoices_content()`
- `format_invoice_status()`

**Purpose:**
- Customer invoice listing in My Account
- Date/status filters
- Query custom invoice table
- Invoice download links

**Dependencies identified:**
- custom table: `${wpdb->prefix}myob_invoices`
- user meta: `myob_customer_id`
- download REST route: `/wp-json/invoice/file_download`

**Recommended destination:**
- `fhs-core/includes/class-fhs-invoices.php`

**Reason:**
- Core customer/business functionality

---

### 3.3.2 MYOB-driven customer/payment logic

**Code areas identified:**
- admin user columns for MYOB metadata
- payment term checks in account order columns
- invoice queries by MYOB customer ID
- payment gateway restrictions by MYOB terms

**Meta keys identified:**
- `myob_customer_id`
- `myob_payment_terms`
- `myob_user_designation`

**Recommended destination:**
- `fhs-core/includes/class-fhs-myob.php`
- `fhs-core/includes/class-fhs-customer.php`
- `fhs-core/includes/class-fhs-orders.php`

**Reason:**
- Central business rule domain

---

### 3.3.3 Quotes integration

**Code areas identified:**
- `woocommerce_account_my-quotes_endpoint` callback using `[stars_quote_page]`
- `quotes_content()` wired to `woocommerce_account_quotes_endpoint`

**Purpose:**
- Quote portal entry inside My Account

**Recommended destination:**
- `fhs-core/includes/class-fhs-account.php`
- optionally `class-fhs-quotes.php`

**Important compatibility note:**
- Preserve existing endpoint slug `my-quotes`
- Audit whether `quotes` endpoint is actually used anywhere else before removing legacy compatibility

---

### 3.3.4 Customer business metadata

**Code areas identified:**
- `add_custom_user_fields_admin()`
- `save_custom_user_fields_admin()`

**Fields identified:**
- `registration_company_name`
- `trading_company_name`
- `billing_first_name`
- `billing_last_name`
- `billing_email`
- `billing_phone`
- `phone_number`
- `abn_number`
- `business_address`
- `shipping_address`
- `recovery_email`

**Storage pattern identified:**
- `ms_fhs_custom_*`

**Recommended destination:**
- `fhs-core/includes/class-fhs-customer.php`

**Reason:**
- Business/customer identity data must not depend on theme

---

### 3.3.5 Account endpoint infrastructure

**Code areas identified:**
- custom account menu items
- custom rewrite endpoints
- invoice endpoint renderer
- quote endpoint renderer

**Recommended destination:**
- `fhs-core/includes/class-fhs-account.php`

**Reason:**
- Customer portal logic should be plugin-owned

---

### 3.3.6 Order/account business columns

**Code areas identified:**
- add pay now / PO number / required date / fulfilment / outstanding columns
- display corresponding order values
- admin order display of fulfilment method
- admin order list column additions

**Recommended destination:**
- `fhs-core/includes/class-fhs-orders.php`
- field capture parts stay in `fhs-checkout`

**Reason:**
- This is account/order business behavior

---

## 3.4 Frontend / UI Features

### 3.4.1 Product downloads

**Code areas identified:**
- `create_downloads_post_type()`
- `show_acf_product_download()`

**Purpose:**
- Downloads content model and product download CTA

**Recommended destination:**
- CPT: `fhs-products`
- product download button: `fhs-products`

---

### 3.4.2 Product FAQ

**Code areas identified:**
- stray ACF FAQ output block using `$product_id = 123`

**Purpose:**
- Likely intended FAQ output

**Recommended destination:**
- If active business requirement: `fhs-products`
- If prototype only: delete candidate

---

### 3.4.3 Variation tabs / selectors

**Code areas identified:**
- `add_custom_variation_tabs()`

**Purpose:**
- Material and color selection UI enhancement for variable products

**Recommended destination:**
- `fhs-products/includes/class-fhs-variation-tabs.php`

---

### 3.4.4 Complete Your Kit / optional extras

**Code areas identified:**
- shortcode registration on `init`
- `render_complete_kit_shortcode()`

**Purpose:**
- merchandising cross-sell panel using custom field product IDs/SKUs

**Recommended destination:**
- `fhs-products/includes/class-fhs-complete-kit.php`

---

### 3.4.5 Gravity Forms UX customizations

**Code areas identified:**
- `custom_confirmation()`
- `gform_confirmation_6`
- `gf_phone_numbers_only_clean()`
- `custom_au_address_type()`

**Purpose:**
- form redirect/confirmation behavior
- phone validation
- custom AU address type behavior

**Recommended destination:**
- If related to customer onboarding or business registration: `fhs-core`
- Otherwise can stay as a later dedicated integration module

---

## 3.5 Checkout Features

These should move into `fhs-checkout`.

### 3.5.1 Checkout field ordering and labels

**Code areas identified:**
- `reorder_and_customize_billing_fields()`
- `force_billing_postcode_label_translation()`

**Purpose:**
- Billing field order
- required phone logic
- relabel address/phone/city/postcode labels
- remove plugin-injected billing helper fields

**Recommended destination:**
- `fhs-checkout/includes/class-fhs-checkout-fields.php`

---

### 3.5.2 Payment relocation

**Code areas identified:**
- remove default `woocommerce_checkout_payment`
- add to custom hook `woocommerce_custom_payment_relocation`

**Purpose:**
- Move payment block to custom checkout layout area

**Recommended destination:**
- `fhs-checkout/includes/class-fhs-checkout.php`

---

### 3.5.3 PO number and required date

**Code areas identified:**
- extra fields rendered in payment area
- `save_purchase_order_number()`
- display in My Account/admin order screen

**Field/meta keys identified:**
- posted field: `billing_po_number`
- posted field: `fhs_required_date`
- order meta: `_product_order_number`
- order meta: `__order_required_date`

**Recommended destination:**
- field rendering/saving/validation: `fhs-checkout`
- order/account display may live in `fhs-core` if shared with account modules

---

### 3.5.4 PO upload

**Code areas identified:**
- upload UI in checkout
- `upload_po_file()` AJAX handler
- order meta save for `_pay_later_po_file`
- admin display for PO file
- upload size limit filter

**Recommended destination:**
- `fhs-checkout/includes/class-fhs-po-upload.php`

---

### 3.5.5 Payment gateway restrictions

**Code areas identified:**
- `restrict_pay_later_gateway()`

**Purpose:**
- Hide `pay_later` gateway based on login state and MYOB terms

**Recommended destination:**
- `fhs-checkout/includes/class-fhs-payment-rules.php`

**Dependency:**
- shared MYOB/customer meta helper from `fhs-core`

---

### 3.5.6 Checkout validation

**Code areas identified:**
- `woocommerce_checkout_process` validation for PO number and PO file
- `woocommerce_after_checkout_validation` phone validation

**Purpose:**
- enforce pay-later requirements
- digits-only phone rule and length limit

**Recommended destination:**
- `fhs-checkout/includes/class-fhs-checkout-validation.php`

---

### 3.5.7 Checkout JS patching / refresh guards

**Code areas identified:**
- multiple `woocommerce_after_checkout_form` inline scripts
- disables billing-triggered checkout refreshes
- forces initial update_checkout logic
- guards against repeated update events

**Purpose:**
- custom checkout performance/state management

**Recommended destination:**
- `fhs-checkout` plugin assets and controller class

**Important note:**
- This is high-risk and should be migrated with very careful regression testing

---

## 3.6 Cart Features

### 3.6.1 Cart endpoint and redirects

**Code areas identified:**
- `cart_content()`
- `custom_cart_url()`
- `/cart/` redirect to `/my-account/cart/`
- `custom_add_cart_endpoint()`

**Recommended destination:**
- `fhs-cart/includes/class-fhs-cart-endpoint.php`

---

### 3.6.2 AJAX cart updates

**Code areas identified:**
- `update_cart_item_quantity_callback()`

**Recommended destination:**
- `fhs-cart/includes/class-fhs-cart-ajax.php`

---

### 3.6.3 Cart totals and custom display

**Code areas identified:**
- `custom_cart_price_details()`
- cart totals UI additions

**Recommended destination:**
- `fhs-cart/includes/class-fhs-cart-totals.php`

---

### 3.6.4 Cart merge logic

**Code areas identified:**
- `merge_duplicated_products_in_cart()`

**Recommended destination:**
- `fhs-cart/includes/class-fhs-cart-merge.php`

---

# 4. Remove / Delete Candidates

These are candidates only. Do not delete until confirmed safe.

## 4.1 Commented-out old code

**Examples found:**
- commented Gravity Forms account/MYOB test block
- old menu registration block
- Elementor card skin experiment
- OneSignal experiment
- demo review generator
- old product order number checkout block

**Why removable:**
- inactive
- no runtime value
- increases cognitive load

---

## 4.2 Debug and testing code

**Examples found:**
- `error_log()` around account form save behavior
- search query logging on `template_redirect`
- REST API request logging through `rest_pre_dispatch`
- comments describing testing code

**Why removable or isolate-only:**
- can expose sensitive data
- production noise
- not theme responsibility

---

## 4.3 Duplicate/conflicting hook logic

**Examples found:**
- cart endpoint registered twice
- rewrite rules flushed repeatedly
- duplicate `after_switch_theme` hook registration
- catalog ordering filtered by multiple callbacks
- account order columns modified in multiple places
- quote endpoint naming inconsistency

**Why cleanup required:**
- unpredictable behavior
- increases migration complexity

---

## 4.4 Rewrite flush on every request

**Code found:**
- `flush_rewrite_rules_on_init()` hooked to `init`

**Why it must be removed during migration:**
- severe performance anti-pattern
- should only happen on activation/deactivation or explicit admin action

---

## 4.5 Hardcoded IDs

**Examples found:**
- Elementor template IDs: `14294`, `13897`, `13889`, `13892`
- `$product_id = 123`

**Why cleanup required:**
- environment-specific
- brittle
- strongly suggests prototype code leakage

---

## 4.6 Hardcoded URLs/domains

**Examples found:**
- `https://fhs.com.com/...`
- `https://fhs.com.au/...`
- hardcoded uploads path for fallback image
- direct path strings for brands/cart/wishlist/account flows

**Why cleanup required:**
- non-portable
- error-prone
- better replaced with config/helpers

---

## 4.7 Inline CSS/JS inside PHP

**Examples found:**
- large checkout style block
- multiple inline checkout scripts
- inline wishlist script
- inline JS inside shortcode output

**Why cleanup required:**
- hard to maintain
- hard to cache/version
- difficult to test

---

## 4.8 Stray execution block

**Code found:**
- Product FAQ block using `$product_id = 123` directly in global file scope

**Why this is a strong delete candidate:**
- runs during file load
- not inside a function or hook
- placeholder product ID indicates test/prototype residue

---

# 5. High-Risk Findings

## 5.1 Rewrite rule flushing on every request

This is one of the highest-priority issues to eliminate safely.

## 5.2 Business logic trapped in theme

Invoices, account endpoints, MYOB logic, customer business metadata, and payment rules are all theme-dependent today.

## 5.3 Large inline checkout JavaScript patching WooCommerce internals

This likely exists to work around refresh issues, but it is fragile and should be isolated into a dedicated plugin asset with controlled load order.

## 5.4 Duplicate/conflicting callbacks

There are overlapping changes to:
- cart endpoint registration
- order columns
- catalog sorting
- quote endpoint behavior

## 5.5 Unsafe or insufficiently isolated data access

Invoice filtering includes a direct SQL string interpolation area that should be rewritten safely.

## 5.6 Hardcoded environment-specific values

IDs, URLs, domains, and static media paths reduce portability and increase deployment risk.

---

# 6. Proposed Target Architecture

```text
wp-content/
│
├── themes/
│   └── astra-child/
│       ├── functions.php
│       └── inc/
│           ├── setup/
│           │   ├── theme-setup.php
│           │   └── menus.php
│           ├── assets/
│           │   ├── enqueue.php
│           │   └── tracking.php
│           ├── theme/
│           │   ├── blog.php
│           │   ├── breadcrumbs.php
│           │   ├── archives.php
│           │   ├── navigation.php
│           │   ├── wishlist.php
│           │   └── account-ui.php
│           └── woocommerce/
│               ├── archive-ui.php
│               └── account-ui.php
│
└── plugins/
    ├── fhs-core/
    │   ├── fhs-core.php
    │   └── includes/
    │       ├── class-fhs-core.php
    │       ├── class-fhs-account.php
    │       ├── class-fhs-customer.php
    │       ├── class-fhs-invoices.php
    │       ├── class-fhs-myob.php
    │       ├── class-fhs-orders.php
    │       └── class-fhs-quotes.php
    │
    ├── fhs-checkout/
    │   ├── fhs-checkout.php
    │   └── includes/
    │       ├── class-fhs-checkout.php
    │       ├── class-fhs-checkout-fields.php
    │       ├── class-fhs-checkout-validation.php
    │       ├── class-fhs-payment-rules.php
    │       └── class-fhs-po-upload.php
    │
    ├── fhs-products/
    │   ├── fhs-products.php
    │   └── includes/
    │       ├── class-fhs-products.php
    │       ├── class-fhs-product-downloads.php
    │       ├── class-fhs-product-faq.php
    │       ├── class-fhs-variation-tabs.php
    │       ├── class-fhs-complete-kit.php
    │       └── class-fhs-catalog.php
    │
    └── fhs-cart/
        ├── fhs-cart.php
        └── includes/
            ├── class-fhs-cart.php
            ├── class-fhs-cart-endpoint.php
            ├── class-fhs-cart-ajax.php
            ├── class-fhs-cart-totals.php
            └── class-fhs-cart-merge.php
```

---

# 7. Recommended Destination Map

## Stay in child theme

- child stylesheet loading
- menu registration
- Astra blog layout/presentation
- menu display filtering
- category/archive hero/banner UI
- category breadcrumbs and icons
- login/register UI shortcode
- wishlist wrapper/navigation UI
- other pure presentation logic

## Move to `fhs-core`

- account endpoints (`invoices`, `my-quotes`)
- invoices UI/data access
- quotes integration
- MYOB customer/payment term logic
- customer business metadata
- admin user MYOB columns
- account/order business columns
- shared order/account business helpers

## Move to `fhs-checkout`

- checkout field ordering and labels
- payment relocation
- PO number
- required date
- PO upload
- payment gateway visibility rules
- checkout validation
- thank-you payment pending notice
- fulfilment method checkout flow
- checkout JS refresh guards

## Move to `fhs-cart`

- cart endpoint
- cart URL overrides/redirects
- AJAX quantity update
- cart totals block
- cart product filter UI
- cart merge logic
- cart fee cleanup logic

## Move to `fhs-products`

- downloads CPT
- product download CTA
- variation tabs
- complete your kit shortcode
- optional extras
- search/catalog product behavior
- availability text
- variation threshold
- products-per-page rule

---

# 8. Migration Plan

## Guiding rules

- Do not move everything at once
- Each migration must be independently testable
- Existing DB structure must remain unchanged
- Existing endpoints must remain compatible
- Existing meta keys must remain unchanged
- Frontend output should remain functionally equivalent

---

## Step 1: Create `fhs-core`

### Move first
- MYOB-related code
- invoice functionality
- quotes functionality
- account endpoints
- customer metadata
- user admin columns for MYOB/customer business fields
- account/order business display logic where not tightly tied to checkout rendering

### Test checklist
- My Account endpoint pages still load
- `/my-account/invoices/` works
- `/my-account/my-quotes/` works
- invoice filters/pagination still work
- admin users table still shows MYOB columns
- new user admin form still saves customer business fields

---

## Step 2: Create `fhs-checkout`

### Move second
- checkout fields
- PO number
- required date
- payment relocation
- checkout validations
- pay-later gateway restrictions
- PO upload flow
- PO file/order meta save logic
- pending payment notice logic
- fulfilment method checkout capture

### Test checklist
- standard checkout works
- pay-later customers see correct gateway behavior
- PO field required only when expected
- PO file upload succeeds
- required date saves correctly
- product order number saves correctly
- admin order screen still shows PO file and required metadata

---

## Step 3: Create `fhs-cart`

### Move third
- AJAX cart quantity updates
- custom cart totals display
- cart redirect logic
- cart endpoint handling
- cart merge logic
- cart fee cleanup logic

### Test checklist
- `/my-account/cart/` works
- `/cart/` redirect still works if required
- AJAX quantity changes work
- totals display matches current behavior
- duplicate items merge correctly

---

## Step 4: Create `fhs-products`

### Move fourth
- product downloads
- product FAQ (if confirmed active)
- variation tabs
- complete your kit
- catalog/search/product page merchandising behavior

### Test checklist
- product download button appears as before
- variation tabs render correctly
- complete-your-kit adds items to cart correctly
- catalog sorting/search behavior still works

---

## Step 5: Clean Astra child theme

### Final cleanup
- reduce `functions.php` to bootstrap imports only
- move remaining presentation code into `inc/`
- remove dead code
- remove debug code
- remove duplicate hook registrations
- remove rewrite flush on `init`

---

# 9. Backward Compatibility Rules

The following must be preserved during implementation.

## 9.1 Endpoints
Do not rename existing public endpoint slugs used in production:
- `invoices`
- `my-quotes`
- `cart`
- `orders` if currently relied upon

## 9.2 Meta keys
Do not change stored keys such as:
- `myob_customer_id`
- `myob_payment_terms`
- `myob_user_designation`
- `ms_fhs_custom_*`
- `_product_order_number`
- `__order_required_date`
- `_pay_later_po_file`
- `_fhs_fulfilment_method`

## 9.3 Frontend forms and posted field names
Preserve fields such as:
- `billing_po_number`
- `fhs_required_date`
- `fhs_fulfilment_method`
- `pay_later_po_file_url`

## 9.4 Shortcodes
Preserve existing shortcodes and integrations:
- `[stars_quote_page]`
- `[woocommerce_cart]`
- `[complete_kit]`
- `[login_register_button]`

---

# 10. Implementation Standards

When implementation begins:

- use classes instead of global functions where practical
- use namespaces
- prevent direct file access
- keep plugin headers proper
- centralize hook registration in class boot/init methods
- avoid duplicate hook registration
- use activation/deactivation hooks for rewrite flushing where needed
- move inline JS/CSS into versioned assets
- replace hardcoded IDs/URLs with constants/configuration
- document business-purpose behavior clearly

---

# 11. First Implementation Recommendation

The safest first coding step is:

## Phase 4A
Create the `fhs-core` plugin scaffold first and migrate only:
- account endpoint registration
- invoices page rendering/querying
- quotes endpoint integration
- MYOB admin user columns
- custom admin user business fields

This provides immediate architectural value while minimizing frontend cart/checkout regression risk.

---

# 12. Final Recommendation

Approve the architecture first, then implement incrementally in this order:

1. `fhs-core`
2. `fhs-checkout`
3. `fhs-cart`
4. `fhs-products`
5. child theme cleanup

This order best protects business continuity while moving the site toward a maintainable plugin-driven architecture.
