<?php

namespace App\V1\Controllers;

use App\ProductVariant;
use App\Supports\Log;
use App\Supports\Message;
use App\V1\Models\ProductAttributeModel;
use App\V1\Models\ProductVariantModel;
use App\V1\Models\ProductVariantPromotionModel;
use App\V1\Transformers\ProductVariant\ProductVariantTransformer;
use App\V1\Validators\ProductVariantUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductVariantController extends BaseController
{
    /**
     * @var ProductVariantModel $productVariantModel
     */
    protected $productVariantModel;

    /**
     * @var ProductAttributeModel $productAttributeModel
     */
    protected $productAttributeModel;

    /**
     * @var ProductVariantPromotionModel $productVariantPromotionModel
     */
    protected $productVariantPromotionModel;

    /**
     * ProductVariantController constructor.
     *
     * @param ProductVariantModel|null $productVariantModel
     * @param ProductAttributeModel|null $productAttributeModel
     * @param ProductVariantPromotionModel|null $productVariantPromotionModel
     */
    public function __construct(ProductVariantModel $productVariantModel = null,
                                ProductAttributeModel $productAttributeModel = null,
                                ProductVariantPromotionModel $productVariantPromotionModel = null)
    {
        $this->productVariantModel          = $productVariantModel ?: new ProductVariantModel();
        $this->productAttributeModel        = $productAttributeModel ?: new ProductAttributeModel();
        $this->productVariantPromotionModel = $productVariantPromotionModel ?: new ProductVariantPromotionModel();
    }

    /**
     * List product variant by product id
     *
     * @param $productId
     * @return \Dingo\Api\Http\Response
     */
    public function listProductVariantByProductId($productId)
    {
        $productVariants = $this->productVariantModel->make([
            'promotions'
        ])
            ->where('product_id', $productId)
            ->get();

        if (!$productVariants->isEmpty()) {
            $productAttributes = $this->productAttributeModel->getListByProductId($productId);
            $attributeWithName = [];

            foreach ($productAttributes as $productAttribute) {
                foreach ($productAttribute['details'] as $detail) {
                    $attributeWithName[$detail['product_attribute_id']] = $detail['name'];
                }
            }

            $productVariants = $productVariants->map(function ($productVariant) use ($attributeWithName) {
                $name                = [];
                $productAttributeIds = $productVariant->product_attributes;
                foreach ($productAttributeIds as $id) {
                    if (!empty($attributeWithName[$id])) {
                        $name[] = $attributeWithName[$id];
                    }
                }
                $productVariant->product_attribute_name = implode(' - ', $name);

                return $productVariant;
            });
        }

        return $this->response->collection($productVariants, (new ProductVariantTransformer()));
    }

    /**
     * Combination product attribute
     *
     * @param $productId
     * @return array
     */
    public function combinationAttribute($productId)
    {
        $productAttributes = $this->productAttributeModel->getListByProductId($productId, true);

        $data              = [];
        $attributeWithName = [];

        foreach ($productAttributes as $productAttribute) {
            $data[] = collect($productAttribute['details'])->pluck('product_attribute_id')->toArray();

            foreach ($productAttribute['details'] as $detail) {
                $attributeWithName[$detail['product_attribute_id']] = $detail['name'];
            }
        }

        $results      = [];
        $combinations = $this->productVariantModel->generateCombinations($data);

        if (!empty($combinations)) {
            foreach ($combinations as $combination) {
                if (!empty($combination)) {
                    $name = [];
                    foreach ($combination as $id) {
                        if (!empty($attributeWithName[$id])) {
                            $name[] = $attributeWithName[$id];
                        }
                    }
                    $results[] = [
                        'ids'  => $combination,
                        'name' => implode(' - ', $name)
                    ];
                }
            }
        }

        if (!empty($results)) {
            $productVariants = ProductVariant::all();
            if (!$productVariants->isEmpty()) {
                foreach ($productVariants as $productVariant) {
                    foreach ($results as $key => $item) {
                        if (!array_diff($item['ids'], $productVariant->product_attributes)) {
                            unset($results[$key]);
                        }
                    }
                }
            }
        }


        return ['data' => array_values($results)];
    }


    /**
     * Store
     *
     * @param $productId
     * @param Request $request
     * @param ProductVariantUpdateValidator $productVariantUpdateValidator
     * @return array|void
     * @throws \Exception
     */
    public function update($productId, Request $request, ProductVariantUpdateValidator $productVariantUpdateValidator)
    {
        $input               = $request->all();
        $input['product_id'] = $productId;

        $productVariantUpdateValidator->validate($input);

//        if (!empty($input['data'])) {
//            foreach ($input['data'] as $item) {
//                $checkProductCode = $this->productVariantModel->getModel()
//                    ->where('product_code', $item['product_code'])
//                    ->where(function ($query) use ($item, $input) {
//                        $query->where('product_id', $input['product_id']);
//                        if (!empty($item['id'])) {
//                            $query->where('id', '!=', $item['id']);
//                        }
//                    })
//                    ->exists();
//
//                if ($checkProductCode) {
//                    return $this->response->errorBadRequest(Message::get('unique', $item['product_code']));
//                }
//            }
//        }

        try {
            DB::beginTransaction();

            foreach ($input['data'] as $key => $item) {
                $item['product_id'] = $productId;
                if (!empty($item['id'])) {
                    $result                     = $this->productVariantModel->getFirstWhere(['id' => $item['id']]);
                    $result->product_id         = $item['product_id'];
                    $result->product_attributes = $item['product_attributes'];
                    $result->product_code       = Arr::get($item, 'product_code', null);
                    $result->image              = Arr::get($item, 'image', null);
                    $result->inventory          = 0;
                    $result->price              = (float)Arr::get($item, 'price', 0);
                    $result->is_active          = Arr::get($item, 'is_active', true);
                    $result->save();
//                    $this->productVariantModel->getModel()
//                        ->where('id', $item['id'])
//                        ->update($this->productVariantModel->fillData($item));

                    $productVariant = $this->productVariantModel->byId($item['id']);
                } else {
//                    dd($this->productVariantModel->fillData($item));
                    $productVariant = $this->productVariantModel->getModel()->create($this->productVariantModel->fillData($item));
                }

                if (!empty($item['promotions'])) {
                    foreach ($item['promotions'] as $promotion) {
                        $promotion['product_variant_id'] = $productVariant->id;
                        if (!empty($promotion['id'])) {
                            $this->productVariantPromotionModel->getModel()
                                ->where('id', $promotion['id'])
                                ->update($this->productVariantPromotionModel->fillData($promotion));
                        } else {
                            $this->productVariantPromotionModel->getModel()->create($this->productVariantPromotionModel->fillData($promotion));
                        }
                    }
                }

                Log::create($this->productVariantModel->getTable(), "#ID:" . $productVariant->id);
            }

            DB::commit();
            return ['status' => Message::get("product_variants.update-success", Message::get('product_variant_id'))];
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * Delete promotion
     *
     * @param $id
     * @return array
     */
    public function deletePromotion($id)
    {
        $model = $this->productVariantPromotionModel->getFirstBy('id', $id);
        if (empty($model)) {
            return $this->response->errorBadRequest(Message::get('V003', Message::get('promotion')));
        }
        $this->productVariantPromotionModel->deleteById($id);
        Log::delete($this->productVariantPromotionModel->getTable(), "#ID:" . $id);
        return ['status' => Message::get("product_variants.promotion.delete-success", Message::get('promotion'))];
    }

    /**
     * Delete product variant
     *
     * @param $id
     * @return array
     */
    public function delete($id)
    {
        $model = $this->productVariantModel->getFirstBy('id', $id);
        if (empty($model)) {
            return $this->response->errorBadRequest(Message::get('V003', Message::get('product_variant_id')));
        }
        $this->productVariantModel->deleteById($id);
        Log::delete($this->productVariantModel->getTable(), "#ID:" . $id);
        return ['status' => Message::get("product_variants.delete-success", Message::get('product_variant_id'))];
    }
}