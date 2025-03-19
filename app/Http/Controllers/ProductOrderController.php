<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\Store;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductOrderController extends Controller
{
    public function createOrder(Request $request)
    {
        // التحقق من صحة البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:1',
            'location_id'=> 'required|exists:locations,id',
            'store_id' => 'required|exists:stores,id',
            'description' => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error('Validation error', $validator->errors(), 422);
        }

        // حساب المجموع الكلي للطلبية
        $totalAmount = 0;
        $productsData = [];
        foreach ($request['products'] as $product) {
            $productData = Product::query()->find($product['product_id']);
            if ($productData->quantity < $product['quantity'] ){
                return ResponseFormatter::error('Quantity available for this product:'.$productData->name.'is'.$productData->quantity,null,404);
            }
            $totalAmount += $productData->price * $product['quantity'];

            // تخزين المنتجات الخاصة بالطلب
            $productsData[] = [
                'product_id' => $product['product_id'],
                'quantity' => $product['quantity'],
                'price' => $productData->price,
                'name' => $productData->name,
                'subtotal' => $productData->price * $product['quantity'],

            ];
        }
        $wallet = Wallet::query()->where('user_id',Auth::id())->first();
        if (!$wallet || $totalAmount > $wallet->balance)
            return ResponseFormatter::error('You do not have enough balance in your wallet',null,404);
        // إنشاء الطلبية
        $order = Order::query()->create([
            'user_id' => Auth::id(),
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'order_date' => now(),
            'location_id' => $request['location_id'],
            'store_id' => $request['store_id'],
            'description' => $request['description'],
        ]);

        // ربط المنتجات بالطلبية
        foreach ($productsData as $productData) {
            ProductOrder::query()->create([
                'order_id' => $order->id,
                'product_id' => $productData['product_id'],
                'name' => $productData['name'],
                'price' => $productData['price'],
                'quantity' => $productData['quantity'],
                'subtotal' => $productData['price'] * $productData['quantity'],

            ]);

        }

        // خصم الرصيد من المحفظة
        $wallet->balance -= $totalAmount;
        $wallet->save();

        WalletTransaction::query()->create([
            'wallet_id'=> $wallet->id,
            'transaction_type' => 'payment',
            'amount'=>$totalAmount,
            'balance_after_transaction'=>$wallet->balance
        ]);


        // البيانات للرد
        $data = Order::with('productsOrder.product','location', 'store')->find($order->id);


        return ResponseFormatter::success('Order created successfully', $data,201);
    }

    public function getUserOrders()
    {
        $orders = Order::with('productsOrder.product','location', 'store')->where('user_id', Auth::id())->get();

        if ($orders->isEmpty()) {
            return ResponseFormatter::error('Orders not found', null, 404);
        }

        return ResponseFormatter::success('Orders found',$orders, 200);
    }


    public function getOrderById($order_id)
    {
        $order = Order::with('productsOrder.product', 'location', 'store')
                      ->where('user_id', Auth::id()) // To make sure users can access only their orders
                      ->where('id', $order_id)
                      ->first();

        if (!$order) {
            return ResponseFormatter::error('Order not found', null, 404);
        }

        return ResponseFormatter::success('Order found', $order, 200);
    }



    public function updateStatusOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,sent,rejected,completed,cancelled',
            'order_id' => 'required|exists:orders,id',
            'message'  => 'nullable'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error('Validation error', $validator->errors(), 422);
        }

        // الحصول على الطلبية
        $order = Order::with('productsOrder.product','location', 'store')->find($request['order_id']);
        if (is_null($order)) {
            return ResponseFormatter::error('Order not found', null, 404);
        }

        // تحقق من أن الحالة الحالية تساوي الحالة المطلوبة
        if ($order->status === $request['status']) {
            return ResponseFormatter::success('This order already has the specified status', $order, 200);
        }

        // التحقق من الحركات المنطقية للحالة
        $invalidTransitions = [
            'completed' => ['cancelled', 'rejected', 'sent'],
            'cancelled' => ['completed', 'rejected', 'sent'],
            'rejected' => ['cancelled', 'completed', 'sent'],
            'sent' => ['cancelled', 'rejected', 'pending'],
            'pending' => ['cancelled', 'rejected', 'sent', 'completed'],
        ];

        if (isset($invalidTransitions[$request['status']]) &&
            in_array($order->status, $invalidTransitions[$request['status']])) {
            return ResponseFormatter::error(
                "Invalid transition: Cannot change status from {$order->status} to {$request['status']}",
                null,
                422
            );
        }

        // تحديث الحالة
        $order->message = $request['message'];
        $order->status = $request['status'];
        $order->save();

        // إذا كانت الحالة مكتملة، قم بتحديث كميات المنتجات
        if ($order->status === 'completed') {
            $productsInOrder = ProductOrder::query()->where('order_id', $request['order_id'])->get();

            foreach ($productsInOrder as $productOrder) {
                $product = Product::query()->find($productOrder->product_id);
                if ($product) {
                    $product->quantity -= $productOrder->quantity;
                    $product->save();
                }
            }
        }

        if ($order->status === 'cancelled'){
            $wallet = Wallet::query()->where('user_id',Auth::id())->first();
            $wallet->balance += $order->total_amount;
            $wallet->save();
        }

        $userOrder = User::query()->find($order->user_id);
        $store=Store::query()->find($order->store_id);
        $userStore = User::query()->find($store->user_id);

        $orderMessages = [
            'cancelled' => [
                'user' => $userStore,
                'title' => 'Order status',
                'message' => 'This customer :' . $userOrder->first_name . ' cancelled his order!',
            ],
            'completed' => [
                'user' => $userOrder,
                'title' => 'Order status',
                'message' => 'Your order has been accepted by the store ' . $order->store->name,
            ],
            'sent' => [
                'user' => $userOrder,
                'title' => 'Order status',
                'message' => 'Your order has been delivered to you by the store ' . $order->store->name,
            ],
            'rejected' => [
                'user' => $userOrder,
                'title' => 'Order status',
                'message' => 'Your order has been rejected by the store ' . ($order->store ? $order->store->name : '') . " because: " . $order->message,
            ],
        ];
        
        // Check if the order status exists in the mapping
        if (isset($orderMessages[$order->status])) {
            // Send the notification
            (new NotificationController)->sendNotification(
                $orderMessages[$order->status]['user'],
                $orderMessages[$order->status]['title'],
                $orderMessages[$order->status]['message'],
                $order
            );
        }

        // if($order->status === 'completed' || $order->status == 'sent' || $order->status == 'rejected'){

        //     ( new NotificationController )->sendNotification($userOrder,'Order status','Your order status has been accepted by the store '.$order->store->name,$order);
        // }

        return ResponseFormatter::success('Order status updated successfully', $order, 200);
    }

    public function getOrderMyStore($store_id)
    {
        $orders = Order::with('productsOrder.product','location', 'store')->where('store_id',$store_id)->get();

        return ResponseFormatter::success('get my Orders successfully',$orders,200);
    }

    public function getMyStoreOrders()
    {
        $store = Store::query()->where('user_id', Auth::id())->first();

        if (!$store) {
            return ResponseFormatter::error('Store not found', null, 404);
        }

        $orders = Order::with('productsOrder.product', 'location', 'store','user')
            ->where('store_id', $store->id)
            ->get();

        if ($orders->isEmpty()) {
            return ResponseFormatter::error('No orders found for this store', [], 404);
        }

        return ResponseFormatter::success('Orders retrieved successfully', $orders, 200);
    }

}
