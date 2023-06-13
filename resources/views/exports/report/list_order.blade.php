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
        <th colspan="5" style="text-align: center;">BÁO CÁO BÁN HÀNG THEO NGÀY</th>
    </tr>
    <tr>
        <th></th>
        <th>TỪ NGÀY</th>
        <th>{{date("d/m/Y", strtotime($from ?? time()))}}</th>
        <th>ĐẾN NGÀY</th>
        <th>{{date("d/m/Y", strtotime($to ?? time()))}}</th>
    </tr>
    <tr class="header-table">
        <th>STT</th>
        <th>Tên sản phẩm, dịch vụ</th>
        <th>Số lượng</th>
        <th>Đơn giá</th>
        <th>Giá giảm</th>
        <th>Thành tiền</th>
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
        <th>Tổng doanh số</th>
        <th>{{$total}}</th>
    </tr>
</table>