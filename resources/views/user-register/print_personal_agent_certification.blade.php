<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
</head>

<body>
<p><strong>&nbsp;</strong></p>
<h1 align="center"><strong>THÔNG BÁO XÁC NHẬN TƯ CÁCH ĐẠI LÝ CÁ NHÂN</strong></h1>
<p><i>Kính gửi:</i> Quý khách hàng</p>

<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Chúng tôi đã nhận được các thông tin và yêu cầu từ phía Khách hàng
    cung cấp để trở thành “Đại lý cá nhân”.</p>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    Bằng thông báo này Công ty Cổ Phần Sữa Quốc Tế (IDP) thông tin đến quý khách hàng: Hồ sơ đăng ký để trở thành Đại lý
    cá nhân của quý khách hàng đã được Công ty chúng tôi xem xét và phê duyệt. Qúy khách hàng sẽ chính thức trở thành
    Đại lý cá nhân của Công ty chúng tôi kể từ ngày <b>{{date('d',strtotime($data['updated_at']))}}</b> tháng
    <b>{{date('m',strtotime($data['updated_at']))}}</b> năm <b>{{date('Y',strtotime($data['updated_at']))}}</b>. Với tên và mã số đại lý như sau:
</p>
<p>
    <i>1. Tên đại lý:</i> <strong>{{$data['name']}}</strong>
</p>
<p>
    <i>2. Mã số đại lý cá nhân:</i> <strong>{{$data['code']}}</strong>
</p>
<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <i>Công ty Cổ Phần Sữa Quốc Tế chào đón và kính chúc quý Khách hàng thành công cùng IDP!</i>
</p>
<p style="text-align: right;">
    <i>Thông báo này được gửi vào ngày {{date('d')}} tháng {{date('m')}} năm {{date('Y')}}</i>
</p>
</body>