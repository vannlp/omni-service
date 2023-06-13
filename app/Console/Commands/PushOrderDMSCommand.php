<?php
/**
 * Created by PhpStorm.
 * User: kpistech2
 * Date: 2019-10-16
 * Time: 22:19
 */

namespace App\Console\Commands;


use App\Notify;
use App\Order;
use App\ProductHub;
use App\Supports\Log;
use App\TM;
use App\User;
use App\V1\Library\OrderSyncDMS;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PushOrderDMSCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push_order_dms:cron';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push order dms';

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

            $rePush = DB::table('log_send_dms')
                ->where('is_active', 0)
                ->select(['id', 'code', 'param'])->get();

            if ($rePush) {
                foreach ($rePush as $order) {
                    try {
                        $count = DB::table('log_send_dms')
                            ->where('code', $order->code)->count();
                        if ($count >= 5) {
                            continue;
                        }
                        DB::table('log_send_dms')->where('code', $order->code)->update(['is_active' => 1]);
                        $syncDMS = OrderSyncDMS::dataOrder(array($order->code), "C");
                        if (!empty($syncDMS)) {
                            $pushOrderDms = OrderSyncDMS::callApiDms($syncDMS, "CREATE-ORDER");
                            if (!empty($pushOrderDms['errors'])) {
                                foreach ($pushOrderDms['errors'] as $item) {
                                    Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $syncDMS ?? [], "CREATE-ORDER", 0, $item);
                                }
                            } else {
                                if (!empty($pushOrderDms)){
                                    Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                                }
                                if (empty($pushOrderDms)){
                                    Log::logSyncDMS($order->code, "Connection Error", $syncDMS ?? [], "CREATE-ORDER", 0, $pushOrderDms);
                                }
//                                Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                            }
                        }
                        Order::where('code', $order->code)->update(['log_order_dms' => json_encode($syncDMS)]);
                    } catch (\Exception $exception) {
                        Log::logSyncDMS($order->code, $exception->getMessage(), $syncDMS ?? [], "CREATE-ORDER", 0, null);
                    }
                }
            }

//        }
    }
}