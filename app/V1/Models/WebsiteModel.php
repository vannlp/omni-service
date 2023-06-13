<?php


namespace App\V1\Models;


use App\Website;

class WebsiteModel extends AbstractModel
{
    /**
     * WebsiteModel constructor.
     * @param Website|null $model
     */
    public function __construct(Website $model = null)
    {
        parent::__construct($model);
    }
}