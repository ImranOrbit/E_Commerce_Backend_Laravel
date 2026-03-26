<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProductController extends Controller
{
    // GET ALL PRODUCTS
    public function index(Request $request)
    {
        $products = Product::with('offer')->get()->map(function ($product) {
            $originalPrice = (float)$product->price;
            $finalPrice = $originalPrice;
            $hasOffer = false;
            $offerData = null;

            // Check if product has offer
            if ($product->offer) {
                $startDate = Carbon::parse($product->offer->start_date);
                $endDate = Carbon::parse($product->offer->end_date);
                $now = Carbon::now();
                
                // Check if offer is active and within date range
                if ($product->offer->is_active && $now->between($startDate, $endDate)) {
                    $hasOffer = true;
                    
                    if ($product->offer->type === 'percentage') {
                        $discount = ($originalPrice * $product->offer->value) / 100;
                        $finalPrice = $originalPrice - $discount;
                    } else {
                        $finalPrice = $originalPrice - $product->offer->value;
                    }
                    
                    $offerData = [
                        'id' => $product->offer->id,
                        'type' => $product->offer->type,
                        'value' => (float)$product->offer->value,
                        'start_date' => $product->offer->start_date,
                        'end_date' => $product->offer->end_date,
                        'is_active' => (bool)$product->offer->is_active
                    ];
                }
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                // 'image' => $product->image ? url('storage/' . $product->image) : null,
                'image' => $product->image ? asset('storage/' . $product->image) : null,
                'original_price' => $originalPrice,
                'final_price' => round(max(0, $finalPrice), 2),
                'hasOffer' => $hasOffer,
                'offer' => $offerData
            ];
        });

        return response()->json($products);
    }

    // CREATE PRODUCT
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Upload image
            $imagePath = $request->file('image')->store('products', 'public');

            // Create product
            $product = Product::create([
                'name' => $request->name,
                'price' => $request->price,
                'category' => $request->category,
                'image' => $imagePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => $product
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product: ' . $e->getMessage()
            ], 500);
        }
    }

    // UPDATE PRODUCT
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'category' => 'sometimes|string',
            'image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update image if provided
            if ($request->hasFile('image')) {
                // Delete old image
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $imagePath = $request->file('image')->store('products', 'public');
                $product->image = $imagePath;
            }

            $product->name = $request->name ?? $product->name;
            $product->price = $request->price ?? $product->price;
            $product->category = $request->category ?? $product->category;
            $product->save();

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'product' => $product
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product: ' . $e->getMessage()
            ], 500);
        }
    }

    // DELETE PRODUCT
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        try {
            // Delete image
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product: ' . $e->getMessage()
            ], 500);
        }
    }
}