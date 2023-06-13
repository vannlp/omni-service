<?php

namespace App\V1\Transformers\Product;

use App\ProductComment;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;
use App\TM;
class CommentByProductTransformer extends TransformerAbstract
{
    public function transform(ProductComment $model)
    {
        $checkuserlike = json_decode($model->user_id_like);
        if(in_array(TM::getCurrentUserId() ?? 0, $checkuserlike ?? [])){
           $is_like = 1;
        }
        return [
            'id'                      => $model->id,
            'product_id'              => $model->product_id,
            'product_code'            => $model->product->code,
            'product_name'            => $model->product->name,
            'product_price'           => $model->product->price,
            'product_slug'            => $model->product->slug,
            'product_thumbnail'       => env('GET_FILE_URL') . Arr::get($model, 'product.file.code'),
            'product_price_formatted' => number_format(Arr::get($model, 'product.price')) . "đ",
            'user_id'                 => $model->user_id,
            'avatar'                  => $model->avatar->avatar ?? null,
            'user_name'               => $model->user_name,
            'content'                 => $model->content,
            'rate'                    => $model->rate,
            'type'                    => $model->type,
            'rate_name'               => RATE_NAME[$model->rate] ?? null,
            'hashtag_rates'           =>json_decode($model->hashtag_rates) ?? [],
            'parent_id'               => $model->parent_id,
            'is_like'                 => $is_like ?? 0,
            'like'                    => $model->like ?? 0,
            'is_active'               => $model->is_active,
            'count_childs'            => $model->childs->count(),
            'count_comments'          => $model->comments->count(),
            'childs'                  => $model->childs,
            'comments'                => $model->comments->map(function ($item) {
                $comments = json_decode($item->user_id_like);
                if(in_array(TM::getCurrentUserId() ?? 0, $comments ?? [])){
                $is_like = 1;
                }
                return [
                    'id'                      => $item->id,
                    'product_id'              => $item->product_id,
                    'product_code'            => $item->product_code,
                    'product_name'            => $item->product_name,
                    'product_price'           => Arr::get($item, 'product.price'),
                    'product_slug'           => Arr::get($item, 'product.slug'),
                    'product_thumbnail'       => env('GET_FILE_URL') . Arr::get($item, 'product.file.code'),
                    'product_price_formatted' => number_format(Arr::get($item, 'product.price')) . "đ",
                    'user_id'                 => $item->user_id,
                    'avatar'                  => $item->avatar->avatar ?? null,
                    'user_name'               => $item->user_name,
                    'content'                 => $item->content,
                    'rate'                    => $item->rate,
                    'type'                    => $item->type,
                    'rate_name'               => RATE_NAME[$item->rate] ?? null,
                    'hashtag_rates'           =>json_decode($item->hashtag_rates) ?? [],
                    'parent_id'               => $item->parent_id,
                    'is_like'                 => $is_like ?? 0,
                    'like'                    => $item->like ?? 0,
                    'is_active'               => $item->is_active,
                    'count_reply_comments'    => $item->reply_comments->count(),
                    'reply_comments'          => $item->reply_comments->map(function ($item) {
                        $reply_comments = json_decode($item->user_id_like);
                        if(in_array(TM::getCurrentUserId() ?? 0, $reply_comments ?? [])){
                        $is_like = 1;
                        }
                        return [
                            'id'                      => $item->id,
                            'product_id'              => $item->product_id,
                            'product_code'            => $item->product_code,
                            'product_name'            => $item->product_name,
                            'product_slug'           => Arr::get($item, 'product.slug'),
                            'product_price'           => Arr::get($item, 'product.price'),
                            'product_thumbnail'       => env('GET_FILE_URL') . Arr::get($item, 'product.file.code'),
                            'product_price_formatted' => number_format(Arr::get($item, 'product.price')) . "đ",
                            'user_id'                 => $item->user_id,
                            'avatar'                  => $item->avatar->avatar ?? null,
                            'user_name'               => $item->user_name,
                            'content'                 => $item->content,
                            'rate'                    => $item->rate,
                            'type'                    => $item->type,
                            'hashtag_rates'           =>json_decode($item->hashtag_rates) ?? [],
                            'rate_name'               => RATE_NAME[$item->rate] ?? null,
                            'parent_id'               => $item->parent_id,
                            'is_like'                 => $is_like ?? 0,
                            'like'                    => $item->like ?? 0,
                            'is_active'               => $item->is_active,
                            'created_at'              => date('d-m-Y H:i:s', strtotime($item->created_at)),
                            'created_by'              => object_get($item, 'createdBy.profile.full_name', null),
                        ];
                    }),
                    'created_at'              => date('d-m-Y H:i:s', strtotime($item->created_at)),
                    'created_by'              => object_get($item, 'createdBy.profile.full_name', null),
                ];
            }),
            'images'                  => json_decode($model->images) ?? [],
            'created_at'              => date('d-m-Y H:i:s', strtotime($model->created_at)),
            'created_by'              => object_get($model, 'createdBy.profile.full_name', null),
        ];
    }
}
