<?php


namespace App\V1\Models;

use App\BannerDetail;
use App\ProductHub;
use App\Supports\Message;
use App\TM;
use Illuminate\Support\Facades\DB;

class ProductHubModel extends AbstractModel
{
    /**
     * ProductInfoModel constructor.
     * @param ProductHub|null $model
     */
    public function __construct(ProductHub $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {

        $productHUB       = ProductHub::model()->where('user_id', $input['user_id'])->get()->toArray();
        $productHUB       = array_pluck($productHUB, 'id', 'id');
        $productHubDelete = $productHUB;
        foreach($input['products'] as $product) {
            try {
                if (!empty($product['id'])) {
                    $item = ProductHub::find($product['id']);
                    if (empty($item)) {
                        throw new \Exception(Message::get("V003", "ID: #" . $product['id'] . ""));
                    }
                    $item->product_id   = $product['product_id'] ?? $item->product_id;
                    $item->product_code = $product['product_code'] ?? $item->product_code;
                    $item->product_name = $product['product_name'] ?? $item->product_name;
                    $item->unit_id      = $product['unit_id'] ?? $item->unit_id;
                    $item->unit_name    = $product['unit_name'] ?? $item->unit_name;
                    $item->user_id      = array_get($input, 'user_id', $item->user_id);
                    $item->limit_date   = $product['limit_date'] ?? $item->limit_date;
                    $item->updated_at   = date('Y-m-d H:i:s', time());
                    $item->updated_by   = TM::getCurrentUserId();
                    $item->save();
                    unset($productHubDelete[$product['id']]);
                } else {
                    $this->refreshModel();
                    $this->create([
                        'product_id'   => $product['product_id'],
                        'product_code' => $product['product_code'],
                        'product_name' => $product['product_name'],
                        'unit_id'      => $product['unit_id'],
                        'unit_name'    => $product['unit_name'],
                        'user_id'      => $input['user_id'],
                        'limit_date'   => $product['limit_date'],
                        'store_id'     => TM::getCurrentStoreId(),
                        'company_id'   => TM::getCurrentCompanyId(),
                        "created_at"   => date('Y-m-d H:i:s', time()),
                        "created_by"   => TM::getCurrentUserId(),
                    ]);
                }
                DB::commit();
            }
            catch (\Exception $ex) {
                DB::rollBack();
            }
        }
        if (!empty($productHubDelete)) {
            ProductHub::model()->whereIn('id', array_values($productHubDelete))->delete();
        }
    }
}

