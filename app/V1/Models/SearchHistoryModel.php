<?php


namespace App\V1\Models;


use App\SearchHistory;

class SearchHistoryModel extends AbstractModel
{
    public function __construct(SearchHistory $model = null)
    {
        parent::__construct($model);
    }
}