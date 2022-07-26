<?php defined('ABSPATH') || exit('No direct script access allowed');

class Alfa_Transportes_Shipping_Method extends WC_Shipping_Method
{
    public function __construct( $instance_id = 0 )
    {
        $this->id                   = 'alfa-transportes-shipping';
		$this->instance_id          = absint( $instance_id );
        $this->method_title         = 'Alfa Transporte';
        $this->method_description   = 'Entrega pela Alfa Transportes';
        $this->availability         = 'including';
        $this->countries            = ['BR'];

        $this->init();
        
        $this->enabled              = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
        $this->title                = isset($this->settings['title'])   ? $this->settings['title']   : 'Alfa Transportes';
        $this->showingForecast      = $this->settings['show_estimation'] === 'yes';
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

       $api         = $this->getApi();
       $package     = $this->getPackage($_package);

       $api->setPackage($package);

       $estimates   = $api->fetchEstimate($zip);
       $counter     = $estimates ? count($estimates) : '0';

       $this->log('CEP para Simulação: ' . $zip);
       $this->log('Dados do pacote simulado');
       $this->log($package->get_data());
       $this->log($counter . 'fretes válidos recebidos');
       $this->log($estimates);


       if ($estimates) :
            $this->log('Inserindo métodos de envio para escolha do cliente');
            foreach ($estimates as $estimate) :
                $foreacast = $estimate->delivery_forecast;
            
                $this->add_rate([
                    'id'        => $estimate->key,
                    'label'     => 'Alfa Transporte '. $estimate->type,
                    'cost'      => $estimate->price,
                    'meta_data' => [
                        'delivery_forecast' => $foreacast && $this->isShowingForeacast ? $foreacast : 0
                    ]
                ]);
            endforeach;
        endif;

        $this->log('Finalizando requisição');

    }

    // private function getApi(): \Aciulog\API
    // {
    //    return new Aciulog\API($this);
    // }
 
    // private function getPackage(array $package): \Aciulog\Package
    // {
    //    return new Aciulog\Package($package);
    // }

}