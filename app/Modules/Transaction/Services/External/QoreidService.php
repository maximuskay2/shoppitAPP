<?php

namespace App\Modules\Transaction\Services\External;

class QoreidService
{
     /**
     * The base URL for Qoreid API.
     *
     * @var string
     */
    private static $baseUrl;

    /**
     * QoreidService constructor.
     *
     * @param string $baseUrl The base URL for Qoreid API.
     */
    public function __construct(string $baseUrl)
    {
        self::$baseUrl = $baseUrl;
    }   
}