<?php


namespace App\V1\Models;


use App\File;

class FileModel extends AbstractModel
{
    /**
     * FileModel constructor.
     * @param File|null $model
     */
    public function __construct(File $model = null)
    {
        parent::__construct($model);
    }
}