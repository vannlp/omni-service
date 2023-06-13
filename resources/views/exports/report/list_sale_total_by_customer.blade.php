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
        <th colspan="9" style="text-align: center;font-weight: bold;">BÁO CÁO DOANH THU THEO KHÁCH HÀNG</th>
    </tr>
    <tr>
        <th>TỪ NGÀY</th>
        <th>{{date("d/m/Y", strtotime($from ?? time()))}}</th>

    </tr>
    <tr>
        <th>ĐẾN NGÀY</th>
        <th>{{date("d/m/Y", strtotime($to ?? time()))}}</th>
    </tr>
    <tr class="header-table">
        <th style="text-align: left; font-weight: bold;">Mã khách hàng</th>
        <th style="text-align: left; font-weight: bold;">Tên khách hàng</th>
        <th style="text-align: center; font-weight: bold;">Số điện thoại</th>
        <th style="text-align: center; font-weight: bold;">Số đơn hàng</th>
        <th style="text-align: right; font-weight: bold;">Doanh thu</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $key => $item)
            <tr>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['customerCode']) ? $item['customerCode'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: left">{{!empty($item['customerName']) ? str_replace(['\b','?',''], '', $item['customerName']) : ''}}</td>
                <td style="border: 1px solid #777; text-align: center">{{ !empty($item['phone']) ? $item['phone'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: center">{{!empty($item['qty']) ? $item['qty'] : ''}}</td>
                <td style="border: 1px solid #777; text-align: right">{{!empty($item['total']) ? number_format($item['total']) : ''}}</td>
            </tr>
        @endforeach
    @endif
    </tbody>
</table>