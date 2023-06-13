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
        <th colspan="7" style="text-align: center;font-weight: bold; font-size: 16px">Danh sách đối tác</th>
    </tr>
    <tr>
       <th colspan="1" style="font-size: 12px">Ngày: {{date("d/m/Y", time())}}</th>
    </tr>
    <tr class="header-table">
        <th colspan="1" style="text-align: left; font-weight: bold;">#</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Tên đối tác</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Số điện thoại</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Email</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">CMND/CCCD</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Ảnh CMND/CCCD</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Vai trò</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Mã số thuế</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Địa chỉ</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Tên ngân hàng</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Tên chủ tài khoản</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Số tài khoản</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Chi nhánh</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Gia đình bạn có sử dụng sản phẩm của Nutifood không?</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Bạn dự định bán các sản phẩm của Nutifood thông qua kênh/ khu vực nào?</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Bạn có tham gia là thành viên trên các kênh online/ nhóm chợ online không</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Các kênh bán hàng online/ nhóm chợ online mà bạn tham gia</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Bạn có đồng ý bán duy nhất các sản phẩm Nutifood không?</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Trạng thái</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Ngày tạo</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Ngày cập nhật</th>
        <th colspan="1" style="text-align: left; font-weight: bold;">Ngày xóa</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $key => $item)
            <tr>
                <td colspan="1" style="border: 1px solid #777; text-align: left">{{$key +1}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right">{{ !empty($item['name']) ? $item['name'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right">{{!empty($item['phone']) ? $item['phone'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right">{{!empty($item['email']) ? $item['email'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: left">{{!empty($item['id_number']) ? $item['id_number'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: left">
                    <?php
                        if(!empty($item['id_images'])){
                            $img = explode(",", $item['id_images']);
                            for ($i=0; $i < count($img); $i++) { 
                                echo env('GET_FILE_URL').$img[$i]. "<br>";
                            }
                        }  
                    ?>
                </td>
                <td colspan="1" style="border: 1px solid #777; text-align: right">{{ !empty($item['cooperate']) ? $item['cooperate'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right">{{!empty($item['tax']) ? $item['tax'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right">{{!empty($item['address']) ? $item['address'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: left">{{!empty($item['bank_name']) ? $item['bank_name'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right">{{ !empty($item['bank_account_name']) ? $item['bank_account_name'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right">{{!empty($item['bank_account_number']) ? $item['bank_account_number'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right">{{!empty($item['bank_branch']) ? $item['bank_branch'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: left">{{!empty($item['anwser_1']) ? $item['anwser_1'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right">{{ !empty($item['anwser_2']) ? $item['anwser_2'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right">{{!empty($item['anwser_3']) ? $item['anwser_3'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right">{{!empty($item['anwser_4']) ? $item['anwser_4'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: left">{{!empty($item['anwser_5']) ? $item['anwser_5'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right">
                    <?php
                        if(empty($item['deleted'])){
                            echo "Đã xóa";
                        }else {
                            echo "Đang hoạt động";
                        }
                    ?>
                </td>
                <td colspan="1" style="border: 1px solid #777; text-align: right">{{!empty($item['created_at']) ? $item['created_at'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right">{{!empty($item['updated_at']) ? $item['updated_at'] : ''}}</td>
                <td colspan="1" style="border: 1px solid #777; text-align: right">{{!empty($item['deleted_at']) ? $item['deleted_at'] : ''}}</td>
            </tr>
        @endforeach
    @endif
    </tbody>
</table>