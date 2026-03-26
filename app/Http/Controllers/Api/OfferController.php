<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OfferController extends Controller
{
    // Get all offers
    public function index()
    {
        $offers = Offer::with('product')->get();
        return response()->json($offers);
    }

    // Get single offer
    public function show($id)
    {
        $offer = Offer::with('product')->find($id);
        
        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found'
            ], 404);
        }
        
        return response()->json($offer);
    }

    // Create offer
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Deactivate any existing active offer for this product
            Offer::where('product_id', $request->product_id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Create new offer
            $offer = Offer::create([
                'product_id' => $request->product_id,
                'type' => $request->type,
                'value' => $request->value,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->is_active ?? true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Offer created successfully',
                'data' => $offer
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create offer: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update offer
    public function update(Request $request, $id)
    {
        $offer = Offer::find($id);
        
        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:percentage,fixed',
            'value' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $offer->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Offer updated successfully',
                'data' => $offer
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update offer: ' . $e->getMessage()
            ], 500);
        }
    }

    // Delete offer
    public function destroy($id)
    {
        $offer = Offer::find($id);
        
        if (!$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Offer not found'
            ], 404);
        }

        try {
            $offer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Offer deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete offer: ' . $e->getMessage()
            ], 500);
        }
    }
}