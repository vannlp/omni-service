<?php
namespace App\V1\Models;


use App\Channel;
use App\Area;
use App\Supports\Message;
use App\TM;
use Illuminate\Support\Arr;

class ChannelModel extends AbstractModel
{
    public function __construct(Channel $model = null)
    {
        parent::__construct($model);
    }
}