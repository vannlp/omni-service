<?php
/**
 * User: kpistech2
 * Date: 2020-06-08
 * Time: 22:47
 */

namespace App\V1\Models;


use App\Company;
use App\Feature;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;

class FeatureModel extends AbstractModel
{
    public function __construct(Feature $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            $id = !empty($input['id']) ? $input['id'] : 0;
            if ($id) {
                /** @var Feature $item */
                $item = Feature::find($id);
                if (empty($item)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $item->name = $input['name'];
                $item->code = $input['code'];
                $item->description = array_get($input, 'description', null);
                $item->updated_at = date("Y-m-d H:i:s", time());
                $item->updated_by = TM::getCurrentUserId();
                $item->save();
            } else {
                $param = [
                    'code'        => $input['code'],
                    'name'        => $input['name'],
                    'description' => array_get($input, 'description'),
                    'is_active'   => 1,
                ];
                /** @var Feature $item */
                $item = $this->create($param);
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }

        return $item;
    }

    /**
     * @param $id
     * @return Feature
     * @throws \Exception
     */
    public function activate($id)
    {
        /** @var Feature $feature */
        $feature = Feature::model()->where('id', $id)->first();
        if (empty($feature)) {
            throw new \Exception(Message::get("V002", Message::get("features") . " #$id"));
        }

        $company = Company::model()->where('id', TM::getCurrentCompanyId())->first();
        if (!empty($company)) {
            $old_features = !empty($company->features) ? explode(",", $company->features) : [];
            $old_features[] = $feature->code;
            $old_features = array_unique(array_values($old_features));
            $company->features = implode(",", $old_features);
            $company->save();
        }

        return $feature;
    }

    /**
     * @param $id
     * @return Feature
     * @throws \Exception
     */
    public function inActivate($id)
    {
        /** @var Feature $feature */
        $feature = Feature::model()->where('id', $id)->first();
        if (empty($feature)) {
            throw new \Exception(Message::get("V002", Message::get("features") . " #$id"));
        }

        $company = Company::model()->where('id', TM::getCurrentCompanyId())->first();
        if (!empty($company)) {
            $old_features = !empty($company->features) ? explode(",", $company->features) : [];
            $old_features = array_map(function ($f) use ($feature) {
                return strtoupper($f) != strtoupper($feature->code) ? strtoupper($f) : null;
            }, $old_features);
            $old_features = array_unique(array_filter($old_features));
            $company->features = implode(",", $old_features);
            $company->save();
        }

        return $feature;
    }
}