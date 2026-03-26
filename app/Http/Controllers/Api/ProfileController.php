<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function show()
    {
        $user = Auth::user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'profile_image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                
            ]
        ]);
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:500',
            'image' => 'sometimes|string', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = [];
        
        // Regular fields
        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }
        
        if ($request->has('email') && $request->email !== $user->email) {
            $updateData['email'] = $request->email;
        }
        
        if ($request->has('phone')) {
            $updateData['phone'] = $request->phone;
        }
        
        if ($request->has('address')) {
            $updateData['address'] = $request->address;
        }
        
        // Handle image upload
        if ($request->has('image') && !empty($request->image)) {
            $imagePath = $this->handleBase64Image($request->image, $user);
            
            if ($imagePath) {
                $updateData['profile_image'] = $imagePath;
            }
        }

        // Update user if there's data to update
        if (!empty($updateData)) {
            $user->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'profile_image' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No data to update'
        ], 400);
    }

    /**
     * Handle base64 image upload
     */
    private function handleBase64Image($base64Image, $user)
    {
        // Check if it's a base64 string
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            return false;
        }
        
        // Extract image data
        $imageData = substr($base64Image, strpos($base64Image, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, gif
        
        // Validate image type
        if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return false;
        }
        
        // Decode base64
        $decodedImage = base64_decode($imageData);
        
        if ($decodedImage === false) {
            return false;
        }
        
        // Validate image size (max 5MB)
        if (strlen($decodedImage) > 5 * 1024 * 1024) {
            return false;
        }
        
        // Generate unique filename
        $filename = 'profile_' . $user->id . '_' . time() . '.' . $type;
        $directory = 'profile_images';
        $path = $directory . '/' . $filename;
        
        // Ensure directory exists
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }
        
        // Delete old image if exists
        if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
            Storage::disk('public')->delete($user->profile_image);
        }
        
        // Save new image
        Storage::disk('public')->put($path, $decodedImage);
        
        return $path;
    }

    /**
     * Delete profile image
     */
    public function deleteImage()
    {
        $user = Auth::user();
        
        if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
            Storage::disk('public')->delete($user->profile_image);
            
            $user->update(['profile_image' => null]);
            
            return response()->json([
                'success' => true,
                'message' => 'Profile image deleted successfully'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'No profile image found'
        ], 404);
    }
}