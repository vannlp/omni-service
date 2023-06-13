<?php


namespace App\V1\Models;


use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\ZoneHub;

class ZoneHubModel extends AbstractModel
{
    public function __construct(ZoneHub $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            $a = TM::getCurrentCompanyId();
            $id = !empty($input['id']) ? $input['id'] : 0;
            if ($id) {
                $zoneHub = ZoneHub::find($id);
                if (empty($zoneHub)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $zoneHub->name = array_get($input, 'name', $zoneHub->name);
                $zoneHub->latlong = array_get($input, 'latlong', $zoneHub->latlong);
                $zoneHub->company_id = TM::getCurrentCompanyId();
                $zoneHub->description = array_get($input, 'description', $zoneHub->description);
                $zoneHub->save();
            } else {
                $param = [
                    'name'            => $input['name'],
                    'latlong'         => $input['latlong'],
                    'company_id'      => TM::getCurrentCompanyId(),
                    'description'     => $input['description'],
                ];
                $zoneHub = $this->create($param);
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }
        return $zoneHub;
    }
}