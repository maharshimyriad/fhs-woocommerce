<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

?>
<div class="main-checkout-container">
<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">
    <div class="checkout-form-wrapper">

	<?php if ( $checkout->get_checkout_fields() ) : ?>

		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<div class="col2-set" id="customer_details">
			<div class="col-12">
				<?php do_action( 'woocommerce_checkout_billing' ); ?>
			</div>

			<div class="col-2">
				<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			</div>
		</div>

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
	<?php	do_action('woocommerce_custom_payment_relocation'); ?>

	<?php endif; ?>
	</div>
	</form>
	<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
	<div class="order-summary-container">
	<div class="order-summary-heading"><i class="icofont-price"></i><h3 id="order_review_heading"><?php esc_html_e( 'Order Summary', 'woocommerce' ); ?></h3></div>
	
	<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

	<div id="order_review" class="woocommerce-checkout-review-order">
		<?php do_action( 'woocommerce_checkout_order_review' ); ?>
	</div>

	<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
	</div>
</div>



<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>

<?php if ( defined('WP_DEBUG') && WP_DEBUG ) : ?>
<style>
#fhs-debug-panel {
    position: fixed; bottom: 0; right: 0; width: 480px; max-height: 55vh;
    background: #0d1117; color: #e6edf3; font-family: 'Courier New', monospace;
    font-size: 11px; z-index: 999999; border-top: 3px solid #f85149;
    border-left: 3px solid #f85149; border-radius: 6px 0 0 0; overflow: hidden;
    box-shadow: -4px -4px 20px rgba(248,81,73,0.4);
}
#fhs-debug-panel .dp-header {
    background: #161b22; padding: 6px 10px; display: flex;
    justify-content: space-between; align-items: center;
    border-bottom: 1px solid #30363d; cursor: pointer; user-select: none;
}
#fhs-debug-panel .dp-header span { color: #f85149; font-weight: bold; font-size: 12px; }
#fhs-debug-panel .dp-header .dp-count {
    background: #f85149; color: #fff; border-radius: 12px;
    padding: 1px 8px; font-size: 11px; font-weight: bold;
}
#fhs-debug-panel .dp-body { overflow-y: auto; max-height: calc(55vh - 32px); padding: 6px 0; }
#fhs-debug-panel .dp-entry {
    padding: 6px 10px; border-bottom: 1px solid #21262d;
    transition: background 0.2s;
}
#fhs-debug-panel .dp-entry:hover { background: #161b22; }
#fhs-debug-panel .dp-entry .dp-num { color: #f85149; font-weight: bold; }
#fhs-debug-panel .dp-entry .dp-time { color: #3fb950; }
#fhs-debug-panel .dp-entry .dp-source { color: #79c0ff; word-break: break-all; }
#fhs-debug-panel .dp-entry .dp-trigger { color: #ffa657; }
#fhs-debug-panel .dp-entry .dp-interval { color: #d2a8ff; }
#fhs-debug-panel .dp-entry .dp-stack { color: #8b949e; font-size: 10px; margin-top: 3px; word-break: break-all; }
#fhs-debug-panel .dp-clear {
    background: none; border: 1px solid #30363d; color: #8b949e;
    padding: 2px 8px; border-radius: 4px; cursor: pointer; font-size: 10px;
}
#fhs-debug-panel .dp-clear:hover { border-color: #f85149; color: #f85149; }
#fhs-debug-minimised { display: none; }
</style>

<div id="fhs-debug-panel">
    <div class="dp-header" id="fhs-dp-toggle">
        <span>⚡ update_order_review debugger</span>
        <div style="display:flex;gap:8px;align-items:center;">
            <span class="dp-count" id="fhs-dp-count">0</span>
            <button class="dp-clear" id="fhs-dp-clear">Clear</button>
            <span style="color:#8b949e;font-size:10px;" id="fhs-dp-min">▼</span>
        </div>
    </div>
    <div class="dp-body" id="fhs-dp-body"></div>
</div>

<script>
(function() {
    'use strict';

    /* ── state ──────────────────────────────────────────────────── */
    var callLog     = [];
    var callCount   = 0;
    var lastCallTs  = null;
    var panelBody   = document.getElementById('fhs-dp-body');
    var panelCount  = document.getElementById('fhs-dp-count');
    var panelMin    = document.getElementById('fhs-dp-min');
    var panelBody_  = document.getElementById('fhs-dp-body');
    var collapsed   = false;

    /* ── toggle collapse ────────────────────────────────────────── */
    document.getElementById('fhs-dp-toggle').addEventListener('click', function(e) {
        if (e.target.id === 'fhs-dp-clear') return;
        collapsed = !collapsed;
        panelBody_.style.display = collapsed ? 'none' : '';
        panelMin.textContent = collapsed ? '▲' : '▼';
    });
    document.getElementById('fhs-dp-clear').addEventListener('click', function(e) {
        e.stopPropagation();
        callLog = []; callCount = 0; lastCallTs = null;
        panelBody_.innerHTML = '';
        panelCount.textContent = '0';
    });

    /* ── helpers ────────────────────────────────────────────────── */
    function ts() {
        return new Date().toISOString().replace('T',' ').replace('Z','');
    }

    function elapsed() {
        if (lastCallTs === null) return 'first call';
        return '+' + (Date.now() - lastCallTs).toFixed(0) + ' ms since last';
    }

    /**
     * Parse a JS Error stack to extract the most useful non-anonymous frame
     * that is NOT from woocommerce.js / checkout.js core (those are expected),
     * so we can see which PLUGIN or custom script triggered the call.
     */
    function parseStack(stack) {
        if (!stack) return { source: 'unknown', fullStack: '' };
        var lines = stack.split('\n').map(function(l){ return l.trim(); });

        // Known WooCommerce internal frames to skip past when looking for the "real" caller
        var wcCorePatterns = [
            'update_order_review',
            'update_checkout',
            'woocommerce.min.js',
            'woocommerce.js',
            'checkout.js',
            'jquery.min.js',
            'jquery-migrate',
            'fhs-debug'   // skip our own frames
        ];

        var firstExternal = null;
        var allFrames = [];

        for (var i = 1; i < lines.length; i++) {
            var line = lines[i];
            if (!line) continue;
            allFrames.push(line);

            if (!firstExternal) {
                var isCore = wcCorePatterns.some(function(p){ return line.indexOf(p) !== -1; });
                if (!isCore && line.indexOf('http') !== -1) {
                    firstExternal = line;
                }
            }
        }

        // Extract filename from URL in stack line
        function extractFile(frameLine) {
            var urlMatch = frameLine.match(/https?:\/\/[^\s\)]+/);
            if (!urlMatch) return frameLine;
            var url = urlMatch[0].split('?')[0];
            var parts = url.split('/');
            // Return last 3 path segments for context: plugin/subdir/file.js:line:col
            var tail = parts.slice(-3).join('/');
            var lineCol = frameLine.match(/:(\d+):(\d+)\)?$/);
            return tail + (lineCol ? ' [L' + lineCol[1] + ':C' + lineCol[2] + ']' : '');
        }

        return {
            source: firstExternal ? extractFile(firstExternal) : extractFile(allFrames[0] || 'unknown'),
            fullStack: allFrames.slice(0, 8).join('\n')
        };
    }

    /**
     * Determine what WooCommerce event / trigger description is most likely
     * responsible by inspecting the stack for known event binders.
     */
    function guessTrigger(stack) {
        if (!stack) return 'unknown';
        var s = stack.toLowerCase();
        if (s.indexOf('change') !== -1 || s.indexOf('select') !== -1)  return 'field change / select';
        if (s.indexOf('keyup') !== -1 || s.indexOf('keydown') !== -1)  return 'keyboard input';
        if (s.indexOf('blur') !== -1)                                   return 'field blur';
        if (s.indexOf('click') !== -1)                                  return 'click event';
        if (s.indexOf('settimeout') !== -1 || s.indexOf('setinterval') !== -1) return 'timer (setTimeout/setInterval)';
        if (s.indexOf('mutationobserver') !== -1)                       return 'MutationObserver';
        if (s.indexOf('domcontentloaded') !== -1 || s.indexOf('ready') !== -1) return 'DOMContentLoaded / jQuery ready';
        if (s.indexOf('load') !== -1)                                   return 'page load / window.load';
        return 'unknown — see stack';
    }

    /* ── log an interception ─────────────────────────────────────── */
    function logCall(method, url, postBody, stackInfo) {
        callCount++;
        var now = Date.now();
        var interval = elapsed();
        lastCallTs = now;

        var entry = {
            n:        callCount,
            time:     ts(),
            method:   method,
            url:      url,
            source:   stackInfo.source,
            trigger:  guessTrigger(stackInfo.fullStack),
            interval: interval,
            stack:    stackInfo.fullStack,
            body:     postBody ? postBody.substring(0, 300) : ''
        };
        callLog.push(entry);

        /* console group */
        console.group(
            '%c[FHS DEBUG] update_order_review #' + callCount + ' @ ' + ts(),
            'color:#f85149;font-weight:bold;'
        );
        console.log('%cSource file (likely caller):', 'color:#79c0ff', entry.source);
        console.log('%cTrigger type guess:',          'color:#ffa657', entry.trigger);
        console.log('%cInterval:',                    'color:#d2a8ff', entry.interval);
        console.log('%cHTTP method:',                 'color:#3fb950', method);
        if (entry.body) console.log('%cPayload (first 300 chars):', 'color:#8b949e', entry.body);
        console.log('%cFull stack trace:',            'color:#8b949e', '\n' + entry.stack);
        console.groupEnd();

        /* panel */
        panelCount.textContent = callCount;
        var div = document.createElement('div');
        div.className = 'dp-entry';
        div.innerHTML =
            '<div><span class="dp-num">#' + callCount + '</span> ' +
            '<span class="dp-time">' + ts() + '</span> ' +
            '<span class="dp-interval">(' + interval + ')</span></div>' +
            '<div class="dp-source">📄 ' + escHtml(entry.source) + '</div>' +
            '<div class="dp-trigger">⚡ ' + escHtml(entry.trigger) + '</div>' +
            '<details><summary class="dp-stack" style="cursor:pointer;">Stack trace ▸</summary>' +
            '<pre class="dp-stack" style="margin:4px 0 0;white-space:pre-wrap;">' + escHtml(entry.stack) + '</pre></details>';
        panelBody_.appendChild(div);
        panelBody_.scrollTop = panelBody_.scrollHeight;

        /* collapse panel back open if minimised */
        if (collapsed) {
            collapsed = false;
            panelBody_.style.display = '';
            panelMin.textContent = '▼';
        }
    }

    function escHtml(s) {
        return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    /* ── INTERCEPT XMLHttpRequest ────────────────────────────────── */
    var OrigXHR  = window.XMLHttpRequest;
    var OrigOpen = OrigXHR.prototype.open;
    var OrigSend = OrigXHR.prototype.send;

    OrigXHR.prototype.open = function(method, url) {
        this._fhsMethod = method;
        this._fhsUrl    = (url || '').toString();
        return OrigOpen.apply(this, arguments);
    };

    OrigXHR.prototype.send = function(body) {
        var url    = this._fhsUrl || '';
        var isUOR  = url.indexOf('update_order_review') !== -1 ||
                     (typeof body === 'string' && body.indexOf('update_order_review') !== -1);
        if (isUOR) {
            var stack = (new Error('XHR intercepted')).stack;
            var info  = parseStack(stack);
            logCall(this._fhsMethod || 'XHR', url, typeof body === 'string' ? body : null, info);
        }
        return OrigSend.apply(this, arguments);
    };

    /* ── INTERCEPT fetch() ───────────────────────────────────────── */
    if (window.fetch) {
        var origFetch = window.fetch;
        window.fetch  = function(resource, init) {
            var url  = (typeof resource === 'string' ? resource : (resource && resource.url)) || '';
            var body = (init && init.body) ? String(init.body) : '';
            var isUOR = url.indexOf('update_order_review') !== -1 ||
                        body.indexOf('update_order_review') !== -1;
            if (isUOR) {
                var stack = (new Error('fetch intercepted')).stack;
                var info  = parseStack(stack);
                logCall('fetch', url, body, info);
            }
            return origFetch.apply(this, arguments);
        };
    }

    /* ── INTERCEPT jQuery $.ajax (as a belt-and-suspenders check) ── */
    /* We do this after DOMContentLoaded so jQuery is guaranteed loaded */
    function hookJQueryAjax() {
        if (typeof jQuery === 'undefined') return;

        jQuery(document).ajaxSend(function(event, jqXHR, settings) {
            var url  = settings.url  || '';
            var data = settings.data || '';
            var isUOR = url.indexOf('update_order_review') !== -1 ||
                        (typeof data === 'string' && data.indexOf('update_order_review') !== -1);
            if (isUOR) {
                var stack = (new Error('$.ajax intercepted')).stack;
                var info  = parseStack(stack);
                // de-duplicate: XHR hook above will also fire; mark this one with prefix
                console.log('%c[FHS DEBUG] jQuery ajaxSend confirmed the above call', 'color:#3fb950');
            }
        });

        /* Also spy on the WC checkout jQuery event that WC itself fires
           right before it dispatches the AJAX – this gives us the cleanest trigger */
        jQuery(document.body).on('update_checkout', function(e, args) {
            var stack = (new Error('update_checkout event')).stack;
            var info  = parseStack(stack);
            console.log(
                '%c[FHS DEBUG] WC update_checkout event fired — args:',
                'color:#ffa657;font-weight:bold;', args,
                '\n→ Caller:', info.source,
                '\n→ Stack:\n', info.fullStack
            );
        });
    }

    /* ── WATCH checkout field DOM mutations that trigger updates ─── */
    function watchCheckoutFields() {
        var checkoutForm = document.querySelector('form.woocommerce-checkout');
        if (!checkoutForm) return;

        /* Track which inputs actually change to correlate with AJAX calls */
        var lastChanged = null;
        checkoutForm.addEventListener('change', function(e) {
            if (e.target && e.target.name) {
                lastChanged = { field: e.target.name, value: e.target.value, time: Date.now() };
                console.log(
                    '%c[FHS DEBUG] Checkout field changed:',
                    'color:#d2a8ff;font-weight:bold;',
                    e.target.name, '=', e.target.value,
                    '— may trigger update_order_review'
                );
            }
        }, true);

        /* MutationObserver on the order review block (WC replaces it on each AJAX response) */
        var orderReview = document.getElementById('order_review');
        if (orderReview) {
            var mo = new MutationObserver(function(mutations) {
                console.log(
                    '%c[FHS DEBUG] #order_review DOM updated (' + mutations.length + ' mutation(s)) — ' +
                    'this happens AFTER update_order_review response was processed',
                    'color:#3fb950;'
                );
            });
            mo.observe(orderReview, { childList: true, subtree: true });
        }
    }

    /* ── REPORT a summary after 30 s of monitoring ──────────────── */
    setTimeout(function() {
        if (callCount === 0) {
            console.log('%c[FHS DEBUG] 30 s elapsed — update_order_review was NOT called yet', 'color:#3fb950;font-weight:bold;');
            return;
        }
        console.group('%c[FHS DEBUG] 30-second summary', 'color:#f85149;font-weight:bold;');
        console.log('Total calls:', callCount);

        /* tally by source */
        var tally = {};
        callLog.forEach(function(e) {
            tally[e.source] = (tally[e.source] || 0) + 1;
        });
        console.log('Calls by source file:');
        Object.keys(tally).sort(function(a,b){ return tally[b]-tally[a]; }).forEach(function(k) {
            console.log('  ' + tally[k] + 'x  ' + k);
        });

        /* tally by trigger type */
        var tt = {};
        callLog.forEach(function(e) {
            tt[e.trigger] = (tt[e.trigger] || 0) + 1;
        });
        console.log('Calls by trigger type:');
        Object.keys(tt).sort(function(a,b){ return tt[b]-tt[a]; }).forEach(function(k) {
            console.log('  ' + tt[k] + 'x  ' + k);
        });

        if (callCount >= 2) {
            var gaps = [];
            for (var i = 1; i < callLog.length; i++) {
                gaps.push((new Date(callLog[i].time) - new Date(callLog[i-1].time)));
            }
            var avgGap = gaps.reduce(function(a,b){return a+b;},0) / gaps.length;
            console.log('Average interval between calls: ' + avgGap.toFixed(0) + ' ms');
            if (avgGap < 3000) {
                console.warn('%c⚠  Calls firing more often than every 3 s — likely a poll/timer or cascade loop', 'color:#f85149;font-weight:bold;');
            }
        }
        console.groupEnd();
    }, 30000);

    /* ── REPORT again after 5 minutes ──────────────────────────── */
    setTimeout(function() {
        if (callCount === 0) return;
        console.group('%c[FHS DEBUG] 5-minute summary', 'color:#f85149;font-weight:bold;');
        console.log('Total calls in 5 min:', callCount);
        console.log('Full call log:', callLog);
        console.groupEnd();
    }, 300000);

    /* ── init once DOM is ready ──────────────────────────────────── */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            hookJQueryAjax();
            watchCheckoutFields();
            console.log('%c[FHS DEBUG] update_order_review debugger active — all XHR + fetch + jQuery.ajax intercepted', 'color:#3fb950;font-weight:bold;');
        });
    } else {
        hookJQueryAjax();
        watchCheckoutFields();
        console.log('%c[FHS DEBUG] update_order_review debugger active', 'color:#3fb950;font-weight:bold;');
    }

})();
</script>
<?php endif; ?>

