<?php
/**
 * User: kpistech2
 * Date: 2020-05-09
 * Time: 22:10
 */

namespace App\V1\Transformers\Company;


use App\Company;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class CompanyTransformer extends TransformerAbstract
{
    public function transform(Company $item)
    {
        $fileCode = Arr::get($item, 'file.code', null);
        try {
            return [
                'id'                 => $item->id,
                'code'               => $item->code,
                'name'               => $item->name,
                'email'              => $item->email,
                'address'            => $item->address,
                'tax_code'           => $item->tax_code,
                'phone'              => $item->phone,
                'firebase_token'     => object_get($item, 'firebase_token', null),
                'email_notification' => object_get($item, 'email_notification', null),
                'description'        => $item->description,
                'avatar_id'          => $item->avatar_id,
                'avatar'             => !empty($fileCode) ? env('GET_FILE_URL') . $fileCode : null,
                'company_key'        => $item->company_key,
                'is_active'          => $item->is_active,
                'created_at'         => date('d-m-Y', strtotime($item->created_at)),
                'updated_at'         => date('d-m-Y', strtotime($item->updated_at)),
                'created_by'         => object_get($item, 'createdBy.full_name', null),
                'updated_by'         => object_get($item, 'updatedBy.full_name', null),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
