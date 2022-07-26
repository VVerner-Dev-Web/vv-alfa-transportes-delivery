<?php defined('ABSPATH') || exit('No direct script access allowed');

add_filter('woocommerce_shipping_methods', function( array $methods = [] ) {
    $methods[] = 'Alfa_Transportes_Shipping_Method';
    return $methods;
});

add_filter('wocommerce_after_shipping_rate', function( WC_Shipping_Rate $shipping ) {
    $meta_data = $shipping->get_meta_data();
    $total     = isset($meta_data['delivery_forecast'] ) ? (int) $meta_data['delivery_forecast'] : 0;

    if ($total) :
        $message = $total . _n('dia para entrega', 'dias para entrega', $total);
        echo '<p><small>'. $message . '</small><p>';
    endif;
});