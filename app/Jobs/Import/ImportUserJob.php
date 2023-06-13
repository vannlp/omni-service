<?php
/**
 * User: kpistech2
 * Date: 2020-11-25
 * Time: 22:32
 */

namespace App\Jobs\Import;

use App\Company;
use App\Jobs\Job;
use App\Role;
use App\User;
use App\UserLog;
use Illuminate\Support\Facades\DB;

class ImportUserJob extends Job
{
    protected $data;
    protected $to;

    public function __construct($data, $user_id, $company_id, $store_id)
    {
        $this->data = $data;
        $this->user = $user_id;
        $this->company = $company_id;
        $this->store = $store_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->data;
        try {
            $this->startImport($data);
            UserLog::insert([
                'action'     => 'JOB-IMPORT',
                'target'     => 'Import User: ' . (count($this->data)) . ' lines',
                'browser'    => 'Cron Job',
                'user_id'    => $this->user,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $this->user,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $this->user
            ]);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            die;
        }
    }

    private function startImport($data)
    {
        if (empty($data)) {
            return 0;
        }

        DB::beginTransaction();
        $queryHeader = "INSERT INTO `users` (" .
            "`code`, " .
            "`password`, " .
            "`phone`, " .
            "`name`, " .
            "`company_id`, " .
            "`store_id`, " .
            "`group_id`, " .
            "`group_code`, " .
            "`group_name`, " .
            "`distributor_id`, " .
            "`distributor_code`, " .
            "`distributor_name`, " .
            "`created_at`, " .
            "`created_by`, " .
            "`updated_at`, " .
            "`updated_by`) VALUES ";
        $now = date('Y-m-d H:i:s', time());
        $userImported = [];
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {
            $phone = $datum['phone'];
            if (!empty($userImported[$phone])) {
                continue;
            }
            $distributor_id = !empty($datum['distributor_id']) ? "'{$datum['distributor_id']}'" : "null";
            $distributor_code = !empty($datum['distributor_code']) ? "'{$datum['distributor_code']}'" : "null";
            $distributor_name = !empty($datum['distributor_name']) ? "'{$datum['distributor_name']}'" : "null";
            $userImported[$phone] = $phone;
            $queryContent .=
                "(" .
                $pdo->quote($datum['code']) . "," .
                $pdo->quote('FROM-IMPORT') . "," .
                $pdo->quote($datum['phone']) . "," .
                $pdo->quote($datum['name']) . "," .
                $pdo->quote($this->company) . "," .
                $pdo->quote($this->store) . "," .
                (int)$datum['group_id'] . "," .
                $pdo->quote($datum['group_code']) . "," .
                $pdo->quote($datum['group_name']) . "," .
                $distributor_id . "," .
                $distributor_code . "," .
                $distributor_name . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($this->user) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($this->user) . "), ";
        }
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`code`= values(`code`), " .
                "`name`= values(`name`), " .
                "`group_id` = values(`group_id`), " .
                "`group_code` = values(`group_code`), " .
                "`group_name` = values(`group_name`), " .
                "`distributor_id` = values(`distributor_id`), " .
                "`distributor_code` = values(`distributor_code`), " .
                "`distributor_name` = values(`distributor_name`), " .
                "updated_at='$now', updated_by={$this->user}";
            DB::statement($queryUpdate);
        }
        // Profile
        $allUserId = User::model()->select(['id', 'phone'])->whereIn('phone',
            array_values($userImported))->get()->pluck('id', 'phone')->toArray();

        $queryHeader = "INSERT INTO `profiles` (" .
            "`user_id`, " .
            "`first_name`, " .
            "`last_name`, " .
            "`short_name`, " .
            "`full_name`, " .
            "`address`, " .
            "`phone`, " .
            "`created_at`, " .
            "`created_by`, " .
            "`updated_at`, " .
            "`updated_by`) VALUES ";
        $now = date('Y-m-d H:i:s', time());
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {
            $phone = $datum['phone'];
            if (empty($allUserId[$phone])) {
                continue;
            }
            $userId = $allUserId[$phone];
            $name = explode(" ", trim($datum['name']));
            $first_name = $name[0];
            unset($name[0]);
            $last = !empty($name) ? implode(" ", $name) : null;
            $full_name = $datum['name'];
            $city_code = !empty($datum['city_code']) ? "'{$datum['city_code']}'" : "null";
            $district_code = !empty($datum['district_code']) ? "'{$datum['district_code']}'" : "null";
            $ward_code = !empty($datum['ward_code']) ? "'{$datum['ward_code']}'" : "null";
            $queryContent .=
                "(" .
                $pdo->quote($userId) . "," .
                $pdo->quote($first_name) . "," .
                $pdo->quote($last) . "," .
                $pdo->quote($full_name) . "," .
                $pdo->quote($full_name) . "," .
                $pdo->quote($datum['address']) . "," .
                $pdo->quote($phone) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($this->user) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($this->user) . "), ";
        }
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`user_id`= values(`user_id`), " .
                "`email`= values(`email`), " .
                "`address` = values(`address`), " .
                "`phone` = values(`phone`), " .
                "`is_active` = values(`is_active`), " .
                "updated_at='$now', updated_by={$this->user}";
            DB::statement($queryUpdate);
        }

        //User Company
        $allUserId = User::model()->select(['id', 'phone'])->whereIn('phone',
            array_values($userImported))->get()->pluck('id', 'phone')->toArray();
        $role = Role::model()->select(['id', 'code', 'name'])->where('code', 'GUEST')->first()->toArray();
        $company = Company::model()->select(['id', 'code', 'name'])->where('id', $this->company)->first()->toArray();
        $queryHeader = "INSERT INTO `user_companies` (" .
            "`user_id`, " .
            "`role_id`, " .
            "`company_id`, " .
            "`user_code`, " .
            "`user_name`, " .
            "`role_code`, " .
            "`role_name`, " .
            "`company_code`, " .
            "`company_name`, " .
            "`created_at`, " .
            "`created_by`, " .
            "`updated_at`, " .
            "`updated_by`) VALUES ";
        $now = date('Y-m-d H:i:s', time());
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {
            $phone = $datum['phone'];
            if (empty($allUserId[$phone])) {
                continue;
            }
            $userId = $allUserId[$phone];
            $phone = str_replace(" ", "", $datum['phone']);
            $phone = str_replace(".", "", $phone);
            $queryContent .=
                "(" .
                $pdo->quote($userId) . "," .
                $pdo->quote($role['id']) . "," .
                $pdo->quote($company['id']) . "," .
                $pdo->quote($phone) . "," .
                $pdo->quote($phone) . "," .
                $pdo->quote($role['code']) . "," .
                $pdo->quote($datum['name']) . "," .
                $pdo->quote($company['code']) . "," .
                $pdo->quote($company['name']) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($this->user) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($this->user) . "), ";
        }
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`user_id`= values(`user_id`), " .
                "`company_id` = values(`company_id`), " .
                "updated_at='$now', updated_by={$this->user}";
            DB::statement($queryUpdate);
        }
        // Commit transaction
        DB::commit();
    }
}