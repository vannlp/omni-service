<style>
    thead tr th {
        vertical-align: middle;
    }

    .header-table th {
        text-align: center;
        /*border: 1px solid #777;*/
    }
    .border td {
        border-bottom: #0d0d0d solid 2px;
    }
</style>
<table>
    @foreach ($orders as $order)
        <thead>
            <tr>
                <th colspan="4" style="text-align: center; height: 15px; font-size: 15px">
                    {{ \Illuminate\Support\Arr::get($order, 'store.name') }}
                </th>
            </tr>
        <tr>
            <td colspan="4" style="height: 40px; text-align: center; font-size: 9px">
                <br>
                {!! Illuminate\Support\Arr::get($order, 'store.address') .
                    ' ' . Illuminate\Support\Arr::get($order, 'store.ward_type') .
                    ' ' . Illuminate\Support\Arr::get($order, 'store.ward_name')
                !!}
                <br>
                {!!
                    Illuminate\Support\Arr::get($order, 'store.district_type') .
                    ' ' . Illuminate\Support\Arr::get($order, 'store.district_name') .
                    ' ' . Illuminate\Support\Arr::get($order, 'store.city_type') .
                    ' ' . Illuminate\Support\Arr::get($order, 'store.city_name')
                !!}
                <br>
                SĐT: {{ Illuminate\Support\Arr::get($order, 'store.contact_phone') }}
            </td>
        </tr>
        </thead>
    @endforeach
    <tbody>
    @foreach ($orders as $order)
        <tr class="border">
            <td colspan="1" style="width: 10px; font-size: 10px; border-width:5px;border-style:ridge;">Khách Hàng: </td>
            <td colspan="3" style="font-size: 8px; text-transform: capitalize; text-align: right">
                {{ \Illuminate\Support\Arr::get($order->customer, 'name', \Illuminate\Support\Arr::get($order->customer, 'phone')) }}
            </td>
        </tr>
        <tr class="border">
            <td colspan="1" style="width: 10px; font-size: 10px">Số HĐ:  </td>
            <td colspan="3" style="font-size: 8px; text-transform: capitalize; text-align: right">
                {{ $order->code }}
            </td>
        </tr>
        <tr class="border">
            <td colspan="1" style="width: 10px; font-size: 10px">Ngày:  </td>
            <td colspan="3" style="font-size: 8px; text-transform: capitalize; text-align: right">
                {{ date("Y-m-d H:i", time()) }}
            </td>
        </tr>
        <tr>iá

        </tr>
        <tr  style="text-decoration: underline;" class="header-table border">
            <td colspan="1" style="font-weight: bold;padding-bottom: 3px; text-align: center;font-size: 7px;width: 11px">Mặt Hàng</td>
            <td colspan="1" style="font-weight: bold;padding-bottom: 3px; text-align: center;font-size: 7px;width: 2px">SL</td>
            <td colspan="1" style="font-weight: bold;padding-bottom: 3px; text-align: center;font-size: 7px;width: 7px">Đ.Giá</td>
            <td colspan="1" style="font-weight: bold;padding-bottom: 3px;text-align: center;font-size: 7px">Thành Tiền</td>
        </tr>
    @endforeach
    @php $total_detail = 0 @endphp
    @php $cout_qty = 0 @endphp
    @if(!empty($orders) && !$orders->isEmpty())
        @foreach($orders as $order)
            @foreach($order->details as $key => $detail)ía
                <tr class="border">
                    <td colspan="1" style="height: 30px;text-align: left ;font-size: 8px;width: 11px; wrap-text: true;"><br>{{ \Illuminate\Support\Arr::get($detail->product, 'name') }}</td>
                    <td colspan="1" style="text-align: center ;font-size: 8px;width: 2px">{{ \Illuminate\Support\Arr::get($detail, 'qty') }}</td>
                    <td colspan="1" style="text-align: right ;font-size: 8px;width: 7px">{{ number_format($detail->price) . ' đ'}}</td>
                    <td colspan="1" style="text-align: right ;font-size: 8px">{{ number_format($detail->total) . ' đ'}}</td>
                </tr>
                @php
                    $total_detail += $detail->total;
                    $cout_qty += $detail->qty;
                @endphp
            @endforeach
            <br>
            <tr class="border">
                <td colspan="1" style="text-align: left;font-size: 8px;width: 11px">Tổng:</td>
                <td colspan="1" style="text-align: center;font-size: 8px;width: 2px">{{ $cout_qty }}</td>
                <td colspan="1" style="text-align: right;font-size: 8px;width: 7px"></td>
                <td colspan="1" style="text-align: right;font-size: 8px;">{{ number_format($total_detail) . ' đ' }}</td>
            </tr>
            <tr class="border">
                <td colspan="1" style="text-align: left;font-size: 8px;width: 11px">Tiền khách trả:</td>
                <td colspan="1" style="text-align: center;font-size: 8px;width: 2px"></td>
                <td colspan="1" style="text-align: right;font-size: 8px;width: 7px"></td>
                <td colspan="1" style="text-align: right;font-size: 8px;">{{ number_format($customers_pay) . ' đ' }}</td>
            </tr>
            <tr class="border">
                <td colspan="1" style="text-align: left;font-size: 8px;width: 11px">Tiền thừa:</td>
                <td colspan="1" style="text-align: center;font-size: 8px;width: 2px"></td>
                <td colspan="1" style="text-align: right;font-size: 8px;width: 7px"></td>
                <td colspan="1" style="text-align: right;font-size: 8px;">{{ number_format($customers_pay - $total_detail) . ' đ' }}</td>
            </tr>
        @endforeach
    @endif
    <tr>
        <th olspan="4"></th>
    </tr>
    <tr>
        <th colspan="4" style="text-align: center;">CẢM ƠI QUÝ KHÁCH</th>
    </tr>
    <tr>
        <th colspan="4" style="text-align: center">HẸN GẶP LẠI</th>
    </tr>
    </tbody>
</table>
