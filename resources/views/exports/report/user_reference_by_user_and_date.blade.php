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
        <th colspan="4" style="text-align: center;">BÁO CÁO DOANH SỐ NHÓM ĐỐI TƯỢNG THEO NGƯỜI GIỚI THIỆU</th>
    </tr>
    <tr>
        <th colspan="4" style="text-align: center;">Đối tượng: {{$user->name}}</th>
    </tr>
    <tr>
        <th colspan="4" style="text-align: center;">ID: {{ $user->id }}</th>
    </tr>
    <tr>
        <th colspan="4" style="text-align: center;">Doanh số từ ngày: {{date("d/m/Y", strtotime($from))}} đến {{date("d/m/Y", strtotime($to))}}</th>
    </tr>
    <tr class="header-table">
        <th>STT</th>
        <th>Họ Tên</th>
        <th>Số điện thoại</th>
        <th>Doanh số</th>
    </tr>
    </thead>
    <tbody>
    @php $count = 1 @endphp
    @if(!empty($userReference->grandChildrenWithSales))
        @foreach($userReference->grandChildrenWithSales as $grandChildren)
            <tr>
                <td>{{ $count }}</td>
                <td>{{ $grandChildren->userWithTotalSales->name }}</td>
                <td>{{ $grandChildren->userWithTotalSales->phone }}</td>
                <td>{{ $grandChildren->userWithTotalSales->orders->sum('original_price') }}</td>
            </tr>
            @php $count += 1 @endphp
            @if(!$grandChildren->grandChildrenWithSales->isEmpty())
                @foreach($grandChildren->grandChildrenWithSales as $item)
                    <tr>
                        <td>{{ $count }}</td>
                        <td>{{ $item->userWithTotalSales->name }}</td>
                        <td>{{ $item->userWithTotalSales->phone }}</td>
                        <td>{{ $item->userWithTotalSales->orders->sum('original_price') }}</td>
                    </tr>
                    @php $count += 1 @endphp
                @endforeach
            @endif
        @endforeach
    @endif
    </tbody>
</table>