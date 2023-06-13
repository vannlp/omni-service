<?php
/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:49 PM
 */

namespace App\V1\Models;


use App\PromotionDetail;

class PromotionDetailModel extends AbstractModel
{
    /**
     * OrderDetailModel constructor.
     *
     * @param PromotionDetail|null $model
     */
    public function __construct(PromotionDetail $model = null)
    {
        parent::__construct($model);
    }
}