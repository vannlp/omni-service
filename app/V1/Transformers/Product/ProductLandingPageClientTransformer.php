<?php

namespace App\V1\Transformers\Product;

use App\Category;
use App\Foundation\PromotionHandle;
use App\Product;
use App\OrderDetail;
use App\ProductComment;
use App\Supports\TM_Error;
use App\PromotionProgram;
use App\TM;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;
use App\Supports\DataUser;
use App\V1\Controllers\PromotionProgramController;
use Monolog\Handler\TelegramBotHandler;

class ProductLandingPageClientTransformer extends TransformerAbstract
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
            list($store_id, $company_id) = DataUser::getInstance()->info();
            $iframe_image_id = null;
            $iframe_image = null;

            $PromotionsGiftAndIframe = (new PromotionProgram())->PromotionsGiftAndIframe($product);

            $price = Arr::get($product->priceDetail($product), 'price', $product->price);

            if ($price < $product->price) {
                $percent_price = $product->price - $price;
                $percentage_price_old = round(($percent_price / $product->price) * 100);
            }

            $special = null;
            $special_formated = null;
            $special_percentage = 0;

            $promotionPrograms = $this->promotionPrograms;

            // $promotionPrograms = (new PromotionHandle())->promotionApplyProduct($this->promotionPrograms, $product);

            $promotionPrice = 0;
            if ($promotionPrograms && !$promotionPrograms->isEmpty()) {

                // $type_promotion = array_pluck($promotionPrograms, 'promotion_type');
                // $check_flash_sale = array_search('FLASH_SALE', $type_promotion);

                $type_promotion   = array_pluck($promotionPrograms, 'promotion_type');
                $keyIsFlashSale = [];
                foreach($type_promotion ?? [] as $_key => $_value){
                    if($_value !== 'FLASH_SALE'){
                        continue;
                    }
                    $keyIsFlashSale[$_key] = $_key;
                }

                foreach ($promotionPrograms as $key => $promotion) {

                    if($promotion->promotion_type == 'FLASH_SALE'){
                        if (empty($keyIsFlashSale)){
                            continue;
                        } 
                        if (!in_array($key,$keyIsFlashSale)){
                            continue;
                        }
                    }
                    
                    $chk = false;
                    if(!empty($keyIsFlashSale)){
                        foreach($keyIsFlashSale as $item){
                            $prodPluck = array_pluck($promotionPrograms[$item]->productPromotion, 'product_id');
                            $search_prod = array_search($product->id, $prodPluck);
                            if(is_numeric($search_prod)){
                                $chk = true;
                                break;
                            }
                        }
                    }
                    if(!empty($keyIsFlashSale) && !in_array($key,$keyIsFlashSale) && $chk){
                        continue;
                    }

                    $prod = array_pluck($promotion->productPromotion, 'product_id');
                    $search_prod = array_search($product->id, $prod);

                    // if(is_numeric($check_flash_sale) && !is_numeric($search_prod)){
                    //         continue;
                    // }

                    if (is_numeric($search_prod)) {
                        $iframe_image_id = $promotion->iframe_image_id;
                        $iframe_image = $promotion->iframeImage->code ?? null;
                    }


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



                    // if ($promotion->promotion_type == 'FLASH_SALE') {
                    //     if (($order_sale ?? 0) >= ($product->qty_flash_sale ?? 1)) {
                    //         $promotionPrice = 0;
                    //     } else {
                    //         if ($promotion->discount_by == "product") {
                    //             $promotionPrice += (new PromotionHandle())->parsePriceByProducts($product->code, $price, $promotion);
                    //         } else {
                    //             $promotionPrice += (new PromotionHandle())->parsePriceBySaleType($price, $promotion);
                    //         }
                    //     }
                    // } else {
                    //     if ($promotion->discount_by == "product") {
                    //         $promotionPrice += (new PromotionHandle())->parsePriceByProducts($product->code, $price, $promotion);
                    //     } else {
                    //         $promotionPrice += (new PromotionHandle())->parsePriceBySaleType($price, $promotion);
                    //     }
                    // }
                }

                $special = $price - $promotionPrice;
                $special_formated = number_format($special) . "đ";
                if (isset($special)) {
                    $special_percentage = $price != 0 || !empty($price) ? round(($promotionPrice / $price) * 100) : 0;
                }
            }

            setlocale(LC_MONETARY, 'vi_VN');
            $fileCode = object_get($product, 'file.code', null);

            if (empty($iframe_image_id) && empty($iframe_image)) {
                $iframe_image_id = Arr::get($product->promotionTagsAndIframe($product), 'iframe_image_id', null);
                $iframe_image = Arr::get($product->promotionTagsAndIframe($product), 'iframe_image', null);
            }

            $output = [
                'id'                           => $product->id,
                'code'                         => $product->code,
                'name'                         => $product->name,
                'slug'                         => $product->slug,
                'url'                          => env('APP_URL') . "/product/{$product->slug}",
                //                                'star_old'                         => $this->getStarRate($product->id),
                'star'                         => [
                    'total_rate' => [
                        'total' => $product->count_rate,
                    ],
                    'avg_star'   => [
                        'avg'        => $avg = $product->rate_avg,
                        'avg_format' => ($avg ?? "0") . "/5",
                    ]
                ],
                'star_rating'                  => 0,
                'promotion_tags'               => !empty(Arr::get($product->promotionTagsAndIframe($product), 'tags', null)) ? json_decode(Arr::get($product->promotionTagsAndIframe($product), 'tags', null)) : [],
                'short_description'            => $product->short_description,
                'thumbnail'                    => !empty($fileCode) ? env('UPLOAD_URL') . '/file/' . $fileCode : null,
                'iframe_image_id'              => !empty($PromotionsGiftAndIframe['iframe_image_id']) ? $PromotionsGiftAndIframe['iframe_image_id'] : $iframe_image_id,
                'iframe_image'                 => !empty($PromotionsGiftAndIframe['iframe_image_id']) ? env('GET_FILE_URL') . $PromotionsGiftAndIframe['iframe_image'] : (!empty($iframe_image) ? env('GET_FILE_URL') . $iframe_image : null),
                'price'                        => $price,
                'price_formatted'              => number_format($price) . "đ",
                'original_price'               => $price,
                'original_price_formatted'     => number_format($price) . "đ",
                'old_product_price'            => $product->price == $price ? 0 :  $product->price,
                'old_product_price_formatted'  => number_format($product->price) . "đ",
                'percentage_price_old'         => ($percentage_price_old ?? 0) . "%",
                'promotion_price'              => $promotionPrice,
                'promotion_price_formatted'    => number_format($promotionPrice) . "đ",
                'special'                      => $special,
                'special_formatted'            => $special_formated,
                'special_percentage'           => $special_percentage,
                'special_percentage_formatted' => $special_percentage . "%",
                'qty'                          => Arr::get($product->warehouse, 'quantity', 0),
                'sold_count'                   => $sold_count = $product->sold_count ?? 0,
                'sold_count_formatted'         => format_number_in_k_notation($sold_count),
                'product_gift'                 => $PromotionsGiftAndIframe['product_gift'] ?? [],
            ];
            return $output;
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }


    private function count_star($product_id, $star)
    {
        list($store_id, $company_id) = DataUser::getInstance()->info();
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
        return count($data);
    }

    public function getStarRate($id)
    {
        $star_1 = $this->count_star($id, 1);
        $star_2 = $this->count_star($id, 2);
        $star_3 = $this->count_star($id, 3);
        $star_4 = $this->count_star($id, 4);
        $star_5 = $this->count_star($id, 5);
        $total = $this->count_star($id, null);

        $result['total_rate'] = [
            'total' => $total,
        ];
        $start = $star_1 + $star_2 + $star_3 + $star_4 + $star_5;
        $result['avg_star'] = [
            'avg'        => $start > 0 ? $avg = round(($star_1 * 1 + $star_2 * 2 + $star_3 * 3 + $star_4 * 4 + $star_5 * 5) / $start, 2) : 0,
            'avg_format' => ($avg ?? "0") . "/5",
        ];
        return $result;
    }
}
