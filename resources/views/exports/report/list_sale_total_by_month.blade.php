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
        <th colspan="8" style="text-align: center;font-weight: bold;">BÁO CÁO ĐƠN HÀNG TỔNG HỢP THEO THÁNG</th>
    </tr>
    <tr>
        <th style="font-size: 9px">TỪ THÁNG: {{date("m/Y", strtotime($from ?? time()))}}</th>
        <th style="font-size: 9px">ĐẾN THÁNG: {{date("m/Y", strtotime($to ?? time()))}}</th>
    </tr>
    <tr class="header-table">
        <th style="text-align: left; font-weight: bold;">Tháng</th>
        <th style="text-align: left; font-weight: bold;">Số đơn hàng</th>
        <th style="text-align: left; font-weight: bold;">Doanh thu</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $key => $item)
            <tr>
                <td style="border: 1px solid #777; text-align: right">{{!empty($item['month']) ? $item['month'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right">{{ !empty($item['total_order']) ? $item['total_order'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right">{{!empty($item['total_price']) ? number_format($item['total_price']) : ''}}</td>
            </tr>
        @endforeach
    @endif
    </tbody>
</table>