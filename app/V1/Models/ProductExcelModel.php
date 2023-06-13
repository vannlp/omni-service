<?php


namespace App\V1\Models;


use App\OrderDetail;
use App\Product;
use App\ProductExcel;
use App\Profile;
use App\ReasonCancel;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use Illuminate\Support\Facades\DB;

class ProductExcelModel extends AbstractModel
{
    /**
     * ProductCommentModel constructor.
     * @param ProductExcel|null $model
     */
    public function __construct(ProductExcel $model = null)
    {
        parent::__construct($model);
    }

  
}