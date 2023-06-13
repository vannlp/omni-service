<?php
/**
 * User: dai.ho
 * Date: 28/01/2021
 * Time: 3:21 PM
 */

namespace App\Sync\Models;


use App\Area;
use App\Brand;
use App\Category;
use App\InventoryDetail;
use App\OrderDetail;
use App\Product;
use App\ProductDiscount;
use App\ProductOption;
use App\ProductPromotion;
use App\ProductRewardPoint;
use App\ProductVersion;
use App\ProductWebsite;
use App\Store;
use App\Supports\Message;
use App\TM;
use App\Unit;
use App\V1\Models\AbstractModel;
use App\WarehouseDetail;
use App\Website;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class ProductModel
 * @package App\Sync\Models
 */
class ProductModel extends AbstractModel
{
    /**
     * ProductModel constructor.
     * @param Product|null $model
     */
    public function __construct(Product $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input, $isUpdate = false)
    {
        try {
            if (!empty($input['Brand'])) {
                $brand = Brand::model()->where([
                    'name'     => $input['Brand'],
                    'store_id' => TM::getIDP()->store_id,
                ])->first();
            }

            $categories = array_filter([$input['Category'] ?? null, $input['subCategory'] ?? null]);
            $categories = $categories ? Category::model()->whereIn('code',
                $categories)->get()->pluck('id')->toArray() : [];


            if ($isUpdate) {
                $product = Product::model()->where('code', $input['code'])->first();
                $old_unit = $product->unit_id;
                $product->name = array_get($input, 'ProductName', $input['ProductCode']);
                $product->slug = $this->convert_vi_to_en_to_slug($product->name);
                $product->brand_id = $brand->id ?? null;
                $product->description = array_get($input, 'Description', null);
                $product->short_description = array_get($input, 'Description', $product->name);
                $product->type = 'PRODUCT';
                $product->category_ids = implode(",", $categories);
                $product->price = array_get($input, 'Price', 0);
                $product->discount_unit_type = PRODUCT_UNIT_TYPE_PERCENT;
                $product->length_class = PRODUCT_LENGTH_CLASS_CM;
                $product->weight = array_get($input, 'GrossWeight', 0);
                $product->weight_class = PRODUCT_WEIGHT_CLASS_GR;
                $product->unit_id = array_get($input, 'UOM1');
                $product->updated_at = date("Y-m-d H:i:s", time());
                $product->updated_by = TM::getIDP()->sync_name;
                $product->data_sync = json_encode($input);
                $product->save();
                $this->updateProducts($product);
                $company_id = TM::getIDP()->company_id;

                $warehouseDetails = WarehouseDetail::model()->where([
                    'product_id' => $product->id,
                    'company_id' => $company_id,
                    'unit_id'    => $old_unit,
                ])->first();
                if ($product->unit_id != $old_unit && !empty($warehouseDetails)) {
                    $this->updateUnitWarehouseDetail($product->id, $product->unit_id, $company_id, $product->name,
                        $old_unit);
                }

            } else {
                $param = [
                    'name'               => array_get($input, 'ProductName', $input['ProductCode']),
                    'code'               => $input['ProductCode'],
                    'slug'               => Str::slug($input['ProductName']),
                    'brand_id'           => $brand->id ?? null,
                    'area_name'          => $area->name ?? null,
                    'description'        => array_get($input, 'Description', null),
                    'short_description'  => array_get($input, 'Description',
                        array_get($input, 'ProductName', $input['ProductCode'])),
                    'type'               => 'PRODUCT',
                    'category_ids'       => implode(",", $categories),
                    'price'              => array_get($input, 'Price', 0),
                    'discount_unit_type' => PRODUCT_UNIT_TYPE_PERCENT,
                    'length_class'       => PRODUCT_LENGTH_CLASS_CM,
                    'weight'             => array_get($input, 'GrossWeight', 0),
                    'weight_class'       => PRODUCT_WEIGHT_CLASS_GR,
                    'store_id'           => TM::getIDP()->store_id,
                    'unit_id'            => array_get($input, 'UOM1'),
                    'data_sync'          => json_encode($input),
                    'is_active'          => 1,
                ];

                $product = $this->create($param);
            }

            $this->updateStore($product, $input);
        } catch (\Exception $ex) {
            throw $ex;
        }

        return $product;
    }

    private function convert_vi_to_en_to_slug($str, $options = [])
    {
        // Make sure string is in UTF-8 and strip invalid UTF-8 characters
        $str = mb_convert_encoding((string)$str, 'UTF-8', mb_list_encodings());

        $defaults = [
            'delimiter'     => '-',
            'limit'         => null,
            'lowercase'     => true,
            'replacements'  => [],
            'transliterate' => true,
        ];

        // Merge options
        $options = array_merge($defaults, $options);

        // Lowercase
        if ($options['lowercase']) {
            $str = mb_strtolower($str, 'UTF-8');
        }

        $char_map = [
            // Latin
            'á' => 'a',
            'à' => 'a',
            'ả' => 'a',
            'ã' => 'a',
            'ạ' => 'a',
            'ă' => 'a',
            'ắ' => 'a',
            'ằ' => 'a',
            'ẳ' => 'a',
            'ẵ' => 'a',
            'ặ' => 'a',
            'â' => 'a',
            'ấ' => 'a',
            'ầ' => 'a',
            'ẩ' => 'a',
            'ẫ' => 'a',
            'ậ' => 'a',
            'đ' => 'd',
            'é' => 'e',
            'è' => 'e',
            'ẻ' => 'e',
            'ẽ' => 'e',
            'ẹ' => 'e',
            'ê' => 'e',
            'ế' => 'e',
            'ề' => 'e',
            'ể' => 'e',
            'ễ' => 'e',
            'ệ' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'ỉ' => 'i',
            'ĩ' => 'i',
            'ị' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ỏ' => 'o',
            'õ' => 'o',
            'ọ' => 'o',
            'ô' => 'o',
            'ố' => 'o',
            'ồ' => 'o',
            'ổ' => 'o',
            'ỗ' => 'o',
            'ộ' => 'o',
            'ơ' => 'o',
            'ớ' => 'o',
            'ờ' => 'o',
            'ở' => 'o',
            'ỡ' => 'o',
            'ợ' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'ủ' => 'u',
            'ũ' => 'u',
            'ụ' => 'u',
            'ư' => 'u',
            'ứ' => 'u',
            'ừ' => 'u',
            'ử' => 'u',
            'ữ' => 'u',
            'ự' => 'u',
            'ý' => 'y',
            'ỳ' => 'y',
            'ỷ' => 'y',
            'ỹ' => 'y',
            'ỵ' => 'y',
        ];

        // Make custom replacements
        $str = preg_replace(array_keys($options['replacements']), $options['replacements'], $str);

        // Transliterate characters to ASCII
        if ($options['transliterate']) {
            $str = str_replace(array_keys($char_map), $char_map, $str);
        }

        // Replace non-alphanumeric characters with our delimiter
        $str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);

        // Remove duplicate delimiters
        $str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $str);

        // Truncate slug to max. characters
        $str = mb_substr($str, 0, ($options['limit'] ? $options['limit'] : mb_strlen($str, 'UTF-8')), 'UTF-8');

        // Remove delimiter from ends
        $str = trim($str, $options['delimiter']);

        return $str;
    }

    private function updateUnitWarehouseDetail($id, $unit_id, $company_id, $name, $old_unit)
    {
        $warehouseDetails = WarehouseDetail::model()->where([
            'product_id' => $id,
            'company_id' => $company_id,
            'unit_id'    => $old_unit,
        ])->first();
        if (empty($warehouseDetails)) {
            throw new \Exception(Message::get("V055", $name));
        }
        $unit = Unit::find($unit_id);
        $warehouseDetails->unit_id = $unit->id;
        $warehouseDetails->unit_code = $unit->code;
        $warehouseDetails->unit_name = $unit->name;
        $warehouseDetails->save();
    }

    private function updateProducts(Product $product)
    {
        OrderDetail::where('product_id', $product->id)->update([
            'product_code' => $product->code,
            'product_name' => $product->name,
        ]);
        WarehouseDetail::where('product_id', $product->id)->update([
            'product_code' => $product->code,
            'product_name' => $product->name,
        ]);
        InventoryDetail::where('product_id', $product->id)->update([
            'product_code' => $product->code,
            'product_name' => $product->name,
        ]);
    }

    private function updateStore(Product $product, $input)
    {
        $input['stores'][] = ['store_id' => TM::getIDP()->store_id];
        if (!empty($input['store_supermarket'])) {
            $input['stores'][] = ['store_id' => 44];
        }
        $product->stores()->sync(collect($input['stores'] ?? [])->pluck('store_id')->unique()->toArray());
    }
}