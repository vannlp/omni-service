<?php
/**
 * User: dai.ho
 * Date: 5/03/2021
 * Time: 3:50 PM
 */

namespace App\Exceptions;


class DaiException extends \Exception
{
    private $_data;

    public function __construct(
        $message,
        $code = 0,
        \Exception $previous = null,
        $data = []
    ) {
        parent::__construct($message, $code, $previous);

        $this->_data = $data;
    }

    public function getData()
    {
        return $this->_data;
    }
}