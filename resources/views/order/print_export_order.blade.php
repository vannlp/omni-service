<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>Lệnh Xuất hàng</title>
	<style type="text/css" media="screen">
		.table-info{
			width: 96%;
			margin: 0 auto;
			border-collapse: collapse;
		}
	</style>
</head>
<body>
	<table style="width: 90%; margin: 0 auto">
		<tr>
			<td>
				<p></p>
				<b style="text-align: center;">CÔNG TY {{$data['company']}}</b>
			</td>
			<td style="width: 40%"></td>
			<td style="line-height: 0.7;">
				<p></p>
				<b>In lần thứ: {{$data['count_print'] + 1}}</b>
				<p></p>
				<p>Ngày: @php echo date("d-m-Y"); @endphp</p>
			</td>
		</tr>
		<tr>
			<td></td>
		</tr>
		<tr>
			<td></td>
		</tr>
	</table>
	<br>
	<table>
		<thead>
			<tr>
				<th colspan="9" style="text-align: center;line-height: 0.7;">
					<h1 style="font-size: 20px;">LỆNH XUẤT HÀNG</h1>
				<b>Số Đơn Hàng: {{$data['code']}}</b>
				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td colspan="9"></td>
			</tr>
			<tr>
				<td colspan="9"></td>
			</tr>
			<tr>
				<td colspan="9"><b>Họ và tên người mua: </b>{{$data['customer_name']}}</td>
			</tr>
			<tr>
				<td colspan="9"><b>Địa chỉ: </b>{{$data['user_address']}}</td>
			</tr>
			<tr>
				<td colspan="9"><b>Giao hàng tại: </b>{{$data['shipping_address']}}</td>
			</tr>
			<tr>
				<td colspan="9"><b>Phương thức thanh toán: </b>{{$data['payment_method']}}</td>
			</tr>
			<tr>
				<td colspan="9"><b>Phương tiện vận chuyển: </b> {{$data['shipping_method']}}</td>
			</tr>
			<tr>
				<td colspan="9"></td>
			</tr>
			<tr>
				<td colspan="9"></td>
			</tr>
		</tbody>
	</table>
	<table border="1">
		<thead>
			<tr style="text-align: center;font-size: 9px">
				<th style="width: 4%;"><h6></h6><b>STT</b><h6></h6></th>
				<th style="width: 30%"><h6></h6><b>TÊN HÀNG HÓA</b><h6></h6></th>
				<th style="width: 12%"><h6></h6><b>MÃ SẢN PHẨM</b><h6></h6></th>
				<th style="width: 6%"><h6></h6><b>ĐƠN VỊ</b><h6></h6></th>
				<th style="width: 12%"><h6></h6><b>KHO XUẤT</b><h6></h6></th>
				<th style="width: 7%"><h6></h6><b>SỐ LƯỢNG</b><h6></h6></th>
				<th style="width: 14%"><h6></h6><b>ĐƠN GIÁ (bao gồm VAT)</b><h6></h6></th>
				<th style="width: 15%"><h6></h6><b>GHI CHÚ</b><h6></h6></th>
			</tr>
		</thead>
		<tbody>
			
			@foreach($data['shipping_detail'] as $key => $item)
			<tr style="text-align: center;line-height: 0.9;">
				<th style="width: 4%"><p></p>{{$key+1}}<p></p></th>
				<th style="width: 30%"><p></p>{{$item['product_name']}}<p></p></th>
				<th style="width: 12%"><p></p>{{$item['product_code']}}<p></p></th>
				<th style="width: 6%"><p></p>{{$item['unit_name']}}<p></p></th>
				<th style="width: 12%"><p></p>{{$item['warehouse_name']}}<p></p></th>
				<th style="width: 7%"><p></p>{{$item['ship_qty']}}<p></p></th>
				<th style="width: 14%"><p></p>{{number_format($item['price']).' đ'}}<p></p></th>
				<th style="width: 15%"><p></p><p></p></th>
			</tr>
			@endforeach
		</tbody>
	</table>
	<br><br>
	@if(!empty($data['reason']))
		<b><i><u>Lý do:</u></i></b> {{$data['reason']}}
	@endif
	<br>
	<table class="signature" style="width: 50%; margin: 0 auto">
		<tr><td></td></tr>
		<tr><td></td></tr>
		<tr>
			<td style="text-align: center; line-height: 0.5">
				<p>Nhân viên bán hàng</p>
				<i>(Ký, ghi rõ họ tên)</i>
			</td>
			
			<td style="text-align: center;line-height: 0.5">
				<p>Trưởng đơn vị</p>
				<i>(Ký, ghi rõ họ tên)</i>
			</td>
		</tr>
	</table>
</body>
</html>