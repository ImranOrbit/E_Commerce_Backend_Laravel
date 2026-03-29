<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validate request
            $validator = Validator::make($request->all(), [
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric|min:0',
                'total_amount' => 'required|numeric|min:0',
                'shipping_address' => 'required|string',
                'phone' => 'required|string',
                'payment_method' => 'required|string|in:cod,bkash,card',
                'customer_name' => 'required|string',
                'customer_email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();

            // Create Order
            $order = new Order();
            $order->user_id = $user ? $user->id : null;
            $order->customer_name = $request->customer_name;
            $order->customer_email = $request->customer_email;
            $order->total = $request->total_amount;
            $order->total_amount = $request->total_amount;
            $order->shipping_address = $request->shipping_address;
            $order->phone = $request->phone;
            $order->payment_method = $request->payment_method;
            $order->order_status = 'pending';
            $order->status = 'pending';
            $order->save();

            // Create Order Items
            foreach ($request->items as $item) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->product_id = $item['product_id'];
                $orderItem->quantity = $item['quantity'];
                $orderItem->price = $item['price'];
                $orderItem->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'order_id' => $order->id,
                'order' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Order failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to view orders'
            ], 401);
        }
        
        // FIXED: Get orders by user_id OR by customer_email
        $orders = Order::with('items.product')
            ->where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('customer_email', $user->email);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function show($id)
    {
        $user = auth()->user();
        
        $order = Order::with('items.product')->find($id);
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }
        
        // For logged-in users, check if they own the order OR email matches
        if ($user && ($order->user_id == $user->id || $order->customer_email == $user->email)) {
            return response()->json([
                'success' => true,
                'data' => $order
            ]);
        }
        
        // For guest orders, we could implement email verification
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }
}