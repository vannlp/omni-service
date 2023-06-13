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
        <th style="text-align: left;font-weight: bold; font-size: 20px">Danh sách tài khoản</th>
    </tr>
    <tr>
        <th colspan="1" style="font-size: 9px">Ngày tháng: {{date("d/m/Y", time())}}</th>
    </tr>
    <tr class="header-table">
        <th style="text-align: left; font-weight: bold;">STT</th>
        <th style="text-align: left; font-weight: bold;">Mã người dùng</th>
        <th style="text-align: left; font-weight: bold;">Họ và tên</th>
        <th style="text-align: left; font-weight: bold;">Số điện thoại </th>
        <th style="text-align: left; font-weight: bold;">Email </th>
        <th style="text-align: left; font-weight: bold;">Loại tài khoản</th>
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
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['type']) ? $item['type'] : ''}}</td>

                </tr>
        @endforeach
    @endif
    </tbody>
</table>