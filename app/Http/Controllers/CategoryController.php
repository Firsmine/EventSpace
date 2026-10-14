<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $categories = Category::query()->withCount('events')->orderBy('name')->paginate(10);

            return response()->json([
                'success' => true,
                'message' => 'Get all categories success',
                'data' => $categories
            ], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get category data'
            ], 500);
        }
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
        try {
            $data = $request->validate(['name' => 'required|string|max:100|unique:categories,name']);

            $category = Category::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Create category success',
                'data' => $category
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        try {
            $category->loadCount('events');
            return response()->json([
                'success' => true,
                'message' => 'Get category detail success',
                'data' => $category
            ], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get category detail.',
            ], 500);
        }
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
        try {

            $data = $request->validate(['name' => 'required|string|max:100', Rule::unique('categories', 'name')->ignore($category->id)]);
            $category->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Update category success',
                'data' => $category
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to edit category. Make sure you have access.'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        try {

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
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete the category. Make sure you have access.'
            ], 500);
        }
    }

    public function events(Category $category)
    {
        try {

            $events = $category->events()->where('status', 'published')
                ->with(['category', 'creator:id,name,username'])
                ->withCount('activeRegistration as participant_count')
                ->orderBy('event_date')->paginate(10);

            return response()->json([
                'success' => true,
                'message' => 'Get all event based on category success',
                'data' => $events
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get event based on category.'
            ], 500);
        }
    }
}
