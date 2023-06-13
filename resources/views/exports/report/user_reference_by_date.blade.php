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
        <th colspan="7" style="text-align: center;">DANH SÁCH GIỚI THIỆU</th>
    </tr>
    <tr>
        <th colspan="7" style="text-align: center;">Từ ngày {{date("d/m/Y", strtotime($from))}} đến {{date("d/m/Y", strtotime($to))}}</th>
    </tr>
    <tr class="header-table">
        <th>STT</th>
        <th>Họ Tên</th>
        <th>Số điện thoại</th>
        <th>Tổng doanh số</th>
        <th>Cấp độ</th>
        <th>Giới thiệu tài khoản</th>
        <th>Ngày tham gia hệ thống</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($userReferences) && !$userReferences->isEmpty())
        @foreach($userReferences as $key => $item)
            <tr>
                <td>{{ $key+1 }}</td>
                <td>{{ $item->userWithTotalSales->name }}</td>
                <td>{{ $item->userWithTotalSales->phone }}</td>
                <td>{{ $item->userWithTotalSales->orders->sum('original_price') }}</td>
                <td>{{ $item->level }}</td>
                <td>{{ $item->countGrandChildren() }}</td>
                <td>{{ date('d-m-Y', strtotime($item->userWithTotalSales->created_at)) }}</td>
            </tr>
        @endforeach
    @endif
</table>
