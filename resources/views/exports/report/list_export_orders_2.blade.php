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
        <th style="text-align: left;font-weight: bold; font-size: 20px">BÁO CÁO LOGISTICS</th>
    </tr>
    <tr>
        <th style="font-size: 10px;font-weight: bold">TỪ : {{!empty($from) ? date("d/m/Y", strtotime($from ?? time())) : ''}}</th>
        <th style="font-size: 10px;font-weight: bold">ĐẾN : {{!empty($to) ? date("d/m/Y", strtotime($to ?? time())) : ''}}</th>
    </tr>
    <tr class="header-table">
        <th style="text-align: left; font-weight: bold;">STT</th>
        <th style="text-align: left; font-weight: bold;">KHO XUẤT</th>
        <th style="text-align: left; font-weight: bold;">TỈNH/THÀNH</th>
        <th style="text-align: left; font-weight: bold;">QUẬN/HUYỆN</th>
        <th style="text-align: left; font-weight: bold;">PHƯỜNG/XÃ</th>
        <th style="text-align: left; font-weight: bold;">ĐỊA CHỈ</th>
        <th style="text-align: left; font-weight: bold;">MÃ ĐƠN HÀNG</th>
        <th style="text-align: left; font-weight: bold;">LOẠI ĐƠN HÀNG</th>
        <th style="text-align: left; font-weight: bold;">LÝ DO GIAO DỊCH</th>
        <th style="text-align: left; font-weight: bold;">MÃ NVC</th>
        <th style="text-align: left; font-weight: bold;">TÊN NVC</th>
        <th style="text-align: left; font-weight: bold;">GIỜ LÊN ĐƠN</th>
        <th style="text-align: left; font-weight: bold;">GIỜ XÁC NHẬN ĐƠN</th>
        <th style="text-align: left; font-weight: bold;">GIỜ XÁC NHẬN VC</th>
        <th style="text-align: left; font-weight: bold;">GIỜ XUẤT KHO</th>
        <th style="text-align: left; font-weight: bold;">GIỜ NVC NHẬN HÀNG</th>
        <th style="text-align: left; font-weight: bold;">GIỜ NVC GIAO HÀNG XONG</th>
        <th style="text-align: left; font-weight: bold;">MÃ HÀNG</th>
        <th style="text-align: left; font-weight: bold;">TÊN HÀNG</th>
        <th style="text-align: left; font-weight: bold;">NHÓM HÀNG</th>
        <th style="text-align: left; font-weight: bold;">ĐVT</th>
        <th style="text-align: left; font-weight: bold;">QUI CÁCH</th>
        <th style="text-align: left; font-weight: bold;">SỐ LƯỢNG</th>
        <th style="text-align: left; font-weight: bold;">SỐ KG</th>
        <th style="text-align: left; font-weight: bold;">DOANH SỐ (TRƯỚC VAT, SAU KHUYẾN MÃI)</th>
        <th style="text-align: left; font-weight: bold;">CƯỚC PHÍ VC (TRƯỚC VAT, SAU KHUYẾN MÃI)</th>

    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $key => $item)
            <tr>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['stt']) ? $item['stt'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['distributor_name']) ? $item['distributor_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['city_name']) ? $item['city_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['district_name']) ? $item['district_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['ward_name']) ? $item['ward_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['distributor_address']) ? $item['distributor_address'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['order_code']) ? $item['order_code'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['order_type']) ? $item['order_type'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['order_canceled_reason']) ? $item['order_canceled_reason'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['shipping_method_code']) ? $item['shipping_method_code'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['shipping_method_name']) ? $item['shipping_method_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['created_at']) ? $item['created_at'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['updated_date']) ? $item['updated_date'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['order_created_date']) ? $item['order_created_date'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['order_created_date']) ? $item['order_created_date'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['order_revice_date']) ? $item['order_revice_date'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['order_shipping_date']) ? $item['order_shipped_date'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['product_code']) ? $item['product_code'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['product_name']) ? $item['product_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['product_category_name']) ? $item['product_category_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['unit']) ? $item['unit'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['product_specification']) ? $item['product_specification'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['qty']) ? $item['qty'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['total_weight']) ? $item['total_weight'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['total_price']) ? $item['total_price'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['ship_fee_total']) ? $item['ship_fee_total'] : 0}}</td>
            </tr>
        @endforeach
    @endif
    </tbody>
</table>