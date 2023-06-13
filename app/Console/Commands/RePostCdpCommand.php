<?php

/**
 * Created by PhpStorm.
 * User: kpistech2
 * Date: 2019-10-16
 * Time: 22:19
 */

namespace App\Console\Commands;

use App\CdpLogs;
use App\Order;
use App\OrderExportCronLogs;
use App\OrderExportExcelRP;
use App\OrderExportCronLogsRP;
use App\OrderExportExcel;
use App\OrderRP;
use App\Product;
use App\ProductRP;
use App\PromotionProgramRP;
use App\PromotionTotalRP;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TempOrderExportExcel;
use App\TempOrderExportExcelRP;
use App\TM;
use App\User;
use App\V1\Library\CDP;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RePostCdpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repost_cdp:cron';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repost Cdp command';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {

        try {
            $find = CdpLogs::model()->where('status', 'FAILED')->get();

            if (empty($find)) {
                return $this->responseError(Message::get("V003", Message::get("id")));
            }

            if (!empty($find)) {
                foreach ($find as $value) {

                    if ($value->sync_type = 'Đồng bộ đơn hàng') {
                        $order  = Order::where('code', $value->code)->first();
                        if (!empty($order)) {
                            $reponse = CDP::pushOrderCdp($order, 'CdpController@cronRepostSyncCDP', $value);
                        }
                    }

                    if ($value->sync_type = 'Đồng bộ khách hàng') {
                        $customer = User::where('code', $value->code)->first();

                        if (!empty($customer)) {
                            $reponse = CDP::pushCustomerCdp($customer, 'CdpController@cronRepostSyncCDP', $value);
                        }
                    }

                    if ($value->sync_type = 'Đồng bộ sản phẩm') {
                        $product   = Product::where('code', $value->code)->first();
                        if (!empty($product)) {
                            $reponse = CDP::pushProductCdp($product, 'CdpController@cronRepostSyncCDP', $value);
                        }
                    }
                }
            }
            if (!empty($response)) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            TM::sendMessage('CDP Cron Exception: ', $e);
        }
    }
}
