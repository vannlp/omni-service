<?php
/**
 * User: Sy Dai
 * Date: 04-Apr-17
 * Time: 14:59
 */

namespace App\Supports;

use App\TM;
use Illuminate\Support\Facades\DB;

class Log
{
    protected static $DELETED = "DELETED";
    protected static $CREATED = "CREATED";
    protected static $UPDATED = "UPDATED";
    protected static $CHANGED = "CHANGED";
    protected static $VIEW    = "VIEW";
    protected static $UPLOAD  = "UPLOAD";
    protected static $MOVE    = "MOVE";

    /**
     * @param $target
     * @param null $description
     */
    static function view($target, $description = null)
    {
        self::process(self::$VIEW, $target, $description);
    }

    /**
     * @param $target
     * @param $created_data
     */
    static function create($target, $description = null, $old_data = null, $created_data = null)
    {
        self::process(self::$CREATED, $target, $description, $old_data, $created_data);
    }

    /**
     * @param $target
     * @param $old_data
     * @param $new_data
     */
    static function update($target, $description = null, $old_data = null, $new_data = null)
    {
        self::process(self::$UPDATED, $target, $description, $old_data, $new_data);
    }

    /**
     * @param $target
     * @param $deleted_data
     */
    static function delete($target, $description = null, $deleted_data = null)
    {
        self::process(self::$DELETED, $target, $description, $deleted_data);
    }

    /**
     * @param $target
     * @param $old_data
     * @param $new_data
     */
    static function change($target, $old_data, $new_data)
    {
        self::process(self::$CHANGED, $target, $old_data, $new_data);
    }

    /**
     * @param $target
     * @param null $description
     */
    static function move($target, $description = null)
    {
        self::process(self::$MOVE, $target, $description);
    }

    /**
     * @param $target
     * @param null $description
     */
    static function upload($target, $description = null)
    {
        self::process(self::$UPLOAD, $target, $description);
    }

    static function logSyncOrderStatusDMS($code, $exception, $param, $type, $is_active, $status_code = 200)
    {
        if (is_array($param)) {
            $param = json_encode($param);
        }
        $now = date('Y-m-d H:i:s', time());
        DB::table('log_order_status_dms')->insert([
            'code'        => $code,
            'url'         => 'localhost',
            'type'        => $type,
            'error_log'   => $exception ?? null,
            'param'       => $param,
            'is_active'   => $is_active ?? 1,
            'status_code' => $status_code,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

    }

    static function logSyncDMS($code = null, $exception = null, $param = null, $type = null, $is_active = 1, $response=null)
    {
        try {
            if (is_array($param)) {
                $param = json_encode($param);
            }
            $now = date('Y-m-d H:i:s', time());
            DB::table('log_send_dms')->insert([
                'code'       => $code,
                'type'       => $type,
                'error_log'  => $exception,
                'param'      => $param,
                'response'   => json_encode($response) ?? null,
                'is_active'  => $is_active,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Exception $e) {
        }
    }

    /**
     * @param      $action
     * @param      $old_data
     * @param null $new_data
     */
    private static function process($action, $target, $description = null, $old_data = null, $new_data = null)
    {
        return true;
        if (is_array($old_data)) {
            $old_data = json_encode($old_data);
        }

        if (is_array($new_data)) {
            $new_data = json_encode($new_data);
        }

        $user_id = TM::getCurrentUserId();
        $now     = date('Y-m-d H:i:s', time());
//        $browser = get_browser(null, true);
//        $parent = array_get($browser, 'parent', '');
//        $platform = array_get($browser, 'platform', '');
//        $browser = array_get($browser, 'browser', '');

        DB::table('user_logs')->insert([
            'action'      => $action,
            'target'      => "Table: $target",
            'ip'          => $_SERVER['REMOTE_ADDR'],
            'description' => $description,
            // 'browser'     => "Parent: $parent - Platform: $platform - Browser: $browser",
            'user_id'     => $user_id,
            'old_data'    => $old_data,
            'new_data'    => $new_data,
            'created_at'  => $now,
            'created_by'  => $user_id,
            'updated_at'  => $now,
            'updated_by'  => $user_id,
        ]);
    }

    public static function message($user_name, $method, $target, $description)
    {
        $target = strtolower(trim(ltrim($target, 'Table:')));
        //$target = !empty(self::$target[$target]) ? self::$target[$target] : $target;
        return str_replace("  ", " ", trim($user_name . " " . Message::get("L-" . $method) . " " . Message::get($target)));
    }
}