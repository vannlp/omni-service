<?php


namespace App\V1\Controllers;


use App\Cart;
use App\Coupon;
use App\Rotation;
use App\CouponCategory;
use App\CouponCategoryexcept;
use App\CouponHistory;
use App\CouponProduct;
use App\CouponProductexcept;
use App\RotationCondition;
use App\RotationDetail;
use App\RotationResult;
use App\Supports\DataUser;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\User;
use App\V1\Models\RotationModel;
use App\V1\Models\RotationDetailModel;
use App\V1\Transformers\Coupon\CouponApplied;
use App\V1\Transformers\Rotation\RotationDetailTransformer;
use App\V1\Transformers\Rotation\RotationTransformer;
use App\V1\Validators\RotationCreateValidator;
use App\V1\Validators\RotationUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use League\Fractal\Resource\Primitive;
use wataridori\BiasRandom\BiasRandom;
class RotationController extends BaseController
{
    /**
     * @var CouponModel
     */
    protected $model;

    /**
     * CouponController constructor.
     */
    public function __construct()
    {
        $this->model        = new RotationModel();
    }

    /**
     * @param Request $request
     * @param RotationTransformer $couponTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, RotationTransformer $rotationTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['code'])) {
            $input['code'] = ['like' => $input['code']];
        }
        if (!empty($input['type'])) {
            $input['description'] = ['like' => $input['description']];
        }
        $input['is_active'] = 1;

        Log::view($this->model->getTable());
        $result = $this->model->search($input, [], $limit);
        return $this->response->paginator($result, $rotationTransformer);
    }

    /**
     * @param $id
     * @param RotationTransformer $couponTransformer
     * @return array[]|\Dingo\Api\Http\Response
     */
    public function detail($id, RotationTransformer $rotationTransformer)
    {
        $result = Rotation::model()->with(['condition', 'result'])->where('id', $id)->first();
        if (empty($result)) {
            return ['data' => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($result, $rotationTransformer);
    }

    /**
     * @param Request $request
     * @param RationCreateValidator $couponCreateValidator
     * @return array|void
     */
    public function create(Request $request, RotationCreateValidator $rotationCreateValidator)
    {
     
        $input = $request->all();
        $rotationCreateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        $rotationCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            foreach($input['conditions'] as $con){
            $condition = RotationCondition::create([
                'rotation_id'    => $result->id,
                'code'           => $con['code'] ?? null,
                'name'           => $con['name'] ?? null,
                'type'           => $con['type'] ?? null,
                'price'          => $con['price'] ?? null,
                'is_active'      => $con['is_active'] ?? 0
            ]);
            }
            $sumratio = 0;
            foreach($input['results'] as $rr){
                $sumratio += $rr['ratio'];
   
            $rotation_result = RotationResult::create([
                'rotation_id'  => $result->id,
                'name'         => $rr['name'] ?? null,
                'code'         => $rr['code'] ?? null,
                'type'         => $rr['type'] ?? null,
                'coupon_id'    => $rr['coupon_id'] ?? null,
                'coupon_name'  => $rr['coupon_name'] ?? null,
                'description'  => $rr['description'] ?? null,
                'ratio'        => $rr['ratio'] ?? 0,
            ]);}
            if($sumratio != 100){
                DB::rollBack();
                return $this->responseError('Tổng tỷ lệ phần thưởng phải bằng 100!!!');
            }
            Log::create($this->model->getTable(), "#ID:" . $result->code);
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("coupons.create-success", $result->code)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param RotationUpdateValidator $couponUpdateValidator
     * @return array|void
     */
    public function update($id, Request $request, RotationUpdateValidator $rotationUpdateValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $rotationUpdateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        $rotationUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            $sumratio = 0;
            foreach($input['rotation_result'] as $rr){
                if(!empty($rr['ratio'])){
                    $sumratio += $rr['ratio'];
                }
            }
            if($sumratio != 100){
                DB::rollBack();
                return $this->responseError('Tổng tỷ lệ phần thưởng phải bằng 100!!!');
            }
            Log::update($this->model->getTable(), "#ID:" . $result->code);
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("coupons.update-success", $result->code)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $result = Rotation::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            Log::delete($this->model->getTable(), "#ID:" . $result->id . "-" . $result->code);
            $result->delete();
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("coupons.delete-success", $result->code)];
    }
    public function random()
    {
        $user = User::model()->where('id', TM::getCurrentUserId())->first();
        if($user->turn_rotation == 0){
            return ["message" => "Bạn đã hết lượt quay"];
        }
        else{
            $biasRandom = new BiasRandom();
            $dt = RotationResult::model()->pluck('ratio','code');
            $biasRandom->setData($dt);
            $biasRandom->random();
            $ds = RotationResult::model()->where('code', $biasRandom->random())->first();
            $detail = new RotationDetail();
            $detail->user_id = TM::getCurrentUserId();
            $detail->rotation_code = $ds->code;
            $detail->save();
            $user->turn_rotation = $user->turn_rotation -  1;
            $user->save();
            return response()->json(["data"=>[$ds]]);
        }
    }
    public function list(Request $request, RotationDetailTransformer $RotationDetailTransformer)
    {
        $input = $request->all();
        $rotationDetail = new RotationDetailModel();
        $limit = array_get($input, 'limit', 20);
        $result = $rotationDetail->search($input, [], $limit);
        return $this->response->paginator($result, $RotationDetailTransformer);
    }
    public function rotationDetailUser(Request $request, RotationDetailTransformer $RotationDetailTransformer)
    {
        $input = $request->all();
        $rotationDetail = new RotationDetailModel();
        $limit = array_get($input, 'limit', 20);
        $input['user_id'] = TM::getCurrentUserId();
        $result = $rotationDetail->search($input, [], $limit);

        return $this->response->paginator($result, $RotationDetailTransformer);
        // return $this->response->item($result, $RotationDetailTransformer);
    }
}