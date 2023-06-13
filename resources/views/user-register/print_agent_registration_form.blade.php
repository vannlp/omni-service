<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
</head>

<body>
<h1 align="center"><strong>THÔNG TIN ĐĂNG KÝ ĐẠI LÝ CÁ NHÂN</strong></h1>
{{--<p>Kính gửi: Công ty Cổ Phần Sữa Quốc Tế</p>--}}
<ol type="I">
    <li><strong>Thông tin của người giới thiệu</strong></li><br/>
    <ol>
        <li>Họ và Tên người giới thiệu: {{$data['reference_name']}}</li><br/>
        <li>Số điện thoại di động:{{$data['reference_phone']}}</li><br/>
    </ol>
    <li><strong>Thông tin của đối tác đăng ký làm đại lý:</strong></li><br/>
    {{--        <em>Vui lòng nộp kèm theo bản sao chứng minh nhân--}}
    {{--            dân/thẻ căn cước công dân khi đăng ký làm khách hàng đại lý</em>--}}
    <ol>
        <li>Họ và Tên: {{$data['name']}}</li><br/>
        <li>Email: {{$data['email']}}</li><br/>
        <li>Số điện thoại di động: {{$data['phone']}}</li><br/>
        <li>Giấy chứng minh nhân dân/thẻ căn cước công dân: {{$data['id_number']}}</li><br/>
        {{--        <table style="width: 100%;">--}}
        {{--            <tbody>--}}
        {{--            <tr>--}}
        {{--                <td style="width: 50.0000%;"></td>--}}
        {{--                <td style="width: 50.0000%;"><br></td>--}}
        {{--            </tr>--}}
        {{--            <tr>--}}
        {{--                <td style="width: 50.0000%;">Số: {{$data['id_number']}}</td>--}}
        {{--                <td style="width: 50.0000%;">Ngày cấp: {{$data['id_number_at']}}<br></td>--}}
        {{--            </tr>--}}
        {{--            </tbody>--}}
        {{--        </table>--}}
        <li>Mã số thuế cá nhân (nếu có): {{$data['tax']}}</li><br/>
        {{--        <table style="width: 100%;">--}}
        {{--            <tr>--}}
        {{--                <td style="width: 50.0000%;">Số điện thoại di động: {{$data['phone']}}</td>--}}
        {{--                <td style="width: 50.0000%;">Email: {{$data['email']}}<br></td>--}}
        {{--            </tr>--}}
        {{--        </table>--}}

        <li>Tài khoản ngân hàng:</li>
        <br/>
        <table style="width: 100%;">
            {{--            <tbody>--}}
            <tr>
                <td style="width: 50.0000%;"></td>
                <td style="width: 50.0000%;"><br></td>
            </tr>
            <tr>
                <td style="width: 50.0000%;">Chủ Tài khoản: {{$data['bank_account_name']}}</td>
                <td style="width: 50.0000%;">Số Tài Khoản: {{$data['bank_account_number']}}<br></td>
            </tr>
            <tr>
                <td style="width: 50.0000%;">Tên Ngân hàng: {{$data['bank_name']}}<br></td>
                <td style="width: 50.0000%;">Chi nhánh: {{$data['bank_branch']}}<br></td>
            </tr>
            {{--            </tbody>--}}
        </table>
        {{--        <em>Vui lòng cung cấp số điện thoại và địa chỉ email đúng để nhận thông tin từ công ty</em>--}}
        {{--        <li style="background-color: #ffc30e; width: 20%">Mã số thuế cá nhân (nếu có): {{$data['tax']}}</li>--}}
        <li>Địa chỉ nhận hàng:</li>
        <br/>
        <table style="width: 100%;">
            <tbody>
            <tr>
                <td style="width: 50.0000%;"></td>
                <td style="width: 50.0000%;"><br></td>
            </tr>
            <tr>
                <td style="width: 50.0000%;">Số nhà và đường: {{$data['address']}}</td>
                <td style="width: 50.0000%;">Phường/xã: {{$data['ward']}}<br></td>
            </tr>
            <tr>
                <td style="width: 50.0000%;">Quận/huyện: {{$data['district']}}<br></td>
                <td style="width: 50.0000%;">Tỉnh/thành phố: {{$data['city']}}<br></td>
            </tr>
            </tbody>
        </table>
    </ol>
    <li><strong>Phần xác nhận thông tin:</strong></li>
    <br/>
    <ol>
        <li>Cá nhân đăng ký xác nhận đồng ý cung cấp những thông tin cá nhân ở trên và sẽ tự chịu trách nhiệm về
            những thông tin đã đăng ký; và (II) cho phép Công Ty Cổ Phần Sữa Quốc Tế liên lạc theo số điện thoại, Email
            và
            địa chỉ đã đăng ký.
        </li>
        <br/>
        <li>Cá nhân đăng ký xác nhận đã đọc và đồng ý với ĐIỀU KIỆN VÀ ĐIỀU KHOẢN CHÍNH SÁCH ĐẠI LÝ
            CÁ NHÂN của Công ty Cổ Phần Sữa Quốc Tế.
        </li>
        <br/>
        <li>Công Ty Cổ Phần Sữa Quốc Tế đã nhận thông tin của cá nhân đăng ký và sẽ phản hồi lại qua email đã
            được cung cấp.
        </li>
        <br/>
    </ol>
    <br/>
    Bằng việc Công ty Cổ Phần Sữa Quốc Tế xác nhận thông tin đăng ký của cá nhân qua email, hành vi này sẽ
    cấu thành hợp đồng giữa hai bên mà không cần phải in và ký vào văn bản.

</ol>
{{--<p style="text-align: right; margin-right: 30%"><b>Khách hàng Đại Lý</b> <br/> <em>(Ký và ghi rõ họ tên)</em></p>--}}
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
{{--<p>&nbsp;</p>--}}
{{--<p>&nbsp;</p>--}}
{{--<p>&nbsp;</p>--}}
<ol>
    <p style="text-align: center;"><strong>ĐIỀU KIỆN VÀ ĐIỀU KHOẢN CHÍNH SÁCH ĐẠI LÝ CÁ NHÂN</strong></p>
    {{--<p style="text-align: center;"><strong>Đính kèm theo Phiếu đăng ký</strong></p>--}}
    <p>&nbsp;&nbsp;&nbsp;&nbsp; Các điều kiện và điều khoản hiển thị dưới đây (sau đây gọi là "<strong>Các Điều
            khoản</strong>") quy định về chính
        sách Đại lý cá nhân, áp dụng bởi và giữa CÔNG TY CỔ PHẦN SỮA QUỐC TẾ (Công ty) và người đăng ký. Bằng việc cung
        cấp
        thông tin và chọn XÁC NHẬN ĐĂNG KÝ, người đăng ký xác nhận mình đã đọc, hiểu và đồng ý chịu sự ràng buộc
        của
        Các Điều Khoản này cũng như mọi thay đổi, chỉnh sửa, bổ sung của Các Điều Khoản này trong tương lai.</p>
    <strong>I. ĐIỀU KIỆN TRỞ THÀNH ĐẠI LÝ CÁ NHÂN VÀ CÁCH THỨC ĐĂNG KÝ</strong>
    <p>
    <ol type="a">
        <li>Đại lý cá nhân là những khách hàng trực tiếp mua sản phẩm của Công ty để tiêu dùng và/hoặc bán trực tiếp cho
            người tiêu dùng khác.
        </li>
        <br/>
        <li>Người đăng ký làm Đại lý cá nhân phải là công dân Việt Nam, từ đủ 18 tuổi trở lên, có năng lực hành vi dân
            sự
            đầy đủ, có giấy chứng minh nhân dân, căn cước công dân hoặc hộ chiếu còn hiệu lực tại thời điểm đăng ký.
        </li>
        <br/>
        <li>Người đăng ký sẽ được xét duyệt tư cách Đại lý cá nhân nhân sau khi điền phiếu đăng ký theo mẫu do Công ty
            phát hành và cung cấp đầy đủ hình ảnh hai mặt thẻ CMND/CCCD/Hộ chiếu. Người đăng ký sẽ trở thành Đại lý cá
            nhân
            sau
            khi được Công ty phản hồi chấp thuận qua email hoặc các hình thức phản hồi khác.
        </li>
        <br/>
    </ol>
    </p>
    <strong>II. CHÍNH SÁCH THƯỞNG ĐẠI LÝ CÁ NHÂN</strong>
    <p>
    <ol type="a">
        <li>Đại lý cá nhân sẽ được nhận chiết khấu, thưởng doanh số bán hàng, tiền phí hỗ trợ phát triển kinh
            doanh và các khoản thưởng khác từ hoạt động kinh doanh độc lập của mình (sau đây gọi “<strong>Thưởng Đại
                lý
                cá
                nhân</strong>”). Đại lý cá nhân sẽ được nhận tiền thưởng Đại lý cá nhân và các khoản thưởng khác căn
            cứ
            vào
            doanh số mua hàng với IDP/ Nhà Phân Phối. Chi tiết cách tính thưởng Đại lý cá nhân sẽ được thực hiện theo
            quy
            định
            của Công Ty, và có thể được thay đổi theo thông báo của Công ty tại từng thời điểm.
        </li>
        <br/>
        <li>Đại lý cá nhân có nghĩa vụ phát triển kinh doanh và sẽ nhận được tiền phí hỗ trợ phát triển
            kinh
            doanh khi mở rộng mạng lưới kinh doanh của mình.
        </li>
        <br/>
        <li>Tiền thưởng cho Đại lý cá nhân sẽ được Công Ty thanh toán bằng chuyển khoản tiền đồng Việt Nam vào tài
            khoản
            được Đại lý cá nhân đăng ký sau khi trừ các khoản thuế, phí mà Đại lý cá nhân phải chịu theo quy định của
            pháp
            luật
            Việt Nam.
        </li>
        <br/>
        <li>Đại lý cá nhân sẽ mua hàng/nhận hàng từ IDP hoặc Nhà Phân Phối là đơn vị được IDP chỉ định thực hiện
            việc
            phân phối các sản phẩm của IDP.
        </li>
        <br/>
    </ol>
    </p>
    <strong>III. QUY TẮC HOẠT ĐỘNG ĐẠI LÝ CÁ NHÂN</strong>
    <p>
    <ol type="a">
        <li>Đại lý cá nhân hoạt động kinh doanh một cách độc lập và phải duy trì phát sinh doanh số tối thiểu 1.000.000
            đồng/tháng.
        </li>
        <br/>
        <li>Tuân thủ chính sách và cung cấp thông tin trung thực, chính xác trong quá trình kinh doanh:<br/>
            <ol type="i">
                <li>Đại lý cá nhân luôn gương mẫu, có trách nhiệm và được khách hàng & Đại lý cá nhân khác tôn trọng,
                    làm
                    gương
                    trong
                    việc tiến hành hoạt động kinh doanh đúng đắn. Hỗ trợ nhóm Đại lý cá nhân được giới thiệu của mình
                    thực hiện
                    đúng
                    các
                    nguyên tắc trong kinh doanh;
                </li>
                <br/>
                <li>Đại lý cá nhân phải sẵn sàng hợp tác với IDP trong việc xử lý và sửa chữa sai phạm chính sách
                    kinh
                    doanh;
                </li>
                <br/>
                <li>Mỗi người chỉ được đăng ký thành 1 Đại lý cá nhân duy nhất, nếu phát hiện vi phạm, lần đầu sẽ bị
                    buộc giữ
                    lại
                    1 mã số Đại lý cá nhân duy nhất, hủy bỏ kết quả kinh doanh của các mã số Đại lý cá nhân khác, lần
                    thứ 2 sẽ
                    bị
                    tước bỏ kết quả trước đó và quyền kinh doanh trong 6 tháng từ lúc phát hiện vi phạm. Sau thời gian 6
                    tháng
                    này,
                    Đại lý cá nhân nếu muốn tham gia kinh doanh, sẽ phải làm đơn đăng ký và được xét duyệt lại từ
                    đầu;
                </li>
                <br/>
            </ol>
        </li>
        <br/>
        <li>Các cuộc họp nhóm Đại lý cá nhân có quy mô trên 20 người phải được đăng ký với IDP bằng email, tin nhắn,
            điện
            thoại đến hotline, trừ khi có quy định pháp luật nào khác. Các cuộc họp nhóm Đại lý cá nhân phải được tổ
            chức
            một
            cách tự nguyện, không thu phí bằng mọi hình thức.
        </li>
        <br/>
        <li>Đại lý cá nhân không được:</li>
        <br/>
        <ol type="i">
            <li>Bán phá giá các sản phẩm của IDP hoặc các sản phẩm không được giao bởi Nhà Phân Phối của Công ty
                chỉ
                định;
            </li>
            <br/>
            <li>Ép buộc, lôi kéo, xúi giục khách hàng hiện có/khách hàng mới của Đại lý cá nhân hay Đối tác Kinh doanh
                của
                Đại lý cá nhân khác để dụ dỗ họ mua sản phẩm của mình;
            </li>
            <br/>
            <li>Mua bán – cho tặng sản phẩm- dùng “quà tặng” để biện minh cho việc cung cấp sản phẩm cho Đại lý cá nhân
                khác hoặc khách hàng của Đại lý cá nhân khác;
            </li>
            <br/>
            <li>Sử dụng các mối quan hệ kinh doanh sản phẩm IDP để khuyến khích hoặc ép buộc các Đại lý cá nhân của IDP
                tham
                gia vào các sự kiện hoặc chương trình không liên quan đến IDP.
            </li>
            <br/>
            <li>Phóng đại hay nói không đúng sự thật về sản phẩm/thu nhập/cơ hội kinh doanh sản phẩm của IDP. Các tuyên
                bố về
                thu nhập phải được xác minh bởi IDP.
            </li>
            <br/>
            <li>Tự ý sản xuất, quảng cáo & bán bất cứ tài liệu hỗ trợ kinh doanh mà nguồn gốc không chính thức từ
                IDP.
            </li>
            <br/>
        </ol>
        <li>Các sản phẩm của IDP chỉ được bán và phân phối trong phạm vi Việt Nam và Đại lý cá nhân không được bán
            những
            sản phẩm cạnh tranh trực tiếp với sản phẩm IDP.
        </li>
        <br/>
        <li>Trong mọi thông tin trao đổi, không được đề cập đến các vấn đề chính trị, chính sách nhà nước, tôn giáo hay
            niềm
            tin cá nhân, nghiêm cấm truyền bá văn hóa phẩm đồi trụy.
        </li>
        <br/>
    </ol>
    </p>
    <strong>IV. CHUYỂN ĐỔI ĐẠI LÝ CÁ NHÂN, CHẤM DỨT HỢP TÁC VÀ GIẢI QUYẾT TRANH CHẤP</strong>
    <ol type="a">
        <li>Việc chuyển đổi Đại lý cá nhân sẽ do Công ty xem xét và phải đáp ứng các quy định như sau:</li>
        <br/>
        <ol type="i">
            <li>a. Đại lý cá nhân yêu cầu chuyển đổi đã tạm ngừng hoạt động trong 03 tháng liên tiếp trước đó và đã nộp
                đơn
                xin
                chấm dứt quan hệ với Đại lý cá nhân giới thiệu ban đầu.
            </li>
            <br/>
            <li>Công ty gửi thông báo bằng email hoặc trên ứng dụng đặt hàng cho Đại lý cá nhân giới thiệu ban đầu và
                yêu cầu
                phản hồi đồng ý trong vòng 14 ngày từ ngày nhận thông báo này. Nếu Công ty không nhận phản hồi từ Đại lý
                cá nhân
                sau
                14 ngày, kể từ ngày gửi thông báo Công ty sẽ xem như Đại lý cá nhân đã đồng ý.
            </li>
            <br/>
            <li>Đại lý cá nhân được giới thiệu từ Đại lý cá nhân yêu cầu chuyển đổi này sẽ được tự động chuyển giao cho
                Đại
                lý cá
                nhân giới thiệu ban đầu. Trường hợp Đại lý cá nhân được giới thiệu này muốn xin chuyển đổi, Đại lý cá
                nhân đó
                phải
                tạm ngừng hoạt động trong vòng 06 tháng và đã làm đơn xin chấm dứt quan hệ với Đại lý cá nhân giới thiệu
                ban
                đầu.
                Công ty giữ quyền quyết định về việc gia nhập trở lại hệ thống kinh doanh của Đại lý cá nhân này.
            </li>
            <br/>
        </ol>
        <li>Việc hợp tác giữa Công Ty và Đại lý cá nhân sẽ được chấm dứt trong các trường hợp sau:</li>
        <br/>
        <ol type="a">
            <li>Đại lý cá nhân vi phạm bất kỳ quy định nào tại Điều 3 nói trên;</li>
            <br/>
            <li>Công Ty chấm dứt chính sách Đại lý cá nhân. Trong trường hợp này, Công Ty sẽ gửi một thông báo bằng văn
                bản
                cho
                Đại lý cá nhân, trong đó nêu rõ thời điểm chấm dứt chính sách Đại lý cá nhân, thời điểm và phương thức
                giải
                quyết
                quyền lợi của các bên;
            </li>
            <br/>
            <li>Các trường hợp khác theo quy định của pháp luật.</li>
            <br/>
        </ol>
        <li>Các tranh chấp phát sinh từ mối quan hệ Đại lý cá nhân giữa Công Ty và Đại lý cá nhân sẽ được giải quyết
            được
            thông qua thương lượng giữa các bên, trong trường hợp thương lượng không thành công thì sẽ giải quyết
            thông
            qua
            thủ tục tố tụng tại trọng tài kinh tế.
        </li>
        <br/>
        </p>
    </ol>
</ol>
<table>
    <tr>
        <td width="20%">
        </td>
        <td style="text-align: center; width: 80%">
            <i>Tôi đồng ý với tất cả các điều khoản trên vào ngày:
                <strong>{{date('d/m/Y H:i:s',strtotime($data['created_at']))}}</strong><br/>
                Khách hàng thực hiện đăng ký<br/>
                <strong>{{$data['name']}}</strong>
            </i>
        </td>
    </tr>
</table>

{{--<p><strong>&nbsp;</strong></p>--}}
{{--<p><strong>QUY TRÌNH ĐĂNG KÝ LÀM ĐẠI LÝ</strong></p>--}}
{{--<p><strong>&nbsp;</strong></p>--}}
{{--<p><img width="554" height="550" src="{{ URL::to('image-agent-register/dangkydaily.jpg') }}" alt="image"></p>--}}
{{--<p><strong>&nbsp;</strong></p>--}}
{{--<p><strong>&nbsp;</strong></p>--}}
{{--<p><strong>&nbsp;</strong></p>--}}
{{--<p><strong>&nbsp;</strong></p>--}}
{{--<p><strong>&nbsp;</strong></p>--}}
{{--<p><strong>QUY TRÌNH ĐẶT HÀNG</strong></p>--}}
{{--<p><strong>&nbsp;</strong></p>--}}
{{--<p><img width="554" height="530" src="{{ URL::to('image-agent-register/quitrinhdathang.jpg') }}" alt="image"></p>--}}
{{--<p><strong>&nbsp;</strong></p>--}}
{{--<p><strong>&nbsp;</strong></p>--}}
{{--<p><strong>&nbsp;</strong></p>--}}
{{--<p><strong>&nbsp;</strong></p>--}}
{{--<p><strong>&nbsp;</strong></p>--}}
{{--<p><strong>&nbsp;</strong></p>--}}
{{--<p><strong>&nbsp;</strong></p>--}}
{{--<p><strong>QUY TRÌNH THANH TOÁN</strong></p>--}}
{{--<p><strong>&nbsp;</strong></p>--}}
{{--<p><img width="554" height="500" src="{{ URL::to('image-agent-register/quitrinhthanhtoan.png') }}" alt="image"></p>--}}
</body>
</html>