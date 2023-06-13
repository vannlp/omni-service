<?php


namespace App;


use Illuminate\Support\Str;

class Specification extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'specifications';
    /**
     * @var array
     */
    protected $fillable
        = [
            "code",
            "value",
            "company_id",
            "store_id",
            "deleted",
            "created_at",
            "created_by",
            "updated_at",
            "updated_by",
        ];

    public function createdBy()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function scopeSearch($query, $request)
    {
        if ($code = $request->get('code')) {
            $code = Str::upper($code);
            $query->whereRaw("UPPER(code) LIKE '%{$code}%'");
        }
        if ($value = $request->get('value')) {
            $value = Str::upper($value);
            $query->whereRaw("UPPER(value) LIKE '%{$value}%'");
        }

        return $query;
    }

}