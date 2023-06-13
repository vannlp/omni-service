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
               <th colspan="14" style="text-align: center;font-weight: bold;">Danh sách nhóm người dùng</th>
          </tr>
          <tr>
               {{-- <th colspan="2" style="font-size: 9px">TỪ THÁNG: {{date("d/m/Y", strtotime($from ?? time()))}}</th>--}}
               {{-- <th colspan="2" style="font-size: 9px">ĐẾN THÁNG: {{date("d/m/Y", strtotime($to ?? time()))}}</th>--}}
          </tr>
          <tr class="header-table">
               <th colspan="2" style="text-align: left; font-weight: bold;">Mã đối tượng</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Tên đối tượng</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Mô tả</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Là mặc định</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Chỉ xem và mua hàng theo ngành đăng ký</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Cho phép đăng ký trên ứng dụng</th>
               <th colspan="2" style="text-align: left; font-weight: bold;">Ngày tạo</th>
          </tr>
     </thead>
     <tbody>
          @if(!empty($data))
          @foreach($data as $key => $item)
          <tr>
               <td colspan="2" style="border: 1px solid #777; text-align: left">{{!empty($item['code']) ? $item['code'] : ''}}</td>
               <td colspan="2" style="border: 1px solid #777; text-align: right">{{ !empty($item['name']) ? $item['name'] : ''}}</td>
               <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['description']) ? $item['description'] : ''}}</td>
               <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['is_default']) ? $item['is_default'] : ''}}</td>
               <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['is_view_app']) ? $item['is_view_app'] : ''}}</td>
               <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['is_view']) ? $item['is_view'] : ''}}</td>
               <td colspan="2" style="border: 1px solid #777; text-align: right">{{!empty($item['created_at']) ? $item['created_at'] : ''}}</td>
          </tr>
          @endforeach
          @endif
     </tbody>
</table>
