<?php

namespace VVerner\AlfaTransportesDelivery;

use SimpleXMLElement;

defined('ABSPATH') || exit('No direct script access allowed');

class API 
{
    private string $token;
    private string $destinationZip;
    private int $contentPrice;
    private int $contentWeight;

    private const URL = 'http://www.alfatransportes.com.br/ws/cotacao';

    public function __construct( string $token )
    {
        $this->token = trim( sanitize_text_field( $token ) );
    }

    public function getEstimate( string $destinationZip, float $contentPrice, float $contentWeight )
    {
        $this->destinationZip   = trim( preg_replace('/\D/', '', $destinationZip) );
        $this->contentPrice     = (int) round($contentPrice);
        $this->contentWeight    = (int) round($contentWeight);

        $response = $this->fetchEstimate();
        $response = new SimpleXMLElement( $response );
    }

    private function getClientType( string $type ): int
    {
        return $type === 'pf' ? 2 : 1;
    }

    private function getCubicMeters( int $weight ): float
    {
        return $weight / 100;
    }

    private function fetchEstimate()
    {
        $url      = add_query_arg( $this->getArgs(), self::URL );
        $request  = wp_remote_post( $url );
        $response = wp_remote_retrieve_body( $request );
        return $response;
    }

    private function getArgs(): array
    {
        return [
            'idr'       => $this->token,
            'cliTip'    => $this->getClientType( 'pf' ),
            'cliCep'    => $this->destinationZip,
            'merVlr'    => $this->contentPrice,
            'merPeso'   => $this->contentWeight,
            'merM3'     => $this->getCubicMeters( $this->contentWeight ),
            'quim'      => 0
        ];
    }
}