<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::all());
    }

    public function show($id)
    {
        return response()->json(User::findOrFail($id));
    }

    public function store(Request $request)
    {
        $user = User::create($request->all());
        return response()->json($user, 201);
    }
    
    public function count()
    {
        $userCount = User::count();
        return response()->json(['count' => $userCount]);
    }
    public function update(Request $request, $id)
{
    $user = User::findOrFail($id);
    
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,'.$user->id,
        'password' => 'nullable|string|min:8' // Make password optional
    ]);
    
    // Only update password if it was provided
    if (empty($validated['password'])) {
        unset($validated['password']);
    }
    
    $user->update($validated);
    return response()->json($user);
}

    public function destroy($id)
    {
        User::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

}

