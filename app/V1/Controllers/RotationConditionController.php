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
use App\V1\Models\RotationConditionModel;
use App\V1\Models\RotationModel;
use App\V1\Models\RotationDetailModel;
use App\V1\Transformers\Coupon\CouponApplied;
use App\V1\Transformers\Rotation\RotationDetailTransformer;
use App\V1\Transformers\Rotation\RotationTransformer;
use App\V1\Transformers\Rotation\RotationConditionTransformer;
use App\V1\Validators\RotationConditionCreateValidator;
use App\V1\Validators\RotationConditionUpdateValidator;
use App\V1\Validators\RotationCreateValidator;
use App\V1\Validators\RotationUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use League\Fractal\Resource\Primitive;
use wataridori\BiasRandom\BiasRandom;
class RotationConditionController extends BaseController
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
        $this->model        = new RotationConditionModel();
    }

    /**
     * @param Request $request
     * @param RotationTransformer $couponTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, RotationConditionTransformer $RotationConditionTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['code'])) {
            $input['code'] = ['like' => $input['code']];
        }
        if (!empty($input['rotation_id'])) {
            $input['rotation_id'] = ['like' => $input['rotation_id']];
        }
        Log::view($this->model->getTable());
        $result = $this->model->search($input, [], $limit);
        return $this->response->paginator($result, $RotationConditionTransformer);
    }

    /**
     * @param $id
     * @param RotationTransformer $couponTransformer
     * @return array[]|\Dingo\Api\Http\Response
     */
    public function detail($id)
    {
        $result = RotationCondition::find($id);
        if (empty($result)) {
            return ["data" => []];
        }
        Log::view($this->model->getTable());
        return response()->json(["data"=>[$result]]);
    }

    /**
     * @param Request $request
     * @param RationCreateValidator $couponCreateValidator
     * @return array|void
     */
    public function create(Request $request, RotationConditionCreateValidator $RotationConditionCreateValidator)
    {
     
        $input = $request->all();
        $RotationConditionCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
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
    public function update($id, Request $request, RotationConditionUpdateValidator $RotationConditionUpdateValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $RotationConditionUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
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
            $result = RotationCondition::find($id);
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
}