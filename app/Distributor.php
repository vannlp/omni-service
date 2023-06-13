<?php
/**
 * User: kpistech2
 * Date: 2020-11-21
 * Time: 13:42
 */

namespace App;
use Illuminate\Support\Str;

class Distributor extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'distributors';

    protected $fillable
        = [
            'code',
            'name',
            'city_code',
            'city_full_name',
            'district_code',
            'district_full_name',
            'ward_code',
            'ward_full_name',
            'value',
            'is_active',
            'company_id',
            'store_id',
            'deleted',
            'created_at',
            'created_by',
            'updated_by',
            'updated_at',
            'deleted',
        ];

        public function scopeSearch($query, $request)
        {
            if ($code = $request->get('code')) {
                $query->where('code', $code);
            }
            if ($store_id = $request->get('store_id')) {
                $query->where('store_id', $store_id);
            }
            $is_active = $request->get('is_active');

            if ($is_active) {
                $query->where('is_active', $is_active);
            }
            if ($is_active == '0') {
                $query->where('is_active', "0");
            }
            if ($name = $request->get('name')) {
                $name = Str::upper($name);
                $query->whereRaw("UPPER(name) LIKE '%{$name}%'");
            }
            if ($ward_full_name = $request->get('ward_full_name')) {
                $ward_full_name = Str::upper($ward_full_name);
                $query->whereRaw("UPPER(ward_full_name) LIKE '%{$ward_full_name}%'");
            }
            if ($district_full_name = $request->get('district_full_name')) {
                $district_full_name = Str::upper($district_full_name);
                $query->whereRaw("UPPER(district_full_name) LIKE '%{$district_full_name}%'");
            }
            if ($city_full_name = $request->get('city_full_name')) {
                $city_full_name = Str::upper($city_full_name);
                $query->whereRaw("UPPER(city_full_name) LIKE '%{$city_full_name}%'");
            }
//            $query->where('is_active',1);
            $query->groupBy(['code','city_code','district_code','ward_code']);
    
            return $query;
        }
    public function users()
    {
        return $this->hasOne(User::class, 'code', 'code')->whereNull('deleted_at');
    }

    public function created_by()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updated_by()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }

    public function getCity()
    {
        return $this->hasOne(__NAMESPACE__ . '\City', 'code', 'city_code');
    }

    public function getDistrict()
    {
        return $this->hasOne(__NAMESPACE__ . '\District', 'code', 'district_code');
    }

    public function getWard()
    {
        return $this->hasOne(__NAMESPACE__ . '\Ward', 'code', 'ward_code');
    }

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'store_id');
    }

    public function city()
    {
        return $this->hasOne(City::class, 'code', 'city_code');
    }
    public function countOrder()
    {
        return $this->hasMany(Order::class, 'distributor_code', 'code')
            ->whereBetween('created_at',[date('Y-m-d 00:00:00'),date('Y-m-d 23:59:59')]);
    }
}
