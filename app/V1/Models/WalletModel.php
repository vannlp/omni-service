<?php


namespace App\V1\Models;

use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\Wallet;
use App\WalletDetail;
use Illuminate\Support\Facades\DB;

class WalletModel extends AbstractModel
{
    public function __construct(Wallet $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $wallet = Wallet::model()->where('user_id', TM::getCurrentUserId())->first();
        $money = (float)$input['money'];
        if (!empty($input['status']) && $input['status'] == WALLET_STATUS_WITHDRAW) {
            $money *= -1;
        }

        $pin = null;
        if (!empty($input['pin'])) {
            $pin = encrypt($input['pin']);
        }

        DB::beginTransaction();
        if (!empty($wallet)) {
            if ($wallet->is_active == 0) {
                throw new \Exception(Message::get("V026"));
            }
            $wallet->balance += $money;
            $wallet->total_pay += ($money > 0) ? $money : 0;
            $wallet->total_deposit += ($money < 0) ? abs($money) : 0;
            $wallet->pin = $pin;
            $wallet->using_pin = array_get($input, 'using_pin', $wallet->using_pin);
            $wallet->is_active = array_get($input, 'is_active', $wallet->is_active);
            $wallet->updated_at = date("Y-m-d H:i:s", time());
            $wallet->updated_by = TM::getCurrentUserId();
            $wallet->save();
        } else {
            $param = [
                'user_id'       => TM::getCurrentUserId(),
                'code'          => $this->walletCode(),
                'balance'       => abs($money),
                'total_pay'     => abs($money),
                'total_deposit' => 0,
                'using_pin'     => array_get($input, 'using_pin', 0),
                'pin'           => $pin,
                'is_active'     => 1,

            ];
            $wallet = $this->create($param);
        }
        // Create Wallet Detail
        $walletDetail = new WalletDetail();
        $walletDetail->create([
            'wallet_id'   => $wallet->id,
            'total'       => abs($money),
            'description' => array_get($input, 'description', null),
            'status'      => $input['status'],
            'is_active'   => 1,
        ]);


        //Create Transaction History
        $paymentHistory = new PaymentHistoryModel();
        $paramCreate = [
            'code'      => $wallet->code,
            'date'      => date('Y-m-d H:i:s', time()),
            'type'      => $input['status'],
            'content'   => WALLET_STATUS_NAME[$input['status']] . " " . array_get($input, 'description'),
            'total_pay' => $wallet->total_pay,
            'balance'   => $wallet->balance,
            'user_id'   => TM::getCurrentUserId(),
            'is_active' => 1,
        ];
        $paymentHistory->create($paramCreate);
        DB::commit();
        return $wallet;
    }

    private function walletCode()
    {
        $walletCode = mt_rand(100000000000, 999999999999);
        $wallet = Wallet::model()->where('code', $walletCode)->first();
        if (!empty($wallet)) {
            return $this->walletCode();
        }
        return $walletCode;
    }

    public function store()
    {

        $param = [
            'user_id'       => TM::getCurrentUserId(),
            'code'          => $this->walletCode(),
            'balance'       => 0,
            'total_pay'     => 0,
            'total_deposit' => 0,
            'using_pin'     => 0,
            'pin'           => null,
            'is_active'     => 1,

        ];
        $wallet = $this->create($param);
        return $wallet;
    }
}