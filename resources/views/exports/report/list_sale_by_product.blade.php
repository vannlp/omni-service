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
        <th colspan="12" style="text-align: center;font-weight: bold;">BÁO CÁO DOANH THU THEO SẢN PHẨM</th>
    </tr>
    <tr>
        <th style="font-size: 9px">TỪ THÁNG: {{date("d/m/Y", strtotime($from ?? time()))}}</th>
        <th style="font-size: 9px">ĐẾN THÁNG: {{date("d/m/Y", strtotime($to ?? time()))}}</th>
    </tr>
    <tr class="header-table">
        <th style="text-align: left; font-weight: bold;">Tên sản phẩm</th>
        <th style="text-align: left; font-weight: bold;">Mã sản phẩm</th>
        <th style="text-align: left; font-weight: bold;">Đơn vị tính</th>
        <th style="text-align: left; font-weight: bold;">Số lượng bán ra</th>
        <th style="text-align: left; font-weight: bold;">Doanh thu sản phẩm</th>
        <th style="text-align: left; font-weight: bold;">Đơn hàng xuất hiện</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $key => $item)
            <tr>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['product_name']) ? $item['product_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right">{{ !empty($item['product_code']) ? $item['product_code'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right">{{!empty($item['unit_name']) ? $item['unit_name'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right">{{!empty($item['total_qty']) ? $item['total_qty'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right">{{!empty($item['total_price']) ? number_format($item['total_price']) : ''}}</td>
                <td style="border: 1px solid #777; text-align: right">{{!empty($item['total_orders']) ? $item['total_orders'] : ''}}</td>
            </tr>
        @endforeach
    @endif
    </tbody>
</table>