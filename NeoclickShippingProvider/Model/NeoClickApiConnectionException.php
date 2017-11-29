<?php
namespace IIA\NeoclickShippingProvider\Model;

/**
 *
 * @author pawelszczepaniak
 *
 */
class NeoClickApiConnectionException extends \Exception
{

    /**
     *
     * @return string
     */
    public function errorMessage()
    {
        $errorMsg = 'Problem when connecting or getting result from NeoClick API';
        return $errorMsg;
    }
}

