<?php
/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App\V1\Transformers\Product;

use App\Category;
use App\File;
use App\Folder;
use App\Image;
use App\Product;
use App\ProductPromotion;
use App\Store;
use App\Supports\TM_Error;
use App\TM;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;
use function GuzzleHttp\Psr7\str;

/**
 * Class ProductTransformer
 *
 * @package App\V1\CMS\Transformers
 */
class ProductListTransformer extends TransformerAbstract
{
    public function transform(Product $product)
    {
        try {
            $output      = [
                'id'                  => $product->id,
                'code'                => $product->code,
                'name'                => $product->name,
                'unit_id'             => $product->unit_id ?? null,
                'created_at'          => date('d-m-Y', strtotime($product->created_at)),
                'updated_at'          => !empty($product->updated_at) ? date('d-m-Y',
                    strtotime($product->updated_at)) : null,
            ];
            return $output;
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
