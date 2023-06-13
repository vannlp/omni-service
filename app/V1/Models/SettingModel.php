<?php
/**
 * User: kpistech2
 * Date: 2020-07-04
 * Time: 00:52
 */

namespace App\V1\Models;


use App\Setting;
use App\Supports\Message;
use App\TM;
use Illuminate\Support\Str;

class SettingModel extends AbstractModel
{
    public function __construct(Setting $model = null)
    {
        parent::__construct($model);
    }

    /**
     * @param $input
     * @param null $code
     * @return Setting|mixed
     * @throws \Exception
     */
    public function upsert($input, $code = null)
    {
        $newData = [];
        foreach ($input['data'] ?? [] as $key => $item) {
            $newData[] = $item;
        }
        $newDataClient = [];
        foreach ($input['data_client'] ?? [] as $key => $item) {
            $newDataClient[] = $item;
        }
        $newDataFirst = [];
        foreach ($input['data_first'] ?? [] as $key => $item) {
            $newDataFirst[] = $item;
        }
        $categoryData = [];
        foreach ($input['categories'] ?? [] as $key => $item) {
            $categoryData[] = $item;
        }
        if ($code) {
            $settings = Setting::model()->where(['code' => $code, 'company_id' => TM::getCurrentCompanyId()])
                ->get()->toArray();

            // Check duplicate Code
            if (empty($settings)) {
                throw new \Exception(Message::get("V003", Message::get("settings") . " #$code"));
            }
            if (count($settings) > 1 || $settings[0]['code'] != $code) {
                throw new \Exception(Message::get("V003", Message::get("code")));
            }

            /** @var Setting $item */
            $item = Setting::model()->where('code', $code)->where('company_id', TM::getCurrentCompanyId())->first();
            if (empty($item)) {
                throw new \Exception(Message::get("V007", "#$code"));
            }
            $item->name        = $input['name'];
            $item->slug        = Str::slug($input['name']);
            $item->value       = $input['value'] ?? null;
            $item->description = $input['description'] ?? null;
            $item->publish     = !empty($input['publish']) ? 1 : 0;
            $item->data        = json_encode($newData);
            $item->data_client = json_encode($newData);
            $item->data_first  = json_encode($newDataFirst);
            $item->categories  = json_encode($categoryData);
            $item->type        = $input['type'] ?? null;
            $item->data_cke    = $input['data_cke'] ?? null;
            $item->store_id    = TM::getCurrentStoreId();
            $item->company_id  = TM::getCurrentCompanyId();
            $item->updated_at  = date("Y-m-d H:i:s", time());
            $item->updated_by  = TM::getCurrentUserId();
            $item->save();
        } else {
            $settings = Setting::model()->where(['code' => $input['code'], 'company_id' => TM::getCurrentCompanyId()])
                ->get()->toArray();
            if (!empty($settings)) {
                throw new \Exception(Message::get("V007", Message::get("code")));
            }
            $param = [
                'code'        => $input['code'],
                'name'        => $input['name'],
                'slug'        => Str::slug($input['name']),
                'value'       => $input['value'] ?? null,
                'type'        => $input['type'] ?? null,
                'data_cke'    => $input['data_cke'] ?? null,
                'description' => $input['description'] ?? null,
                'publish'     => !empty($input['publish']) ? 1 : 0,
                'data'        => json_encode($newData),
                'data_client' => json_encode($newData),
                'data_first ' => json_encode($newDataFirst),
                'store_id'    => TM::getCurrentStoreId(),
                'company_id'  => TM::getCurrentCompanyId(),
                'is_active'   => 1,
            ];

            $item = $this->create($param);
        }

        return $item;
    }

    /**
     * @param $key
     * @param string $sort_key
     * @param string $sort_type
     * @return array|mixed
     */
    public function getDataForKey($key, $sort_key = "", $sort_type = 'ASC')
    {
        $data = Setting::model()->where([
            'code'       => $key,
            'company_id' => TM::getCurrentCompanyId(),
            'publish'    => '1'
        ])->first();

        if (empty($data->data)) {
            return [];
        }

        $data = json_decode($data->data, true);

        if ($sort_key) {
            usort($data, function ($a, $b) use ($sort_key, $sort_type) {
                if (strtoupper($sort_type) == 'DESC') {
                    return $a[$sort_key] < $b[$sort_key];
                }
                return $a[$sort_key] <=> $b[$sort_key];
            });
        }

        return $data;
    }

    /**
     * @param $key
     * @param string $sort_key
     * @param string $sort_type
     * @return array|mixed
     */
    public function getCategory($key, $sort_key = "", $sort_type = 'ASC')
    {
        $setting = Setting::model()->where([
            'code'       => $key,
            'company_id' => TM::getCurrentCompanyId(),
            'publish'    => '1'
        ])->first();

        if (empty($setting->categories)) {
            return [];
        }

        $data = json_decode($setting->categories, true);

        return $data;
    }

    /**
     * @param $key
     * @param string $sort_key
     * @param string $sort_type
     * @return array|mixed
     */
    public function getForKey($key, $sort_key = "", $sort_type = 'ASC')
    {
        $setting = Setting::model()->where([
            'code'       => $key,
            'company_id' => TM::getCurrentCompanyId(),
            'publish'    => '1'
        ])->first();
        if (empty($setting)) {
            return [];
        }

        $data = json_decode($setting->data, true);
        if ($sort_key) {
            usort($data, function ($a, $b) use ($sort_key, $sort_type) {
                if (strtoupper($sort_type) == 'DESC') {
                    return $a[$sort_key] < $b[$sort_key];
                }
                return $a[$sort_key] <=> $b[$sort_key];
            });
        }

        $setting         = $setting->toArray();
        $setting['data'] = $data;

        return $setting;
    }
}