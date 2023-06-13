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
        <th colspan="8" style="text-align: center;">BÁO CÁO DOANH SỐ ĐỐI TÁC</th>
    </tr>
    <tr>
        <th></th>
        <th>Đối tác</th>
        <th>{{$name}}</th>
        <th></th>
        <th></th>
        <th>Từ ngày</th>
        <th>{{date("d/m/Y", strtotime($from ?? time()))}}</th>
    </tr>
    <tr>
        <th></th>
        <th>Số điện thoại</th>
        <th>{{$phone}}</th>
        <th></th>
        <th></th>
        <th>Đến ngày</th>
        <th>{{date("d/m/Y", strtotime($to ?? time()))}}</th>
    </tr>
    <tr>
        <th></th>
        <th>Email</th>
        <th>{{$email}}</th>
    </tr>
    <tr class="header-table">
        <th>STT</th>
        <th>Tên sản phẩm, dịch vụ</th>
        <th>Loại</th>
        <th>Mã đơn hàng</th>
        <th>Giá</th>
        <th>Giá giảm</th>
        <th>Số lượng</th>
        <th>Thành tiền</th>
        <th>Ngày</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($dataTable))
        @foreach($dataTable as $item)
            <tr>
                @foreach($item as $value)
                    <td style="border: 1px solid #777">{{!empty($value) ? $value : ''}}</td>
                @endforeach
            </tr>
        @endforeach
    @endif
    </tbody>
    <tr class="header-table">
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th>Tổng doanh số</th>
        <th>{{$total}}</th>
        <th></th>
    </tr>
</table>