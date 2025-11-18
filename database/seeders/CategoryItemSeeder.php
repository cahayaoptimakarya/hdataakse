<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Seeder;

class CategoryItemSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Electronics', 'parent' => 0],
            ['name' => 'Phones', 'parent' => 'Electronics'],
            ['name' => 'Laptops', 'parent' => 'Electronics'],
            ['name' => 'Furniture', 'parent' => 0],
            ['name' => 'Chairs', 'parent' => 'Furniture'],
            ['name' => 'Tables', 'parent' => 'Furniture'],
        ];

        $created = [];

        // Create root categories
        foreach ($categories as $cat) {
            if ($cat['parent'] === 0) {
                $category = Category::updateOrCreate(
                    ['name' => $cat['name']],
                    ['parent_id' => 0]
                );
                $created[$cat['name']] = $category->id;
            }
        }

        // Create children
        foreach ($categories as $cat) {
            if ($cat['parent'] !== 0) {
                $parentId = $created[$cat['parent']] ?? 0;
                $category = Category::updateOrCreate(
                    ['name' => $cat['name']],
                    ['parent_id' => $parentId]
                );
                $created[$cat['name']] = $category->id;
            }
        }

        $items = [
            ['name' => 'iPhone 15', 'category' => 'Phones', 'description' => 'Smartphone'],
            ['name' => 'Galaxy S24', 'category' => 'Phones', 'description' => 'Android flagship'],
            ['name' => 'MacBook Pro', 'category' => 'Laptops', 'description' => 'Powerful notebook'],
            ['name' => 'Office Chair', 'category' => 'Chairs', 'description' => 'Ergonomic chair'],
            ['name' => 'Dining Table', 'category' => 'Tables', 'description' => 'Family dining table'],
        ];

        foreach ($items as $item) {
            $catId = $created[$item['category']] ?? 0;
            Item::updateOrCreate(
                ['name' => $item['name']],
                [
                    'category_id' => $catId,
                    'description' => $item['description'] ?? '',
                ]
            );
        }
    }
}
