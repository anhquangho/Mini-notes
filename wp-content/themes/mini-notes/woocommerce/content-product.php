<?php
defined('ABSPATH') || exit;

global $product;

if (!is_a($product, WC_Product::class) || !$product->is_visible()) {
    return;
}
?>
<li <?php wc_product_class('mn-product-card', $product); ?>>
    <div class="mn-product-media">
        <?php do_action('woocommerce_before_shop_loop_item'); ?>
        <?php do_action('woocommerce_before_shop_loop_item_title'); ?>
    </div>
    <div class="mn-product-body">
        <?php do_action('woocommerce_shop_loop_item_title'); ?>
        <?php do_action('woocommerce_after_shop_loop_item_title'); ?>
        <?php do_action('woocommerce_after_shop_loop_item'); ?>
    </div>
</li>
