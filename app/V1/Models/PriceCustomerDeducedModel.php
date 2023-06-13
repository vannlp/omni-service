<?php
namespace App\V1\Models;

use App\Module;
use App\NewsPost;
use App\PriceCustomerDeduced;
use App\PriceInfo;
use App\Supports\Message;
use App\TM;

class PriceCustomerDeducedModel extends AbstractModel
{
    public function __construct(PriceCustomerDeduced $model = null)
    {
        parent::__construct($model);
    }


}
