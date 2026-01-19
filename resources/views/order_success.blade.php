<!DOCTYPE html>
<html>
<head>
    <title>Xác nhận đơn hàng</title>
</head>
<body>
    <h1>Cảm ơn bạn đã đặt hàng, {{ $order->name }}!</h1>
    <p>Mã đơn hàng của bạn là: <strong>#{{ $order->id }}</strong></p>
    
    <p>Trạng thái: 
        @if($order->status == 1) Chờ xác nhận
        @elseif($order->status == 2) Đã thanh toán
        @else Đang xử lý
        @endif
    </p>

    <h3>Chi tiết đơn hàng:</h3>
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Số lượng</th>
                <th>Đơn giá</th>
                <th>Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderDetails as $detail)
            <tr>
                <td>{{ $detail->product->name ?? 'Sản phẩm #' . $detail->product_id }}</td>
                <td>{{ $detail->qty }}</td>
                <td>{{ number_format($detail->price) }} đ</td>
                <td>{{ number_format($detail->amount) }} đ</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Tổng tiền: {{ number_format($order->total_money) }} đ</h3>
    
    <p>Địa chỉ giao hàng: {{ $order->address }}</p>
    <p>Điện thoại: {{ $order->phone }}</p>

    <p>Cảm ơn bạn đã mua sắm tại PDA Fashion!</p>
</body>
</html>