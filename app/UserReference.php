<?php


namespace App;


use Illuminate\Support\Facades\DB;

class UserReference extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_references';

    protected $fillable = [
        'id',
        'user_id',
        'level',
        'parent_id',
        'store_id',
        'is_active',
        'deleted',
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function userWithTotalSales()
    {
        return $this->hasOne(User::class, 'id', 'user_id')
            ->with([
                'orders' => function ($query) {
                    $query->addSelect(['id', 'customer_id', 'original_price'])
                        ->where('status', '!=', ORDER_STATUS_CANCELED)
                        ->where(function ($query) {
                            $request = app('request');
                            if (!empty($request->input('sales_by_date'))) {
                                $query->whereBetween('created_at', [date('Y-m-d', strtotime($request->input('from'))), date('Y-m-d', strtotime($request->input('to')))]);
                            }
                        });
                }
            ])
            ->addSelect(['id', 'name', 'email', 'phone', 'created_at']);
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->addSelect(['id', 'user_id', 'level', 'parent_id'])
            ->with('user:id,name,email,phone,store_id,code');
    }

    public function grandChildren()
    {
        return $this->children()->with('grandChildren');
    }

    public function childrenWithSales()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->with('userWithTotalSales')
            ->addSelect(['id', 'user_id', 'level', 'parent_id']);
    }

    public function grandChildrenWithSales()
    {
        return $this->childrenWithSales()->with('grandChildrenWithSales');
    }

    private function countChildren($grandChildren, &$count)
    {
        $count += $grandChildren->count();

        foreach ($grandChildren as $item) {
            if (!$item->grandChildren->isEmpty()) {
                $this->countChildren($item->grandChildren, $count);
            }
        }

        return $count;
    }

    public function scopeCountGrandChildren()
    {
        try {
            $count = 0;
            if (!$this->grandChildren->isEmpty()) {
                $count = $this->countChildren($this->grandChildren, $count);
            }

            return $count;
        } catch (\Exception $exception) {
            
        }
    }

}