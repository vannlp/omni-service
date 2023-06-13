<?php


namespace App\V1\Models;


use App\PostSearchHistory;
use Illuminate\Support\Facades\DB;

class PostSearchHistoryModel extends AbstractModel
{
    public function __construct(PostSearchHistory $model = null)
    {
        parent::__construct($model);
    }
}