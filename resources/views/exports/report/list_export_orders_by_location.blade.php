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
        <th style="text-align: left;font-weight: bold; font-size: 20px">BÁO CÁO KHÁCH HÀNG - DOANH SỐ</th>
    </tr>
    <tr>
        <th style="font-size: 10px;font-weight: bold">TỪ : {{!empty($from) ? date("d/m/Y", strtotime($from ?? time())) : ''}}</th>
        <th style="font-size: 10px;font-weight: bold">ĐẾN : {{!empty($to) ? date("d/m/Y", strtotime($to ?? time())) : ''}}</th>
    </tr>
    <tr class="header-table">
        <th style="text-align: left; font-weight: bold;">Mã khách hàng</th>
        <th style="text-align: left; font-weight: bold;">Tên khách hàng</th>
        <th style="text-align: left; font-weight: bold;">Số điện thoại khách hàng</th>
        <th style="text-align: left; font-weight: bold;">Email khách hàng</th>
        @if($data['is_district'] == 1)<th style="text-align: left; font-weight: bold;">Quận/Huyện</th>@endif
        <th style="text-align: left; font-weight: bold;">Tỉnh/TP</th>
        <th style="text-align: left; font-weight: bold;">Tổng doanh thu</th>
{{--        <th style="text-align: left; font-weight: bold;">Đơn hàng đã hoàn thành</th>--}}
{{--        <th style="text-align: left; font-weight: bold;">Doanh số tích lũy</th>--}}
{{--        <th style="text-align: left; font-weight: bold;">Giá trị đơn hàng trung bình</th>--}}
{{--        <th style="text-align: left; font-weight: bold;">Số lượng item trung bình</th>--}}
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data['data'] as $key => $item)
            @foreach($item['details'] as $detail)
                <tr>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($detail['code']) ? $detail['code'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($detail['name']) ? $detail['name'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($detail['phone']) ? $detail['phone'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($detail['email']) ? $detail['email'] : ''}}</td>
                    @if($data['is_district'] == 1)<td style="border: 1px solid #777; text-align: left">{{!empty($item['district_name']) ? $item['district_name'] : ''}}</td>@endif
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['city_name']) ? $item['city_name'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($detail['totalPrice']) ? $detail['totalPrice'] : 0}}</td>
{{--                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['total_completed']) ? $item['total_completed'] : 0}}</td>--}}
{{--                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['saving']) ? $item['saving'] : 0}}</td>--}}
{{--                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['orderAverage']) ? $item['orderAverage'] : 0}}</td>--}}
{{--                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['qtyOrderAverage']) ? $item['qtyOrderAverage'] : 0}}</td>--}}
                </tr>
            @endforeach
        @endforeach
    @endif
    </tbody>
</table>