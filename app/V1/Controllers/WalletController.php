<?php


namespace App\V1\Controllers;


use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\WalletModel;
use App\V1\Transformers\Wallet\WalletTransformer;
use App\V1\Validators\WalletCreateValidator;
use App\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends BaseController
{
    protected $model;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->model = new WalletModel();
    }


    /**
     * @param Request $request
     * @param WalletTransformer $walletTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, WalletTransformer $walletTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $wallet = $this->model->search($input, ['user', 'details'], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($wallet, $walletTransformer);
    }

    /**
     * @param WalletTransformer $walletTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function view(WalletTransformer $walletTransformer)
    {
        $wallet = Wallet::model()->with(['user', 'details'])->where('user_id', TM::getCurrentUserId())->first();
        if (empty($wallet)) {
            $wallet = $this->model->store();
        }
        Log::view($this->model->getTable());
        return $this->response->item($wallet, $walletTransformer);
    }

    public function detail($id, WalletTransformer $walletTransformer)
    {
        $wallet = Wallet::find($id);
        if (empty($wallet)) {
            return ["data" => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($wallet, $walletTransformer);
    }

    public function create(Request $request, WalletCreateValidator $walletCreateValidator)
    {
        $input = $request->all();
        $walletCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $wallet = $this->model->upsert($input);
            Log::create($this->model->getTable(), "#ID:" . $wallet->id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("wallets.create-success", $input['money'])];
    }
}