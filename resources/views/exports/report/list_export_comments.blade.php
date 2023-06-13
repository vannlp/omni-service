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
        <th style="text-align: left;font-weight: bold; font-size: 20px">BÁO CÁO ĐÁNH GIÁ BÌNH LUẬN NUTISHOP</th>
    </tr>
    <tr>
        <th style="font-size: 10px;font-weight: bold">TỪ : {{!empty($from) ? date("d/m/Y", strtotime($from ?? time())) : ''}}</th>
        <th style="font-size: 10px;font-weight: bold">ĐẾN : {{!empty($to) ? date("d/m/Y", strtotime($to ?? time())) : ''}}</th>
    </tr>
    <tr class="header-table">
        <th style="text-align: left; font-weight: bold;">STT</th>
        <th style="text-align: left; font-weight: bold;">Mã sản phẩm</th>
        <th style="text-align: left; font-weight: bold;">Tên sản phẩm</th>
        <th style="text-align: left; font-weight: bold;">Tên khách hàng</th>
        <th style="text-align: left; font-weight: bold;">Nội dung</th>
        <th style="text-align: left; font-weight: bold;">Số sao đánh giá</th>
        <th style="text-align: left; font-weight: bold;">Tên đánh giá</th>
        <th style="text-align: left; font-weight: bold;">Hashtag</th>
        <th style="text-align: left; font-weight: bold;">Lượt thích bình luận</th>
        <th style="text-align: left; font-weight: bold;">Mã khách hàng thích bình luận</th>
        <th style="text-align: left; font-weight: bold;">Tên khách hàng thích bình luận</th>
        <th style="text-align: left; font-weight: bold;">Mã đơn hàng</th>
        <th style="text-align: left; font-weight: bold;">Trả lời bình luận</th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $key => $item)
                <tr>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['stt']) ? $item['stt'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['product_code']) ? $item['product_code'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['product_name']) ? $item['product_name'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['user_name']) ? $item['user_name'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['content']) ? $item['content'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['rate']) ? $item['rate'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['rate_name']) ? $item['rate_name'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['hashtag']) ? $item['hashtag'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['like_cmt']) ? $item['like_cmt'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['user_code_like']) ? $item['user_code_like'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['user_name_like']) ? $item['user_name_like'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['order_code']) ? $item['order_code'] : ''}}</td>
                    <td style="border: 1px solid #777; text-align: left">{{!empty($item['replied'])==1 ? 'Shop đã trả lời' : 'Shop chưa trả lời'}}</td>
                </tr>
        @endforeach
    @endif
    </tbody>
</table>