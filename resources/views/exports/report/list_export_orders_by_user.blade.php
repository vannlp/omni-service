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
        <th style="text-align: left; font-weight: bold;">STT</th>
        <th style="text-align: left; font-weight: bold;">Mã khách hàng</th>
        <th style="text-align: left; font-weight: bold;">Tên khách hàng</th>
        <th style="text-align: left; font-weight: bold;">Số điện thoại khách hàng</th>
        <th style="text-align: left; font-weight: bold;">Email khách hàng</th>
        <th style="text-align: left; font-weight: bold;">Xã/Phường</th>
        <th style="text-align: left; font-weight: bold;">Quận/Huyện</th>
        <th style="text-align: left; font-weight: bold;">Tỉnh/TP</th>
        <th style="text-align: left; font-weight: bold;">Tổng đơn hàng</th>
        <th style="text-align: left; font-weight: bold;">Đơn hàng đã hoàn thành</th>
        <th style="text-align: left; font-weight: bold;">Doanh số tích lũy</th>
        <th style="text-align: left; font-weight: bold;">Giá trị đơn hàng trung bình</th>
        <th style="text-align: left; font-weight: bold;">Số lượng item trung bình</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $key => $item)
                <tr>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['stt']) ? $item['stt'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['code']) ? $item['code'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['name']) ? $item['name'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['phone']) ? $item['phone'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['email']) ? $item['email'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['ward']) ? $item['ward'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['district']) ? $item['district'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['city']) ? $item['city'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['total_order']) ? $item['total_order'] : 0}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['total_completed']) ? $item['total_completed'] : 0}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['saving']) ? $item['saving'] : 0}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['orderAverage']) ? $item['orderAverage'] : 0}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['qtyOrderAverage']) ? $item['qtyOrderAverage'] : 0}}</td>
                </tr>
        @endforeach
    @endif
    </tbody>
</table>