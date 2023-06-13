<?php


namespace App\V1\Models;


use App\OrderDetail;
use App\Product;
use App\ProductComment;
use App\Profile;
use App\ReasonCancel;
use App\Supports\DataUser;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use Illuminate\Support\Facades\DB;
use App\User;

class ProductCommentModel extends AbstractModel
{
    /**
     * ProductCommentModel constructor.
     * @param ProductComment|null $model
     */
    public function __construct(ProductComment $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            $id = !empty($input['id']) ? $input['id'] : 0;
            $allProfiles = Profile::model()->pluck('full_name', 'user_id');
            //Check Rate Star
            if (isset($input['rate']) && ($input['rate'] <= 0 || $input['rate'] > 5)) {
                throw new \Exception(Message::get('rate_errors', 1, 5));
            }
            DB::beginTransaction();
            if ($id) {
                $productComment = ProductComment::find($id);
                if (empty($productComment)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $product = Product::findOrFail($productComment->product_id);
                $productComment->type = array_get($input, 'type', PRODUCT_COMMENT_TYPE_RATE);
                $productComment->product_id = array_get($input, 'product_id', $productComment->product_id);
                $productComment->product_code = $product->code ?? $productComment->product_code;
                $productComment->product_name = $product->name ?? $productComment->product_name;
                $productComment->user_id = array_get($input, 'user_id', $productComment->user_id);
                // $productComment->user_name = $allProfiles[TM::getCurrentUserId()] ?? $productComment->user_name;
                $productComment->user_name = User::find(TM::getCurrentUserId())->name ?? $productComment->user_name;
                $productComment->content = array_get($input, 'content', $productComment->content);
                $productComment->rate = array_get($input, 'rate', $productComment->rate);
                $productComment->rate_name = RATE_NAME[$productComment->rate];
                $productComment->hashtag_rates = !empty($input['hashtag_rates']) ? json_encode($input['hashtag_rates']) : $productComment->hashtag_rates;
                $productComment->parent_id = array_get($input, 'parent_id', $productComment->parent_id);
                $productComment->company_id = array_get($input, 'company_id', $productComment->company_id);
                $productComment->store_id = array_get($input, 'store_id', $productComment->store_id);
                $productComment->images = !empty($input['images']) ? json_encode($input['images']) : $productComment->images;
                $productComment->is_active = array_get($input, 'is_active', $productComment->is_active);
                $productComment->order_id = array_get($input, 'order_id', $productComment->order_id);
                $productComment->order_code = array_get($input, 'order_code', $productComment->order_code);
                $productComment->order_detail_id = array_get($input, 'order_detail_id', $productComment->order_detail_id);
                $productComment->updated_at = date("Y-m-d H:i:s", time());
                $productComment->updated_by = TM::getCurrentUserId();
                $productComment->save();
            } else {
                if (!empty($input['parent_id'])) {
                    $parent = $input['parent_id'];
                    $productComment = ProductComment::find($parent);
                    if (empty($productComment)) {
                        throw new \Exception(Message::get("V003", "ID: #$parent"));
                    }
                    if ($input['type'] == PRODUCT_COMMENT_TYPE_REPLY) {
                        $parentCheck = ProductComment::model()->where('parent_id', $parent)->first();
                        if ($parentCheck) {
                            throw new \Exception(Message::get("V064"));
                        }
                        $productComment->replied = 1;
                        $productComment->save();
                    }

                    if ($input['type'] == PRODUCT_COMMENT_TYPE_REPLY_COMMENT) {
                        $parentCheck = ProductComment::model()
                            ->where('id', $parent)
                            ->whereIn('type', [PRODUCT_COMMENT_TYPE_COMMENT, PRODUCT_COMMENT_TYPE_REPLY_COMMENT])
                            ->whereNotNull('parent_id')
                            ->first();
                        if (!$parentCheck) {
                            throw new \Exception(Message::get("V003", "Parent ID: #$parent"));
                        }
                    }

                }
                if(!empty($input['product_id'])){
                    $product_id = $input['product_id'];
                    $product = Product::find($product_id ?? ($productComment->product_id ?? 0));
                }
                $is_active = 0;

                $check_language = ReasonCancel::model()->where('company_id', TM::getCurrentCompanyId())->where('type', 'BLACKLIST')->pluck('value')->toArray();
                if ($check_language) {
                    if (!empty($input['content'])) {
                        $text = strtolower($input['content']);
                        $arr_text = explode(" ", $text);
                        $check = array_intersect($check_language, $arr_text);
                        if (count($check) == 0) {
                            if (!empty($input['rate']) && $input['rate'] > 2) {
                                $is_active = 1;
                            }
                            if (empty($input['rate'])) {
                                $is_active = 1;
                            }
                        };
                    }
                }
                $hashtag_rates = $input['hashtag_rates'] ?? "[]";
                $images = $input['images'] ?? "[]";
                $param = [
                    'type'            => $input['type'],
                    'product_id'      => $product->id ?? null,
                    'product_code'    => $product->code ?? null,
                    'product_name'    => $product->name ?? null,
                    'user_id'         => TM::getCurrentUserId(),
                    // 'user_name'       => $allProfiles[TM::getCurrentUserId()] ?? null,
                    'user_name'       => User::find(TM::getCurrentUserId())->name ?? null,
                    'content'         => $input['content'] ?? null,
                    'rate'            => $input['rate'] ?? null,
                    'rate_name'       => RATE_NAME[$input['rate'] ?? null],
                    'hashtag_rates'   => $hashtag_rates != "[]" ? json_encode($hashtag_rates) : "[]",
                    'parent_id'       => $input['parent_id'] ?? null,
                    'company_id'      => TM::getCurrentCompanyId(),
                    'store_id'        => TM::getCurrentStoreId(),
                    'images'          => $images != "[]" ? json_encode($images) : null,
                    'is_active'       => $is_active,
                    'order_id'        => $input['order_id'] ?? null,
                    'order_code'      => $input['order_code'] ?? null,
                    'order_detail_id' => $input['order_detail_id'] ?? null,
                    'created_by'       => TM::getCurrentUserId(),
                ];

                $productComment = $this->create($param);

                // $fields  = [      
                //     'notification' => "Có một thông báo mới"                
                // ];
                // $curl = curl_init();

                // curl_setopt_array($curl, array(
                //   CURLOPT_URL =>  env('FIREBASE_DBR_URL', ''),
                //   CURLOPT_RETURNTRANSFER => true,
                //   CURLOPT_ENCODING => '',
                //   CURLOPT_MAXREDIRS => 10,
                //   CURLOPT_TIMEOUT => 0,
                //   CURLOPT_FOLLOWLOCATION => true,
                //   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                //   CURLOPT_CUSTOMREQUEST => 'POST',
                //   CURLOPT_POSTFIELDS =>json_encode($fields),
                //   CURLOPT_HTTPHEADER => array(
                //     'Content-Type: application/json'
                //   ),
                // ));
                
                // $response = curl_exec($curl);
                
                // curl_close($curl);           

                //Update Rate Product
                if (!empty($product_id) && $productComment->type == PRODUCT_COMMENT_TYPE_RATE) {
                    $product = DB::table('products')->where('id', $product_id);
                    $countRate = $product->first()->count_rate;
                    $star_1 = $this->count_star($product_id, 1);
                    $star_2 = $this->count_star($product_id, 2);
                    $star_3 = $this->count_star($product_id, 3);
                    $star_4 = $this->count_star($product_id, 4);
                    $star_5 = $this->count_star($product_id, 5);
                    $start = $star_1 + $star_2 + $star_3 + $star_4 + $star_5;
                    $avg = $start > 0 ? round(($star_1 * 1 + $star_2 * 2 + $star_3 * 3 + $star_4 * 4 + $star_5 * 5) / $start, 2) : 0;
                    $product->update([
                        'count_rate' => $countRate + 1,
                        'rate_avg'   => $avg
                    ]);
                }
                if (!empty($productComment->type) && $productComment->type == PRODUCT_COMMENT_TYPE_RATE && !empty($input['order_detail_id'])) {
                    $orderDetail = OrderDetail::find($input['order_detail_id']);
                    $orderDetail->commented = 1;
                    $orderDetail->save();
                }
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            throw new \Exception($response['message']);
        }
        return $productComment;
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
}