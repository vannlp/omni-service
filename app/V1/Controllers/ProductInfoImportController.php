<?php


namespace App\V1\Controllers;


use App\Company;
use App\Product;
use App\ProductFavorite;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\ProductInfoImportModel;

use App\V1\Validators\ProductInfoImport\ProductInfoImportValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductInfoImportController extends BaseController
{
    /**
     * @var ProductInfoImportModel
     */
    protected $model;
    protected $productModel;

    /**
     * ProductInfoImportController constructor.
     */
    public function __construct()
    {
        $this->model = new ProductInfoImportModel();
    }


    public function create(Request $request, ProductInfoImportValidator $createValidator)
    {
        $input = $request->all();
        $createValidator->validate($input);

        try {
            DB::beginTransaction();
            $promotion = $this->ProductInfoImportModel->upsert($input);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $promotion->title)];
    }
}