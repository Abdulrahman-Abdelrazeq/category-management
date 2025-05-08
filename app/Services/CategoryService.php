<?php

namespace App\Services;

use App\Models\Category;

class CategoryService
{
    public function hasCircularDependency($parentId, $currentId = null)
    {
        // If no parent ID is given, no cycle is possible
        if (!$parentId) {
            return false;
        }

        $visited = [];
        $currentId = $parentId;

        // Traverse up the parent chain
        while ($currentId) {
            // If we've seen this category before, a cycle exists
            if (in_array($currentId, $visited)) {
                return true;
            }

            $visited[] = $currentId;
            $category = Category::find($currentId);

            // If category not found, stop traversal
            if (!$category) {
                return false;
            }

            $currentId = $category->parent_id;
        }

        return false;
    }
}