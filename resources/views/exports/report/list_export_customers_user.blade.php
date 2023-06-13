
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
        <th colspan="20" style="text-align: center;font-weight: bold;">DANH SÁCH KHÁCH HÀNG</th>
    </tr>
    {{--    <tr>--}}
    {{--        <th colspan="2" style="font-size: 9px">TỪ : {{!empty($from) ? date("d/m/Y", strtotime($from ?? time())) : ''}}</th>--}}
    {{--        <th colspan="2" style="font-size: 9px">ĐẾN : {{!empty($to) ? date("d/m/Y", strtotime($to ?? time())) : ''}}</th>--}}
    {{--    </tr>--}}
    <tr class="header-table">
        <th colspan="2" style="text-align: left; font-weight: bold;">Tên khách hàng</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">SDT khách hàng</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Email khách hàng</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Mã khách hàng</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Loại khách hàng</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Mã nhóm khách hàng</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Tên nhóm khách hàng</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Tên tài khoản ngân hàng</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Số tài khoản ngân hàng</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Chi nhánh ngân hàng</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Mã số thuế</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Trạng thái</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Quyền</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Tên quyền</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Mã người giới thiệu</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Tên người giới thiệu</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">SĐT người giới thiệu</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Mã nhà phân phôi</th>
        <th colspan="2" style="text-align: left; font-weight: bold;">Tên nhà phân phôi</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $key => $item)
            <tr>
                <td colspan="2" style="border: 1px solid #777; text-align: left">{{!empty($item['name']) ? $item['name'] : ''}}</td>
                <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['phone']) ? $item['phone' ] : ''}}</td>
                <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['email']) ? $item['email'] : ''}}</td>
                <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['code']) ? $item['code'] : ''}}</td>
                <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['type']) ? $item['type'] : ''}}</td>
                <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['group_code']) ? $item['group_code'] : ''}}</td>
                <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['group_name']) ? $item['group_name'] : ''}}</td>
                <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['bank_account_name']) ? $item['bank_account_name'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right">{{!empty($item['bank_account_number']) ? $item['bank_account_number'] : ''}}</td>
                <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['bank_branch']) ? $item['bank_branch'] : ''}}</td>
                <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['tax']) ? $item['tax'] : ''}}</td>
                @if($item['account_status'] == 'pending')
                    <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['account_status']) ? 'Đang chờ xử lý' : ''}}</td>
                @else
                    <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['account_status']) ? 'Đã duyệt' : ''}}</td>
                @endif
                <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty(object_get($item, 'role.code', null)) ? object_get($item, 'role.code', null) : ''}}</td>
                <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty(object_get($item, 'role.name', null)) ? object_get($item, 'role.name', null) : ''}}</td>
                <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['reference_code']) ? $item['reference_code'] : ''}}</td>
                <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['reference_name']) ? $item['reference_name'] : ''}}</td>
                <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['reference_phone']) ? $item['reference_phone'] : ''}}</td>
                <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['distributor_code']) ? $item['distributor_code'] : ''}}</td>
                <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['distributor_name']) ? $item['distributor_name'] : ''}}</td>
            </tr>
        @endforeach
    @endif
    </tbody>
</table>