<?php


namespace App\V1\Transformers\Banner;


use App\Banner;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class BannerClientTransformer extends TransformerAbstract
{
    public function transform(Banner $banner)
    {
        try {
            $display_in_categories = !empty($banner->display_in_categories) ? explode(",", $banner->display_in_categories) : [];
            return [
                'id'                          => $banner->id,
                'title'                       => $banner->title,
                'code'                        => $banner->code,
                'store_id'                    => $banner->store_id,
                'is_active'                   => $banner->is_active,
                'display_in_categories'       => $banner->is_active == 0
                    ? []
                    : $banner->category->map(function ($item) {
                        return $item->only(['id', 'name']);
                    }),
                'display_in_category_details' => $banner->is_active == 0
                    ? []
                    : $banner->category->map(function ($item) {
                        return $item->only(['id', 'name']);
                    }),

                'details'    => $banner->is_active == 0
                    ? []
                    : $banner->details->map(function ($detail) {
                        $fileCode = Arr::get($detail, 'file.code', null);
                        $fileType = Arr::get($detail, 'file.type', null);
                        return [
                            'id'                   => $detail['id'],
                            'image_url'            => !empty($fileCode) ? env('GET_FILE_URL') . $fileCode : null,
                            'image_type'           => $fileType,
                            'image'                => $detail['image'],
                            'lp_name'              => $detail['lp_name'],
                            'slug'                 => $detail['slug'],
                            'is_active'            => $detail['is_active'],
                            'router'               => $detail['router'],
                            'query'                => $detail['query'],
                            'color'                => $detail['color'],
                            'category_id'          => $detail['category_id'],
                            'category_name'        => $category_name = array_get($detail, "categoryBanner.name", null),
                            'category_slug'        => Str::slug($category_name),
                            'post_name'            => $detail['post_name'],
                            'name'                 => $detail['name'],
                            'product_search_query' => $detail['product_search_query'],
                            'target_id'            => $detail['target_id'],
                            'data'                 => $detail['data'],
                            'type'                 => $detail['type'],
                            'order_by'             => $detail['order_by'],
                            'display_in_categories'=> json_decode($detail['display_in_categories']) ?? [],
                        ];
                    })->sortBy('order_by')->where('is_active', 1)->values()->all(),
                'columns'    =>
                    [
                        ["field" => "image_url", "header" => "Hình Ảnh"],
                        ["field" => "router", "header" => "Router"],
                        ["field" => "name", "header" => "Tên hiển thị"],
                        ["field" => "query", "header" => "Query"],
                        ["field" => "is_active", "header" => "Trạng thái"]
                    ],
                'created_at' => date('d-m-Y', strtotime($banner->created_at)),
                'created_by' => object_get($banner, 'createdBy.profile.full_name'),
                'updated_at' => date('d-m-Y', strtotime($banner->updated_at)),
                'updated_by' => object_get($banner, 'updatedBy.profile.full_name'),
            ];
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}