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
        <th style="text-align: center;font-size: 24px;"><b>BÁO CÁO DOANH THU CHI TIẾT</b></th>
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
        <th>Mã sản phẩm</th>
        <th>Tên sản phẩm</th>
        <th>Đơn vị tính</th>
        <th>Số lượng</th>
        <th>Mã đơn hàng</th>
        <th>Mã khách hàng</th>
        <th>Tên khách hàng</th>
        <th>Loại khách hàng</th>
        <th>Mã nhân viên</th>
        <th>Tên nhân viên</th>
        <th>Ngày</th>
        <th>Loại CTKM</th>
        <th>Khách trả phí vận chuyển</th>
        <th>Shop trả phí vận chuyển</th>
        <th>Tổng phí vận chuyển</th>
        <th>Doanh thu</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $key => $item)
            <tr>
                <td style="border: 1px solid #777">{{ !empty($item['product_code']) ? $item['product_code'] : ''}}</td>
                <td style="border: 1px solid #777">{{!empty($item['product_name']) ? $item['product_name'] : ''}}</td>
                <td style="border: 1px solid #777">{{!empty($item['unit_name']) ? $item['unit_name'] : ''}}</td>
                <td style="border: 1px solid #777">{{!empty($item['qty']) ? $item['qty'] : ''}}</td>
                <td style="border: 1px solid #777">{{!empty($item['order_code']) ? $item['order_code'] : ''}}</td>
                <td style="border: 1px solid #777">{{!empty($item['codeUser']) ? $item['codeUser'] : ''}}</td>
                <td style="border: 1px solid #777">{{!empty($item['nameCustomer']) ? str_replace(['\b','?',''], '', $item['nameCustomer']) : ''}}</td>
                <td style="border: 1px solid #777">{{!empty($item['group_name']) ? $item['group_name'] : ''}}</td>
                <td style="border: 1px solid #777">{{!empty($item['staffCode']) ?str_replace(['\b','?',''], '', $item['staffCode']) : ''}}</td>
                <td style="border: 1px solid #777">{{!empty($item['staffName']) ? str_replace(['\b','?',''], '', $item['staffName']) : ''}}</td>
                <td style="border: 1px solid #777">{{!empty($item['order_created_at']) ? date("d/m/Y", strtotime($item['order_created_at'])) : ''}}</td>
                <td style="border: 1px solid #777">{{!empty($item['promotion']) ? $item['promotion'] : ''}}</td>
                <td style="border: 1px solid #777">{{!empty($item['ship_fee_user']) ? $item['ship_fee_user'] : 0}}</td>
                <td style="border: 1px solid #777">{{!empty($item['ship_fee_shop']) ? $item['ship_fee_shop'] : 0}}</td>
                <td style="border: 1px solid #777">{{!empty($item['ship_fee']) ? $item['ship_fee'] : 0}}</td>
                <td style="border: 1px solid #777">{{!empty($item['total']) ? number_format($item['total']) : ''}}</td>
            </tr>
        @endforeach
    @endif
    </tbody>
</table>