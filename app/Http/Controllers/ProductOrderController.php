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
            'location_id'=> 'required|exists:locations,id'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error('Validation error', $validator->errors(), 422);
        }
        // حساب المجموع الكلي للطلبية
        $totalAmount = 0;
        $productsData = [];
        foreach ($request->products as $product) {
            $productData = Product::find($product['product_id']);
            $totalAmount += $productData->price * $product['quantity'];

            // تخزين المنتجات الخاصة بالطلب
            $productsData[] = [
                'product_id' => $product['product_id'],
                'quantity' => $product['quantity'],
                'price' => $productData->price,
            ];
        }
        $wallet = Wallet::query()->where('user_id',Auth::id())->first();
        if (!$wallet || $totalAmount > $wallet->balance)
            return ResponseFormatter::error('You do not have enough balance in your wallet',null,404);
        // إنشاء الطلبية
        $order = Order::create([
            'user_id' => Auth::id(),
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'order_date' => now(),
            'location_id' => $request->location_id
        ]);

        // ربط المنتجات بالطلبية
        foreach ($productsData as $productData) {
            ProductOrder::create([
                'order_id' => $order->id,
                'product_id' => $productData['product_id'],
                'number' => $productData['quantity'],
            ]);
        }
        // خصم الرصيد من المحفظة
        $wallet->balance -= $totalAmount;
        $wallet->save();

        WalletTransaction::create([
            'wallet_id'=> $wallet->id,
            'transaction_type' => 'payment',
            'amount'=>$totalAmount,
            'balance_after_transaction'=>$wallet->balance
        ]);


        // البيانات للرد
        $data = [
            'order_id' => $order->id,
            'status' => $order->status,
            'order_date' => $order->order_date,
            'location_id' => $order->location_id,
            'content' => $productsData,
            'amount_total' => $totalAmount
        ];

        return ResponseFormatter::success('Order created successfully', $data,201);
    }
    public function updateOrderStatus(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,completed,canceled',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error('Validation error', $validator->errors(), 422);
        }

        // الحصول على الطلبية
        $order = Order::find($orderId);
        if (is_null($order)) {
            return ResponseFormatter::error('Order not found', null, 404);
        }

        // تعديل حالة الطلب
        $order->status = $request->status;
        $order->save();

        // إذا كانت حالة الطلب هي "مكتملة"، نقوم بتحديث الكميات
        if ($order->status == 'completed') {
            // الحصول على جميع المنتجات المرتبطة بالطلب
            $productsInOrder = ProductOrder::where('order_id', $orderId)->get();

            foreach ($productsInOrder as $productOrder) {
                $product = Product::find($productOrder->product_id);
                if ($product) {
                    // تقليص الكمية بناءً على الكمية المطلوبة في الطلب
                    $product->quantity -= $productOrder->number;
                    $product->save();
                }
            }
        }

        return ResponseFormatter::success('Order status updated successfully', $order, 200);
    }

    public function addProductToOrder(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error('Validation error', $validator->errors(), 422);
        }

        // الحصول على الطلبية
        $order = Order::find($orderId);
        if (is_null($order)) {
            return ResponseFormatter::error('Order not found', null, 404);
        }

        // التحقق من أن الطلبية في حالة "pending" قبل إضافة منتجات
        if ($order->status !== 'pending') {
            return ResponseFormatter::error('Cannot modify order, it is no longer pending', null, 403);
        }

        // إضافة المنتج للطلبية
        $product = Product::find($request->product_id);
        ProductOrder::create([
            'order_id' => $order->id,
            'product_id' => $request->product_id,
            'number' => $request->quantity,
        ]);

        // تحديث المبلغ الإجمالي للطلبية
        $totalAmount = $order->total_amount + ($product->price * $request->quantity);
        $order->total_amount = $totalAmount;
        $order->save();

        return ResponseFormatter::success('Product added to order successfully', $order, 200);
    }
    public function removeProductFromOrder(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error('Validation error', $validator->errors(), 422);
        }

        // الحصول على الطلبية
        $order = Order::find($orderId);
        if (is_null($order)) {
            return ResponseFormatter::error('Order not found', null, 404);
        }

        // التحقق من أن الطلبية في حالة "pending"
        if ($order->status !== 'pending') {
            return ResponseFormatter::error('Cannot modify order, it is no longer pending', null, 403);
        }

        // العثور على المنتج في الطلبية وحذفه
        $productOrder = ProductOrder::where('order_id', $orderId)
            ->where('product_id', $request->product_id)
            ->first();

        if ($productOrder) {
            // تحديث المبلغ الإجمالي للطلبية
            $product = Product::find($request->product_id);
            $totalAmount = $order->total_amount - ($product->price * $productOrder->number);
            $order->total_amount = $totalAmount;
            $order->save();

            // حذف المنتج من الطلب
            $productOrder->delete();
            return ResponseFormatter::success('Product removed from order successfully', $order, 200);
        } else {
            return ResponseFormatter::error('Product not found in this order', null, 404);
        }
    }


}
