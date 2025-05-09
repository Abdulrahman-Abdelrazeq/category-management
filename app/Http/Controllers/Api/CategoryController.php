<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Requests\DestroyMultiCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Traits\CacheKeyManager;
use App\Traits\Response;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Services\CategoryService;

class CategoryController extends Controller
{
    use Response, CacheKeyManager;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of categories with optional search, filtering, and pagination.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Extract query parameters
            $keyword = $request->query('keyword');
            $parentId = $request->query('parent_id');
            $perPage = $request->query('per_page', 10);
            $validRelations = ['parent', 'children'];
            $with = array_intersect(explode(',', $request->query('with', '')), $validRelations);
            $parentOnly = $request->has('parent_only');
            $isList = $request->has('list');
            $page = $request->query('page', 1);

            // Generate unique cache key based on request parameters
            $cacheKey = 'categories_index_' . md5(
                $keyword . '|' .
                $parentId . '|' .
                $perPage . '|' .
                implode(',', $with) . '|' .
                ($parentOnly ? '1' : '0') . '|' .
                ($isList ? 'list' : 'paginated') . '|' .
                $page
            );

            // Store cache key using CacheKeyManager
            $this->storeCacheKey($cacheKey, 'categories_index_keys');

            $data = Cache::remember($cacheKey, now()->addHours(1), function () use (
                $keyword,
                $parentId,
                $perPage,
                $with,
                $parentOnly,
                $isList,
                $page
            ) {
                $query = Category::query();

                // Search by id, name, or parent name
                if ($keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('id', $keyword)
                          ->orWhere('name', 'like', "%$keyword%")
                          ->orWhereHas('parent', function ($q) use ($keyword) {
                              $q->where('name', 'like', "%$keyword%");
                          });
                    });
                }

                // Filter by parent ID
                if ($parentId) {
                    $query->where('parent_id', $parentId);
                }

                // Filter categories with children
                if ($parentOnly) {
                    $query->whereHas('children');
                }

                // Load specified relationships
                if (!empty($with)) {
                    $query->with($with);
                }

                // Return full list or paginated data
                if ($isList) {
                    $categories = $query->select('id', 'name', 'parent_id')->get();
                    return CategoryResource::collection($categories);
                }

                $categories = $query->select('id', 'name', 'parent_id')
                                   ->paginate($perPage, ['*'], 'page', $page);

                return [
                    'categories' => CategoryResource::collection($categories->items()),
                    'total' => $categories->total(),
                    'page' => $categories->currentPage(),
                    'per_page' => $categories->perPage()
                ];
            });

            return $this->sendRes( true, $isList ? 'Categories list retrieved successfully' : 'Categories retrieved successfully', $data, null, 200
            );
        } catch (QueryException $e) {
            return $this->sendRes(false, 'Failed to retrieve categories', null, ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created category.
     *
     * @param CategoryRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CategoryRequest $request)
    {
        try {
            $category = Category::create($request->validated());
            $this->invalidateCacheKeys('categories_index_keys'); // Invalidate index cache
            Cache::forget('categories_tree'); // Invalidate tree cache
            return $this->sendRes(true, 'Category created successfully', new CategoryResource($category), null, 201);
        } catch (QueryException $e) {
            return $this->sendRes(false, 'Failed to create category', null, ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified category.
     *
     * @param Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Category $category)
    {
        return $this->sendRes(true, 'Category retrieved successfully', new CategoryResource($category->load('parent')));
    }

    /**
     * Update the specified category.
     *
     * @param CategoryRequest $request
     * @param Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(CategoryRequest $request, Category $category)
    {
        try {
            $category->update($request->validated());
            // Prevent setting a parent that would create a circular hierarchy
            $parentId = $request->validated('parent_id');
            if ($parentId && $this->categoryService->hasCircularDependency($parentId, $category->id)) {
                return $this->sendRes(false, 'Cannot update category: Circular dependency detected', null, ['error' => ['The selected parent would create a circular reference']], 422);
            }
            
            $this->invalidateCacheKeys('categories_index_keys'); // Invalidate index cache
            Cache::forget('categories_tree'); // Invalidate tree cache
            return $this->sendRes(true, 'Category updated successfully', new CategoryResource($category->fresh()), null, 200);
        } catch (QueryException $e) {
            return $this->sendRes(false, 'Failed to update category', null, ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified category.
     *
     * @param Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Category $category)
    {
        try {
            // Update subclasses to set parent_id to null
            $category->children()->update(['parent_id' => null]);

            $category->delete();
            $this->invalidateCacheKeys('categories_index_keys'); // Invalidate index cache
            Cache::forget('categories_tree'); // Invalidate tree cache
            return $this->sendRes(true, 'Category deleted successfully', null, null, 200);
        } catch (QueryException $e) {
            return $this->sendRes(false, 'Failed to delete category', null, ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove multiple categories.
     *
     * @param DestroyMultiCategoryRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyMultiple(DestroyMultiCategoryRequest $request)
    {
        try {
            $ids = $request->validated()['ids'];

            // Update subclasses to set parent_id to null for all selected classes
            Category::whereIn('parent_id', $ids)->update(['parent_id' => null]);

            Category::whereIn('id', $ids)->delete();
            $this->invalidateCacheKeys('categories_index_keys'); // Invalidate index cache
            Cache::forget('categories_tree'); // Invalidate tree cache
            return $this->sendRes(true, 'Categories deleted successfully', null, null, 200);
        } catch (QueryException $e) {
            return $this->sendRes(false, 'Failed to delete categories', null, ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve the categories tree starting from root nodes.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tree()
    {
        try {
            $categories = Cache::remember('categories_tree', now()->addHours(1), function () {
                return Category::select('id', 'name', 'parent_id')
                    ->with('childrenRecursive')
                    ->whereNull('parent_id')
                    ->get();
            });
            return $this->sendRes(true, 'Categories tree retrieved successfully', CategoryResource::collection($categories), null, 200);
        } catch (QueryException $e) {
            return $this->sendRes(false, 'Failed to retrieve categories tree', null, ['error' => $e->getMessage()], 500);
        }
    }
}