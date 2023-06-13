<?php

namespace App\V1\Transformers\NNDD;

use App\Product;
use App\ProductComment;
use App\PromotionProgram;
use App\Supports\DataUser;
use App\Supports\TM_Error;
use App\V1\Controllers\PromotionProgramController;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class NNDDPProductListTransformer extends TransformerAbstract
{
    /**
     * @var $promotionPrograms
     */
    protected $promotionPrograms;

    /**
     * ProductClientTransformer constructor.
     * @param $promotionPrograms
     */
    public function __construct($promotionPrograms)
    {
        $this->promotionPrograms = $promotionPrograms;
    }

    public function transform(Product $product)
    {
        try {

            $price = Arr::get($product->priceDetail($product), 'price', $product->price);

            if ($price < $product->price) {
                $percent_price = $product->price - $price;
                $percentage_price_old = round(($percent_price / $product->price) * 100);
            }

            $special = null;
            $special_formated = null;
            $special_percentage = 0;

            $promotionPrograms = $this->promotionPrograms;

            $promotionPrice = 0;
            if ($promotionPrograms && !$promotionPrograms->isEmpty()) {
                $type_promotion   = array_pluck($promotionPrograms, 'promotion_type');
                $keyIsFlashSale = [];
                foreach ($type_promotion ?? [] as $_key => $_value) {
                    if ($_value !== 'FLASH_SALE') {
                        continue;
                    }
                    $keyIsFlashSale[$_key] = $_key;
                }

                foreach ($promotionPrograms as $key => $promotion) {

                    if ($promotion->promotion_type == 'FLASH_SALE') {
                        if (empty($keyIsFlashSale)) {
                            continue;
                        }
                        if (!in_array($key, $keyIsFlashSale)) {
                            continue;
                        }
                    }

                    $chk = false;
                    if (!empty($keyIsFlashSale)) {
                        foreach ($keyIsFlashSale as $item) {
                            $prodPluck = array_pluck($promotionPrograms[$item]->productPromotion, 'product_id');
                            $search_prod = array_search($product->id, $prodPluck);
                            if (is_numeric($search_prod)) {
                                $chk = true;
                                break;
                            }
                        }
                    }
                    if (!empty($keyIsFlashSale) && !in_array($key, $keyIsFlashSale) && $chk) {
                        continue;
                    }

                    $prod = array_pluck($promotion->productPromotion, 'product_id');
                    $search_prod = array_search($product->id, $prod);


                    if ($promotion->promotion_type == 'FLASH_SALE') {
                        if (($order_sale ?? 0) >= ($product->qty_flash_sale ?? 1)) {
                            $promotionPrice = 0;
                        } else {
                            $promotionPrice += (new PromotionProgramController())->promotionPrice($promotion->productPromotion, $product->id,  $price, $promotion->discount_by, $promotion->act_sale_type, $promotion->act_price);
                        }
                    }
                    if ($promotion->promotion_type != 'FLASH_SALE') {
                        $promotionPrice += (new PromotionProgramController())->promotionPrice($promotion->productPromotion, $product->id,  $price, $promotion->discount_by, $promotion->act_sale_type, $promotion->act_price);
                    }
                }

                $special = $price - $promotionPrice;
                $special_formated = number_format($special) . "đ";
                if (isset($special)) {
                    $special_percentage = $price != 0 || !empty($price) ? round(($promotionPrice / $price) * 100) : 0;
                }
            }

            setlocale(LC_MONETARY, 'vi_VN');
            $fileCode = object_get($product, 'file.code', null);

            return [
                'id'                           => $product->id,
                'code'                         => $product->code,
                'name'                         => $product->name,
                'slug'                         => $product->slug,
                'url'                          => env('APP_URL') . "/product/{$product->slug}",
                'short_description'            => $product->short_description,
                'description'                  => $product->description,
                'thumbnail'                    => !empty($fileCode) ? env('UPLOAD_URL') . '/file/' . $fileCode : null,
                'original_price'               => $price,
                'original_price_formatted'     => number_format($price) . "đ",
                'percentage_price_old'         => ($percentage_price_old ?? 0) . "%",
                'promotion_price'              => $promotionPrice,
                'promotion_price_formatted'    => number_format($promotionPrice) . "đ",
                'special'                      => $special,
                'special_formatted'            => $special_formated,
                'special_percentage'           => $special_percentage,
                'special_percentage_formatted' => $special_percentage . "%",
                'star'                         => $this->getStarRate($product->id),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }

    public function getStarRate($id)
    {
        $star_1 = $this->count_star($id, 1);
        $star_2 = $this->count_star($id, 2);
        $star_3 = $this->count_star($id, 3);
        $star_4 = $this->count_star($id, 4);
        $star_5 = $this->count_star($id, 5);
        $total  = $this->count_star($id, null);

        $result['total_rate'] = [
            'total' => $total,
        ];
        $start                = $star_1 + $star_2 + $star_3 + $star_4 + $star_5;
        $result['avg_star']   = [
            'avg'        => $start > 0 ? $avg = round(($star_1 * 1 + $star_2 * 2 + $star_3 * 3 + $star_4 * 4 + $star_5 * 5) / $start, 2) : 0,
            'avg_format' => $avg ?? "0" . "/5",
        ];
        return $result;
    }

    private function count_star($product_id, $star)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $data = ProductComment::model()
            ->where('store_id', $store_id)
            ->where('company_id', $company_id)
            ->where('type', PRODUCT_COMMENT_TYPE_RATE)
            ->where('product_id', $product_id)
            ->where('is_active', 1);
        if (!empty($star)) {
            $data = $data->where('rate', $star);
        }
        $data = $data->select('rate')->get()->toArray();
        return (int)count($data);
    }
}
