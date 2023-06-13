<?php


namespace App\V1\Transformers\Category;


use App\Category;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class CategoryProductTopSaleTransformer extends TransformerAbstract
{
    public function transform(Category $category)
    {
        $fileCode = object_get($category, 'file.code');
        try {
            return [
                'id'               => $category->id,
                'code'             => $category->code,
                'name'             => $category->name,
                'slug'             => $category->slug,
                'order'            => $category->order ?? null,
                'sort_order'       => $category->sort_order ?? null,
                'description'      => $category->description,
                'area_id'          => $category->area_id,
                'property_ids'     => $category->property_ids,
                'property'         => $category->properties->map(function ($item) {
                    return $item->only(['id', 'code', 'name']);
                }),
                'parent_id'        => $category->parent_id,
                'image_url'        => !empty($fileCode) ? env('GET_FILE_URL') . $fileCode : null,
                'image_id'         => $category->image_id,
                'category_publish' => $category->category_publish,
                'product_publish'  => $category->product_publish,
                'is_active'        => $category->is_active,
                'meta_title'       => $category->meta_title,
                'meta_description' => $category->meta_description,
                'meta_robot'       => $category->meta_robot,
                'meta_keyword'     => $category->meta_keyword,
                'created_at'       => date('d-m-Y', strtotime($category->created_at)),
                'updated_at'       => date('d-m-Y', strtotime($category->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}