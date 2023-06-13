<?php

namespace App\V1\Controllers;

use App\ProductAttribute;
use App\Supports\Log;
use App\Supports\Message;
use App\V1\Models\ProductAttributeModel;
use App\V1\Validators\ProductAttributeUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductAttributeController extends BaseController
{
    /**
     * @var ProductAttributeModel $model
     */
    protected $model;

    /**
     * ProductAttributeController constructor.
     *
     * @param ProductAttributeModel|null $productAttributeModel
     */
    public function __construct(ProductAttributeModel $productAttributeModel = null)
    {
        $this->model = $productAttributeModel ?: new ProductAttributeModel();
    }

    /**
     * Get list by product ID
     *
     * @param $productId
     * @return array
     */
    public function getListByProductId($productId)
    {
        $productAttributes = $this->model->getListByProductId($productId);
        Log::view($this->model->getTable());
        return ['data' => $productAttributes];
    }

    /**
     * Update
     *
     * @param $productId
     * @param Request $request
     * @param ProductAttributeUpdateValidator $productAttributeUpdateValidator
     * @return array
     * @throws \Exception
     */
    public function update($productId, Request $request, ProductAttributeUpdateValidator $productAttributeUpdateValidator)
    {
        $input               = $request->all();
        $input['product_id'] = $productId;
        $productAttributeUpdateValidator->validate($input);
        $this->model->syncData($input);
        Log::update($this->model->getTable(), "#ID:" . $productId);
        return ['status' => Message::get("product_attributes.update-success", Message::get('product_attribute'))];
    }

    public function delete($id)
    {
        $result = ProductAttribute::find($id);
        if (empty($result)) {
            return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
        }
        $query = "DELETE FROM `product_attributes` WHERE `id` = {$id}";
        DB::statement($query);
        return ['status' => Message::get("R003", "#$result->id")];
    }
}