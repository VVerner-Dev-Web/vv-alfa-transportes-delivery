<?php

namespace VVerner\AlfaTransportesDelivery;

use SimpleXMLElement;
use stdClass;
use WP_Error;

defined('ABSPATH') || exit('No direct script access allowed');

class API 
{
    private string $token;
    private string $destinationZip;
    private int $contentPrice;
    private int $contentWeight;

    private const URL = 'http://www.alfatransportes.com.br/ws/cotacao';
    private const SUCCESS_STATUS = 1;

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
        $errors   = $this->traitErrors( $response );

        return $errors ? $errors : $this->parseEstimate( $response );
    }

    private function traitErrors( string $estimateFromApi ): ?WP_Error
    {
        if (!$estimateFromApi) :
            return new WP_Error(-1, 'Retorno vazio da API');
        endif;

        $xml        = new SimpleXMLElement( $estimateFromApi );
        $statusCol  = (array) $xml->cot->cotStatus;
        $statusCode = (int) $statusCol['@attributes']['stsCd'];

        if ($statusCode !== self::SUCCESS_STATUS) : 
            return new WP_Error($statusCode, $statusCol[0]);
        endif;

        return null;
    }

    private function parseEstimate( string $estimateFromApi ): stdClass
    {
        $xml        = new SimpleXMLElement( $estimateFromApi );
        $cotArray = (array) $xml->cot;

        $estimate = new stdClass();
        $estimate->id       = $cotArray['@attributes']['id'];
        $estimate->price    = (float) $xml->cot->vlr->cotVlrTot;
        $estimate->forecast = (string) $xml->cot->ent->entPrev;

        return $estimate;
    }

    private function getClientType( string $type ): int
    {
        return $type === 'pf' ? 2 : 1;
    }

    private function getCubicMeters( int $weight ): float
    {
        return $weight / 1000;
    }

    private function fetchEstimate(): string
    {
        $url      = add_query_arg( $this->getArgs(), self::URL );
        $request  = wp_remote_post( $url );
        $response = is_wp_error($request) ? '' : wp_remote_retrieve_body( $request );
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