<?php

/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App\V1\Models;

use App\Category;
use App\City;
use App\Country;
use App\District;
use App\File;
use App\Image;
use App\InventoryDetail;
use App\OrderDetail;
use App\Price;
use App\PriceDetail;
use App\Product;
use App\ProductComment;
use App\ProductDiscount;
use App\ProductOption;
use App\ProductPromotion;
use App\ProductReview;
use App\ProductRewardPoint;
use App\ProductStore;
use App\ProductVersion;
use App\ProductWebsite;
use App\Store;
use App\TM;
use App\Supports\Message;
use App\Unit;
use App\UserStore;
use App\Ward;
use App\WarehouseDetail;
use App\Website;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\PromotionProgram;
use App\Supports\DataUser;
use App\Foundation\PromotionHandle;
use App\V1\Controllers\PromotionProgramController;

/**
 * Class Products
 *
 * @package App\V1\Models
 */
class ProductModel extends AbstractModel
{
    public function __construct(Product $model = null)
    {
        parent::__construct($model);
    }

    /**
     * @param $input
     *
     * @return mixed
     * @throws \Exception
     */
    public function upsert($input)
    {
        DB::beginTransaction();
        $id = !empty($input['id']) ? $input['id'] : 0;
        if (!empty($input['sale_area'])) {
            $ward_code     = [];
            $district_code = [];
            $city_code     = [];
            foreach ($input['sale_area'] as $key => $saleArea) {
                $city_code[] = $saleArea['code'];
                if (!empty($saleArea['districts'])) {
                    foreach ($saleArea['districts'] as $district) {
                        $district_code[] = $district['code'];
                        if ($district['wards']) {
                            foreach ($district['wards'] as $ward) {
                                $ward_code[] = $ward['code'];
                            }
                        }
                        if (empty($district['wards'])) {
                            $wardSaleAreas = Ward::model()->where('code', $district['code'])->pluck('code');
                            foreach ($wardSaleAreas as $wardSaleArea) {
                                $ward_code = $wardSaleArea;
                            }
                        }
                    }
                }
                if (empty($saleArea['districts'])) {
                    $cities = District::model()->select('id', 'code')->with('ward')->where('city_code', $saleArea['code'])->get();
                    foreach ($cities as $city) {
                        $district_code[] = $city['code'];
                        foreach ($city['ward'] as $valueWard) {
                            $ward_code[] = $valueWard['code'];
                        }
                    }
                }
            }
            $ward_code     = implode(',', $ward_code);
            $district_code = implode(',', $district_code);
            $city_code     = implode(',', $city_code);
        }
        if (!empty($input['gallery_images'])) {
            $gallery_images = $input['gallery_images'];
            $gallery_images = array_pluck($gallery_images, 'id');
            $gallery_images = implode(',', $gallery_images);
        }
        if (!empty($input['property_variant_root'])) {
            $property_variant_root = $input['property_variant_root'];
            $property_variant_root = array_pluck($property_variant_root, 'id');
            $property_variant_root = implode(',', $property_variant_root);
        }
        if (!empty($input['group_code'])) {
            $group_code_ids = $input['group_code'];
            $group_code_ids = array_pluck($group_code_ids, 'id');
            $group_code_ids = implode(',', $group_code_ids);
        }
        if (!empty($input['related_products'])) {
            $related_products = $input['related_products'];
            $relatedIds       = array_pluck($related_products, 'id', null);
            $strRelatedIds    = implode(",", $relatedIds);
        }
        $personalObject   = !empty($input['personal_object']) ? str_replace(
            ["%", " ", ".", ","],
            "",
            $input['personal_object']
        ) : null;
        $enterpriseObject = !empty($input['enterprise_object']) ? str_replace(
            ["%", " ", ".", ","],
            "",
            $input['enterprise_object']
        ) : null;
        $websiteId        = !empty($input['website_ids']) ? implode(",", $input['website_ids']) : null;

        if (!empty($input['category_supermarket_ids'])) {
            $input['category_ids'] = array_merge($input['category_ids'] ?? [], $input['category_supermarket_ids']);
        }

        if ($id) {
            $product  = Product::find($id);
            $old_unit = $product->unit_id;
            if (empty($product)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $product->name                      = array_get($input, 'name', $product->name);
            $product->code                      = array_get($input, 'code', $product->code);
            $product->is_cool                   = array_get($input, 'is_cool', $product->is_cool);
            $product->slug                      = Str::slug($product->name);
            $product->tags                      = !empty($input['tags']) ? $input['tags'] : null;
            $product->tax                       = array_get($input, 'tax', $product->tax);
            $product->sale_area                 = !empty($input['sale_area']) ? json_encode($input['sale_area']) : $product->sale_area;
            $product->city_area_code            = $city_code ?? null;
            $product->district_area_code        = $district_code ?? null;
            $product->ward_area_code            = $ward_code ?? null;
            $product->meta_title                = array_get($input, 'meta_title', $product->meta_title);
            $product->meta_description          = array_get($input, 'meta_description', $product->meta_description);
            $product->meta_robot                = array_get($input, 'meta_robot', $product->meta_robot);
            $product->meta_keyword              = array_get($input, 'meta_keyword', $product->meta_keyword);
            $product->area_id                   = array_get($input, 'area_id', $product->area_id);
            $product->brand_id                  = array_get($input, 'brand_id', $product->brand_id);
            $product->child_brand_id            = array_get($input, 'child_brand_id', $product->child_brand_id);
            $product->child_brand_name          = array_get($input, 'child_brand_name', $product->child_brand_name);
            $product->age_id                    = array_get($input, 'age_id', $product->age_id);
            $product->capacity                  = array_get($input, 'capacity', $product->capacity);
            $product->cadcode                   = array_get($input, 'cadcode', $product->cadcode);
            $product->manufacture_id            = array_get($input, 'manufacture_id', $product->manufacture_id);
            $product->area_name                 = array_get($input, 'area_name', $product->area_name);
            $product->description               = array_get($input, 'description', $product->description);
            $product->short_description         = array_get($input, 'short_description', $product->short_description);
            $product->type                      = array_get($input, 'type', $product->type);
            $product->shop_id                   = array_get($input, 'shop_id', $product->shop_id);
            $product->specification_id          = array_get($input, 'specification_id', $product->specification_id);
            $product->gallery_images            = $gallery_images ?? null;
            $product->thumbnail                 = array_get($input, 'thumbnail_id', $product->thumbnail);
            $product->store_supermarket         = $input['store_supermarket'] ?? 0;
            $product->category_supermarket_ids  = !empty($input['category_supermarket_ids']) ? implode(",", $input['category_supermarket_ids']) : null;
            $product->category_ids              = !empty($input['category_ids']) ? (is_array($input['category_ids']) ? implode(",", $input['category_ids']) : $input['category_ids']) : null;
            $product->price                     = array_get($input, 'price', 0);
            $product->price_down                = $input['price_down'] ?? null;
            $product->down_from                 = !empty($input['down_from']) ? date(
                "Y-m-d H:i:s",
                strtotime($input['down_from'])
            ) : $product->down_from;
            $product->down_to                   = !empty($input['down_to']) ? date("Y-m-d H:i:s", strtotime($input['down_to'])) : $product->down_to;
            $product->expiry_date               = !empty($input['expiry_date']) ? $input['expiry_date'] : $product->expiry_date;
            $product->handling_object           = array_get($input, 'handling_object', $product->handling_object);
            $product->discount_unit_type        = array_get($input, 'discount_unit_type', PRODUCT_UNIT_TYPE_PERCENT);
            $product->personal_object           = $personalObject;
            $product->enterprise_object         = $enterpriseObject;
            $product->user_group_id             = array_get($input, 'user_group_id', null);
            $product->qty_out_min               = array_get($input, 'qty_out_min', $product->qty_out_min);
            $product->sku                       = array_get($input, 'sku', $product->sku);
            $product->cat                       = array_get($input, 'cat', $product->cat);
            $product->subcat                    = array_get($input, 'subcat', $product->subcat);
            $product->division                  = array_get($input, 'division', $product->division);
            $product->source                    = array_get($input, 'source', $product->source);
            $product->cad_code_brandy           = array_get($input, 'cad_code_brandy', $product->cad_code_brandy);
            $product->cad_code_subcat           = array_get($input, 'cad_code_subcat', $product->cad_code_subcat);
            $product->cad_code_brand            = array_get($input, 'cad_code_brand', $product->cad_code_brand);
            $product->packing                   = array_get($input, 'packing', $product->packing);
            $product->sku_standard              = array_get($input, 'sku_standard', $product->sku_standard);
            $product->sku_name                  = array_get($input, 'sku_name', $product->sku_name);
            $product->p_type                    = array_get($input, 'p_type', $product->p_type);
            $product->p_attribute               = array_get($input, 'p_attribute', $product->p_attribute);
            $product->p_variant                 = array_get($input, 'p_variant', $product->p_variant);
            $product->p_sku                     = array_get($input, 'p_sku', $product->p_sku);
            $product->upc                       = array_get($input, 'upc', $product->upc);
            $product->qty                       = array_get($input, 'qty', $product->qty);
            $product->length                    = array_get($input, 'length', $product->length);
            $product->width                     = array_get($input, 'width', $product->width);
            $product->height                    = array_get($input, 'height', $product->height);
            $product->length_class              = array_get($input, 'length_class', $product->length_class);
            $product->weight_class              = array_get($input, 'weight_class', $product->weight_class);
            $product->weight                    = array_get($input, 'weight', $product->weight);
            $product->status                    = array_get($input, 'status', $product->status);
            $product->order                     = array_get($input, 'order', $product->order);
            $product->view                      = array_get($input, 'view', $product->view);
            $product->group_code                = array_get($input, 'group_code', $product->group_code);
            $product->group_code_ids            = $group_code_ids ?? null;
            $product->gift_item                 = json_encode(array_get($input, 'gift_item'));
            $product->property_variant_root     = json_encode(array_get($input, 'property_variant_root'));
            $product->property_variant_root_ids = $property_variant_root ?? null;
            //            $product->store_id            = array_get($input, 'store_id', $product->store_id);
            $product->is_featured         = array_get($input, 'is_featured', $product->is_featured);
            $product->related_ids         = $strRelatedIds ?? null;
            $product->custom_date_updated = !empty($input['custom_date_updated']) ? date(
                "Y-m-d",
                strtotime($input['custom_date_updated'])
            ) : $product->custom_date_updated;
            $product->combo_liked         = array_get($input, 'combo_liked', $product->combo_liked);
            $product->exclusive_premium   = array_get($input, 'exclusive_premium', $product->exclusive_premium);
            $product->barcode             = array_get($input, 'barcode', $product->barcode);
            $product->unit_id             = array_get($input, 'unit_id');
            $product->version_name        = array_get($input, 'version_name');
            $product->point               = array_get($input, 'point');
            $product->publish_status      = array_get($input, 'publish_status', null);
            $product->is_combo            = array_get($input, 'is_combo', 0);
            $product->is_odd              = array_get($input, 'is_odd', 0);
            $product->is_combo_multi      = array_get($input, 'is_combo_multi', 0);
            $product->product_combo       = array_get($input, 'product_combo', null);
            $product->combo_code_from     = array_get($input, 'combo_code_from', null);
            $product->combo_name_from     = array_get($input, 'combo_name_from', null);
            $product->combo_specification_from     = array_get($input, 'combo_specification_from', null);
            $product->website_ids         = $websiteId ?? null;
            $product->attribute_info      = !empty($input['attribute_info']) ? json_encode($input['attribute_info']) : $product->attribute_info;
            $product->updated_at          = date("Y-m-d H:i:s", time());
            $product->updated_by          = TM::getUpdatedBy();
            $product->save();
            $this->updateProducts($id, $product->code, $product->name);
            $company_id = TM::getCurrentCompanyId();

            $warehouseDetails = WarehouseDetail::model()->where([
                'product_id' => $id,
                'company_id' => $company_id,
                'unit_id'    => $old_unit,
            ])->first();
            if ($product->unit_id != $old_unit && !empty($warehouseDetails)) {
                $this->updateUnitWarehouseDetail($id, $product->unit_id, $company_id, $product->name, $old_unit);
            }
        } else {
            $param   = [
                'name'                     => $input['name'],
                'code'                     => $input['code'],
                'is_cool'                  => $input['is_cool'] ?? 0,
                'slug'                     => Str::slug($input['name']),
                'tags'                     => $input['tags'] ?? null,
                'attribute_info'           => !empty($input['attribute_info']) ? json_encode($input['attribute_info']) : null,
                'tax'                      => array_get($input, 'tax', null),
                'sale_area'                => !empty($input['sale_area']) ? json_encode($input['sale_area']) : null,
                'meta_title'               => array_get($input, 'meta_title', null),
                'meta_keyword'             => array_get($input, 'meta_keyword', null),
                'meta_robot'               => array_get($input, 'meta_robot', null),
                'meta_description'         => array_get($input, 'meta_description', null),
                'area_id'                  => array_get($input, 'area_id', null),
                'brand_id'                 => array_get($input, 'brand_id', null),
                'child_brand_id'           => array_get($input, 'child_brand_id', null),
                'child_brand_name'         => array_get($input, 'child_brand_name', null),
                'age_id'                   => array_get($input, 'age_id', null),
                'capacity'                 => array_get($input, 'capacity', null),
                'cadcode'                  => array_get($input, 'cadcode', null),
                'manufacture_id'           => array_get($input, 'manufacture_id', null),
                'area_name'                => array_get($input, 'area_name', null),
                'description'              => $input['description'],
                'short_description'        => $input['short_description'],
                'type'                     => array_get($input, 'type'),
                'shop_id'                  => array_get($input, 'shop_id', null),
                'specification_id'         => array_get($input, 'specification_id', null),
                'website_ids'              => $websiteId ?? null,
                'gallery_images'           => $gallery_images ?? null,
                'thumbnail'                => array_get($input, 'thumbnail_id', null),
                'store_supermarket'        => $input['store_supermarket'] ?? 0,
                'category_ids'             => !empty($input['category_ids']) ? (is_array($input['category_ids']) ? implode(",", $input['category_ids']) : $input['category_ids']) : null,
                'category_supermarket_ids' => !empty($input['category_supermarket_ids']) ? implode(",", $input['category_supermarket_ids']) : null,
                'price'                    => array_get($input, 'price', 0),
                'price_down'               => $input['price_down'] ?? null,
                'down_from'                => !empty($input['down_from']) ? date(
                    "Y-m-d H:i:s",
                    strtotime($input['down_from'])
                ) : null,

                'down_to'                   => !empty($input['down_to']) ? date(
                    "Y-m-d H:i:s",
                    strtotime($input['down_to'])
                ) : null,
                'expiry_date'               => !empty($input['expiry_date']) ? $input['expiry_date'] : null,
                'handling_object'           => array_get($input, 'handling_object', null),
                'discount_unit_type'        => array_get($input, 'discount_unit_type', PRODUCT_UNIT_TYPE_PERCENT),
                'personal_object'           => $personalObject,
                'user_group_id'             => array_get($input, 'user_group_id', null),
                'enterprise_object'         => $enterpriseObject,
                'sku'                       => array_get($input, 'sku', null),
                'upc'                       => array_get($input, 'upc', null),
                'qty'                       => array_get($input, 'qty', 1),
                'qty_out_min'               => array_get($input, 'qty_out_min', 1),
                'length'                    => array_get($input, 'length', null),
                'width'                     => array_get($input, 'width', null),
                'height'                    => array_get($input, 'height', null),
                'length_class'              => array_get($input, 'length_class', null),
                'weight_class'              => array_get($input, 'weight_class', null),
                'weight'                    => array_get($input, 'weight', null),
                'status'                    => array_get($input, 'status', 1),
                'order'                     => array_get($input, 'status', null),
                'warehouse_id'              => array_get($input, 'warehouse_id', null),
                'view'                      => array_get($input, 'view', 0),
                'group_code'                => json_encode(array_get($input, 'group_code')),
                'group_code_ids'            => $group_code_ids ?? null,
                'store_id'                  => TM::getHeaderStoreID(),
                'is_featured'               => array_get($input, 'is_featured', 1),
                'related_ids'               => $strRelatedIds ?? null,
                'gift_item'                 => json_encode(array_get($input, 'gift_item')),
                "property_variant_root"     => json_encode(array_get($input, 'property_variant_root')),
                "property_variant_root_ids" => $property_variant_root ?? null,
                'custom_date_updated'       => !empty($input['custom_date_updated']) ? date(
                    "Y-m-d",
                    strtotime($input['custom_date_updated'])
                ) : null,
                'combo_liked'               => array_get($input, 'combo_liked', null),
                'exclusive_premium'         => array_get($input, 'exclusive_premium', null),
                'unit_id'                   => array_get($input, 'unit_id'),
                'version_name'              => array_get($input, 'version_name'),
                'point'                     => array_get($input, 'point'),
                'publish_status'            => array_get($input, 'publish_status', null),
                'cat'                       => array_get($input, 'cat', null),
                'subcat'                    => array_get($input, 'subcat', null),
                'division'                  => array_get($input, 'division', null),
                'source'                    => array_get($input, 'source', null),
                'cad_code_brandy'           => array_get($input, 'cad_code_brandy', null),
                'cad_code_subcat'           => array_get($input, 'cad_code_subcat', null),
                'cad_code_brand'            => array_get($input, 'cad_code_brand', null),
                'packing'                   => array_get($input, 'packing', null),
                'sku_standard'              => array_get($input, 'sku_standard', null),
                'sku_name'                  => array_get($input, 'sku_name', null),
                'p_type'                    => array_get($input, 'p_type', null),
                'p_attribute'               => array_get($input, 'p_attribute', null),
                'p_variant'                 => array_get($input, 'p_variant', null),
                'p_sku'                     => array_get($input, 'p_sku', null),
                'barcode'                   => array_get($input, 'barcode', null),
                'is_combo'                  => array_get($input, 'is_combo', 0),
                'is_odd'                    => array_get($input, 'is_odd', 0),
                'is_combo_multi'            => array_get($input, 'is_combo_multi', 0),
                'product_combo'             => array_get($input, 'product_combo', null),
                'combo_code_from'           => array_get($input, 'combo_code_from', null),
                'combo_name_from'           => array_get($input, 'combo_name_from', null),
                'combo_specification_from'  => array_get($input, 'combo_specification_from', null),
                'is_active'                 => 1,
            ];
            $product = $this->create($param);
        }

        if (!empty($input['property_variant_ids'])) {
            $product->property_variant_ids = $input['property_variant_ids'];
            $product->productPropertyVariants()->sync(explode(",", $input['property_variant_ids']));
            $product->save();
        }

        $this->updateProductWebsite($product, $input);
        $this->updateStore($product, $input);
        $this->updateOption($product, $input);
        $this->updateDiscount($product, $input);
        $this->updatePromotion($product, $input);
        $this->updateRewardPoint($product, $input);
        $this->updateVersion($product, $input);

        DB::commit();
        return $product;
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
        $unit                        = Unit::find($unit_id);
        $warehouseDetails->unit_id   = $unit->id;
        $warehouseDetails->unit_code = $unit->code;
        $warehouseDetails->unit_name = $unit->name;
        $warehouseDetails->save();
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

    /**
     * @param $input
     * @return array|string
     */
    public function createAtParam($input)
    {
        if (isset($input['from']) && $input['from'] !== "" && isset($input['to']) && $input['to'] !== "") {
            $input['from'] = date("Y-m-d", strtotime($input['from']));
            $input['to']   = date("Y-m-d", strtotime($input['to']));
            return ['between' => [$input['from'] . " 00:00:00", $input['to'] . " 23:59:59"]];
        }

        if (isset($input['from']) && $input['from'] !== "") {
            $input['from'] = date("Y-m-d", strtotime($input['from']));
            return ['>=' => $input['from'] . " 00:00:00"];
        }

        if (isset($input['to']) && $input['to'] !== "") {
            $input['to'] = date("Y-m-d", strtotime($input['to']));
            return ['<=' => $input['to'] . " 23:59:59"];
        }
        return "";
    }

    public function search($input = [], $with = [], $limit = null)
    {
        ;
        $query = $this->make($with);
        $this->sortBuilder($query, $input);

        if (!empty($input['area_id'])) {
            $query->where('area_id', $input['area_id']);
        }

        if (!empty($input['name'])) {
            $query->where('name', 'like', "%{$input['name']}%");
        }

        //        if (!empty($input['code'])) {
        //            $query->where('code', 'like', "%{$input['code']}%");
        //        }

        if (!empty($input['code'])) {
            $query->where(function ($q) use ($input) {
                $code_products = explode(",", $input['code']);
                foreach ($code_products as $code_product) {
                    $q->orwhere('code', 'like', "%$code_product%");
                }
            });
        }

        if (!empty($input['publish_status'])) {
            $query->where('publish_status', "{$input['publish_status']}");
        }

        $query->whereHas('stores', function ($q) use ($input) {
            if (!empty($input['store_id'])) {
                $q->where('store_id', $input['store_id']);
            } else {
                $q->where('store_id', TM::getHeaderStoreID());
            }
        });


        if (!empty($input['type'])) {
            $query->where('type', 'like', "%{$input['type']}%");
        }

        if (!empty($input['barcode'])) {
            $query->where('barcode', "{$input['barcode']}");
        }

        if (!empty($input['price_down'])) {
            $query->where('price_down', '>', "0")
                ->whereNotNull('price_down');
        }

        if (!empty($input['category_ids'])) {
            $category_ids = $input['category_ids'];
            $query        = $query->where(function ($q) use ($category_ids) {
                $category_ids = explode(",", $category_ids);
                foreach ($category_ids as $item) {
                    $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
                }
            });
        }
        if (!empty($input['city_code'])) {
            $city_code = $input['city_code'];
            $query     = $query->where(function ($q) use ($city_code) {
                $q->orWhere(DB::raw("CONCAT(',',city_area_code,',')"), 'like', "%,$city_code,%");
            });
        }
        if (!empty($input['district_code'])) {
            $district_code = $input['district_code'];
            $query         = $query->where(function ($q) use ($district_code) {
                $q->orWhere(DB::raw("CONCAT(',',district_area_code,',')"), 'like', "%,$district_code,%");
            });
        }
        if (!empty($input['ward_code'])) {
            $ward_code = $input['ward_code'];
            $query     = $query->where(function ($q) use ($ward_code) {
                $q->orWhere(DB::raw("CONCAT(',',district_area_code,',')"), 'like', "%,$ward_code,%");
            });
        }
        if (!empty($input['category_id'])) {
            $category_id = $input['category_id'];
            $query       = $query->where(function ($q) use ($category_id) {
                $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$category_id,%");
            });
        }

        if (!empty($input['brand_id'])) {
            $query = $query->where('brand_id', $input['brand_id']);
        }

        if (!empty($input['brand_name'])) {
            $query->whereHas('brand', function ($q) use ($input) {
                $q->where('name', 'like', $input['brand_name']);
            });
        }

        if (!empty($input['unit_id'])) {
            $query->where('unit_id', $input['unit_id']);
        }

        if (!empty($input['product_ids'])) {
            $query = $query->whereIn('id', $input['product_ids']);
        }

        if (!empty($input['tags'])) {
            $tags  = explode(',', $input['tags']);
            $query = $query->where(function ($q) use ($tags) {
                foreach ($tags as $item) {
                    $q->orWhere(DB::raw("CONCAT(',',tags,',')"), 'like', "%,$item,%");
                }
            });
        }

        if (isset($input['status'])) {
            $query->where('status', $input['status']);
        }


        if (isset($input['is_featured'])) {
            $query->where('is_featured', '=', $input['is_featured']);
        }

        if (!empty($input['product_favorite_ids'])) {
            $query->whereIn('id', $input['product_favorite_ids']);
        }

        if (!empty($input['price'])) {
            $query->where('price', $input['price']);
        }

        if (isset($input['is_active'])) {
            $query->where('is_active', $input['is_active']);
        }

        if (isset($input['custom_date_updated'])) {
            $query->whereDate('custom_date_updated', date('Y-m-d H:i:s', strtotime($input['custom_date_updated'])));
        }

        if (isset($input['combo_liked'])) {
            $query->where('combo_liked', $input['combo_liked']);
        }

        if (isset($input['exclusive_premium'])) {
            $query->where('exclusive_premium', $input['exclusive_premium']);
        }

        // if(!empty($input['is_admin'])){
        //     $warehouse_code = 'NUTIFOOD';
        //     $query->whereHas('warehouse', function($query) use ($warehouse_code) {
        //         $query->where('warehouse_code', $warehouse_code)
        //             ->where('quantity', '<=', 0);
        //     });
        // }

        $query = $query->whereNull('products.deleted_at');
// dd($query->toSql());
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                if (!empty($input['sort']['date'])) {
                    return $query->get();
                } else {
                    return $query->paginate($limit);
                }
            }
        } else {
            if (!empty($input['sort']['date'])) {
                return $query->get();
            } else {
                return $query->get();
            }
        }
    }

    public function searchClient($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        //        dd(['1',null]);

        $query->where('publish_status', 'approved');
        $query->where('is_active', 1);
        if (!empty($input['group_id'])) {
            $query->Where(DB::raw("CONCAT(',',group_code_ids,',')"), 'like', "%,{$input['group_id']},%")->orWhereNull('group_code_ids');
        }
        $query->whereHas('stores', function ($query) use ($input) {
            $query->where('store_id', $input['store_id']);
        });
        if (!empty($input['weight'])) {
            if ($input['weight'] >= 1000) {
                $query->where('weight', $input['weight'] / 1000);
                $query->where('weight_class', "KG");
            }
            if ($input['weight'] < 1000) {
                $query->where('weight', $input['weight']);
                $query->where('weight_class', "GRAM");
            }
        }

        if (!empty($input['brand_name'])) {
            $query->whereHas('brand', function ($q) use ($input) {
                $q->where('name', 'like', $input['brand_name']);
            });
        }
        if (!empty($input['capacity'])) {
            $query->where('capacity', $input['capacity']);
        }

        $input['category_ids'] = !empty($input['category_ids']) ? $input['category_ids'] : [];

        if (!empty($input['category_slug'])) {

            $cate = DB::table('categories')->whereNull('deleted_at')->where('slug', 'like', "%{$input['category_slug']}%")->select('id')->first();
            if ($cate) {
                $input['category_slug'] = $cate->id;
            }

        }

        if (!empty($input['category_ids'])) {
            $category_ids = $input['category_ids'];
            $query        = $query->where(function ($q) use ($category_ids) {
                $category_ids = explode(",", $category_ids);
                foreach ($category_ids as $item) {
                    $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
                }
            });
        }
        if (!empty($input['id'])) {
            $product_id = $input['id'];
            $query      = $query->where(function ($q) use ($product_id) {
                $product_id = explode(",", $product_id);
                foreach ($product_id as $item) {
                    $q->orwhere('id', $item);
                }
            });
        }
        if (!empty($input['variant_ids'])) {
            $variant_ids = $input['variant_ids'];
            $query       = $query->where(function ($q) use ($variant_ids) {
                $category_ids = explode(",", $variant_ids);
                foreach ($category_ids as $item) {
                    $q->orWhere(DB::raw("CONCAT(',',property_variant_ids,',')"), 'like', "%,$item,%");
                }
            });
        }

        if (!empty($input['area_id'])) {
            $query->where('area_id', $input['area_id']);
        }

        if (!empty($input['name'])) {
            $query->where('name', 'like', "%{$input['name']}%");
            // $reservedSymbols = ['-', '+', '<', '>', '@', '(', ')', '~'];
            // $term = $input['name'];
            // $term = str_replace($reservedSymbols, '', $term);
            // $query->whereRaw("MATCH (name) AGAINST ('$term' IN BOOLEAN MODE)");
        }

        if (!empty($input['code'])) {
            $query->where('code', 'like', "%{$input['code']}%");
        }
        if (!empty($input['type'])) {
            $query->where('type', 'like', "%{$input['type']}%");
        } else {
            $query->where('type', 'PRODUCT');
        }
        function arrCategoryGrandchildren($categoryGrandchildren)
        {
            $result = [];
            foreach ($categoryGrandchildren as $items) {
                $result = array_merge($result, [$items->id]);
                if (!empty($items->grandChildren)) {
                    $result = array_merge($result, $items->grandChildren->pluck('id')->toArray());

                    $result = array_merge($result, arrCategoryGrandchildren($items->grandChildren));
                }
            }
            return $result;
        }

        if (!empty($input['category_ids'])) {
            $categoryGrandchildren = Category::whereIn('id', explode(',', $input['category_ids']))
                ->where([
                    'category_publish' => 1,
                    'product_publish'  => 1
                ])
                ->where(function ($query) use ($input) {
                    if (!empty($input['area_ids'])) {
                        $query->whereIn('area_id', $input['area_ids']);
                    }
                })
                ->with('grandChildren')
                ->get();

            $category_ids = arrCategoryGrandchildren($categoryGrandchildren);
        } else {
            $category_ids = (new Category())->getIdsOfProduct($input['store_id'] ?? null, $input['area_ids'] ?? []);
        }

        $query = $query->where(function ($q) use ($category_ids) {
            foreach ($category_ids as $item) {
                $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
            }
        });

        if (!empty($input['brand_id'])) {
            $arrBrandId = explode(',', $input['brand_id']);
            $query      = $query->whereIn('brand_id', (array)$arrBrandId);
        }

        if (!empty($input['product_ids'])) {
            $query = $query->whereIn('id', $input['product_ids']);
        }

        if (!empty($input['tags'])) {
            $tags  = explode(',', $input['tags']);
            $query = $query->where(function ($q) use ($tags) {
                foreach ($tags as $item) {
                    $q->orWhere(DB::raw("CONCAT(',',tags,',')"), 'like', "%,$item,%");
                }
            });
        }

        if (isset($input['is_featured'])) {
            $query->where('is_featured', '=', $input['is_featured']);
        }

        if (!empty($input['product_favorite_ids'])) {
            $query->whereIn('id', $input['product_favorite_ids']);
        }

        if (isset($input['combo_liked'])) {
            $query->where('combo_liked', $input['combo_liked']);
        }
        if (isset($input['status'])) {
            $query->where('status', $input['status']);
        }

        if (isset($input['exclusive_premium'])) {
            $query->where('exclusive_premium', $input['exclusive_premium']);
        }

        $query->where(function ($query) use ($input) {
            $priceFrom = $input['price_from'] ?? null;
            $priceTo   = $input['price_to'] ?? null;

            if (isset($priceFrom) && isset($priceTo) && $priceTo != 0) {
                $query->whereBetween('products.price', [$priceFrom, $priceTo]);
            }

            if (isset($priceFrom) && !isset($priceTo)) {
                $query->where('products.price', '>=', $priceFrom);
            }

            if (!isset($priceFrom) && isset($priceTo)) {
                $query->where('products.price', '<=>', $priceTo);
            }
        });

        $query->with('priceDetail');

        $query = $query->whereNull('products.deleted_at');

        if (empty($limit)) {
            return $query->get();
        }

        return $query->paginate($limit);
    }
    // protected function fullTextWildcards($term)
    //     {
    //         $reservedSymbols = ['-', '+', '<', '>', '@', '(', ')', '~'];
    //         $term = str_replace($reservedSymbols, '', $term);

    //         $words = explode(' ', $term);

    //         foreach($words as $key => $word) {
    //             if(strlen($word) >= 3) {
    //                 $words[$key] = '+' . $word . '*';
    //             }
    //         }

    //         $searchTerm = implode( ' ', $words);

    //         return $searchTerm;
    //     }

    //     /**
    //      * Scope a query that matches a full text search of term.
    //      *
    //      * @param \Illuminate\Database\Eloquent\Builder $query
    //      * @param string $term
    //      * @return \Illuminate\Database\Eloquent\Builder
    //      */
    //     public function scopeSearch($query, $term)
    //     {
    //         $validColumn = DB::getSchemaBuilder()->getColumnListing($this->getTable());
    //         // $product = new Product;
    //         // $table = $product->getTableColumns();

    //         $columns = implode(',',$validColumn);
    //         // print_r($query->toSql());die;

    //         // $query->whereRaw("MATCH ({$columns}) AGAINST (? IN BOOLEAN MODE)" , $this->fullTextWildcards($term));
    //         $query->whereRaw("MATCH (name) AGAINST ('$term' IN BOOLEAN MODE)");
    //         // $query->where(DB::raw());
    //         return $query;
    //     }
    public function searchProductByCategory($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        if (!empty($input['code'])) {
            $allCat = Category::model()
                ->where('code', 'like', "%{$input['code']}%")
                ->get()->pluck('id');
            $query  = $query->where(function ($q) use ($allCat) {
                foreach ($allCat as $item) {
                    $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
                }
            });
        }

        if (!empty($input['cat_id'])) {
            $category_ids = $input['cat_id'];
            $query        = $query->where(function ($q) use ($category_ids) {
                $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$category_ids,%");
            });
        }
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }

    // public function stringToImage($ids)
    // {
    //     if (empty($ids)) {
    //         return [];
    //     }
    //     $result = [];
    //     $images = File::model()->whereIn('id', explode(",", $ids))->get();
    //     foreach ($images as $key => $image) {
    //         $data               = '';
    //         $result[$key]['id'] = $image->id;
    //         if (!empty($image->folder_id)) {
    //             $result[$key]['url'] = url('/v0') . "/img/" . 'uploads,' . $this->getFolder(
    //                     $image->folder_id,
    //                     $data
    //                 ) . $image->file_name;
    //         } else {
    //             $result[$key]['url'] = url('/v0') . "/img/" . 'uploads,' . $image->file_name;
    //         }
    //     }
    //     return $result;
    // }

    private function stringToImageDataString($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $result = [];
        $images = File::model()->whereIn('id', explode(",", $ids))->get();
        foreach ($images as $key => $image) {
            $result[$key]['id']   = $image->id;
            $result[$key]['type'] = $image->type;
            $result[$key]['url']  = env('UPLOAD_URL') . '/file/' . $image->code;
        }
        return $result;
    }

    public function getNameCategory($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $category = Category::model()->select(['name'])->whereIn('id', explode(",", $ids))->get()->toArray();
        $category = array_pluck($category, 'name');
        $category = implode(', ', $category);
        return $category;
    }

    public function getProduct($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $product = Product::model()->whereIn('id', explode(",", $ids))->get()->toArray();
        $data    = [];
        foreach ($product as $key => $item) {
            $data[] = [
                'id'                => $item['id'],
                'code'              => $item['code'],
                'name'              => $item['name'],
                'slug'              => $item['slug'],
                'type'              => $item['type'],
                'tags'              => $item['tags'],
                'tax'               => $item['tax'],
                'short_description' => $item['short_description'],
                'description'       => $item['description'],
                'thumbnail_id'      => $item['thumbnail'],
                'thumbnail'         => !empty($this->getImageThumbnail($item['thumbnail'])) ? url('/v0') . "/img/" . 'uploads,' . $this->getImageThumbnail($item['thumbnail']) : null,
                'gallery_image_ids' => $item['gallery_images'],
                'gallery_images'    => $this->getImage($item['gallery_images']),
                'category_ids'      => $item['category_ids'],
                'categories'        => $this->getNameCategory($item['category_ids']),
                'price'             => $item['price'],
                'sku'               => $item['sku'],
                'upc'               => $item['upc'],
                'qty'               => $item['qty'],
                'length'            => $item['length'],
                'width'             => $item['width'],
                'height'            => $item['height'],
                'length_class'      => $item['length_class'],
                'weight_class'      => $item['weight_class'],
                'status'            => $item['status'],
                'order'             => $item['order'],
                'view'              => $item['view'],
                'store_id'          => $item['store_id'],
                //                'related_ids'       => $item['related_ids'],
                //                'related_products'  => $this->getProduct($item['related_ids']),
                'manufacturer_id'   => $item['manufacturer_id'],
                'created_at'        => date('d-m-Y', strtotime($item['created_at'])),
                'updated_at'        => !empty($item['updated_at']) ? date(
                    'd-m-Y',
                    strtotime($item['updated_at'])
                ) : null,
            ];
        }
        return $data;
    }

    public function productReview($input)
    {
        $productId   = $input['product_id'];
        $rate        = $input['rate'];
        $message     = $input['message'] ?? "";
        $checkUnique = ProductReview::model()->where('product_id', $productId)->where(
            'created_by',
            TM::getCurrentUserId()
        )->first();
        $product     = Product::find($productId);
        if (!empty($checkUnique)) {
            throw new \Exception(Message::get("V042", $product->name));
        }
        //Create Product Review
        ProductReview::create(
            [
                'product_id' => $productId,
                'rate'       => $rate,
                'message'    => $message,
            ]
        );

        return $product;
    }

    public function getBoughtProductCountList($product_ids, $store_id)
    {
        $data = OrderDetail::model()->select([DB::raw('sum(qty) as qty'), 'order_details.product_id'])
            ->join('orders as o', 'o.id', '=', 'order_details.order_id')
            ->where('o.status', ORDER_STATUS_COMPLETED)
            ->whereIn('order_details.product_id', $product_ids)
            ->whereNull('o.deleted_at')
            ->where('o.store_id', $store_id)
            ->get()->pluck('qty', 'product_id')->toArray();

        return $data;
    }

    private function getImage($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $images = File::model()->select(['id as file_id', 'url'])->whereIn('id', explode(",", $ids))->get();
        return $images->toArray();
    }

    private function getImageThumbnail($id)
    {
        if (empty($id)) {
            return [];
        }
        $thumbnail = File::model()->select(['file_name'])->where('id', explode(",", $id))->get()->toArray();
        $thumbnail = array_pluck($thumbnail, 'file_name');
        $thumbnail = implode(', ', $thumbnail);
        return $thumbnail;
    }

    /**
     * @param Product $product
     * @param $input
     * @throws \Exception
     */
    private function updateStore(Product $product, $input)
    {
        $input['stores'][] = ['store_id' => TM::getCurrentStoreId()];
        if (!empty($input['store_supermarket'])) {
            $input['stores'][] = ['store_id' => 44];
        }
        $product->stores()->sync(collect($input['stores'] ?? [])->pluck('store_id')->unique()->toArray());
        //        if (key_exists('stores', $input)) {
        //            $allStore        = ProductStore::model()->where('product_id', $product->id)->get();
        //            $allDeletedStore = array_pluck($allStore, 'id', 'store_id');
        //            $allStore        = $allStore->pluck(null, 'store_id');
        //            $now             = date('Y-m-d H:i:s', time());
        //            $storeIdUsed     = [];
        //            foreach ($input['stores'] as $item) {
        //                if (!empty($storeIdUsed[$item['store_id']])) {
        //                    throw new \Exception(Message::get("V008", "Store: #{$item['store_id']}"));
        //                }
        //                $productStore = new ProductStore();
        //                if (!empty($allStore[$item['store_id']])) {
        //                    // Update
        //                    unset($allDeletedStore[$item['store_id']]);
        //                    $productStore             = $allStore[$item['store_id']];
        //                    $productStore->updated_at = $now;
        //                    $productStore->updated_by = TM::getCurrentUserId();
        //                }
        //                $productStore->product_id = $product->id;
        //                $productStore->store_id   = $item['store_id'];
        //                $productStore->created_at = $now;
        //                $productStore->created_by = TM::getCurrentUserId();
        //                $productStore->save();
        //                $storeIdUsed[$item['store_id']] = $item['store_id'];
        //            }
        //            // Delete Old Product Store
        //            if ($allDeletedStore) {
        //                ProductStore::model()->whereIn('id', array_values($allDeletedStore))->delete();
        //            }
        //        }
    }

    /**
     * @param Product $product
     * @param $input
     * @throws \Exception
     */
    private function updateOption(Product $product, $input)
    {
        if (key_exists('options', $input)) {
            $allOption        = ProductOption::model()->where('product_id', $product->id)->get();
            $allDeletedOption = array_pluck($allOption, 'id', 'option_id');
            $allOption        = $allOption->pluck(null, 'option_id');
            $now              = date('Y-m-d H:i:s', time());
            $optionIdUsed     = [];
            foreach ($input['options'] as $item) {
                if (!empty($optionIdUsed[$item['id']])) {
                    throw new \Exception(Message::get("V008", "Option: #{$item['id']}"));
                }
                $productOption = new ProductOption();
                if (!empty($allOption[$item['id']])) {
                    // Update
                    unset($allDeletedOption[$item['id']]);
                    $productOption             = $allOption[$item['id']];
                    $productOption->updated_at = $now;
                    $productOption->updated_by = TM::getCurrentUserId();
                }
                $productOption->product_id = $product->id;
                $productOption->option_id  = $item['id'];
                $productOption->values     = !empty($item['values']) ? json_encode($item['values']) : json_encode([]);
                $productOption->created_at = $now;
                $productOption->created_by = TM::getCurrentUserId();
                $productOption->save();
                $optionIdUsed[$item['id']] = $item['id'];
            }
            // Delete Old Product Option
            if ($allDeletedOption) {
                ProductOption::model()->whereIn('id', array_values($allDeletedOption))->delete();
            }
        }
    }

    /**
     * @param Product $product
     * @param $input
     * @throws \Exception
     */
    private function updateDiscount(Product $product, $input)
    {
        if (key_exists('discounts', $input)) {
            $allDiscount        = ProductDiscount::model()->where('product_id', $product->id)->get();
            $allDeletedDiscount = array_pluck($allDiscount, 'id', 'user_group_id');
            $allDiscount        = $allDiscount->pluck(null, 'user_group_id');
            $now                = date('Y-m-d H:i:s', time());
            $userIdUsed         = [];
            foreach ($input['discounts'] as $item) {
                if (!empty($userIdUsed[$item['user_group_id']])) {
                    throw new \Exception(Message::get("V008", "Group: #{$item['user_group_id']}"));
                }
                $productDiscount = new ProductDiscount();
                if (!empty($allDiscount[$item['user_group_id']])) {
                    // Update
                    unset($allDeletedDiscount[$item['user_group_id']]);
                    $productDiscount             = $allDiscount[$item['user_group_id']];
                    $productDiscount->updated_at = $now;
                    $productDiscount->updated_by = TM::getCurrentUserId();
                }
                $productDiscount->product_id         = $product->id;
                $productDiscount->user_group_id      = $item['user_group_id'];
                $productDiscount->code               = $item['code'] ?? null;
                $productDiscount->price              = $item['price'] ?? null;
                $productDiscount->discount_unit_type = $item['discount_unit_type'] ?? PRODUCT_UNIT_TYPE_PERCENT;
                $productDiscount->created_at         = $now;
                $productDiscount->created_by         = TM::getCurrentUserId();
                $productDiscount->save();
                $userIdUsed[$item['user_group_id']] = $item['user_group_id'];
            }
            // Delete Old Product Discount
            if ($allDeletedDiscount) {
                ProductDiscount::model()->whereIn('id', array_values($allDeletedDiscount))->delete();
            }
        }
    }

    /**
     * @param Product $product
     * @param $input
     * @throws \Exception
     */
    private function updatePromotion(Product $product, $input)
    {
        if (key_exists('promotions', $input)) {
            $allPromotion        = ProductPromotion::model()->where('product_id', $product->id)->get();
            $allDeletedPromotion = array_pluck($allPromotion, 'id', 'user_group_id');
            $allPromotion        = $allPromotion->pluck(null, 'user_group_id');
            $now                 = date('Y-m-d H:i:s', time());
            $userIdUsed          = [];
            foreach ($input['promotions'] as $item) {
                if (!empty($userIdUsed[$item['user_group_id']])) {
                    throw new \Exception(Message::get("V008", "Group: #{$item['user_group_id']}"));
                }
                $productPromotion = new ProductPromotion();
                if (!empty($allPromotion[$item['user_group_id']])) {
                    // Update
                    unset($allDeletedPromotion[$item['user_group_id']]);
                    $productPromotion             = $allPromotion[$item['user_group_id']];
                    $productPromotion->updated_at = $now;
                    $productPromotion->updated_by = TM::getCurrentUserId();
                }
                $productPromotion->product_id    = $product->id;
                $productPromotion->user_group_id = $item['user_group_id'];
                $productPromotion->priority      = $item['priority'] ?? null;
                $productPromotion->price         = $item['price'] ?? null;
                $productPromotion->start_date    = !empty($item['start_date']) ? date(
                    'Y-m-d',
                    strtotime($item['start_date'])
                ) : null;
                $productPromotion->end_date      = !empty($item['end_date']) ? date(
                    'Y-m-d',
                    strtotime($item['end_date'])
                ) : null;
                $productPromotion->is_default    = $item['is_default'] == true ? 1 : 0;
                $productPromotion->created_at    = $now;
                $productPromotion->created_by    = TM::getCurrentUserId();
                $productPromotion->save();
                $userIdUsed[$item['user_group_id']] = $item['user_group_id'];
            }
            // Delete Old Product Promotion
            if ($allDeletedPromotion) {
                ProductPromotion::model()->whereIn('id', array_values($allDeletedPromotion))->delete();
            }
        }
    }

    /**
     * @param Product $product
     * @param $input
     * @throws \Exception
     */
    private function updateRewardPoint(Product $product, $input)
    {
        if (key_exists('reward_points', $input)) {
            $allRewardPoint        = ProductRewardPoint::model()->where('product_id', $product->id)->get();
            $allDeletedRewardPoint = array_pluck($allRewardPoint, 'id', 'user_group_id');
            $allRewardPoint        = $allRewardPoint->pluck(null, 'user_group_id');
            $now                   = date('Y-m-d H:i:s', time());
            $userIdUsed            = [];
            foreach ($input['reward_points'] as $item) {
                if (!empty($userIdUsed[$item['user_group_id']])) {
                    throw new \Exception(Message::get("V008", "Group: #{$item['user_group_id']}"));
                }
                $productRewardPoint = new ProductRewardPoint();
                if (!empty($allRewardPoint[$item['user_group_id']])) {
                    // Update
                    unset($allDeletedRewardPoint[$item['user_group_id']]);
                    $productRewardPoint             = $allRewardPoint[$item['user_group_id']];
                    $productRewardPoint->updated_at = $now;
                    $productRewardPoint->updated_by = TM::getCurrentUserId();
                }
                $productRewardPoint->product_id    = $product->id;
                $productRewardPoint->user_group_id = $item['user_group_id'];
                $productRewardPoint->point         = !empty($item['point']) ? $item['point'] : 0;
                $productRewardPoint->created_at    = $now;
                $productRewardPoint->created_by    = TM::getCurrentUserId();
                $productRewardPoint->save();
                $userIdUsed[$item['user_group_id']] = $item['user_group_id'];
            }
            // Delete Old Product RewardPoint
            if ($allDeletedRewardPoint) {
                ProductRewardPoint::model()->whereIn('id', array_values($allDeletedRewardPoint))->delete();
            }
        }
    }

    /**
     * @param Product $product
     * @param $input
     * @throws \Exception
     */
    private function updateVersion(Product $product, $input)
    {
        if (key_exists('versions', $input)) {
            $allVersion        = ProductVersion::model()->where('product_id', $product->id)->get();
            $allDeletedVersion = array_pluck($allVersion, 'id', 'product_version');
            $allVersion        = $allVersion->pluck(null, 'product_version');
            $now               = date('Y-m-d H:i:s', time());
            $versionUsed       = [];
            foreach ($input['versions'] as $item) {
                $productVersion        = new ProductVersion();
                $item['version']       = strtoupper(str_replace(" ", "-", trim($item['version'])));
                $input_product_version = $item['version_product_id'] . "|" . $item['version'];
                if (!empty($versionUsed[$input_product_version])) {
                    throw new \Exception(Message::get("V008", "Version: #{$item['version']}"));
                }
                if (!empty($allVersion[$input_product_version])) {
                    // Update
                    unset($allDeletedVersion[$input_product_version]);
                    $productVersion             = $allVersion[$input_product_version];
                    $productVersion->updated_at = $now;
                    $productVersion->updated_by = TM::getCurrentUserId();
                }
                $productVersion->product_id         = $product->id;
                $productVersion->version_product_id = $item['version_product_id'];
                $productVersion->version            = $item['version'];
                $productVersion->price              = $item['price'] ?? null;
                $productVersion->product_version    = $input_product_version;
                $productVersion->created_at         = $now;
                $productVersion->created_by         = TM::getCurrentUserId();
                $productVersion->save();
                $versionUsed[$input_product_version] = $input_product_version;
            }
            // Delete Old Product Version
            if ($allDeletedVersion) {
                ProductVersion::model()->whereIn('id', array_values($allDeletedVersion))->delete();
            }
        }
    }

    private function updateProducts($id, $code, $name)
    {
        OrderDetail::where('product_id', $id)->update([
            'product_code' => $code,
            'product_name' => $name
        ]);
        WarehouseDetail::where('product_id', $id)->update([
            'product_code' => $code,
            'product_name' => $name
        ]);
        InventoryDetail::where('product_id', $id)->update([
            'product_code' => $code,
            'product_name' => $name
        ]);
    }

    private function updateProductWebsite($product, $input)
    {
        if (!empty($input['website_ids'])) {
            $allProductWebsite = ProductWebsite::model()->where('product_id', $product->id)->get();
            $allDeletedWebsite = array_pluck($allProductWebsite, 'id', 'website_id');
            $allProductWebsite = $allProductWebsite->pluck(null, 'website_id');
            $allWebsite        = Website::model()->pluck('domain', 'id')->toArray();
            foreach ($input['website_ids'] as $item) {
                $productWebsite = new ProductWebsite();
                //Check Website
                if (empty($allWebsite[$item])) {
                    throw new \Exception(Message::get("V003", "ID Website: #$item"));
                }
                if (!empty($allProductWebsite[$item])) {
                    unset($allDeletedWebsite[$item]);
                } else {
                    $productWebsite->product_id = $product->id;
                    $productWebsite->website_id = $item;
                    $productWebsite->save();
                }
            }
            if ($allDeletedWebsite) {
                ProductWebsite::model()->whereIn('id', array_values($allDeletedWebsite))->delete();
            }
        }
    }

    public function transformProduct($products, $promotionPrograms)
    {
        // $data_string = !empty($product) ? json_encode($product) : null;
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();

        foreach ($products as $product) {

            $category_ids = explode(',', $product->category_ids);
            $item_gift    = json_decode($product->gift_item);
            if (empty($item_gift)) {
                $category = Category::model()->whereIn('id', $category_ids)->get();
                foreach ($category as $value) {
                    if ($value->gift_item) {
                        foreach (json_decode($value->gift_item) as $key => $value) {
                            $item_gift[] = $value;
                        }
                    }
                }
            }
            //            $realPrice = $product->price_down;
            //            if ($product->price_down) {
            //                $downFrom = strtotime($product->down_from);
            //                $downTo   = strtotime($product->down_to);
            //                $now      = time();
            //                if ($now < $downFrom || $now > $downTo) {
            //                    $realPrice = 0;
            //                }
            //            }
            $is_comment = 0;
            if (!empty(TM::getCurrentUserId())) {
                // $countProductComment = $product->comments->where('user_id', TM::getCurrentUserId())
                //     ->where('type', PRODUCT_COMMENT_TYPE_RATE)
                //     ->count();
                $countProductComment = ProductComment::model()->where('product_id', $product->id)
                    ->where('user_id', TM::getCurrentUserId())
                    ->where('type', PRODUCT_COMMENT_TYPE_RATE)
                    ->count();

                $countProductOrder = OrderDetail::where('product_id', $product->id)->whereHas('order', function ($query) {
                    $query->where('customer_id', TM::getCurrentUserId());
                    $query->where('status', ORDER_STATUS_COMPLETED);
                })->count();
                if ($countProductComment != $countProductOrder) {
                    $is_comment = 1;
                }
            }
            $category_flash_sale_ids = [];
            $product_flash_sale_ids  = [];
            $product_gift            = [];
            $category_gift           = [];
            $iframe_image_id         = null;
            $iframe_image            = null;
            if (!empty($promotionPrograms)) {
                foreach ($promotionPrograms as $value) {
                    if ($value->promotion_type == 'FLASH_SALE') {
                        foreach (json_decode($value->act_categories) as $key) {
                            $category_flash_sale_ids[] = $key->category_id;
                        }
                        foreach (json_decode($value->act_products) as $key) {
                            $product_flash_sale_ids[] = $key->product_id;
                        }
                    }
                    $order_sale = OrderDetail::model()
                        ->join('orders', 'orders.id', 'order_details.order_id')
                        ->whereRaw("order_details.created_at BETWEEN '$value->start_date' AND '$value->end_date'")
                        ->where('orders.status', '!=', 'CANCELED')
                        ->where('order_details.product_id', $product->id)
                        ->groupBy('order_details.product_id')
                        ->sum('order_details.qty');
                }
            }
            $PromotionsGiftAndIframe = (new PromotionProgram())->PromotionsGiftAndIframe($product);

            $flash_sale   = 0;
            $chek_product = array_search($product->id, $product_flash_sale_ids);
            if (!empty($chek_product)) {
                $flash_sale = 1;
            }
            if (empty($chek_product)) {
                if (!empty($category_flash_sale_ids)) {
                    $category_product = explode(',', $product->category_ids);
                    $check_category   = array_intersect($category_product, $category_flash_sale_ids);
                    if ($check_category) {
                        $flash_sale = 1;
                    }
                }
            }
            $price = Arr::get((new Product)->priceDetail($product), 'price', $product->price);

            if ($price < $product->price) {
                $percent_price        = $product->price - $price;
                $percentage_price_old = round(($percent_price / $product->price) * 100);
            }

            $special            = null;
            $special_start_date = null;
            $special_end_date   = null;
            $special_formated   = null;
            $special_percentage = 0;

            $promotionPrograms = (new PromotionHandle())->promotionApplyProduct($promotionPrograms, (new Product));

            $promotionPrice = 0;
            if ($promotionPrograms && !$promotionPrograms->isEmpty()) {

                $type_promotion   = array_pluck($promotionPrograms, 'promotion_type');
                $keyIsFlashSale = [];
                foreach($type_promotion ?? [] as $_key => $_value){
                    if($_value !== 'FLASH_SALE'){
                        continue;
                    }
                    $keyIsFlashSale[$_key] = $_key;
                }
                // $check_flash_sale = array_search('FLASH_SALE', $type_promotion);

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
                    
                    $prod        = array_pluck($promotion->productPromotion, 'product_id');
                    $search_prod = array_search($product->id, $prod);

                    if(!is_numeric($search_prod) ){
                        continue;
                    }

                    // if (is_numeric($check_flash_sale) && !is_numeric($search_prod)) {
                    //     continue;
                    // }

                    if (empty($iframe_image_id) && empty($iframe_image)) {
                        $iframe_image_id = $promotion->iframe_image_id;
                        $iframe_image    = $promotion->iframeImage->code ?? null;
                    }

                    if ($promotion->promotion_type == 'FLASH_SALE') {
                        if (($order_sale ?? 0) >= ($product->qty_flash_sale ?? 1)) {
                            $promotionPrice = 0;
                        } else {
                            $promotionPrice += (new PromotionProgramController())->promotionPrice($promotion->productPromotion, $product->id, $price, $promotion->discount_by, $promotion->act_sale_type, $promotion->act_price);
                        }
                    }
                    if ($promotion->promotion_type != 'FLASH_SALE') {
                        $promotionPrice += (new PromotionProgramController())->promotionPrice($promotion->productPromotion, $product->id, $price, $promotion->discount_by, $promotion->act_sale_type, $promotion->act_price);
                    }


                    if ($key == 0) {
                        $special_start_date = !empty($promotion->start_date) ? date('d-m-Y', strtotime($promotion->start_date)) : null;
                        $special_end_date   = !empty($promotion->end_date) ? date('d-m-Y', strtotime($promotion->end_date)) : null;
                    }
                }

                $special          = $price - $promotionPrice;
                $special_formated = number_format($special) . "đ";
                if (isset($special)) {
                    $special_percentage = $price != 0 || !empty($price) ? round(($promotionPrice / $price) * 100) : 0;
//                    $special_percentage = round(($promotionPrice / $price) * 100);
                }
            }

            setlocale(LC_MONETARY, 'vi_VN');
            //            $productAttributes = (new ProductAttributeModel())->getListByProductId($product->id, true);
            //            $rate     = $product->comments;

            $fileType = DB::table('products')
                ->join('files', 'files.id', '=', 'products.thumbnail')
                ->where('files.id', $product->thumbnail)
                ->select('files.type')
                ->first();

            $fileCode = DB::table('products')
                ->join('files', 'files.id', '=', 'products.thumbnail')
                ->where('files.id', $product->thumbnail)
                ->select('files.code')
                ->first();

            $brand = DB::table('products')
                ->join('brands', 'brands.id', '=', 'products.brand_id')
                ->where('products.id', $product->id)
                ->select('brands.*')
                ->first();

            $area = DB::table('products')
                ->join('areas', 'areas.id', '=', 'products.area_id')
                ->where('products.id', $product->id)
                ->select('areas.*')
                ->first();

            $warehouse = DB::table('products')
                ->join('warehouse_details', 'warehouse_details.product_id', '=', 'products.id')
                ->where('products.id', $product->id)
                ->select('warehouse_details.quantity')
                ->first();

            $unit = DB::table('products')
                ->join('units', 'units.id', '=', 'products.unit_id')
                ->where('products.id', $product->id)
                ->select('units.id', 'units.name')
                ->first();

            $len_rate = DB::table('products')
                ->join('product_comments', 'product_comments.product_id', '=', 'products.id')
                ->where('products.id', $product->id)
                ->select('product_comments.id')
                ->get();

            $property_variant = DB::table('property_variants')
                ->whereIn('id', explode(',', $product->property_variant_ids))
                ->select('id', 'code', 'name')
                ->get();

            if (empty($iframe_image_id) && empty($iframe_image)) {
                $iframe_image_id = Arr::get((new Product)->promotionTagsAndIframe($product), 'iframe_image_id', null);
                $iframe_image    = Arr::get((new Product)->promotionTagsAndIframe($product), 'iframe_image', null);
            }

            $output[] = [
                'id'                           => $product->id,
                'is_comment'                   => $is_comment,
                'code'                         => $product->code,
                'name'                         => $product->name,
                'slug'                         => $product->slug,
                'url'                          => env('APP_URL') . "/product/{$product->slug}",
                'type'                         => $product->type,
                'tags'                         => $product->tags,
                'tax'                          => $product->tax,
                'promotion_tags'               => !empty(Arr::get((new Product)->promotionTagsAndIframe($product), 'tags', null)) ? json_decode(Arr::get((new Product)->promotionTagsAndIframe($product), 'tags', null)) : [],
                'attribute_info'               => !empty($product->attribute_info) ? json_decode($product->attribute_info, true) : [],
                'qr_scan'                      => $product->qr_scan,
                'star_rating'                  => 0,
                'short_description'            => $product->short_description,
                'description'                  => $product->description,
                'thumbnail_id'                 => $product->thumbnail,
                'thumbnail_type'               => !empty($fileType) ? $fileType->type : null,
                'thumbnail'                    => !empty($fileCode) ? env('UPLOAD_URL') . '/file/' . $fileCode->code : null,
                'iframe_image_id'              => !empty($PromotionsGiftAndIframe['iframe_image_id']) ? $PromotionsGiftAndIframe['iframe_image_id'] : $iframe_image_id,
                'iframe_image'                 => !empty($PromotionsGiftAndIframe['iframe_image_id']) ? env('GET_FILE_URL') . $PromotionsGiftAndIframe['iframe_image'] : (!empty($iframe_image) ? env('GET_FILE_URL') . $iframe_image : null),
                'gallery_image_ids'            => $product->gallery_images,
                'gallery_images'               => $this->stringToImageDataString($product->gallery_images),
                //                'category_ids'                 => $product->category_ids,
                //                'categories'                   => $this->getNameCategory($product->category_ids),
                //                'favorites_count'              => $product->favorites_count,
                'brand'                        => $brand,
                'area'                         => $area,
                //                'variants'                     => $product->variants,
                //                'productAttributes'            => $productAttributes,
                'price'                        => $price,
                'price_formatted'              => number_format($price) . "đ",
                'original_price'               => $price,
                'original_price_formatted'     => number_format($price) . "đ",
                'old_product_price'            => $product->price == $price ? 0 : $product->price,
                'old_product_price_formatted'  => number_format($product->price) . "đ",
                'percentage_price_old'         => ($percentage_price_old ?? 0) . "%",
                'promotion_price'              => $promotionPrice,
                'promotion_price_formatted'    => number_format($promotionPrice) . "đ",
                'special'                      => $special,
                'special_formatted'            => $special_formated,
                'special_start_date'           => $special_start_date,
                'special_end_date'             => $special_end_date,
                'special_percentage'           => $special_percentage,
                'special_percentage_formatted' => $special_percentage . "%",
                //                'real_price'                   => $realPrice,
                'property_variant_ids'         => $product->property_variant_ids,
                'property_variant'             => $property_variant,
                //                'price_down'                   => $product->price_down,
                //                'down_rate'                    => $product->price != 0 ? $product->price_down * 100 / $product->price : 0,
                //                'down_from'                    => !empty($product->down_from) ? date('d-m-Y H:i:s',
                //                    strtotime($product->down_from)) : null,
                //                'down_to'                      => !empty($product->down_to) ? date('d-m-Y H:i:s',
                //                    strtotime($product->down_to)) : null,
                //                'handling_object'              => $product->handling_object,
                //                'personal_object'              => $product->personal_object,
                //                'enterprise_object'            => $product->enterprise_object,
                'check_flash_sale'             => $flash_sale,
                'sku'                          => $product->sku,
                'upc'                          => $product->upc,
                'qty'                          => !empty($warehouse) ? $warehouse->quantity : 0,
                'length'                       => $product->length,
                'width'                        => $product->width,
                'height'                       => $product->height,
                'length_class'                 => $product->length_class,
                'weight_class'                 => $product->weight_class,
                'weight'                       => $product->weight,
                'status'                       => $product->status,
                'order'                        => $product->order,
                'view'                         => $product->view,
                //                'store_id'                     => $product->store_id,
                //                'store_name'                   => Arr::get($product->storeOrigin, 'name'),
                'unit_id'                      => !empty($unit) ? $unit->id : null,
                'unit_name'                    => !empty($unit) ? $unit->name : null,
                //                'store_origin'                 => [
                //                    'id'   => Arr::get($product->storeOrigin, 'id'),
                //                    'name' => Arr::get($product->storeOrigin, 'name')
                //                ],
                //                'stores'                       => $product->stores->map(function ($item) {
                //                    return $item->only(['id', 'name']);
                //                }),
                'is_featured'                  => $product->is_featured,
                //                'related_ids'                  => $product->related_ids,
                //                'combo_liked'                  => $product->combo_liked,
                //                'exclusive_premium'            => $product->exclusive_premium,
                //                'manufacturer_id'              => $product->manufacturer_id,
                //                'manufacturer_name'            => object_get($product, 'masterData.name'),
                //                'manufacturer_code'            => object_get($product, 'masterData.code'),
                'qty_out_min'                  => $product->qty_out_min,
                'custom_date_updated'          => !empty($product->custom_date_updated) ? date(
                    'd-m-Y',
                    strtotime($product->custom_date_updated)
                ) : null,
                'sold_count'                   => $sold_count = $product->sold_count ?? 0,
                'sold_count_formatted'         => format_number_in_k_notation($sold_count),
                'order_count'                  => $product->order_count ?? 0,
                'gift_item'                    => !empty($item_gift) ? array_unique($item_gift, SORT_REGULAR) : [],
                'count_rate'                   => $product->count_rate,
                //                'version_name'                 => $product->version_name,
                'publish_status'               => $product->publish_status,
                'created_at'                   => date('d-m-Y', strtotime($product->created_at)),
                'updated_at'                   => date('d-m-Y', strtotime($product->updated_at)),
                'star'                         => $this->getStarRateDataString($product->id),
                'len_rate'                     => (int)count($len_rate),
                'product_gift'                 => $PromotionsGiftAndIframe['product_gift'] ?? [],
                'data_string'                  => $data_string ?? null,
            ];
        }
        return $output;
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

    public function getStarRateDataString($id)
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
}
