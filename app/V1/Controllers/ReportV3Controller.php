<?php
/**
 * User: kpistech2
 * Date: 2020-11-13
 * Time: 20:52
 */

namespace App\V1\Controllers;

use App\Exports\CustomerCommissionExport;
use App\Setting;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\User;
use App\V1\Models\OrderModel;
use App\V1\Models\SettingModel;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReportV3Controller extends BaseController
{
    /**
     * 1.    Báo cáo tổng hợp doanh số từ ngày – đến ngày theo khách hàng
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function getCustomerSales(Request $request)
    {
        //ob_end_clean();
        $input = $request->all();

        if (empty($input['from'])) {
            $input['from'] = date("Y-m-d");
        }
        if (empty($input['to'])) {
            $input['to'] = date("Y-m-d");
        }

        if (!empty($input['user_id'])) {
            $customer = User::model()->select([
                'id',
                'code',
                'name',
                'type',
                'phone',
                'level_number',
                'reference_phone',
                'user_level1_id',
                'user_level2_ids',
                'user_level3_ids'
            ])->where([
                'id'         => $input['user_id'],
                'company_id' => TM::getCurrentCompanyId(),
                'type'       => USER_TYPE_CUSTOMER
            ])->first();

            if (empty($customer)) {
                return $this->responseError(Message::get("V001", Message::get('customers')));
            }

            $input['user_ids'] = explode(",",
                $customer->id . "," . $customer->user_level1_id . "," . $customer->user_level2_ids . "," . $customer->user_level3_ids);
            $input['user_ids'] = array_unique(array_filter($input['user_ids']));
        }
        try {
            // Get Order
            $orderModel = new OrderModel();
            $orders = $orderModel->getTotalSalesForCustomers($input);
            if (!empty($orders)) {
                $orders = $orderModel->getCustomerSale($orders);
            }
            $excel = new CustomerCommissionExport("BÁO CÁO TỔNG HỢP DOANH SỐ THEO KHÁCH HÀNG", [
                ['label_html' => '<strong>STT</strong>', 'width' => '10',],
                ['label_html' => '<strong>Mã khách hàng</strong>', 'width' => '20',],
                ['label_html' => '<strong>Tên khách hàng</strong>', 'width' => '20',],
                ['label_html' => '<strong>Loại đại lý</strong>', 'width' => '20',],
                ['label_html' => '<strong>Doanh số cá nhân</strong>', 'width' => '20',],
                ['label_html' => '<strong>Doanh số nhóm</strong>', 'width' => '20',],
            ], $input['from'], $input['to']);

            $excel->setFormatNumber([
                'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
                'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            ]);

            $body = [];
            $line = 0;
            foreach ($orders as $i => $order) {
                if (!empty($customer)) {
                    if ($order['customer_id'] == $customer->id) {
                        $body[] = [
                            1,
                            "#" . $order['customer_id'] . "-" . $order['customer_code'],
                            $order['customer_name'],
                            $order['user_level2_ids'] || $order['user_level3_ids'] ? "Nhóm đại lý" : "Đại lý",
                            $order['total_sales'],
                            $order['total_group_sales'],
                        ];
                        break;
                    }
                    continue;
                }
                $line++;
                $body[] = [
                    $line,
                    "#" . $order['customer_id'] . "-" . $order['customer_code'],
                    $order['customer_name'],
                    $order['user_level2_ids'] || $order['user_level3_ids'] ? "Nhóm đại lý" : "Đại lý",
                    $order['total_sales'],
                    $order['total_group_sales'],
                ];
            }

            $excel->setBodyArray($body);
            $excel->addBodyItem([
                ['colspan' => 5, 'value' => 'TỔNG'],
                array_sum(array_column($body, 5))
            ]);
            //ob_start();
            return $excel->download(date("Ymd") . '-customer-sales.xlsx');
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    /**
     * 2.    Báo cáo chi tiết doanh số theo đại lý
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|void
     */
    public function getCustomerSalesDetail(Request $request)
    {
        //ob_end_clean();
        $input = $request->all();

        if (empty($input['customer_code'])) {
            return $this->responseError(Message::get("V001", Message::get('customers')));
        }

        if (empty($input['from'])) {
            $input['from'] = date("Y-m-d");
        }
        if (empty($input['to'])) {
            $input['to'] = date("Y-m-d");
        }

        $customer = User::model()->select([
            'id',
            'code',
            'name',
            'type',
            'phone',
            'level_number',
            'reference_phone',
            'user_level1_id',
            'user_level2_ids',
            'user_level3_ids'
        ])->where([
            'code'       => $input['customer_code'],
            'company_id' => TM::getCurrentCompanyId(),
            'type'       => USER_TYPE_CUSTOMER
        ])->first();

        if (empty($customer)) {
            return $this->responseError(Message::get("V001", Message::get('customers')));
        }
        try {
            // Get Order
            $orderModel = new OrderModel();
            $body = $orderModel->getTotalSalesDetail($input);

            $excel = new CustomerCommissionExport("BÁO CÁO CHI TIẾT DOANH SỐ THEO ĐẠI LÝ", [
                ['label_html' => '<strong>Số đơn hàng</strong>', 'width' => '20',],
                ['label_html' => '<strong>Ngày</strong>', 'width' => '20',],
                ['label_html' => '<strong>Mã sản phẩm</strong>', 'width' => '20',],
                ['label_html' => '<strong>Tên sản phẩm</strong>', 'width' => '50', 'wrap' => 1],
                ['label_html' => '<strong>Số lượng</strong>', 'width' => '10',],
                ['label_html' => '<strong>Đơn giá</strong>', 'width' => '20',],
                ['label_html' => '<strong>Thành tiền</strong>', 'width' => '20',],
            ], $input['from'], $input['to']);

            $excel->setFormatNumber([
                'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
                'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            ]);

            $excel->setBodyArray($body);
            //ob_start();
            return $excel->download(date("Ymd") . '-customer-sales-detail.xlsx');
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    /**
     * 3. Báo cáo tính thưởng hoa hồng đại lý
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function getCustomerCommission(Request $request)
    {
        //ob_end_clean();
        $input = $request->all();
        if (empty($input['from'])) {
            $input['from'] = date("Y-m-d");
        }
        if (empty($input['to'])) {
            $input['to'] = date("Y-m-d");
        }

        if (!empty($input['user_id'])) {
            $customer = User::model()->select([
                'id',
                'code',
                'name',
                'type',
                'phone',
                'level_number',
                'reference_phone',
                'user_level1_id',
                'user_level2_ids',
                'user_level3_ids'
            ])->where([
                'id'         => $input['user_id'],
                'company_id' => TM::getCurrentCompanyId(),
                'type'       => USER_TYPE_CUSTOMER
            ])->first();

            if (empty($customer)) {
                return $this->responseError(Message::get("V001", Message::get('customers')));
            }

            $input['user_ids'] = explode(",",
                $customer->id . "," . $customer->user_level1_id . "," . $customer->user_level2_ids . "," . $customer->user_level3_ids);
            $input['user_ids'] = array_unique(array_filter($input['user_ids']));
        }
        try {
            // Get Setting
            $settingModel = new SettingModel();
            $settingCommission = $settingModel->getDataForKey('CUSTOMER-COMMISSIONS', 'key');

            // Get Order
            $orderModel = new OrderModel();
            $orders = $orderModel->getTotalSalesForCustomers($input);
            if (!empty($orders)) {
                $orders = $orderModel->calculateCustomerCommission($orders, $settingCommission);
            }
            $excel = new CustomerCommissionExport("BÁO CÁO TÍNH THƯỞNG HOA HỒNG ĐẠI LÝ", [
                ['label_html' => '<strong>STT</strong>', 'width' => '10',],
                ['label_html' => '<strong>Mã khách hàng</strong>', 'width' => '20',],
                ['label_html' => '<strong>Tên khách hàng</strong>', 'width' => '20',],
                ['label_html' => '<strong>Loại đại lý</strong>', 'width' => '20',],
                ['label_html' => '<strong>Doanh số cá nhân</strong>', 'width' => '20',],
                ['label_html' => '<strong>Doanh số nhóm</strong>', 'width' => '20',],
                ['label_html' => '<strong>Tỷ lệ hoa hồng</strong>', 'width' => '20',],
                ['label_html' => '<strong>Hoa hồng đại lý</strong>', 'width' => '20',],
            ], $input['from'], $input['to']);
            $excel->setFormatNumber([
                'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
                'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
                'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
            ]);

            $body = [];
            foreach ($orders as $i => $order) {
                if (!empty($customer)) {
                    if ($order['customer_id'] == $customer->id) {
                        $body[] = [
                            1,
                            "#" . $order['customer_id'] . "-" . $order['customer_code'],
                            $order['customer_name'],
                            $order['user_level2_ids'] || $order['user_level3_ids'] ? "Nhóm đại lý" : "Đại lý",
                            $order['total_sales'],
                            $order['total_group_sales'],
                            $order['rate'] . "%",
                            $order['commission'],
                        ];
                        break;
                    }
                    continue;
                }
                $body[] = [
                    $i + 1,
                    "#" . $order['customer_id'] . "-" . $order['customer_code'],
                    $order['customer_name'],
                    $order['user_level2_ids'] || $order['user_level3_ids'] ? "Nhóm đại lý" : "Đại lý",
                    $order['total_sales'],
                    $order['total_group_sales'],
                    $order['rate'] . "%",
                    $order['commission'],
                ];
            }

            $excel->setBodyArray($body);
            //ob_start();
            return $excel->download(date("Ymd") . '-customer-commission.xlsx');
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    /**
     * 4.    Báo cáo tính thưởng hoa hồng giới thiệu
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function getCustomerReferralCommission_ko_dc_xoa(Request $request)
    {
        //ob_end_clean();
        $input = $request->all();
        if (empty($input['from'])) {
            $input['from'] = date("Y-m-d");
        }
        if (empty($input['to'])) {
            $input['to'] = date("Y-m-d");
        }

        if (!empty($input['user_id'])) {
            $customer = User::model()->select([
                'id',
                'code',
                'name',
                'type',
                'phone',
                'level_number',
                'reference_phone',
                'user_level1_id',
                'user_level2_ids',
                'user_level3_ids'
            ])->where([
                'id'         => $input['user_id'],
                'company_id' => TM::getCurrentCompanyId(),
                'type'       => USER_TYPE_CUSTOMER
            ])->first();

            if (empty($customer)) {
                return $this->responseError(Message::get("V001", Message::get('customers')));
            }

            $input['user_ids'] = explode(",",
                $customer->id . "," . $customer->user_level1_id . "," . $customer->user_level2_ids . "," . $customer->user_level3_ids);
            $input['user_ids'] = array_unique(array_filter($input['user_ids']));
        }
        try {
            // Get Setting 1
            $settingModel = new SettingModel();
            $settingCommission1 = $settingModel->getDataForKey('CUSTOMER-COMMISSIONS', 'key');

            // Get Setting 2
            $settingModel = new SettingModel();
            $settingCommission2 = $settingModel->getForKey('CUSTOMER-REFERRAL-COMMISSIONS', 'key');

            // Get Order
            $orderModel = new OrderModel();
            $orders = $orderModel->getTotalSalesForCustomers($input);
            if (!empty($orders)) {
                $orders = $orderModel->calculateCustomerReferralCommission2($orders, $settingCommission1,
                    $settingCommission2);
            }

            $excel = new CustomerCommissionExport("BÁO CÁO TÍNH THƯỞNG HOA HỒNG ĐẠI LÝ", [
                ['label_html' => '<strong>STT</strong>', 'width' => '5',],
                ['label_html' => '<strong>Mã khách hàng</strong>', 'width' => '20',],
                ['label_html' => '<strong>Tên khách hàng</strong>', 'width' => '20',],
                ['label_html' => '<strong>Loại đại lý</strong>', 'width' => '15',],
                ['label_html' => '<strong>Doanh số</strong>', 'width' => '20',],
                ['label_html' => '<strong>Tỷ lệ hoa hồng</strong>', 'width' => '30',],
                ['label_html' => '<strong>Hoa hồng kinh doanh</strong>', 'width' => '30',],
                ['label_html' => '<strong>Hoa hồng giới thiệu</strong>', 'width' => '30',],
                ['label_html' => '<strong>Tổng hoa hồng</strong>', 'width' => '30',],
            ], $input['from'], $input['to']);
            $excel->setFormatNumber([
                'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
                'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
            ]);

            $body = [];
            $line = 0;
            foreach ($orders as $i => $order) {
                if ($order['customer_level'] == 3) {
                    continue;
                }
                if (!empty($customer)) {
                    if ($order['customer_id'] == $customer->id) {
                        $body[] = [
                            1,
                            "#" . $order['customer_id'] . "-" . $order['customer_code'],
                            $order['customer_name'],
                            $order['user_level2_ids'] || $order['user_level3_ids'] ? "Nhóm đại lý" : "Đại lý",
                            $order['total_group_sales'],
                            $order['commission_group_rate'],
                            $order['commission_person_rate'],
                            $order['commission_referral'],
                            $order['total_commission'],
                        ];
                        break;
                    }
                    continue;
                }
                $line++;
                $body[] = [
                    $line,
                    "#" . $order['customer_id'] . "-" . $order['customer_code'],
                    $order['customer_name'],
                    $order['user_level2_ids'] || $order['user_level3_ids'] ? "Nhóm đại lý" : "Đại lý",
                    $order['total_group_sales'],
                    $order['commission_group_rate'],
                    $order['commission_person_rate'],
                    $order['commission_referral'],
                    $order['total_commission'],
                ];
            }

            $excel->setBodyArray($body);
            //ob_start();
            return $excel->download(date("Ymd") . '-customer-referral-commission.xlsx');
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    /**
     * 4.    Báo cáo tính thưởng hoa hồng giới thiệu
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function getCustomerReferralCommission(Request $request)
    {
        //ob_end_clean();
        $input = $request->all();
        if (empty($input['from'])) {
            $input['from'] = date("Y-m-d");
        }
        if (empty($input['to'])) {
            $input['to'] = date("Y-m-d");
        }

        if (!empty($input['user_id'])) {
            $customer = User::model()->select([
                'id',
                'code',
                'name',
                'type',
                'phone',
                'level_number',
                'reference_phone',
                'user_level1_id',
                'user_level2_ids',
                'user_level3_ids'
            ])->where([
                'id'         => $input['user_id'],
                'company_id' => TM::getCurrentCompanyId(),
                'type'       => USER_TYPE_CUSTOMER
            ])->first();

            if (empty($customer)) {
                return $this->responseError(Message::get("V001", Message::get('customers')));
            }

            $input['user_ids'] = explode(",",
                $customer->id . "," . $customer->user_level1_id . "," . $customer->user_level2_ids . "," . $customer->user_level3_ids);
            $input['user_ids'] = array_unique(array_filter($input['user_ids']));
        }
        try {
            // Get Setting
            $settingModel = new SettingModel();
            $setting = $settingModel->getForKey('CUSTOMER-REFERRAL-COMMISSIONS', 'key');

            // Get Order
            $orderModel = new OrderModel();
            $orders = $orderModel->getTotalSalesForCustomers($input);
            if (!empty($orders)) {
                $orders = $orderModel->calculateCustomerReferralCommission($orders, $setting);
            }

            $excel = new CustomerCommissionExport("BÁO CÁO TÍNH THƯỞNG HOA HỒNG GIỚI THIỆU", [
                ['label_html' => '<strong>STT</strong>', 'width' => '5',],
                ['label_html' => '<strong>Mã khách hàng</strong>', 'width' => '20',],
                ['label_html' => '<strong>Tên khách hàng</strong>', 'width' => '20',],
                ['label_html' => '<strong>Loại đại lý</strong>', 'width' => '15',],
                ['label_html' => '<strong>Doanh số cá nhân</strong>', 'width' => '20',],
                ['label_html' => '<strong>Doanh số nhóm</strong>', 'width' => '20',],
                ['label_html' => '<strong>Tỷ lệ hoa hồng</strong>', 'width' => '20',],
                ['label_html' => '<strong>Tổng hoa hồng giới thiệu</strong>', 'width' => '30',],
            ], $input['from'], $input['to']);
            $excel->setFormatNumber([
                'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
                'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
                'G' => NumberFormat::FORMAT_PERCENTAGE,
                'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            ]);

            $body = [];
            $line = 0;
            foreach ($orders as $i => $order) {
                if ($order['customer_level'] == 3 || $order['total_group_sales'] == 0) {
                    continue;
                }

                if (!empty($customer)) {
                    if ($order['customer_id'] == $customer->id) {
                        $line++;
                        $body[] = [
                            $line,
                            "#" . $order['customer_id'] . "-" . $order['customer_code'],
                            $order['customer_name'],
                            $order['user_level2_ids'] || $order['user_level3_ids'] ? "Nhóm đại lý" : "Đại lý",
                            $order['total_sales'],
                            $order['total_group_sales'],
                            $order['rate'] / 100,
                            $order['commission'],
                        ];
                        if (empty($input['with_member'])) {
                            break;
                        }
                        continue;
                    }

                    if (in_array($customer->id, $input['user_ids'])) {
                        $line++;
                        $body[] = [
                            $line,
                            "#" . $order['customer_id'] . "-" . $order['customer_code'],
                            $order['customer_name'],
                            $order['user_level2_ids'] || $order['user_level3_ids'] ? "Nhóm đại lý" : "Đại lý",
                            $order['total_sales'],
                            $order['total_group_sales'],
                            $order['rate'] / 100,
                            $order['commission'],
                        ];
                    }
                    continue;
                }

                $line++;
                $body[] = [
                    $line,
                    "#" . $order['customer_id'] . "-" . $order['customer_code'],
                    $order['customer_name'],
                    $order['user_level2_ids'] || $order['user_level3_ids'] ? "Nhóm đại lý" : "Đại lý",
                    $order['total_sales'],
                    $order['total_group_sales'],
                    $order['rate'] / 100,
                    $order['commission'],
                ];
            }

            $excel->setBodyArray($body);
            //ob_start();
            return $excel->download(date("Ymd") . '-customer-referral-commission.xlsx');
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }
}