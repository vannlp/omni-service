<?php
/**
 * User: dai.ho
 * Date: 10/30/2019
 * Time: 12:11 PM
 */

namespace App\V1\Models;


use App\WalletHistory;

class WalletHistoryModel extends AbstractModel
{
    public function __construct(WalletHistory $model = null)
    {
        parent::__construct($model);
    }
}