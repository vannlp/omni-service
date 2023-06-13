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
        <th colspan="11" style="text-align: center;">BẢN KÊ ĐƠN HÀNG</th>
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
        <th>SỐ ĐƠN HÀNG</th>
        <th>NGÀY ĐƠN HÀNG</th>
        <th>TRẠNG THÁI ĐƠN HÀNG</th>
        <th>KHÁCH HÀNG</th>
        <th>MÃ SẢN PHẨM</th>
        <th>TÊN SẢN PHẨM</th>
        <th>ĐVT</th>
        <th>SỐ LƯỢNG</th>
        <th>ĐƠN GIÁ</th>
        <th>THÀNH TIỀN</th>
    </tr>
    </thead>
    <tbody>
    @php $count = 1 @endphp
    @if(!empty($orders) && !$orders->isEmpty())
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td><b>{{ number_format($orders->sum('total_price')) }}</b></td>
        </tr>
        @foreach($orders as $order)
            @foreach($order->details as $key => $detail)
                <tr>
                    <td>{{ $count }}</td>
                    <td>{{ $order->code }}</td>
                    <td>{{ date('d-m-Y', strtotime($order->created_at)) }}</td>
                    <td>{{ \Illuminate\Support\Arr::get($order->status, 'name') }}</td>
                    <td>{{ \Illuminate\Support\Arr::get($order->customer, 'name', \Illuminate\Support\Arr::get($order->customer, 'phone')) }}</td>
                    <td>{{ \Illuminate\Support\Arr::get($detail->product, 'code') }}</td>
                    <td>{{ \Illuminate\Support\Arr::get($detail->product, 'name') }}</td>
                    <td>{{ \Illuminate\Support\Arr::get($detail->product, 'unit.name') }}</td>
                    <td>{{ $detail->qty }}</td>
                    <td>{{ number_format($detail->price) }}</td>
                    <td>{{ number_format($detail->total) }}</td>
                </tr>
                @php $count += 1 @endphp
            @endforeach
        @endforeach
    @endif
</table>
