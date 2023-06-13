<?php
/**
 * User: kpistech2
 * Date: 2020-11-14
 * Time: 13:20
 */

namespace App\Exports;


class BaseExport
{
    protected $_body;

    /**
     * BaseExport constructor.
     */
    public function __construct()
    {
    }

    public function setBodyArray(array $data)
    {
        $this->_body = $data;
    }

    public function addBodyItem(array $item)
    {
        $this->_body[] = $item;
    }
}