<?php defined('ABSPATH') || exit('No direct script access allowed');

/*
    * Plugin Name: VVerner - Alfa Transportes Delivery
    * Description: Integra o sistema de fretes Alfa Transportes ao Woocommerce 
    * Author: VVerner
    * Author: https://vverner.com
    * Version: 0.1
    * Requires at least:
    * Tested up to:
    * Requires PHP:
*/

define('VVATD_FILE', __FILE__);
define('VVATD_APP', __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR);
require_once VVATD_APP . 'App.php';


$app = new VVATD\App();
$app->init();

