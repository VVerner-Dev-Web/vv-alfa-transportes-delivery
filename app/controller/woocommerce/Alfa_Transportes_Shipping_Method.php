<?php defined('ABSPATH') || exit('No direct script access allowed');

class Alfa_Transportes_Shipping_Method extends WC_Shipping_Method
{
    public function __construct()
    {
        $this->id                   = 'alfa-transportes-shipping';
        $this->method_title         = 'Alfa Transporte';
        $this->method_description   = 'Entrega pela Alfa Transportes';
        $this->availability         = 'including';
        $this->countries            = ['BR'];

        $this->init();
        
        $this->enabled              = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
        $this->title                = isset($this->settings['title'])   ? $this->settings['title']   : 'Alfa Transportes';
        $this->isShowingForecast    = $this->settings['show_estimation'] === 'yes';
        $this->minWeight            = (int) $this->settings['min_weight'];
        $this->isLogEnabled         = $this->settings['debug'] === 'yes';
        $this->logger               = $this->isLogEnabled ? wc_get_logger() : null;

        $this->api_token            = $this->settings['token'];
    }

    public function init()
    {
        $this->init_form_fields();
        $this->init_settings();
       
        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'token'             => [
                'type'     => 'text',
                'title'    => 'Token da API',
            ],
            'min_weight'        => [
                'type'     => 'number',
                'title'    => 'Peso mínimo para entrega',
                'default'  => 0
            ],
            'show_estimation'   => [
                'type'     => 'select',
                'class'    => 'wc-enhanced-select',
                'title'    => 'Estimativa da Entrega',
                'default'  => '0',
                'options'  => [
                    'no'  => 'Não Exibir',
                    'yes' => 'Exibir'
                ]
            ],
            'debug'            => [
                'type'     => 'select',
                'class'    => 'wc-enhanced-select',
                'title'    => 'Ativar log de requisições',
                'default'  => '0',
                'options'  => [
                    'no'  => 'Não habilitar',
                    'yes' => 'Habilitar logs' 
                ]
            ]
        ];
    }

    public function log($thing): void
    {
        if ($this->logger) :
            $message = print_r($thing,true);
            $this->logger->debug($message, ['source' => 'alfa-transportes-shipping']);
        endif;
    }

    public function calculate_shipping($_package = []){
        $this->log('================================================');
        $this->log('Nova Requisição de cálculo de frete');

        $zip = isset($_package['destination']['postcode']) ? preg_replace('/\D/','', $_package['destination']['postcode']) : '';

        if (!$zip) :
            $this->log('Nenhum CEP enviado. Finalizando requisição');
            return;
        endif;

        $api = $this->getApi();

        if (!$api) :
            $this->log('API indisponível por falta de token');
            return;
        endif;

        $packageData = $this->getPackageData( $_package );

        if ($this->minWeight > $packageData->weight) :
            $this->log('Peso do carrinho inferior ao configurado');
            return;
        endif;

        $estimate = $api->getEstimate(
            $zip,
            $packageData->price,
            $packageData->weight
        );

        $this->log('CEP para Simulação: ' . $zip);
        $this->log('Dados do pacote simulado');
        $this->log($packageData);
        $this->log($estimate);

        if (!is_wp_error($estimate)) :
            $this->log('Inserindo métodos de envio para escolha do cliente');
            $this->add_rate([
                'id'        => $this->id,
                'label'     => 'Alfa Transporte',
                'cost'      => $estimate->price,
                'meta_data' => [
                    'delivery_forecast' => $estimate->forecast && $this->isShowingForecast ? $estimate->forecast : 0
                ]
            ]);
        endif;

        $this->log('Finalizando requisição');
    }

    private function getApi(): ?\VVerner\AlfaTransportesDelivery\API
    {
       return $this->api_token ? new \VVerner\AlfaTransportesDelivery\API( $this->api_token ) : null;
    }

    private function getPackageData( array $package ): stdClass
    {
        $response = (object) [
            'price'  => 0,
            'weight' => 0
        ];

        foreach ($package['contents'] as $values) :
            $product = $values['data'];
            $qty     = $values['quantity'];
            $weight  = (float) wc_get_weight((float) $product->get_weight(), 'kg');
   
            if ($qty > 0 && $product->needs_shipping() && $weight) :
               $response->price += $values['line_subtotal'];
               $response->weight += $qty * $weight;
            endif;
        endforeach;

        return $response;
    }
}