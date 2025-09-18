<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function getMainCategories()
    {
        $mainCategories = Category::whereNull('parent_id')->get();

        return response()->json([
            'data' => $mainCategories
        ], 200);
    }

    public function getSubCategories(Request $request)
    {
        // Accept either a single ID or an array of IDs
        $ids = is_array($request->ids) ? $request->ids : [$request->ids];

        $subCategories = Category::whereIn('parent_id', $ids)->get();

        $grouped = $subCategories->groupBy('parent_id');

        return response()->json([
            'data' => $grouped
        ], 200);
    }
    public function getCategories()
    {
        $categories = Category::all();
        return response()->json([
            'data' => $categories
        ], 200);
    }
}
