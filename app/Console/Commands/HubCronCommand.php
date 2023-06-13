<?php
/**
 * Created by PhpStorm.
 * User: kpistech2
 * Date: 2019-10-16
 * Time: 22:19
 */

namespace App\Console\Commands;


use App\Notify;
use App\ProductHub;
use App\TM;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HubCronCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hub:cron';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hub command';

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
        $users = User::model()->where('qty_max_day', '>', 0)->get();
        foreach($users as $user){
            $user_up = User::find($user['id']);
            $user_up->qty_remaining_single = $user['qty_max_day'];
            $user_up->save();
        }

    }


}