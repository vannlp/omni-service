<?php

/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:28 PM
 */

namespace App\V1\Controllers;


use App\Order;
use App\Profile;
use App\Category;
use App\Product;
use App\Promotion;
use App\PromotionDetail;
use App\PromotionProgram;
use App\Supports\DataUser;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\User;
use App\V1\Models\PromotionModel;
use App\V1\Transformers\Promotion\PromotionTransformer;
use App\V1\Validators\PromotionCreateValidator;
use App\V1\Validators\PromotionUpdateValidator;
use http\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Foundation\PromotionHandle;
use App\Store;
use App\V1\Transformers\PromotionProgram\PromotionProgramFlashSaleTransformer;
use App\V1\Transformers\PromotionProgram\PromotionProgramProductTransformer;
use Carbon\Carbon;

class PromotionController extends BaseController
{

    /**
     * @var PromotionModel
     */
    protected $model;

    /**
     * PromotionController constructor.
     */
    public function __construct()
    {
        $this->model = new PromotionModel();
    }

    /**
     * @param PromotionTransformer $promotionTransformer
     *
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, PromotionTransformer $promotionTransformer)
    {
        $input     = $request->all();
        $limit     = array_get($input, 'limit', 20);
        $promotion = $this->model->search($input, [], $limit);
        return $this->response->paginator($promotion, $promotionTransformer);
    }

    public function view($id, PromotionTransformer $promotionTransformer)
    {
        $promotion = Promotion::find($id);
        if (empty($promotion)) {
            return ['data' => []];
        }
        return $this->response->item($promotion, $promotionTransformer);
    }

    public function create(Request $request, PromotionCreateValidator $promotionCreateValidator)
    {
        $input = $request->all();
        $promotionCreateValidator->validate($input);
        if ($input['type'] == 'POINT' && empty($input['point'])) {
            return $this->response->errorBadRequest(Message::get("V001", Message::get("point")));
        } else {
            if ($input['type'] == 'RANKING' && empty($input['ranking_id'])) {
                return $this->response->errorBadRequest(Message::get("V001", Message::get("ranking")));
            }
        }
        try {
            DB::beginTransaction();
            $promotion = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("promotions.create-success", $promotion->code)];
    }

    public function update($id, Request $request, PromotionUpdateValidator $promotionUpdateValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $promotionUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $promotion = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("promotions.update-success", $promotion->code)];
    }

    public function active($id)
    {
        $promotion = Promotion::find($id);
        if (empty($promotion)) {
            return $this->response->errorBadRequest(Message::get("promotions.not-exist", "#$id"));
        }
        try {
            DB::beginTransaction();
            if ($promotion->is_active === 1) {
                $msgCode              = "promotions.inactive-success";
                $promotion->is_active = "0";
            } else {
                $promotion->is_active = "1";
                $msgCode              = "promotions.active-success";
            }
            $promotion->save();
            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest(Message::get($msgCode, $promotion->phone));
        }

        return ['status' => Message::get($msgCode, "Promotion")];
    }

    /**
     * @param $id
     *
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $promotion = Promotion::find($id);
            if (empty($promotion)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            // 1. Delete Promotion detail
            if (!empty($cultureDetailIds)) {
                PromotionDetail::model()->where('promotion_id', $id)->delete();
            }
            // 2. Delete Promotion
            $promotion->delete();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("promotions.delete-success", $promotion->code)];
    }

    public function listUserUsePromotion($id)
    {
        $promotion = Promotion::find($id);
        if (empty($promotion)) {
            return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
        }
        $order_user = Order::model()->where('coupon_code', $promotion->id)->get()->pluck('created_by')->toArray();
        $users      = User::model()->whereIn('id', array_filter($order_user))->get()->toArray();
        $dataUser   = [];
        foreach ($users as $user) {
            $profile = Profile::model()->where('user_id', $user['id'])->get()->toArray();
            foreach ($profile as $item) {
                if (!empty($item['avatar'])) {
                    $avatar = explode(',', $item['avatar']);
                    $avatar = url('/v0') . "/img/" . "uploads," . $avatar[1];
                }
                $dataProfile = [
                    'id'               => $item['id'],
                    'user_id'          => $item['user_id'],
                    'email'            => $item['email'],
                    'full_name'        => $item['full_name'],
                    'address'          => $item['address'],
                    'temp_address'     => $item['temp_address'],
                    'registed_address' => $item['registed_address'],
                    'phone'            => $item['phone'],
                    'avatar'           => $avatar ?? null,
                ];
            }
            $data       = [
                'id'      => $user['id'],
                'phone'   => $user['phone'],
                'email'   => $user['email'],
                'code'    => $user['code'],
                'role_id' => $user['role_id'],
                'type'    => $user['type'],
                'profile' => $dataProfile,
            ];
            $dataUser[] = $data;
        }
        $dataPrint = [
            'id'          => $promotion->id,
            'code'        => $promotion->code,
            'title'       => $promotion->title,
            'from'        => date('d-m-Y', strtotime($promotion->from)),
            'to'          => date('d-m-Y', strtotime($promotion->to)),
            'description' => $promotion->description,
            'type'        => $promotion->type,
            'details'     => $dataUser
        ];
        return response()->json(['data' => $dataPrint]);
    }

    ####################### MOBILE ####################
    public function listMyPromotion(Request $request, PromotionTransformer $promotionTransformer)
    {
        $input     = $request->all();
        $limit     = array_get($input, 'limit', 20);
        $promotion = $this->model->searchMyPromotion($input, [], $limit);
        return $this->response->paginator($promotion, $promotionTransformer);
    }

    public function viewMyPromotion($code, PromotionTransformer $promotionTransformer)
    {
        $promotion = Promotion::model()->where('code', $code)->first();
        if (empty($promotion)) {
            return $this->response->errorBadRequest(Message::get("V002", "$code"));
        }

        $from = strtotime($promotion->from);
        $to   = strtotime($promotion->to);

        $time = time();
        if ($time < $from || $time > $to) {
            return $this->response->errorBadRequest(Message::get("V005", "$code"));
        }

        $user = User::find(TM::getCurrentUserId());
        if ($promotion->type == "POINT" && (int)$promotion->point < (int)$user->point) {
            return $this->response->errorBadRequest(Message::get("V003", "$code"));
        }
        if ($promotion->type == "RANKING_ID" && (int)$promotion->point != (int)$user->ranking_id) {
            return $this->response->errorBadRequest(Message::get("V003", "$code"));
        }

        $order = Order::model()->where('coupon_code', $promotion->code)
            ->where('customer_id', TM::getCurrentUserId())->first();
        if (!empty($order)) {
            return $this->response->errorBadRequest(Message::get("V038", "$code"));
        }

        return $this->response->item($promotion, $promotionTransformer);
    }

    public function viewMyPromotionById($id, PromotionTransformer $promotionTransformer)
    {
        $promotion = Promotion::model()->where('id', $id)->first();
        if (empty($promotion)) {
            return $this->response->errorBadRequest(Message::get("V002", "#$id"));
        }

        return $this->response->item($promotion, $promotionTransformer);
    }

    public function productFlashSale(Request $request)
    {
        $input = $request->all();
        $date  = date('Y-m-d H:i:s', time());
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $input['company_id'] = $company_id;
        $limit               = array_get($input, 'limit', 20);
        $input['store_id']   = $store_id;
        $input['area_ids']   = $area_ids ?? [];
        $input['group_id']   = $group_id;

        $promotionProgams = PromotionProgram::model()
            ->where('promotion_type', 'FLASH_SALE')
            ->where('company_id', $company_id)
            ->where('status', 1)
            ->whereRaw("'{$date}' BETWEEN start_date AND end_date")
            ->select('code', 'name', 'start_date', 'end_date', 'act_products', 'act_categories', 'act_type')
            ->get();

        if ($promotionProgams->isEmpty()) {
            return response()->json(['data' => []]);
        }
        $product_id    = [];
        $category_id   = [];
        $dataPromotion = [];
        foreach ($promotionProgams as $fs) {
            foreach (json_decode($fs->act_products) as $id_product) {
                array_push($product_id, $id_product->product_id);
            }
            foreach (json_decode($fs->act_categories) as $id_category) {
                array_push($category_id, $id_category->category_id);
            }
            $dataPromotion[] = [
                'code'       => $fs->code ?? null,
                'name'       => $fs->name ?? null,
                'start_date' => $fs->start_date ?? null,
                'end_date'   => $fs->end_date ?? null,
                'act_categories'   => !empty($fs->act_categories) ? json_decode($fs->act_categories) : null,
                'act_products'   => !empty($fs->act_products) ? json_decode($fs->act_products) : null,
                'act_type'   => $fs->act_type ?? null,
            ];
        }
        if (count($product_id) == 0 && count($category_id) == 0) {
            return response()->json(['data' => []]);
        }
        if (count($category_id) > 0) {
            $product = Product::with(['file:id,code', 'priceDetail', 'masterData', 'comments'])->where('status', $input['status'])->where(function ($query) use ($category_id) {
                for ($i = 0; $i < count($category_id); $i++) {
                    $query->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%," . $category_id[$i] . ",%");
                }
            });
        }
        if (count($product_id) > 0) {
            $product = Product::with(['file:id,code', 'priceDetail', 'masterData', 'comments'])->where('status', $input['status'])->where(function ($query) use ($product_id) {
                for ($i = 0; $i < count($product_id); $i++) {
                    $query->orWhere(DB::raw("CONCAT(',',id,',')"), 'like', "%," . $product_id[$i] . ",%");
                }
            });
        }
        if (!empty($product_id)) {
            $product->orWherein('id', $product_id);
        }

        $product->whereHas('stores', function ($q) use ($store_id) {
            $q->where('store_id', $store_id);
        });
        $product           = $product->paginate($request->input('limit', 20));
        $promotionPrograms = (new PromotionHandle())->getPromotionByActType(
            PromotionHandle::TYPE_USING_PRODUCT,
            $company_id
        );

        $product->flash_sale = $dataPromotion;
        return $this->response->paginator($product, new PromotionProgramFlashSaleTransformer($promotionPrograms, $dataPromotion));
    }

    public function productPromotionDetail(Request $request, $code)
    {
        $input = $request->all();
        $date  = date('Y-m-d H:i:s', time());
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $input['company_id'] = $company_id;
        $limit               = array_get($input, 'limit', 20);
        $input['store_id']   = $store_id;
        $input['area_ids']   = $area_ids ?? [];
        $input['group_id']   = $group_id;

        // print_r($company_id);die;
        $promotionprogams = PromotionProgram::model()
            ->where('code', $code)
            ->where('company_id', $company_id)
            ->where('status', 1)
            ->whereRaw("'{$date}' BETWEEN start_date AND end_date")
            ->select('code', 'name', 'start_date', 'promotion_type', 'end_date', 'act_products', 'act_categories', 'act_type')
            ->get();

        $product_id    = [];
        $category_id   = [];
        $dataPromotion = [];
        foreach ($promotionprogams as $fs) {
            foreach (json_decode($fs->act_products) as $id_product) {
                array_push($product_id, $id_product->product_id);
            }
            foreach (json_decode($fs->act_categories) as $id_category) {
                array_push($category_id, $id_category->category_id);
            }

            $dataPromotion[] = [
                'code'           => $fs->code ?? null,
                'name'           => $fs->name ?? null,
                'promotion_type' => $fs->promotion_type ?? null,
                'start_date'     => $fs->start_date ?? null,
                'end_date'       => $fs->end_date ?? null,
                'act_categories'   => !empty($fs->act_categories) ? json_decode($fs->act_categories) : null,
                'act_products'   => !empty($fs->act_products) ? json_decode($fs->act_products) : null,
                'act_type'   => $fs->act_type ?? null,
            ];
        }

        $product           = Product::with(['file:id,code', 'priceDetail', 'masterData', 'comments'])->where(function ($query) use ($category_id) {
            for ($i = 0; $i < count($category_id); $i++) {
                $query->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%," . $category_id[$i] . ",%");
            }
        })
            ->orWherein('id', $product_id)->paginate($request->input('limit', 20));
        $promotionPrograms = (new PromotionHandle())->getPromotionByActType(
            PromotionHandle::TYPE_USING_PRODUCT,
            $company_id
        );
        $product->promtion = $dataPromotion;
        if ($fs->promotion_type != 'FLASH_SALE') {
            return $this->response->paginator($product, new PromotionProgramProductTransformer($promotionPrograms, $dataPromotion));
        } else {
            return $this->response->paginator($product, new PromotionProgramFlashSaleTransformer($promotionPrograms, $dataPromotion));
        }
    }
}
