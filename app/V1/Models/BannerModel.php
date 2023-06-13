<?php


namespace App\V1\Models;


use App\Banner;
use App\BannerDetail;
use App\Category;
use App\File;
use App\Supports\Message;
use App\TM;
use Illuminate\Support\Str;

class BannerModel extends AbstractModel
{
    public function __construct(Banner $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id            = !empty($input['id']) ? $input['id'] : 0;
        $bannerId      = [];
        $input['code'] = clean($input['code']);
        if (!empty($input['display_in_categories'])) {
            $categoryBanners = $input['display_in_categories'];
            foreach ($categoryBanners as $categoryBanner) {
                $categoryId = $categoryBanner['id'];
                $category   = Category::find($categoryId);
                if (empty($category)) {
                    throw new \Exception(Message::get("V003", "ID: #$categoryId"));
                }

                $bannerId[] = $categoryId;
            }
        }
        $display_in_categories = implode(",", $bannerId ?? null);
        if ($id) {
            $banner = Banner::find($id);
            if (empty($banner)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $banner->title                 = array_get($input, 'title', $banner->title);
            $banner->code                  = array_get($input, 'code', $banner->code);
            $banner->display_in_categories = $display_in_categories ?? $banner->display_in_categories;
            $banner->store_id              = array_get($input, 'store_id', $banner->store_id);
            $banner->is_active             = array_get($input, 'is_active', $banner->is_active);
            $banner->updated_at            = date("Y-m-d H:i:s", time());
            $banner->updated_by            = TM::getCurrentUserId();
            $banner->save();
        } else {
            $param  = [
                'title'                 => $input['title'],
                'code'                  => clean($input['code']),
                'is_active'             => $input['is_active'] ?? 0,
                'display_in_categories' => $display_in_categories ?? null,
                'store_id'              => $input['store_id'],
            ];
            $banner = $this->create($param);
        }

        if (!empty($bannerId)) {
            $banner->category()->sync($bannerId);
        }
        // Create|Update Banner Detail
        $allBannerDetail       = BannerDetail::model()->where('banner_id', $banner->id)->get()->toArray();
        $allBannerDetail       = array_pluck($allBannerDetail, 'id', 'id');
        $allBannerDetailDelete = $allBannerDetail;
        if (!empty($input['details'])) {
            foreach ($input['details'] as $key => $detail) {
                $b = File::model()->where('code', substr($detail['image_url'], -9))->get();
                if(!empty($b)){
                    foreach ($b as $k => $ban) {
                        $param = [
                            'banner_id'            => $banner->id,
                            'image'                => !empty($ban->id) ? $ban->id : null,
                            'slug'                 => !empty($detail['slug']) ? $detail['slug'] : null,
                            'router'               => !empty($detail['router']) ? $detail['router'] : null,
                            'lp_name'              => !empty($detail['lp_name']) ? $detail['lp_name'] : null,
                            'query'                => !empty($detail['query']) ? $detail['query'] : null,
                            'color'                => !empty($detail['color']) ? $detail['color'] : "#ssssss",
                            'category_id'          => !empty($detail['category_id']) ? $detail['category_id'] : null,
                            'is_active'            => !empty($detail['is_active']) ? $detail['is_active'] : 0,
                            'post_name'            => !empty($detail['post_name']) ? $detail['post_name'] : null,
                            'product_search_query' => !empty($detail['product_search_query']) ? $detail['product_search_query'] : null,
                            'target_id'            => !empty($detail['target_id']) ? $detail['target_id'] : null,
                            'data'                 => !empty($detail['data']) ? $detail['data'] : null,
                            'type'                 => !empty($detail['type']) ? $detail['type'] : null,
                            'name'                 => !empty($detail['name']) ? $detail['name'] : null,
                            'order_by'             => $key,
                            'display_in_categories' => !empty($detail['display_in_categories']) && $detail['display_in_categories'] != "[]" ? json_encode($detail['display_in_categories']) : [],
                            'created_by'           => TM::getCurrentUserId(),
                        ];
                    }
                }
                if(strlen($b)<3){
                    $param = [
                        'banner_id'            => $banner->id,
                        'image'                => null,
                        'slug'                 => !empty($detail['slug']) ? $detail['slug'] : null,
                        'router'               => !empty($detail['router']) ? $detail['router'] : null,
                        'lp_name'              => !empty($detail['lp_name']) ? $detail['lp_name'] : null,
                        'query'                => !empty($detail['query']) ? $detail['query'] : null,
                        'color'                => !empty($detail['color']) ? $detail['color'] : "#ssssss",
                        'category_id'          => !empty($detail['category_id']) ? $detail['category_id'] : null,
                        'is_active'            => !empty($detail['is_active']) ? $detail['is_active'] : 0,
                        'post_name'            => !empty($detail['post_name']) ? $detail['post_name'] : null,
                        'product_search_query' => !empty($detail['product_search_query']) ? $detail['product_search_query'] : null,
                        'target_id'            => !empty($detail['target_id']) ? $detail['target_id'] : null,
                        'data'                 => !empty($detail['data']) ? $detail['data'] : null,
                        'type'                 => !empty($detail['type']) ? $detail['type'] : null,
                        'name'                 => !empty($detail['name']) ? $detail['name'] : null,
                        'order_by'             => $key,
                        'display_in_categories' => !empty($detail['display_in_categories']) && $detail['display_in_categories'] != "[]" ? json_encode($detail['display_in_categories']) : [],
                        'created_by'           => TM::getCurrentUserId(),
                    ];
                    
                }
                
                if (empty($detail['id']) || empty($allBannerDetail[$detail['id']])) {
                    // Create Detail
                    $bannerDetail = new BannerDetail();
                    $bannerDetail->create($param);
                    continue;
                }

                // Update
                $this->refreshModel();
                $param['id'] = $detail['id'];
                $detailModel = new BannerDetailModel();
                $detailModel->update($param);
                unset($allBannerDetailDelete[$detail['id']]);
            }
        }

        if (!empty($allBannerDetailDelete)) {
            BannerDetail::model()->whereIn('id', array_values($allBannerDetailDelete))->delete();
        }

        return $banner;
    }
}
