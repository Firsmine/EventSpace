<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Psr\Http\Message\ResponseInterface;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'Get all categories success',
            'data' => Category::query()->withCount('events')->orderBy('name')->paginate(10)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100|unique:categories,name']);

        $category = Category::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Create category success',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return response()->json([
            $category->loadCount('events')
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $data = $request->validate(['name' => 'required|string|max:100', Rule::unless('categories', 'name')->ignore($category->id)]);
        $category->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Update category success',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        if ($category->events()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Category is still used in an event'
            ], 409);
        }
        $category->delete();
        return response()->json([
            'success' => true,
            'message' => 'Delete category success'
        ]);
    }

    public function events(Category $category)
    {
        $events = $category->events()->where('status', 'published')
            ->with(['category', 'creator:id,name,username'])
            ->withCount('activeRegistration as participant_count')
            ->orderBy('event_date')->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Get all event based on category success',
            'data' => $events
        ]);
    }
}
