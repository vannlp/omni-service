<?php
/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App;

class UserLog extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_logs';

    /**
     * @var array
     */
    protected $fillable = [
        'action',
        'target',
        'ip',
        'browser',
        'user_id',
        'description',
        'old_data',
        'new_data',
        'company_id',
    ];


    public function user()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'user_id');
    }
}
