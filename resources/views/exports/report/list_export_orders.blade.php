<style>
    thead tr th {
        vertical-align: middle;
    }

    .header-table th {
        text-align: center;
        border: 1px solid #777;
    }

</style>
<table>
    <thead>
        <tr>
            <th style="text-align: left;font-weight: bold; font-size: 20px">BÁO CÁO CHI TIẾT ĐƠN HÀNG NUTIFOODSHOP</th>
        </tr>
        <tr>
            <th style="font-size: 10px;font-weight: bold">TỪ :
                {{ !empty($from) ? date('d/m/Y', strtotime($from ?? time())) : '' }}</th>
            <th style="font-size: 10px;font-weight: bold">ĐẾN :
                {{ !empty($to) ? date('d/m/Y', strtotime($to ?? time())) : '' }}</th>
        </tr>
        <tr class="header-table">
            <th style="text-align: left; font-weight: bold;">STT</th>
            <th style="text-align: left; font-weight: bold;">Mã nhà phân phối</th>
            <th style="text-align: left; font-weight: bold;">Tên nhà phân phối</th>
            <th style="text-align: left; font-weight: bold;">Mã phường nhà phân phối</th>
            <th style="text-align: left; font-weight: bold;">Tên phường nhà phân phối</th>
            <th style="text-align: left; font-weight: bold;">Mã quận nhà phân phối</th>
            <th style="text-align: left; font-weight: bold;">Tên quận nhà phân phối</th>
            <th style="text-align: left; font-weight: bold;">Mã tỉnh nhà phân phối</th>
            <th style="text-align: left; font-weight: bold;">Tên tỉnh nhà phân phối</th>
            <th style="text-align: left; font-weight: bold;">TT_NT</th>
            @if ($data['role_id'] == 1 || ($data['role_id'] != 1 && $data['is_ac'] == 1) || $data['role_id'] == 9)
                <th style="text-align: left; font-weight: bold;">Đối tượng khách hàng</th>
                <th style="text-align: left; font-weight: bold;">Mã khách hàng</th>
                <th style="text-align: left; font-weight: bold;">Tên khách hàng</th>
                <th style="text-align: left; font-weight: bold;">SĐT Khách hàng</th>
                <th style="text-align: left; font-weight: bold;">Email</th>
                <th style="text-align: left; font-weight: bold;">Giới tính</th>
                <th style="text-align: left; font-weight: bold;">Ngày sinh</th>
                <th style="text-align: left; font-weight: bold;">Tên người nhận hàng</th>
                <th style="text-align: left; font-weight: bold;">SĐT nhận hàng</th>
                <th style="text-align: left; font-weight: bold;">Địa chỉ nhận hàng</th>
                <th style="text-align: left; font-weight: bold;">Mã Phường/Xã nhận hàng</th>
                <th style="text-align: left; font-weight: bold;">Phường/Xã nhận hàng</th>
                <th style="text-align: left; font-weight: bold;">Mã Quận/Huyện nhận hàng</th>
                <th style="text-align: left; font-weight: bold;">Quận/Huyện nhận hàng</th>
                <th style="text-align: left; font-weight: bold;">Mã Tỉnh/Thành phố nhận hàng</th>
                <th style="text-align: left; font-weight: bold;">Tỉnh/Thành phố nhận hàng</th>
            @endif
            <th style="text-align: left; font-weight: bold;">Mã đơn hàng</th>
            <th style="text-align: left; font-weight: bold;">Ngày đặt đơn</th>
            <th style="text-align: left; font-weight: bold;">Ngày CRM gọi đơn hàng</th>
            <th style="text-align: left; font-weight: bold;">Ngày xác nhận đơn</th>
            <th style="text-align: left; font-weight: bold;">Trạng thái CRM</th>
            <th style="text-align: left; font-weight: bold;">Ngày duyệt đơn</th>
            <th style="text-align: left; font-weight: bold;">Ngày tạo lệnh giao hàng</th>
            <th style="text-align: left; font-weight: bold;">Ngày giao hàng thành công</th>
            <th style="text-align: left; font-weight: bold;">Ngày hoàn thành đơn hàng</th>
            <th style="text-align: left; font-weight: bold;">Ngày huỷ đơn hàng</th>
            <th style="text-align: left; font-weight: bold;">Mã giảm giá</th>
            <th style="text-align: left; font-weight: bold;">Mã vận chuyển</th>
            <th style="text-align: left; font-weight: bold;">CRM leader</th>
            <th style="text-align: left; font-weight: bold;">CRM caller</th>
            <th style="text-align: left; font-weight: bold;">Số ngày chưa xử lý đơn</th>
            <th style="text-align: left; font-weight: bold;">Tổng thời gian chưa xử lý đơn</th>
            <th style="text-align: left; font-weight: bold;">Mã SP</th>
            <th style="text-align: left; font-weight: bold;">Tên Sản phẩm</th>
            <th style="text-align: left; font-weight: bold;">Loại sản phẩm</th>
            <th style="text-align: left; font-weight: bold;">Đơn vị</th>
            <th style="text-align: left; font-weight: bold;">Số lượng</th>
            <th style="text-align: left; font-weight: bold;">Giá gốc sản phẩm</th>
            <th style="text-align: left; font-weight: bold;">% CKTT</th>
            <th style="text-align: left; font-weight: bold;">Số tiền chiết khấu</th>
            <th style="text-align: left; font-weight: bold;">Giá khuyến mãi</th>
            <th style="text-align: left; font-weight: bold;">Thành tiền sản phẩm</th>
            <th style="text-align: left; font-weight: bold;">Khách trả phí vận chuyển</th>
            <th style="text-align: left; font-weight: bold;">Shop trả phí vận chuyển</th>
            <th style="text-align: left; font-weight: bold;">Phí vận chuyển thực tế</th>
            <th style="text-align: left; font-weight: bold;">Tổng phí vận chuyển</th>
            <th style="text-align: left; font-weight: bold;">Mã giảm giá</th>
            <th style="text-align: left; font-weight: bold;">Mã freeship</th>
            <!-- <th style="text-align: left; font-weight: bold;">% CK trên đơn hàng</th> -->
            <th style="text-align: left; font-weight: bold;">Tổng tiền chiết khấu trên đơn hàng</th>
            <th style="text-align: left; font-weight: bold;">Thành tiền đơn hàng</th>
            <th style="text-align: left; font-weight: bold;">Mã CTKM</th>
            <th style="text-align: left; font-weight: bold;">Tên CTKM</th>
            <!-- <th style="text-align: left; font-weight: bold;">Mã SP tặng</th>
        <th style="text-align: left; font-weight: bold;">Tên SP tặng</th>
        <th style="text-align: left; font-weight: bold;">Số lượng sản phẩm quà tặng</th> -->
            <th style="text-align: left; font-weight: bold;">Kênh bán hàng</th>
            <th style="text-align: left; font-weight: bold;">Tham số app (Android/iOS)</th>
            <th style="text-align: left; font-weight: bold;">Phương thức thanh toán</th>
            <th style="text-align: left; font-weight: bold;">Mã giao dịch thanh toán</th>
            <th style="text-align: left; font-weight: bold;">Số tiền khách hàng chuyển khoản</th>
            @if ($data['role_id'] == 1 || ($data['role_id'] != 1 && $data['is_ac'] == 1) || $data['role_id'] == 9)
                <th style="text-align: left; font-weight: bold;">Số tài khoản KH</th>
            @endif
            <th style="text-align: left; font-weight: bold;">Số virtual account</th>
            <th style="text-align: left; font-weight: bold;">Trạng thái thanh toán</th>
            <th style="text-align: left; font-weight: bold;">Đơn vị vận chuyển</th>
            <th style="text-align: left; font-weight: bold;">Mã vận đơn DVVC</th>
            <th style="text-align: left; font-weight: bold;">Dịch vụ giao hàng</th>
            <th style="text-align: left; font-weight: bold;">Trạng thái giao hàng</th>
            <th style="text-align: left; font-weight: bold;">Trạng thái đơn hàng</th>
            <th style="text-align: left; font-weight: bold;">Lý do hủy</th>
            <th style="text-align: left; font-weight: bold;">Lý do hoàn trả</th>
            <th style="text-align: left; font-weight: bold;">Nhận hóa đơn</th>
            <th style="text-align: left; font-weight: bold;">Tên công ty</th>
            <th style="text-align: left; font-weight: bold;">Mã số thuế</th>
            <th style="text-align: left; font-weight: bold;">Địa chỉ công ty</th>
            <th style="text-align: left; font-weight: bold;">Vùng</th>
            <th style="text-align: left; font-weight: bold;">Khu vực</th>
            <th style="text-align: left; font-weight: bold;">% Thuế suất</th>
            <th style="text-align: left; font-weight: bold;">Quy cách</th>
            <th style="text-align: left; font-weight: bold;">Độ tuổi</th>
            <th style="text-align: left; font-weight: bold;">Dung tích</th>
            <!-- <th style="text-align: left; font-weight: bold;">Đơn vị dung tích</th> -->
            <th style="text-align: left; font-weight: bold;">HSD(tháng)</th>
            <th style="text-align: left; font-weight: bold;">Industry</th>
            <th style="text-align: left; font-weight: bold;">CADCODE</th>
            <th style="text-align: left; font-weight: bold;">Manufacture</th>
            <th style="text-align: left; font-weight: bold;">CAT</th>
            <th style="text-align: left; font-weight: bold;">SUBCAT</th>
            <th style="text-align: left; font-weight: bold;">Brand</th>
            <th style="text-align: left; font-weight: bold;">Brandy</th>
            <th style="text-align: left; font-weight: bold;">CADCODESUBCAT</th>
            <th style="text-align: left; font-weight: bold;">CADCODEBRAND</th>
            <th style="text-align: left; font-weight: bold;">CADCODEBRANDY</th>
            <th style="text-align: left; font-weight: bold;">Division</th>
            <th style="text-align: left; font-weight: bold;">Source</th>
            <th style="text-align: left; font-weight: bold;">Packing</th>
            <th style="text-align: left; font-weight: bold;">SKU</th>
            <th style="text-align: left; font-weight: bold;">SKU name</th>
            <th style="text-align: left; font-weight: bold;">SKU standard</th>
            <th style="text-align: left; font-weight: bold;">Type</th>
            <th style="text-align: left; font-weight: bold;">Attribute</th>
            <th style="text-align: left; font-weight: bold;">Variant</th>
            <th style="text-align: left; font-weight: bold;">Tổng cân nặng(Kg)</th>
            <th style="text-align: left; font-weight: bold;">Tổng quãng đường giao hàng(KM)</th>
        </tr>
    </thead>
    <tbody>
        @if (!empty($data))
            @foreach ($data as $key => $item)
                <tr>
                    <td style="border: 1px solid #777; text-align: left">{{(int) ++$key}}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->distributor_code) ? $item->distributor_code : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->distributor_name) ? $item->distributor_name : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->ward_code) ? $item->ward_code : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->ward_name) ? $item->ward_name : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->district_code) ? $item->district_code : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->district_name) ? $item->district_name : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->city_code) ? $item->city_code : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->city_name) ? $item->city_name : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">{{ '' }}</td>
                    @if ($data['role_id'] == 1 || ($data['role_id'] != 1 && $data['is_ac'] == 1) || $data['role_id'] == 9)
                        <td style="border: 1px solid #777; text-align: left">
                            {{ !empty($item->order_type) ? $item->order_type : '' }}</td>
                        <td style="border: 1px solid #777; text-align: left">
                            {{ !empty($item->customer_code) ? $item->customer_code : '' }}</td>
                        <td style="border: 1px solid #777; text-align: left">
                            {{ !empty($item->customer_name) ? $item->customer_name : '' }}</td>
                        <td style="border: 1px solid #777; text-align: left">
                            {{ !empty($item->customer_phone) ? $item->customer_phone : '' }}</td>
                        <td style="border: 1px solid #777; text-align: left">
                            {{ !empty($item->customer_email) ? $item->customer_email : '' }}</td>
                        <td style="border: 1px solid #777; text-align: left">
                            {{ !empty($item->customer_gender) ? $item->customer_gender : '' }}</td>
                        <td style="border: 1px solid #777; text-align: left">
                            {{ !empty($item->customer_birthday) ? $item->customer_birthday : '' }}</td>
                        <td style="border: 1px solid #777; text-align: left">
                            {{ !empty($item->shipping_cus_name) ? $item->shipping_cus_name : '' }}</td>
                        <td style="border: 1px solid #777; text-align: left">
                            {{ !empty($item->shipping_cus_phone) ? $item->shipping_cus_phone : '' }}</td>
                        <td style="border: 1px solid #777; text-align: left">
                            {{ !empty($item->shipping_address) ? $item->shipping_address : '' }}</td>
                        <td style="border: 1px solid #777; text-align: left">
                            {{ !empty($item->ward_ship_code) ? $item->ward_ship_code : '' }}</td>
                        <td style="border: 1px solid #777; text-align: left">
                            {{ !empty($item->ward) ? $item->ward : '' }}</td>
                        <td style="border: 1px solid #777; text-align: left">
                            {{ !empty($item->district_ship_code) ? $item->district_ship_code : '' }}</td>
                        <td style="border: 1px solid #777; text-align: left">
                            {{ !empty($item->district) ? $item->district : '' }}</td>
                        <td style="border: 1px solid #777; text-align: left">
                            {{ !empty($item->city_ship_code) ? $item->city_ship_code : '' }}</td>
                        <td style="border: 1px solid #777; text-align: left">
                            {{ !empty($item->city) ? $item->city : '' }}</td>
                    @endif
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->order_code) ? $item->order_code : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->order_created_at) ? $item->order_created_at : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->date_crm) ? $item->date_crm : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->updated_date) ? $item->updated_date : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->status_crm) ? $item->status_crm : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->updated_date) ? $item->updated_date : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->order_created_date) ? $item->order_created_date : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->order_shipped_date) ? $item->order_shipped_date : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->order_updated_date) ? $item->order_updated_date : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->order_canceled_date) ? $item->order_canceled_date : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->coupon_code) ? $item->coupon_code : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->coupon_delivery_code) ? $item->coupon_delivery_code : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->leader) ? $item->leader : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->seller) ? $item->seller : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->date_time) ? $item->date_time : 0 }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->date_time) ? $item->date_time * 24 : 0 }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->product_code) ? $item->product_code : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->product_name) ? $item->product_name : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->product_type) ? $item->product_type : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">{{ !empty($item->unit) ? $item->unit : '' }}
                    </td>
                    <td style="border: 1px solid #777; text-align: left">{{ !empty($item->qty) ? $item->qty : 0 }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->product_real_price) ? $item->product_real_price : 0 }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->special_percentage) ? $item->special_percentage : 0 }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->price_CK) ? $item->price_CK : 0 }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->product_price) ? $item->product_price : 0 }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->total_price_product) ? $item->total_price_product : 0 }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->ship_fee_customer) ? $item->ship_fee_customer : 0 }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->ship_fee_shop) ? $item->ship_fee_shop : 0 }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->ship_fee_real) ? $item->ship_fee_real : 0 }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->ship_fee_total) ? $item->ship_fee_total : 0 }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->discount_coupon) ? $item->discount_coupon : 0 }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->discount_fee_ship) ? $item->discount_fee_ship : 0 }}</td>
                    <!-- <td style="border: 1px solid #777; text-align: left">{{ !empty($item->order_CK) ? $item->order_CK : 0 }}</td> -->
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->order_price_CK) ? $item->order_price_CK : 0 }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->total_price) ? $item->total_price : 0 }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->promotion_code) ? $item->promotion_code : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->promotion_name) ? $item->promotion_name : '' }}</td>
                    <!-- <td style="border: 1px solid #777; text-align: left">{{ !empty($item->item_product_code) ? $item->item_product_code : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">{{ !empty($item->item_product_name) ? $item->item_product_name : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">{{ !empty($item->item_product_qty) ? $item->item_product_qty : '' }}</td> -->
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->order_channel) ? $item->order_channel : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->order_channel) ? $item->order_channel : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->payment_method) ? $item->payment_method : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->payment_code) ? $item->payment_code : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->customer_bank) ? $item->customer_bank : '' }}</td>
                    @if ($data['role_id'] == 1 || ($data['role_id'] != 1 && $data['is_ac'] == 1) || $data['role_id'] == 9)
                        <td style="border: 1px solid #777; text-align: left">
                            {{ !empty($item->customer_bank_code) ? $item->customer_bank_code : '' }}</td>
                    @endif
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->virtual_account_number) ? $item->virtual_account_number : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->payment_status) ? $item->payment_status : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->shipping_method_name) ? $item->shipping_method_name : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->shipping_method_code) ? $item->shipping_method_code : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->lading_method) ? $item->lading_method : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->shipping_order_status) ? $item->shipping_order_status : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->status) ? $item->status : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->cancel_reason) ? $item->cancel_reason : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">{{ '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->invoice_code) ? $item->invoice_code : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->invoice_company_name) ? $item->invoice_company_name : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->invoice_tax) ? $item->invoice_tax : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->invoice_company_address) ? $item->invoice_company_address : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">{{ '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">{{ '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->tax_product) ? $item->tax_product : 0 }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->product_specification) ? $item->product_specification : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">{{ !empty($item->age) ? $item->age : '' }}
                    </td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->capacity) ? $item->capacity : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->expiry) ? $item->expiry : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->intrustry) ? $item->intrustry : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->cad_code) ? $item->cad_code : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->manufacture) ? $item->manufacture : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">{{ !empty($item->cat) ? $item->cat : '' }}
                    </td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->sub_cat) ? $item->sub_cat : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">{{ !empty($item->brand) ? $item->brand : '' }}
                    </td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->brandy) ? $item->brandy : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->cad_code_sub_cat) ? $item->cad_code_sub_cat : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->cad_code_brand) ? $item->cad_code_brand : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->cad_code_brandy) ? $item->cad_code_brandy : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->division) ? $item->division : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->source) ? $item->source : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->packing) ? $item->packing : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->p_sku) ? $item->p_sku : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->p_sku_name) ? $item->p_sku_name : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->sku_standard) ? $item->sku_standard : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->p_type) ? $item->p_type : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->p_attribute) ? $item->p_attribute : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->p_variant) ? $item->p_variant : '' }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->total_weight) ? $item->total_weight : 0 }}</td>
                    <td style="border: 1px solid #777; text-align: left">
                        {{ !empty($item->total_km) ? $item->total_km : 0 }}</td>
                    <!-- <td style="border: 1px solid #777; text-align: left">{{ !empty($item->cad_code_brandy) ? $item->cad_code_brandy : '' }}</td> -->
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
