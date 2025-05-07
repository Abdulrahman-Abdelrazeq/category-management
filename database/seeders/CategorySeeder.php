<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء 5 تصنيفات رئيسية
        $parents = Category::factory()->count(5)->create();

        // لكل تصنيف رئيسي، إنشاء من 2 إلى 5 تصنيفات فرعية
        foreach ($parents as $parent) {
            $children = Category::factory()
                ->count(rand(2, 5))
                ->create(['parent_id' => $parent->id]);

            // ولكل تصنيف فرعي، أنشئ تصنيفات فرعية فرعية
            foreach ($children as $child) {
                Category::factory()
                    ->count(rand(1, 3))
                    ->create(['parent_id' => $child->id]);
            }
        }
    }
}
