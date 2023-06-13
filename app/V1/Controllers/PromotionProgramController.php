<?php

/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:28 PM
 */

namespace App\V1\Controllers;

use App\Area;
use App\Exports\PromotionProgramExport;
use App\PromotionProgram;
use App\Store;
use App\PromotionProgramCondition;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\UserGroup;
use App\V1\Models\PromotionProgramConditionModel;
use App\V1\Models\PromotionProgramModel;
use App\V1\Transformers\PromotionProgram\PromotionProgramComingSoonTransformer;
use App\V1\Transformers\PromotionProgram\PromotionProgramClientTransformer;
use App\V1\Transformers\PromotionProgram\PromotionProgramTransformer;
use App\V1\Validators\PromotionProgram\PromotionProgramCreateValidator;
use App\V1\Validators\PromotionProgram\PromotionProgramUpdateValidator;
use http\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use function GuzzleHttp\json_decode;

class PromotionProgramController extends BaseController
{

    protected $promotionProgramModel;
    protected $promotionProgramConditionModel;

    public function __construct()
    {
        $this->promotionProgramModel          = new PromotionProgramModel();
        $this->promotionProgramConditionModel = new PromotionProgramConditionModel();
    }

    public function search(Request $request, PromotionProgramTransformer $transformer)
    {
        $input               = $request->all();
        $limit               = array_get($input, 'limit', 20);
        $input['company_id'] = TM::getCurrentCompanyId();
        if (!empty($input['from']) && !empty($input['to'])) {
            $input['start_date'] = ['BETWEEN' => [date('Y-m-d 00:00:00', strtotime($input['from'])), date('Y-m-d 23:59:59', strtotime($input['to']))]];
        }
        if (!empty($input['from']) && empty($input['to'])) {
            $input['start_date'] = ['>=' => date('Y-m-d 00:00:00', strtotime($input['from']))];
        }
        if (!empty($input['to'])  && empty($input['from'])) {
            $input['start_date'] = ['<=' => date('Y-m-d 00:00:00', strtotime($input['to']))];
        }
        $result              = $this->promotionProgramModel->search($input, ['thumbnail'], $limit);
        return $this->response->paginator($result, $transformer);
    }

    public function view($id, PromotionProgramTransformer $transformer)
    {
        $result = PromotionProgram::model()->where(['id' => $id, 'company_id' => TM::getCurrentCompanyId()])->first();

        if (empty($result)) {
            return ['data' => []];
        }

        $conditions = PromotionProgramCondition::model()->where('promotion_program_id', $id)->get();

        $result->conditions = [
            'condition_combine' => $result->condition_combine,
            'condition_bool'    => $result->condition_bool,
            'details'           => $conditions->toArray(),
        ];

        $result->actions = [
            'act_type'                  => $result->act_type,
            'act_sale_type'             => $result->act_sale_type,
            'discount_by'               => $result->discount_by,
            'act_price'                 => $result->act_price,
            'act_time'                  => $result->act_time,
            'act_gift'                  => json_decode($result->act_gift),
            'act_not_product_condition' => $result->act_not_product_condition,
            'act_not_special_product'   => $result->act_not_special_product,
            'act_max_quality'           => $result->act_max_quality,
            'act_not_categories'        => $result->act_not_categories ? json_decode($result->act_not_categories) : [],
            'act_not_products'          => $result->act_not_products ? \GuzzleHttp\json_decode($result->act_not_products) : [],
            'act_categories'            => $result->act_not_products ? \GuzzleHttp\json_decode($result->act_categories) : [],
            'act_products'              => $result->act_not_products ? \GuzzleHttp\json_decode($result->act_products) : [],
            'act_products_gift'         => $result->act_products_gift ? \GuzzleHttp\json_decode($result->act_products_gift) : [],
            'act_quatity_sale'          => $result->act_quatity_sale,
            'limit_qty_flash_sale'      => $result->limit_qty_flash_sale,
            'min_qty_sale'              => $result->min_qty_sale,
            'limit_buy'                 => $result->limit_buy,
            'limit_price'               => $result->limit_price,
            'act_quatity'               => $result->act_quatity,
            'act_approval'              => $result->act_approval,
            'act_exchange'              => $result->act_exchange,
            'act_point'                 => $result->act_point,
        ];

        return $this->response->item($result, $transformer);
    }

    public function create(Request $request, PromotionProgramCreateValidator $createValidator)
    {
        $input = $request->all();
        $createValidator->validate($input);
        $input['code'] = str_clean_special_characters($input['code']);
        $createValidator->validate($input);

        try {
            DB::beginTransaction();

            $promotion = $this->promotionProgramModel->upsert($input);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            // $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($ex->getMessage(), $ex->getCode());
        }

        return ['status' => Message::get("promotions.create-success", $promotion->name)];
    }

    public function update($id, Request $request, PromotionProgramUpdateValidator $updateValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $updateValidator->validate($input);

        try {
            DB::beginTransaction();

            $promotion = $this->promotionProgramModel->upsert($input);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("promotions.update-success", $promotion->name)];
    }

    public function updateStatus($id, Request $request)
    {

        $input = $request->all();
        $promo = PromotionProgram::find($id);
        $promo->status = $input['status'];
        $promo->save();
        return ['status' => Message::get("promotions.update-success", $promo->code)];
    }

    public function active($id)
    {
        $promotion = PromotionProgram::model()->where([
            'id'         => $id,
            'company_id' => TM::getCurrentCompanyId(),
        ])->first();

        if (empty($promotion)) {
            return $this->response->errorBadRequest(Message::get("promotions.not-exist", "#$id"));
        }

        try {
            DB::beginTransaction();

            if ($promotion->active === 1) {
                $msgCode           = "promotions.inactive-success";
                $promotion->active = 0;
            } else {
                $promotion->active = 1;
                $msgCode           = "promotions.active-success";
            }

            $promotion->save();

            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest(Message::get($msgCode, $promotion->name));
        }

        return ['status' => Message::get($msgCode, $promotion->name)];
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();

            $promotion = PromotionProgram::model()->where([
                'id'         => $id,
                'company_id' => TM::getCurrentCompanyId(),
            ])->first();

            if (empty($promotion)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            if (!empty($cultureDetailIds)) {
                PromotionProgramCondition::model()->where('promotion_program_id', $id)->delete();
            }

            $promotion->delete();

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("promotions.delete-success", $promotion->name)];
    }

    public function getPromotionProgramComingSoon(Request $request, PromotionProgramComingSoonTransformer $transformer)
    {
        $input               = $request->all();
        $mytime              = Carbon::now();
        $mytime              = date('Y-m-d', strtotime($mytime));
        $limit               = array_get($input, 'limit', 20);
        $input['company_id'] = TM::getCurrentCompanyId();
        $input['start_date'] = ['>=' => $mytime];
        $result              = $this->promotionProgramModel->search($input, ['thumbnail'], $limit);

        return $this->response->paginator($result, $transformer);
    }

    public function getClientPromotionProgram(Request $request, PromotionProgramClientTransformer $transformer)
    {
        $store_id   = null;
        $company_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }

        $input               = $request->all();
        $limit               = array_get($input, 'limit', 20);
        $input['company_id'] = $company_id;
        $input['status'] = "1";
        $input['end_date']   = ['>=' => date('Y-m-d H:i:s', time())];
        $result              = $this->promotionProgramModel->search($input, [], $limit);

        return $this->response->paginator($result, $transformer);
    }

    public function getClientPromotionProgramDetail($id, PromotionProgramClientTransformer $transformer, Request $request)
    {
        $store_id   = null;
        $company_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }
        $result       = PromotionProgram::model()->where(['id' => $id, 'company_id' => $company_id])->first();
        $result->view += 1;
        $result->save();
        if (!$result) {
            return ["data" => null];
        }
        $conditions = PromotionProgramCondition::model()->where('promotion_program_id', $id)->get();
        $result->conditions = [
            'condition_combine' => $result->condition_combine,
            'condition_bool'    => $result->condition_bool,
            'details'           => $conditions->toArray(),
        ];

        $result->actions = [
            'act_type'                  => $result->act_type,
            'act_sale_type'             => $result->act_sale_type,
            'discount_by'               => $result->discount_by,
            'act_time'                  => $result->act_time,
            'act_gift'                  => json_decode($result->act_gift),
            'act_price'                 => $result->act_price,
            'act_not_product_condition' => $result->act_not_product_condition,
            'act_not_special_product'   => $result->act_not_special_product,
            'act_max_quality'           => $result->act_max_quality,
            'act_not_categories'        => $result->act_not_categories ? json_decode($result->act_not_categories) : [],
            'act_not_products'          => $result->act_not_products ? \GuzzleHttp\json_decode($result->act_not_products) : [],
            'act_categories'            => $result->act_not_products ? \GuzzleHttp\json_decode($result->act_categories) : [],
            'act_products'              => $result->act_not_products ? \GuzzleHttp\json_decode($result->act_products) : [],
            'act_quatity_sale'          => $result->act_quatity_sale,
            'act_quatity'               => $result->act_quatity,
            'act_approval'              => $result->act_approval,
            'act_exchange'              => $result->act_exchange,
            'act_point'                 => $result->act_point,
        ];

        return $this->response->item($result, $transformer);
    }
    public function exportPromotion(Request $request)
    {
        //ob_end_clean();
        ini_set('max_execution_time', 50000);
        ini_set('memory_limit', '-1');
        $C_CONDITION_NAME = [
            "cart_quantity"         => "Số lượng sản phẩm:",
            "cart_total"            => "Tổng tiền giỏ hàng:",
            "product_name"          => "Sản phẩm trong giỏ hàng (autocomplete):",
            "product_group"         => "Nhóm sản phẩm",
            "total_products"        => "Tổng tiền sản phẩm:",
            "customer_name"         => "Tên khách hàng:",
            "customer_group"        => "Nhóm khách hàng:",
            "customer_order"        => "Số lượng đặt hàng của khách hàng:",
            "customer_reg_date"     => "Khách hàng đăng ký từ:",
            "category_name"         => "Sản phẩm nằm trong danh mục:",
            "not_in_category_name"  => "Sản phẩm không nằm trong danh mục:",
            "day"                   => "Ngày trong tuần:",
            "apply_app"             => "Chỉ áp dụng cho app",
        ];

        $C_CONDITION_INPUT = [
            "mon" => "Thứ hai",
            "tue" => "Thứ ba",
            "wed" => "Thứ tư",
            "thu" => "Thứ năm",
            "fri" => "Thứ sáu",
            "sat" => "Thứ bảy",
            "sun" => "Chủ nhật",
        ];
        $C_CONDITION_TYPE = [
            "eq"   => "=",
            "gtr"  => ">=",
            "neq"  => "!=",
            "lth"  => "<=",
        ];
        $C_MULTIPLY_TYPE = [ // multiple_type
            "total"     => "Tổng tiền",
            "quantity"  => "Số lượng"
        ];
        $C_ACT_TYPE = [ // hành động (act_type)
            "order_sale_off"            => "Giảm giá giỏ hàng",
            "last_buy"                  => "Giảm giá đơn hàng tiếp theo",
            "sale_off_all_products"     => "Giảm giá tất cả các sản phẩm",
            "sale_off_on_products"      => "Giảm giá sản phẩm cụ thể",
            "sale_off_on_categories"    => "Giảm giá cho tất cả các sản phẩm trong danh mục",
            "add_product_cart"          => "Thêm sản phẩm vào giỏ hàng và áp dụng giảm giá",
            "order_discount"            => "Chiết khấu đơn hàng",
            "accumulate_point"          => "Tích điểm",
            "buy_x_get_y"               => "Quà tặng",
            "combo"                     => "Flexi combo",

        ];
        $C_ACT_TYPE_SALE = [ // loại giảm giá (act_type_sale)
            "fixed_price"           => "Giá cố định (thấp hơn thực tế)",
            "fixed"                 => "Số điểm cố định",
            "config"                => "Thiết lập số điểm"
        ];

        $C_ACT_DISCOUNT = [
            "all" => "Áp dụng tất cả sản phẩm",
            "product" => "Áp dụng sản phẩm được chọn"
        ];

        $C_TYPE_DISCOUNT = [
            "gift" => "Quà tặng, giảm giá",
        ];

        $input = $request->all();

        $datas = PromotionProgram::model()
            ->where("company_id", TM::getCurrentCompanyId())
            ->whereNull('deleted_at')
            ->where(function ($q) use ($input) {
                if (!empty($input['code'])) {
                    $input['code'] = explode(',', $input['code']);
                    $q->whereIn('code', $input['code']);
                }
                if (!empty($input['from']) && !empty($input['to'])) {
                    $q->whereBetween('created_at', [$input['from'], $input['to']]);
                }
            })->get();
        if (!$datas->count()) {
            return $this->response->errorNotFound('Không tìm thấy dữ liệu');
        }

        try {
            $excel = new PromotionProgramExport("BÁO CÁO CHƯƠNG TRÌNH KHUYẾN MÃI", [
                ['label_html' => '<strong>STT</strong>', 'width' => '10',],
                ['label_html' => '<strong>Mã CT</strong>', 'width' => '30',],
                ['label_html' => '<strong>Tên CT</strong>', 'width' => '30',],
                ['label_html' => '<strong>Ảnh đại diện</strong>', 'width' => '50',],
                ['label_html' => '<strong>Iframe</strong>', 'width' => '50',],
                ['label_html' => '<strong>Mô tả</strong>', 'width' => '30',],
                ['label_html' => '<strong>Loại CT áp dụng</strong>', 'width' => '15',],
                ['label_html' => '<strong>Có thể xếp chồng</strong>', 'width' => '15',],
                ['label_html' => '<strong>Có thể áp dụng luỹ tiến</strong>', 'width' => '15',],
                ['label_html' => '<strong>Ngày tạo</strong>', 'width' => '20',],
                ['label_html' => '<strong>Ngày bắt đầu</strong>', 'width' => '20',],
                ['label_html' => '<strong>Ngày kết thúc</strong>', 'width' => '20',],
                ['label_html' => '<strong>Sử dụng tối đa được phép</strong>', 'width' => '15',],
                ['label_html' => '<strong>Tối đa sử dụng cho mỗi khách hàng</strong>', 'width' => '20',],
                ['label_html' => '<strong>Loại CT</strong>', 'width' => '15',],
                ['label_html' => '<strong>Yêu cầu đăng nhập</strong>', 'width' => '20',],
                ['label_html' => '<strong>Nhóm KH</strong>', 'width' => '30',],
                ['label_html' => '<strong>Khu vực áp dụng khuyến mãi</strong>', 'width' => '30',],
                ['label_html' => '<strong>Chi nhánh</strong>', 'width' => '20',],
                ['label_html' => '<strong>Tags khuyến mãi</strong>', 'width' => '30',],
                ['label_html' => '<strong>Điều kiện</strong>', 'width' => '50',],
                ['label_html' => '<strong>Hành động</strong>', 'width' => '50',],
                ['label_html' => '<strong>Sản phẩm áp dụng</strong>', 'width' => '50',],
            ], $input['from'] ?? null, $input['to'] ?? null);
            $excel->setFormatNumber([
                // 'R' => NumberFormat::FORMAT_NUMBER,
                // 'S' => NumberFormat::FORMAT_NUMBER,
                // 'T' => NumberFormat::FORMAT_NUMBER,
            ]);
            $k = 0;
            foreach ($datas as $data) {

                $area       = [];
                $tags       = [];
                $dieu_kien  = [];

                $data->condition_combine    = $data->condition_combine  == 'All' ? 'Tất cả' : 'Vài';
                $data->condition_bool       = $data->condition_bool     == 'True' ? 'Đúng:' : 'Sai:';

                $dieu_kien_text = "- Chương trình sẽ hiệu lực nếu $data->condition_combine các điều kiện dưới đây là $data->condition_bool";
                // $actionTest = [
                //     'act_type'                  => $data->act_type,
                //     'act_sale_type'             => $data->act_sale_type,
                //     'discount_by'               => $data->discount_by,
                //     'act_price'                 => $data->act_price,
                //     'act_time'                  => $data->act_time,
                //     'act_gift'                  => json_decode($data->act_gift),
                //     'act_not_product_condition' => $data->act_not_product_condition,
                //     'act_not_special_product'   => $data->act_not_special_product,
                //     'act_max_quality'           => $data->act_max_quality,
                //     'act_not_categories'        => $data->act_not_categories ? json_decode($data->act_not_categories) : [],
                //     'act_not_products'          => $data->act_not_products ? \GuzzleHttp\json_decode($data->act_not_products) : [],
                //     'act_categories'            => $data->act_not_products ? \GuzzleHttp\json_decode($data->act_categories) : [],
                //     'act_products'              => $data->act_not_products ? \GuzzleHttp\json_decode($data->act_products) : [],
                //     'act_products_gift'         => $data->act_products_gift ? \GuzzleHttp\json_decode($data->act_products_gift) : [],
                //     'act_quatity_sale'          => $data->act_quatity_sale,
                //     'limit_qty_flash_sale'      => $data->limit_qty_flash_sale,
                //     'min_qty_sale'              => $data->min_qty_sale,
                //     'limit_buy'                 => $data->limit_buy,
                //     'limit_price'               => $data->limit_price,
                //     'act_quatity'               => $data->act_quatity,
                //     'act_approval'              => $data->act_approval,
                //     'act_exchange'              => $data->act_exchange,
                //     'act_point'                 => $data->act_point,
                // ];

                $actions = [];


                array_push($actions, "- Loại hành động: " . $C_ACT_TYPE[$data->act_type]);

                if ($data->act_type == 'sale_off_on_products' || $data->act_type == 'sale_off_on_categories') {
                    array_push($actions,  '- Áp dụng giảm: ' . $C_ACT_DISCOUNT[$data->discount_by]);
                }

                if ($data->act_type == 'order_sale_off' || $data->act_type == 'sale_off_all_products' || $data->act_type == 'add_product_cart' || $data->act_type == 'sale_off_on_categories' || $data->act_type == 'order_discount' || $data->act_type == 'accumulate_point' || $data->act_type == 'last_buy' || $data->act_type == 'sale_off_on_products') {
                    if ($data->act_sale_type != "percentage") {
                        array_push($actions, '- Loại giảm giá: ' . $C_ACT_TYPE_SALE[$data->act_sale_type]);
                    }

                    if ($data->act_sale_type == "percentage" && $data->promotion_type != 'POINT') {
                        array_push($actions, '- Loại giảm giá: ' . "Tỷ lệ phần trăm");
                    }

                    if ($data->act_sale_type == "percentage" && $data->promotion_type == 'POINT') {
                        array_push($actions, '- Loại giảm giá: ' . "Phần trăm giá trị đơn hàng");
                    }
                }

                if (($data->act_type == 'order_sale_off' || $data->act_type == 'sale_off_all_products' || $data->act_type == 'add_product_cart' || $data->act_type == 'sale_off_on_categories' || $data->act_type == 'order_discount' || $data->act_type == 'accumulate_point' || $data->act_type == 'last_buy' || $data->act_type == 'sale_off_on_products') && $data->discount_by == 'all' && $data->act_sale_type != 'config') {
                    if ($data->act_sale_type == 'percentage') {
                        array_push($actions, '- Giảm giá: ' . $data->act_price . '%');
                    }

                    if ($data->act_sale_type == 'fixed_price') {
                        array_push($actions, '- Giảm giá: ' . $data->act_price . 'VND');
                    }

                    if ($data->act_sale_type == 'fixed') {
                        array_push($actions, '- Giảm giá: ' . $data->act_price . 'Điểm');
                    }
                }

                if ($data->act_type == 'accumulate_point' && $data->act_sale_type == 'config') {
                    array_push($actions,  '- Số điểm: ' . $data->act_point);
                    array_push($actions,  '- Số tiền: ' . $data->act_exchange . " VND");
                }

                if ($data->act_type == 'last_buy') {
                    array_push($actions, '- Số ngày: ' . $data->act_time);
                }

                // sản phẩm
                if ($data->act_type == 'add_product_cart' || $data->act_type == 'order_sale_off' || $data->act_type == 'sale_off_on_products') {
                    if (isset($data->act_products) && !empty(json_decode($data->act_products)[0]->product_name)) {
                        $products = [];
                        foreach (json_decode($data->act_products) as $key => $value) {
                            array_push($products,  "+ " . $value->product_name . "\n" . (isset($value->min_qty_sale) && !empty($value->min_qty_sale) ? "* Số lượng tối thiểu: " . $value->min_qty_sale . "\n" : '') . (isset($value->limit_qty_flash_sale) && !empty($value->min_qty_sale) ? "* Số lượng tối đa: " . $value->limit_qty_flash_sale . "\n" : '') . (isset($value->discount) && !empty($value->discount) ?  "* Giảm: " . $value->discount . "% trên SP" . "\n" : '') . (isset($value->qty_flash_sale) && !empty($value->qty_flash_sale) ? "* Số lượng toàn hệ thống: " . $value->qty_flash_sale . "\n" : ''));
                        }
                        array_push($actions, '- Sản phẩm bao gồm: ' . "\n" . implode("\n", $products));
                    }
                }

                // sản phẩm quà tặng
                if ($data->act_type == 'buy_x_get_y' || $data->act_type == 'last_buy') {
                    if (isset($data->act_products_gift) && !empty(json_decode($data->act_products_gift)[0]->product_name)) {
                        $product_gifts = [];
                        foreach (json_decode($data->act_products_gift) as $key => $value) {
                            array_push($product_gifts, "+ " .  $value->product_name . "\n" . (isset($value->title_gift) && !empty($value->title_gift) ? "* Tên hiển thị: " . $value->title_gift . "\n" : '') . (isset($value->qty_gift) && !empty($value->qty_gift) ? "* Số lượng quà:  " . $value->qty_gift . "\n" : '') . (isset($value->unit_name) && !empty($value->unit_name) ? "* Đơn vị tính: " . $value->unit_name . "\n" : '') . (isset($value->specification_value) ?  "* Quy cách đóng gói: " . $value->specification_value . "\n" : ''));
                        }
                        array_push($actions,  '- Sản phẩm quà tặng bao gồm: ' . "\n" . implode("\n", $product_gifts));
                    }
                }
                // if ($data->act_type == 'order_sale_off' || $data->act_type == 'sale_off_on_products' || $data->act_type == 'add_product_cart') {
                //     array_push($header_actions, 'Số lượng mua');
                // }

                if ($data->act_type == "sale_off_on_categories") {
                    if (isset($data->act_categories) && !empty(json_decode($data->act_categories)[0]->category_name)) {
                        $categories = [];
                        foreach (json_decode($data->act_categories) as $key => $value) {
                            array_push($categories, "+ " . $value->category_name . "\n" . (isset($value->min_qty_sale) ? "* Số lượng tối thiểu: " . $value->min_qty_sale . "\n" : '') . (isset($value->limit_qty_flash_sale) ? "* Số lượng tối đa: " . $value->limit_qty_flash_sale . "\n" : '') . (isset($value->discount) ? "* Giảm: " . $value->discount . "% trên SP" . "\n" : '') . (isset($value->qty_flash_sale) ? "* Số lượng toàn hệ thống: " . $value->qty_flash_sale . "\n" : ''));
                        }
                        array_push($actions, '- Danh mục bao gồm: ' . "\n" . implode("\n", $categories));
                    }
                }

                if ($data->act_type == "sale_off_all_products" || $data->act_type == "sale_off_on_categories" || $data->act_type == "discount_on_manufacturers" || $data->act_type == "order_sale_off") {
                    if (isset($data->act_not_products) && !empty(json_decode($data->act_not_products)[0]->product_name)) {
                        $not_products = [];
                        foreach (json_decode($data->act_not_products) as $key => $value) {
                            array_push($not_products, "+ " . $value->product_name);
                        }
                        array_push($actions, '- Không bao gồm các sản phẩm : ' . implode("\n", $not_products));
                    }
                }


                if (($data->act_type == "sale_off_all_products" || $data->act_type == "order_sale_off")) {
                    if (isset($data->act_not_categories) && !empty(json_decode($data->act_not_categories)[0]->category_name)) {
                        $not_categories = [];
                        foreach (json_decode($data->act_not_categories) as $key => $value) {
                            array_push($not_categories, "+ " . $value->category_name);
                        }
                        array_push($actions, '- Không bao gồm các danh mục : ' . implode("\n", $not_categories));
                    }
                }

                if ($data->act_type == "sale_off_on_products" || $data->act_type == "sale_off_all_products") {
                    if (!empty($data->min_qty_sale)) {
                        array_push($actions, '- Giới hạn tổng số lượng min SP/Giỏ hàng: ' . $data->min_qty_sale);
                    }

                    if (!empty($data->limit_qty_flash_sale)) {
                        array_push($actions, '- Giới hạn tổng số lượng max SP/Giỏ hàng: ' . $data->limit_qty_flash_sale);
                    }

                    if (!empty($data->limit_buy)) {
                        array_push($actions, '- Giới hạn đơn hàng/KH: ' . $data->limit_buy);
                    }
                }

                if ($data->act_type == "combo") {
                    if (isset($data->act_gift) && !empty(json_decode($data->act_gift)[0]->product_name)) {
                        $combo = [];
                        foreach (json_decode($data->act_gift) as $key => $value) {
                            $gift_product = [];
                            if (isset($value->gift) && !empty($value->gift[0]->product_name)) {
                                foreach ($value->gift as $key => $value2) {
                                    array_push($gift_product, "+ Tên sản phẩm: " . $value2->product_name . "\n"  . (isset($value2->title_gift) ? "+ Tên quà tặng: " . $value2->title_gift . "\n" : "") . "+ Số lượng quà tặng: " . $value2->qty_gift . "\n" . (isset($value2->unit_name) ? "+ Đơn vị tính: " . $value2->unit_name . "\n" : "") . (isset($value2->specification_value) ? "+ Quy cách đóng gói: " . $value2->specification_value . "\n" : ""));
                                }
                            }
                            array_push($combo, '- Điều kiện: ' . $value->product_name .  "\n" .
                                "+ " . $C_MULTIPLY_TYPE[$value->condition] . " " . $C_CONDITION_TYPE[$value->condition_type] . " " . $value->condition_input . "\n" .
                                "- Hành động: " . $C_TYPE_DISCOUNT[$value->type_discount] . " " . ($value->act_sale_type == "percentage" ? "Giá trị cố định (thấp hơn thực tế) " : "Phần trăm ") . ($value->act_sale_type == "percentage" ?  $value->act_price .  ' %' : $value->act_price . ' VND') . "\n" .
                                isset($value->gift) && !empty($value->gift[0]->product_name) ?  "- Quà tặng: " . "\n" . implode("\n", $gift_product) : "");
                        }
                        array_push($actions, implode("\n", $combo));
                    }
                }



                if (!empty($data->area) && $data->area != '[]') {
                    foreach (json_decode($data->area) as $key => $value) {
                        array_push($area, $value->name);
                    }
                }

                if (!empty($data->tags) && $data->tags != '[]' && $data->tags != "null") {
                    foreach (json_decode($data->tags) as $key => $value) {
                        array_push($tags, $value->name);
                    }
                }
        
                if (!empty($data->conditions) && $data->conditions != '[]') {
                    array_push($dieu_kien, $dieu_kien_text);
                    foreach (json_decode($data->conditions) as $key => $value) {
                        array_push(
                            $dieu_kien,
                            "+ " . $C_CONDITION_NAME[$value->condition_name] . " " .
                                ($value->item_name ?? "") . " " .
                                (!empty($value->condition_input) ? "có " . ($C_MULTIPLY_TYPE[$value->multiply_type] ?? "") . " " .
                                    $C_CONDITION_TYPE[$value->condition_type] . " " .
                                    ((!empty($value->condition_input) && $C_CONDITION_NAME[$value->condition_name] == 'day' ? $C_CONDITION_INPUT[$value->condition_input]
                                        : $C_CONDITION_NAME[$value->condition_name] != 'day') ? $value->condition_input : "") . " " .
                                    (!empty($value->condition_limit) ? 'và số lần lũy tiến' . $value->condition_limit : "") : "") . "\n"
                        );
                    }
                }
                $applicable_products = [];        
                if(!empty($data->type) && $data->type != '[]'){
                    if($data->type == 'PRODUCT'){
                        foreach(json_decode($data->act_products) as $key => $value){
                            array_push($applicable_products, $value->product_name);
                        }
                    }
                }

                $export[] = [
                    'stt'                       => ++$k,
                    'code'                      => $data->code,
                    'name'                      => $data->name,
                    'avatar'                    => !empty($data->thumbnail->code) ? 'https://media.nutifoodshop.com/file/' . $data->thumbnail->code : '',
                    'iframe'                    => !empty($data->iframeImage->code) ? 'https://media.nutifoodshop.com/file/' . $data->iframeImage->code : '',
                    'description'               => strip_tags(html_entity_decode($data->description, 0, 'UTF-8')), // remove tag html
                    'type'                      => TYPE_PROMOTION_NAME[$data->type] ?? null,
                    'stack_able'                => $data->stack_able == 'yes' ? "Có" : "Không",
                    'multiply'                  => $data->multiply == 'yes' ? "Có" : "Không",
                    'created_at'                => $data->created_at,
                    'start_date'                => date("Y-m-d H:i:s", strtotime($data->start_date)) ?? null,
                    'end_date'                  => date("Y-m-d H:i:s", strtotime($data->end_date)) ?? null,
                    'total_user'                => $data->total_user ?? null,
                    'total_use_customer'        => $data->total_use_customer ?? null,
                    'promotion_type'            => PROMOTION_TYPE_NAME[$data->promotion_type] ?? null,
                    'need_login'                => $data->need_login == 1 ? "Có" : "Không",
                    'group_customer'            => $this->groupCustomer($data->group_customer) ?? null,
                    'area'                      => !empty($area) ? implode(",", $area) : null,
                    'default_store'             => !empty($data->default_store) && $data->default_store != '[]' ? 'NUTIFOOD' : null,
                    'tags'                      => !empty($tags) ? implode("\n ", $tags) : null,
                    'conditions'                => !empty($dieu_kien) ? implode("\n ", $dieu_kien) : null,
                    'actions'                   => !empty($actions) ? implode("\n ", $actions) : null,
                    'applicable_products'       => !empty($applicable_products) ? implode("\n ", $applicable_products) : null,
                ];
            }
            $excel->registerEvents();
            $excel->setBodyArray($export);
            //ob_start();
            return $excel->download('report_promotion_programs.xlsx');
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }
    private function groupCustomer($group_customer)
    {
        $data = [];
        foreach (json_decode($group_customer) as $customer_id) {
            $temp = UserGroup::where('id', $customer_id)->first();
            if (!empty($temp)) {
                array_push($data, $temp->name);
            }
        }
        $return = implode(", ", $data);
        return $return;
    }


    public function promotionPrice($productpromotion, $product_id, $price, $discount_by, $act_sale_type, $act_price)
    {
        $prod = array_pluck($productpromotion, 'product_id');
        $search_prod = array_search($product_id, $prod);

        if (is_numeric($search_prod)) {
            if ($discount_by == "product") {
                if ($act_sale_type == 'percentage') {
                    return $price * ($productpromotion[$search_prod]->price / 100);
                }
                if ($act_sale_type != 'percentage') {
                    return $productpromotion[$search_prod]->price;
                }
            }

            if ($discount_by != "product") {
                if ($act_sale_type == 'percentage') {
                    return $price * ($act_price / 100);
                }
                if ($act_sale_type != 'percentage') {
                    return $act_price;
                }
            }
        }
        return 0;
    }


    // private function getAreaFromIds($area_id) {
    //     $data = [];
    //     foreach (json_decode($area_id) as $area) {
    //         $temp = Area::where('id', $area)->first();
    //         if(!empty($temp)){;
    //             array_push($data, $temp->name);
    //         }
    //     }
    //     $return = implode(", ",$data);
    //     return $return;
    // }
}
