<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index() {
        $categories = Category::with('manager')->paginate(10);
        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        $managers = User::role(['manager, admin'])->get();
        return view('categories,create', compact('managers'));

    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'user_id' => $request->user_id,
        ]);

        return redirect()->route('categories.index')->with('success', 'Categoria creada exitosamente');

    }
}
