<?php
/**
 * User: dai.ho
 * Date: 10/14/2019
 * Time: 10:43 AM
 */

namespace App\V1\Controllers;


use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\UserStatusOrderModel;
use App\V1\Transformers\User\UserStatusOrderTransformer;
use App\V1\Validators\UserStatusOrderUpsertValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserStatusOrderController extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new UserStatusOrderModel();
    }

    public function search(Request $request, UserStatusOrderTransformer $userStatusOrderTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $userStatusOrders = $this->model->search($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($userStatusOrders, $userStatusOrderTransformer);
    }

    public function userStatusOrder(Request $request, UserStatusOrderUpsertValidator $userStatusOrderUpsertValidator)
    {
        $input = $request->all();
        $userStatusOrderUpsertValidator->validate($input);
        try {
            DB::beginTransaction();
            $userStatusOrder = $this->model->upsert($input);
            Log::create($this->model->getTable(), "#ID:" . $userStatusOrder->id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("user_status_orders.update-success")];
    }
}
