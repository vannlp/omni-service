<?php


namespace App\V1\Models;


use App\AppMenu;
use App\Supports\Message;
use App\TM;
use Illuminate\Support\Facades\DB;

class AppMenuModel extends AbstractModel
{
    public function __construct(AppMenu $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $appMenu = AppMenu::find($id);
            if (empty($appMenu)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $appMenu->code = array_get($input, 'code', $appMenu->code);
            $appMenu->name = array_get($input, 'name', $appMenu->name);
            $appMenu->data = array_get($input, 'data', $appMenu->data);
            $appMenu->store_id = array_get($input, 'store_id', $appMenu->store_id);
            $appMenu->updated_at = date("Y-m-d H:i:s", time());
            $appMenu->updated_by = TM::getCurrentUserId();
            $appMenu->save();
        } else {
            $param = [
                'name'     => $input['name'],
                'code'     => $input['code'],
                'store_id' => $input['store_id'],
                'data'     => array_get($input, 'data', null),
            ];
            $appMenu = $this->create($param);
        }
        return $appMenu;
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        if (!empty($input['name'])) {
            $query->where('name', 'like', "%{$input['name']}%");
        }
        if (!empty($input['code'])) {
            $query->where('code', 'like', "%{$input['code']}%");
        }
        if (!empty($input['store_id'])) {
            $query->where('store_id', $input['store_id']);
        }
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }
}