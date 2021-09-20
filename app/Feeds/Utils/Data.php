<?php

namespace App\Feeds\Utils;

use App\Helpers\PrintHelper;
use Exception;

class Data
{
    private const EF_JSON_DECODE = "Data getJSON: %s";
    private const DEFAULT_OUTPUT_ENCODING = 'UTF-8';

    private string $raw_data;
    private int $status_code;

    public function __construct( string $raw_data = '', int $status_code = 200 )
    {
        $this->raw_data = $raw_data;
        $this->status_code = $status_code;
    }

    /**
     * @param string|null $encoding - pass if need change encoding data
     * @return string
     */
    public function getData( string $encoding = null ): string
    {
        if ( $encoding ) {
            return iconv( $encoding, self::DEFAULT_OUTPUT_ENCODING, $this->raw_data );
        }

        return $this->raw_data;
    }

    /**
     * @param string $raw_data
     */
    public function setData( string $raw_data ): void
    {
        $this->raw_data = $raw_data;
    }

    public function getJSON( string $encoding = null ): array
    {
        try {
            $json = preg_replace( '/[[:cntrl:]]/', '', $this->getData( $encoding ) );
            return json_decode( $json, true, 512, JSON_THROW_ON_ERROR );
        } catch ( Exception $e ) {
            PrintHelper::printfError( self::EF_JSON_DECODE, $e->getMessage() );
            return [];
        }
    }

    public function setStatusCode( int $status_code ): void
    {
        $this->status_code = $status_code;
    }

    public function getStatusCode(): int
    {
        return $this->status_code;
    }

    public function __toString()
    {
        return $this->raw_data;
    }
}
