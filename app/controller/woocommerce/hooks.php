<?php defined('ABSPATH') || exit('No direct script access allowed');

add_filter('woocommerce_shipping_methods', function( array $methods = [] ) {
    $methods[] = 'Alfa_Transportes_Shipping_Method';
    return $methods;
});

add_filter('woocommerce_after_shipping_rate', function( WC_Shipping_Rate $shipping ) {
    $meta_data = $shipping->get_meta_data();
    $forecast  = isset($meta_data['delivery_forecast'] ) ? $meta_data['delivery_forecast'] : 0;

    if ($forecast) :
        echo '<p><small>'. $forecast . '</small><p>';
    endif;
});