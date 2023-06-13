<?php
/**
 * Created by PhpStorm.
 * User: kpistech2
 * Date: 2019-10-16
 * Time: 22:19
 */

namespace App\Console\Commands;


use App\Notify;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TMCronCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tm:cron';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'TM command';

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
        $time = time();
        $now  = date("Y-m-d H:i", $time);
        try {
            $notifies = DB::table('notifies')
                ->whereBetween('delivery_date', [$now . ":00", $now . ":59"])->get();

            foreach ($notifies as $notify) {
                DB::table('test_cron')->insert([
                    'name'        => date("Y-m-d H:i:s", time()),
                    'description' => $notify->title,
                ]);

                if ($notify->frequency == "ONCE" && $notify->sent == '0') {
                    // Send Notify Once
                    $this->sendNotification($notify);
                    DB::table('notifies')->where('id', $notify->id)->update(['sent' => '1']);
                } elseif ($notify->frequency == "DAILY") {
                    // Send notify
                    $this->sendNotification($notify);
                }
            }
        } catch (\Exception $ex) {
            DB::table('test_cron')->insert([
                'name'        => date("Y-m-d H:i:s", time()),
                'description' => 'Exception: ' . $ex->getMessage(),
            ]);
        }
        DB::table('test_cron')->insert(['name' => date("Y-m-d H:i:s", time()), 'description' => 'Start schedule']);
        $this->info('Demo:Cron Command Run successfully!');
    }

    private function sendNotification($notify)
    {
        $users = User::model()->select(['users.id', 'us.device_token'])
            ->join('user_sessions as us', 'us.user_id', '=', 'users.id')
            ->whereHas('userCompanies', function ($q) use ($notify) {
                $q->where('company_id', $notify->company_id);
            })
            ->where('us.device_token', '!=', '')
            ->whereNotNull('us.device_token');
        if ($notify->notify_for && $notify->notify_for != "ALL") {
            $users = $users->where('users.type', $notify->notify_for);
        }

        $users = $users->groupBy('device_token')->get()->pluck('device_token')->toArray();
        if ($users) {
            // Send Notification
            $body    = [
                'target_id' => $notify->target_id,
                'type'      => $notify->type,
                'title'     => $notify->title,
                'body'      => $notify->body,
            ];
            $fields  = [
                'data'             => [
                    'type'         => 'NOTIFICATION',
                    'body'         => json_encode($body),
                    "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                ],
                'notification'     => ['title' => $notify->title, 'sound' => 'shame', 'body' => $notify->body],
                'registration_ids' => $users,
            ];
            $headers = ['Content-Type:application/json', 'Authorization:key=' . env("FIREBASE_SERVER_KEY", '')];
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, env('FIREBASE_URL', ''));
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

                $result = curl_exec($ch);
                if ($result === false) {
                    DB::table('test_cron')->insert([
                        'name'        => date("Y-m-d H:i:s", time()),
                        'description' => 'FCM Send Error: ' . curl_error($ch),
                    ]);
                    //throw new \Exception('FCM Send Error: ' . curl_error($ch));
                }
                curl_close($ch);
            } catch (\Exception $ex) {
                DB::table('test_cron')->insert([
                    'name'        => date("Y-m-d H:i:s", time()),
                    'description' => $ex->getMessage(),
                ]);
            }
            $param = [
                'title'       => $notify->title,
                'body'        => $notify->body,
                'message'     => $notify->title,
                'notify_type' => "SYSTEM",
                'type'        => $notify->type,
                'extra_data'  => json_encode((array)$notify), // anyType
                'receiver'    => $notify->notify_for,
                'action'      => 1,
                'item_id'     => $notify->id,
                'company_id'  => $notify->company_id,
                'created_at'  => date('Y-m-d H:i:s', time()),
            ];

            DB::table('notification_histories')->insert($param);

            DB::table('test_cron')->insert([
                'name'        => date("Y-m-d H:i:s", time()),
                'description' => "Notification Sent!",
            ]);
        }
    }
}