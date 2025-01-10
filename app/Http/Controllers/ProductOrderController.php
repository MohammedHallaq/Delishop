<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOrder;
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
        $order = Order::query()->find($request['order_id']);
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
        $order->status = $request['status'];
        $order->save();

        // إذا كانت الحالة مكتملة، قم بتحديث كميات المنتجات
        if ($order->status === 'completed') {
            $productsInOrder = ProductOrder::where('order_id', $request['order_id'])->get();

            foreach ($productsInOrder as $productOrder) {
                $product = Product::find($productOrder->product_id);
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


        return ResponseFormatter::success('Order status updated successfully', $order, 200);
    }


    /*public function addProductToOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error('Validation error', $validator->errors(), 422);
        }

        // الحصول على الطلبية
        $order = Order::query()->find($request->input('order_id'));
        if (is_null($order)) {
            return ResponseFormatter::error('Order not found', null, 404);
        }

        // التحقق من أن الطلبية في حالة "pending" قبل إضافة منتجات
        if ($order->status !== 'pending') {
            return ResponseFormatter::error('Cannot modify order, it is no longer pending', null, 403);
        }

        // إضافة المنتج للطلبية
        $product = Product::query()->find($request->input(['product_id']));
        ProductOrder::create([
            'order_id' => $order->id,
            'product_id' => $request->input(['product_id']),
            'quantity' => $request->input(['quantity']),
        ]);

        // تحديث المبلغ الإجمالي للطلبية
        $totalAmount = $order->total_amount + ($product->price * $request->input(['quantity']));
        $order->total_amount = $totalAmount;
        $order->save();

        return ResponseFormatter::success('Product added to order successfully', $order, 200);
    }
    public function removeProductFromOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'order_id' => 'required|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error('Validation error', $validator->errors(), 422);
        }

        // الحصول على الطلبية
        $order = Order::query()->find($request->input('order_id'));
        if (is_null($order)) {
            return ResponseFormatter::error('Order not found', null, 404);
        }

        // التحقق من أن الطلبية في حالة "pending"
        if ($order->status !== 'pending') {
            return ResponseFormatter::error('Cannot modify order, it is no longer pending', null, 403);
        }

        // العثور على المنتج في الطلبية وحذفه
        $productOrder = ProductOrder::query()->where('order_id',$order->id)
            ->where('product_id', $request->input(['product_id']))
            ->first();

        if ($productOrder) {
            // تحديث المبلغ الإجمالي للطلبية
            $product = Product::query()->find($request->input(['product_id']));
            $totalAmount = $order->total_amount - ($product->price * $productOrder->number);
            $order->total_amount = $totalAmount;
            $order->save();

            // حذف المنتج من الطلب
            $productOrder->delete();
            return ResponseFormatter::success('Product removed from order successfully', $order, 200);
        } else {
            return ResponseFormatter::error('Product not found in this order', null, 404);
        }
    }*/
    public function getOrderMyStore($store_id)
    {
        $order = Order::with('productsOrder')->where('store_id',$store_id)->get();
        return ResponseFormatter::success('get my Orders successfully',$order,200);
    }


}
