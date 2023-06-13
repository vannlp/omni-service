<?php
/**
 * User: kpistech2
 * Date: 2019-01-22
 * Time: 00:02
 */

namespace App\V1\Models;


use App\Image;
use App\TM;
use App\Supports\Message;
use App\Supports\TM_Error;

class ImageModel extends AbstractModel
{
    /**
     * ImageModel constructor.
     * @param Image|null $model
     */
    public function __construct(Image $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            $id = !empty($input['id']) ? $input['id'] : 0;

            if ($id) {
                $image = Image::find($id);
                if(empty($image)){
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $image->url = array_get($input, 'url', $image->url);
                $image->code = array_get($input, 'code', $image->code);
                $image->description = array_get($input, 'description', NULL);
                $image->updated_at = date("Y-m-d H:i:s", time());
                $image->updated_by = TM::getCurrentUserId();
                $image->save();
            } else {
                $param = [
                    'code' => $input['code'],
                    'url' => $input['url'],
                    'description' => array_get($input, 'description'),
                    'is_active' => 1,
                ];
                $image = $this->create($param);
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }

        return $image;
    }
}
