<?php
/**
 * User: dai.ho
 * Date: 24/02/2021
 * Time: 6:17 PM
 */

namespace App\Soap\Request;


class SoOAMDetail
{
    /**
     * SoOAMDetail constructor.
     * @param array $detail
     */
    public function __construct(array $detail)
    {
        $this->productCode = $detail['productCode'];
        $this->productName = $detail['productName'];
        $this->quantity = $detail['quantity'];
        $this->isFreeItem = $detail['isFreeItem'];
        $this->price = $detail['price'];
        $this->amount = $detail['amount'];
        $this->discountAmount = $detail['discountAmount'];
        $this->discountPercent = $detail['discountPercent'];
        $this->promotionProgramCode = $detail['promotionProgramCode'];
        $this->vat = $detail['vat'];
        $this->lineNumber = $detail['lineNumber'];
    }
}