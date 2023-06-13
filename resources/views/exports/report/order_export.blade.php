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
        <th colspan="5" style="text-align: center; font-size: 18px;"><b>BÁO CÁO DOANH THU</b></th>
    </tr>
    <tr>
        <th>TỪ NGÀY</th>
        <th>{{date("d/m/Y", strtotime($from ?? time()))}}</th>

    </tr>
    <tr>
        <th>ĐẾN NGÀY</th>
        <th>{{date("d/m/Y", strtotime($to ?? time()))}}</th>
    </tr>
    <tr class="header-table">
        <th  colspan="3" style="border: 1px solid #777; text-align: right">Tổng số lượng đơn hàng</th>
        <th  colspan="3" style="border: 1px solid #777; text-align: right">Tổng giá trị đơn hàng(VND)</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        <tr>
        <td colspan="3" style="border: 1px solid #777; text-align: right">
            {{!empty($data) ? count($data) : ''}}
        </td>
        <td colspan="3" style="border: 1px solid #777; text-align: right">
            {{!empty($total) ? number_format($total) : ''}}
        </td>
        </tr>
    @endif
    </tbody>
</table>