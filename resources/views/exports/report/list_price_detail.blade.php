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
        <th colspan="5" style="text-align: center;">DANH SÁCH BẢNG GIÁ</th>
    </tr>
    <tr class="header-table">
        <th>STT</th>
        <th>Mã sản phẩm</th>
        <th>Tên sản phẩm</th>
        <th>Loại giá</th>
        <th>Từ ngày</th>
        <th>Đến ngày</th>
        <th>Đơn vị</th>
        <th>Giá</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $item)
            <tr>
                @foreach($item as $key => $value)
                    @if(!empty($key == 'price'))
                        <td style="border: 1px solid #777; text-align: right;">{{!empty($value) ? number_format($value) : ''}}</td>
                    @else
                        <td style="border: 1px solid #777">{{!empty($value) ? $value : ''}}</td>
                    @endif
                @endforeach
            </tr>
        @endforeach
    @endif
    </tbody>
</table>