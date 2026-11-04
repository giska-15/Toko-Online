<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseFormmater;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{
    /**
     * Get all users (owner, admin, developer only)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Check permission - only owner, admin, developer can view
        if (!in_array($user->role, ['owner', 'admin', 'developer'])) {
            return ResponseFormmater::error([
                'message' => 'Unauthorized access'
            ], 'Access Denied', 403);
        }
        
        $users = User::all(['id', 'name', 'email', 'role', 'created_at', 'updated_at']);
        
        return ResponseFormmater::success([
            'users' => $users
        ], 'Users retrieved successfully');
    }
    
    /**
     * Get single user by ID
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        // Check permission
        if (!in_array($user->role, ['owner', 'admin', 'developer'])) {
            return ResponseFormmater::error([
                'message' => 'Unauthorized access'
            ], 'Access Denied', 403);
        }
        
        $targetUser = User::find($id);
        
        if (!$targetUser) {
            return ResponseFormmater::error([
                'message' => 'User not found'
            ], 'Not Found', 404);
        }
        
        return ResponseFormmater::success([
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'role' => $targetUser->role,
                'created_at' => $targetUser->created_at,
                'updated_at' => $targetUser->updated_at,
            ]
        ], 'User retrieved successfully');
    }
    
    /**
     * Create new user (owner, admin, developer only)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Check permission
        if (!in_array($user->role, ['owner', 'admin', 'developer'])) {
            return ResponseFormmater::error([
                'message' => 'Unauthorized access'
            ], 'Access Denied', 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:user,admin,owner,developer',
        ]);
        
        if ($validator->fails()) {
            return ResponseFormmater::error([
                'message' => $validator->errors()->first()
            ], 'Validation Failed', 400);
        }
        
        // Only developer can create owner/developer/admin, others can only create user
        if (in_array($request->role, ['owner', 'developer']) && $user->role !== 'developer') {
            return ResponseFormmater::error([
                'message' => 'Only developer can create owner or developer accounts'
            ], 'Access Denied', 403);
        }
        
        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);
        
        return ResponseFormmater::success([
            'user' => [
                'id' => $newUser->id,
                'name' => $newUser->name,
                'email' => $newUser->email,
                'role' => $newUser->role,
                'created_at' => $newUser->created_at,
            ]
        ], 'User created successfully');
    }
    
    /**
     * Update user (owner, admin, developer only)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        // Check permission
        if (!in_array($user->role, ['owner', 'admin', 'developer'])) {
            return ResponseFormmater::error([
                'message' => 'Unauthorized access'
            ], 'Access Denied', 403);
        }
        
        $targetUser = User::find($id);
        
        if (!$targetUser) {
            return ResponseFormmater::error([
                'message' => 'User not found'
            ], 'Not Found', 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$id,
            'role' => 'sometimes|string|in:user,admin,owner,developer',
        ]);
        
        if ($validator->fails()) {
            return ResponseFormmater::error([
                'message' => $validator->errors()->first()
            ], 'Validation Failed', 400);
        }
        
        // Only developer can change role to owner/developer
        if ($request->has('role') && in_array($request->role, ['owner', 'developer']) && $user->role !== 'developer') {
            return ResponseFormmater::error([
                'message' => 'Only developer can change role to owner or developer'
            ], 'Access Denied', 403);
        }
        
        // Cannot change own role
        if ($request->has('role') && $id == $user->id) {
            return ResponseFormmater::error([
                'message' => 'Cannot change your own role'
            ], 'Forbidden', 403);
        }
        
        if ($request->has('name')) {
            $targetUser->name = $request->name;
        }
        
        if ($request->has('email')) {
            $targetUser->email = $request->email;
        }
        
        if ($request->has('role')) {
            $targetUser->role = $request->role;
        }
        
        $targetUser->save();
        
        return ResponseFormmater::success([
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'role' => $targetUser->role,
                'updated_at' => $targetUser->updated_at,
            ]
        ], 'User updated successfully');
    }
    
    /**
     * Delete user (owner, admin, developer only)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        // Check permission
        if (!in_array($user->role, ['owner', 'admin', 'developer'])) {
            return ResponseFormmater::error([
                'message' => 'Unauthorized access'
            ], 'Access Denied', 403);
        }
        
        // Cannot delete yourself
        if ($id == $user->id) {
            return ResponseFormmater::error([
                'message' => 'Cannot delete yourself'
            ], 'Forbidden', 403);
        }
        
        // Cannot delete owner or developer unless you're developer
        $targetUser = User::find($id);
        if (!$targetUser) {
            return ResponseFormmater::error([
                'message' => 'User not found'
            ], 'Not Found', 404);
        }
        
        if (in_array($targetUser->role, ['owner', 'developer']) && $user->role !== 'developer') {
            return ResponseFormmater::error([
                'message' => 'Only developer can delete owner or developer accounts'
            ], 'Access Denied', 403);
        }
        
        $targetUser->delete();
        
        return ResponseFormmater::success([
            'message' => 'User deleted successfully'
        ], 'Success');
    }
    
    /**
     * Reset password (owner, admin, developer only)
     */
    public function resetPassword(Request $request, $id)
    {
        $user = $request->user();
        
        // Check permission
        if (!in_array($user->role, ['owner', 'admin', 'developer'])) {
            return ResponseFormmater::error([
                'message' => 'Unauthorized access'
            ], 'Access Denied', 403);
        }
        
        $targetUser = User::find($id);
        
        if (!$targetUser) {
            return ResponseFormmater::error([
                'message' => 'User not found'
            ], 'Not Found', 404);
        }
        
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6|confirmed',
        ]);
        
        if ($validator->fails()) {
            return ResponseFormmater::error([
                'message' => $validator->errors()->first()
            ], 'Validation Failed', 400);
        }
        
        $targetUser->password = Hash::make($request->password);
        $targetUser->save();
        
        return ResponseFormmater::success([
            'message' => 'Password reset successfully'
        ], 'Success');
    }
}
