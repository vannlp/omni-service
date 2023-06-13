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
    <tr class="header-table">
        <th>STT</th>
        <th>MÃ SẢN PHẨM</th>
        <th>TÊN SẢN PHẨM</th>
        <th>KHO</th>
        <th>ĐƠN VỊ</th>
        <th>LÔ</th>
        <th>SỐ LƯỢNG</th>
        <th>ĐƠN GIÁ</th>
        <th>TỔNG TIỀN</th>
        <th>GHI CHÚ</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $item)
            <tr>
                <td style="border: 1px solid #777">{{ $item['key'] }}</td>
                <td style="border: 1px solid #777">{{ $item['product_code'] ?? '' }}</td>
                <td style="border: 1px solid #777">{{ $item['product_name'] ?? '' }}</td>
                <td style="border: 1px solid #777">{{ $item['warehouse_name'] ?? '' }}</td>
                <td style="border: 1px solid #777">{{ $item['unit_name'] ?? '' }}</td>
                <td style="border: 1px solid #777">{{ $item['batch_name'] ?? '' }}</td>
                <td style="border: 1px solid #777">{{ $item['quantity'] ?? 0 }}</td>
                <td style="border: 1px solid #777">{{ $item['price'] ?? 0 }}</td>
                <td style="border: 1px solid #777">{{ $item['total'] ?? 0 }}</td>
                <td style="border: 1px solid #777">{{ $item['note'] ?? '' }}</td>
            </tr>
        @endforeach
    @endif
    </tbody>
</table>