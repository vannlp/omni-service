<?php


namespace App\V1\Transformers\Wallet;


use App\Supports\TM_Error;
use App\Wallet;
use League\Fractal\TransformerAbstract;

class WalletTransformer extends TransformerAbstract
{
    public function transform(Wallet $wallet)
    {
        $details = $wallet->details;
        foreach ($details as $detail) {
            $walletDetails[] = [
                'id'          => $detail->id,
                'total'       => $detail->total,
                'description' => $detail->description,
                'status'      => $detail->status,
                'created_at'  => date('d-m-Y', strtotime($detail->created_at)),
                'updated_at'  => date('d-m-Y', strtotime($detail->updated_at))
            ];
        }
        try {
            return [
                'id'            => $wallet->id,
                'code'          => $wallet->code,
                'user_name'     => object_get($wallet, "user.profile.full_name", null),
                'user_code'     => object_get($wallet, "user.user_code", null),
                'balance'       => $wallet->balance,
                'total_pay'     => $wallet->total_pay,
                'total_deposit' => $wallet->total_deposit,
                'pin'           => object_get($wallet, "pin", null),
                'using_pin'     => $wallet->using_pin,
                'is_active'     => $wallet->is_active,
                'created_at'    => date('d-m-Y', strtotime($wallet->created_at)),
                'updated_at'    => date('d-m-Y', strtotime($wallet->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}