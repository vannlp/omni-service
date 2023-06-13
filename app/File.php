<?php


namespace App;


use App\Supports\TM_Error;
use GuzzleHttp\Client;

class File extends BaseModel
{
    /**
     * @var
     */
    protected $client;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'files';

    protected $fillable
        = [
            'id',
            'code',
            "file_name",
            "title",
            'extension',
            'size',
            'version',
            "url",
            "type",
            "store_id",
            "company_id",
            "is_active",
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
}
