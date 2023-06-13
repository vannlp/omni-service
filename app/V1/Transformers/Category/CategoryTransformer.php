<?php
/**
 * User: Ho Sy Dai
 * Date: 9/20/2018
 * Time: 2:08 PM
 */

namespace App\V1\Transformers\Category;


use App\Category;
use App\File;
use App\Store;
use App\Supports\TM_Error;
use App\TM;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

/**
 * Class CategoryTransformer
 * @package App\V1\CMS\Transformers\Category
 */
class CategoryTransformer extends TransformerAbstract
{
    /**
     * @param Category $category
     * @return array
     * @throws \Exception
     */
    public function transform(Category $category)
    {
        $store         = null;
        $authorization = app('request')->header('authorization');
        if (!empty($authorization) && strlen($authorization) == 71) {

            $storeToken = str_replace("Bearer ", "", $authorization);

            $store = Store::select(['id', 'company_id'])->where('token', $storeToken)->first();
            if (!$store) {
                return ['data' => []];
            }
            $store = $store->id;
        } else {
            $store = TM::getCurrentStoreId();
        }
        $fileCode = object_get($category, 'file.code', null);
        try {
            $details          = object_get($category, "CategoryStoreDetails", null);
            $children_level_1 = [];
            $level_1          = Category::model()->with('file')
                ->where('parent_id', $category->id)
                ->whereHas('CategoryStoreDetails', function ($q) use ($store) {
                    $q->where('store_id', $store);
                })
                ->orderBy('order', 'ASC')->get()->toArray();
            foreach ($level_1 as $children) {
                $file_code1       = Arr::get($children, 'file.code', null);
                $children_level_2 = [];
                $level_2          = Category::model()->with('file')->where('parent_id', $children['id'])->whereHas('CategoryStoreDetails', function ($q) use ($store) {
                    $q->where('store_id', $store);
                })->orderBy('order', 'ASC')->get()->toArray();

                foreach ($level_2 as $children2) {
                    $file_code2 = Arr::get($children2, 'file.code', null);;
                    $detailChildren2 = Category::model()->where('id', $children2['id'])->whereHas('CategoryStoreDetails', function ($q) use ($store) {
                        $q->where('store_id', $store);
                    })->first();
                    $details2        = Arr::get($detailChildren2, "CategoryStoreDetails", null);
                    $data2           = [];
                    if ($details2) {
                        foreach ($details2 as $detail2) {
                            $data2[] = [
                                'id'               => $detail2->id,
                                'category_id'      => $detail2->category_id,
                                'store_id'         => $detail2->store_id,
                                'store_code'       => $detail2->store_code,
                                'store_name'       => $detail2->store_name,
                                'zalo_category_id' => $detail2->zalo_category_id,
                                'sync_zalo'        => $detail2->sync_zalo,
                                'created_at'       => date('d-m-Y', strtotime($detail2->created_at)),
                                'updated_at'       => date('d-m-Y', strtotime($detail2->updated_at)),
                            ];
                        }
                    }
                    $children_level_2[] = [
                        'id'               => $children2['id'],
                        'code'             => $children2['code'],
                        'name'             => $children2['name'],
                        'type'             => $children2['type'],
                        'sort_order'       => $children2['sort_order'] ?? null,
                        'order'            => $children2['order'] ?? null,
                        'image_id'         => $children2['image_id'] ?? null,
                        'image_url'        => !empty($file_code2) ? env('GET_FILE_URL') . $file_code2 : null,
                        'description'      => $children2['description'],
                        'category_publish' => $children['category_publish'],
                        'product_publish'  => $children['product_publish'],
                        'parent_id'        => $children2['parent_id'],
                        'is_active'        => $children2['is_active'],
                        'store_details'    => $data2,
                        'created_at'       => date('d-m-Y', strtotime($children2['created_at'])),
                        'updated_at'       => date('d-m-Y', strtotime($children2['updated_at'])),
                    ];
                }
                $detailChildren = Category::model()->where('id', $children['id'])->whereHas('CategoryStoreDetails', function ($q) use ($store) {
                    $q->where('store_id', $store);
                })->first();
                $details1       = Arr::get($detailChildren, "CategoryStoreDetails", null);
                if ($details1) {
                    foreach ($details1 as $detail1) {
                        $data1[] = [
                            'id'               => $detail1->id,
                            'category_id'      => $detail1->category_id,
                            'store_id'         => $detail1->store_id,
                            'store_code'       => $detail1->store_code,
                            'store_name'       => $detail1->store_name,
                            'zalo_category_id' => $detail1->zalo_category_id,
                            'sync_zalo'        => $detail1->sync_zalo,
                            'created_at'       => date('d-m-Y', strtotime($detail1->created_at)),
                            'updated_at'       => date('d-m-Y', strtotime($detail1->updated_at)),
                        ];
                    }
                }

                $children_level_1[] = [
                    'id'               => $children['id'],
                    'code'             => $children['code'],
                    'name'             => $children['name'],
                    'type'             => $children['type'],
                    'sort_order'       => $children['sort_order'] ?? null,
                    'order'            => $children['order'] ?? null,
                    'image_id'         => $children['image_id'] ?? null,
                    'image_url'        => !empty($file_code1) ? env('GET_FILE_URL') . $file_code1 : null,
                    'description'      => $children['description'],
                    'parent_id'        => $children['parent_id'],
                    'category_publish' => $children['category_publish'],
                    'product_publish'  => $children['product_publish'],
                    'is_active'        => $children['is_active'],
                    'store_details'    => $data1,
                    'created_at'       => date('d-m-Y', strtotime($children['created_at'])),
                    'updated_at'       => date('d-m-Y', strtotime($children['updated_at'])),
                    'children'         => $children_level_2,
                ];
            }
//            $data    = [];
//            foreach ($details as $detail) {
//                $data[] = [
//                    'id'               => $detail->id,
//                    'category_id'      => $detail->category_id,
//                    'store_id'         => $detail->store_id,
//                    'store_code'       => $detail->store_code,
//                    'store_name'       => $detail->store_name,
//                    'zalo_category_id' => $detail->zalo_category_id,
//                    'sync_zalo'        => $detail->sync_zalo,
//                    'created_at'       => date('d-m-Y', strtotime($detail->created_at)),
//                    'updated_at'       => date('d-m-Y', strtotime($detail->updated_at)),
//                ];
//            }
            return [
                'id'               => $category->id,
                'code'             => $category->code,
                'name'             => $category->name,
                'slug'             => $category->slug,
                'order'            => $category->order ?? null,
                'sort_order'       => $category->sort_order ?? null,
                'description'      => $category->description,
                'area_id'          => $category->area_id,
                'is_nutizen'          => $category->is_nutizen,
                'area'             => $category->area,
                'property_ids'     => $category->property_ids,
                // 'property'         => $category->properties->map(function ($item) {
                //     return $item->only(['id', 'code', 'name']);
                // }),
                'property'         => !empty($category->property) ? json_decode($category->property) : [],
                'parent_id'        => $category->parent_id,
                'parent_code'      => object_get($category, 'parent.code', null),
                'parent_name'      => object_get($category, 'parent.name', null),
                'image_url'        => !empty($fileCode) ? env('UPLOAD_URL') . '/file/' . $fileCode : null,
                'data'             => json_decode($category->data) ?? [],
                'image_id'         => $category->image_id,
                'category_publish' => $category->category_publish,
                'product_publish'  => $category->product_publish,
                'is_active'        => $category->is_active,
                'store_details'    => $details,
                'created_at'       => date('d-m-Y', strtotime($category->created_at)),
                'updated_at'       => date('d-m-Y', strtotime($category->updated_at)),
                'children'         => $children_level_1,
                'meta_title'       => $category->meta_title,
                'meta_description' => $category->meta_description,
                'meta_robot'       => $category->meta_robot,
                'meta_keyword'     => $category->meta_keyword,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
