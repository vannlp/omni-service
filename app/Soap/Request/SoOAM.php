<?php
/**
 * User: dai.ho
 * Date: 24/02/2021
 * Time: 6:05 PM
 */

namespace App\Soap\Request;


class SoOAM
{

    /**
     * SoOAM constructor.
     * @param $args
     */
    public function __construct($args)
    {
        $this->orderNumber = $args['orderNumber'];
        $this->createDate = $args['createDate'];
        $this->updateDate = $args['updateDate'];
        $this->shortCode = $args['shortCode'];
        $this->customerName = $args['customerName'];
        $this->phone = $args['phone'];
        $this->address = $args['address'];
        $this->lat = $args['lat'];
        $this->lng = $args['lng'];
        $this->shopCode = $args['shopCode'];
        $this->status = $args['status'];
        $this->deliveryDate = $args['deliveryDate'];
        $this->description = $args['description'];
        $this->amount = $args['amount'];
        $this->discountAmountSo = $args['discountAmountSo'];
        $this->discountPercentSo = $args['discountPercentSo'];
        $this->discount = $args['discount'];
        $this->total = $args['total'];
        $this->totalDetail = $args['totalDetail'];
        $details = [];
        foreach ($args['soOAMDetails'] as $soOAMDetail) {
            $details[] = new SoOAMDetail($soOAMDetail);
        }
        $this->soOAMDetails = $details;
    }
}