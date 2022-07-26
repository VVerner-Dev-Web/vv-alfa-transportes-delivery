<?php

namespace VVerner\AlfaTransportesDelivery;

use \WC_Shipping_Rate;

defined('ABSPATH') || exit('No direct script access allowed');

class App
{
    public static function init(): void
    {
        $handler = new self();
        if (!$handler->isWooCommerceInstalled()) :
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            deactivate_plugins(plugin_basename(VVATD_FILE));
            add_action('admin_notices', [$handler, 'displayWooCommerceMissingAlert']);
        
        else:
            $handler->enqueueHooks();

        endif;
    }
    
    public function isWooCommerceInstalled(): bool
    {
        $plugins = apply_filters('active_plugins', get_option('active_plugins'));
        return in_array('woocommerce/woocommerce.php', $plugins);
    }

    public function enqueueHooks(): void
    {
        add_action('plugins_loaded', [$this, 'loadFiles'], 999);
    }

    public static function loadFiles( string $path = null ): void
    {
        $path = $path ? $path : VVATD_APP . 'controller';
        $isPhpFile = strpos($path, '.php') !== false;

        if (is_dir($path)) :
            $ignoredFiles = ['index.php', '..', '.'];
            $dependencies = array_diff(scandir($path), $ignoredFiles);

            foreach ($dependencies as $dependency) :
                $dPath = $path . DIRECTORY_SEPARATOR . $dependency;
                self::loadFiles($dPath);
            endforeach;

        elseif (is_file($path) && $isPhpFile) :
            require_once $path;
        endif;
    }

    public function displayWooCommerceMissingAlert(): void
    {
        echo '<div class="notice error">
                <p><strong>Integração Alfa Transportes:</strong> Por favor, instale o WooCommerce para utilizar a integração com o Alfa Transportes</p>
              </div>';
    }
}

App::init();