<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HelloController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'age' => 'required|integer|min:18',
        ]);

        $name = $validated['name'];
        $age = $validated['age'];

        return response()->json([
            'message' => 'Hello ' . $name . ', you are ' . $age . ' years old.',
        ]);
    }
}
