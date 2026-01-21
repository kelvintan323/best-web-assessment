<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
    }

    public function test_can_list_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(5)->create(['category_id' => $category->id]);

        $response = $this
            ->actingAs($this->admin, 'admin')
            ->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'products' => [
                        'data' => [
                            '*' => ['id', 'name', 'category_id', 'price', 'stock', 'is_enabled'],
                        ],
                    ],
                ],
            ]);
    }

    public function test_can_list_products_with_pagination(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(15)->create(['category_id' => $category->id]);

        $response = $this
            ->actingAs($this->admin, 'admin')
            ->getJson('/api/products?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data.products.data'));
    }

    public function test_can_filter_products_by_status(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id, 'is_enabled' => true]);
        Product::factory()->count(2)->create(['category_id' => $category->id, 'is_enabled' => false]);

        $response = $this
            ->actingAs($this->admin, 'admin')
            ->getJson('/api/products?status=1');

        $response->assertStatus(200);
        $products = $response->json('data.products.data');
        foreach ($products as $product) {
            $this->assertTrue((bool) $product['is_enabled']);
        }
    }

    public function test_can_filter_products_by_category(): void
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category1->id]);
        Product::factory()->count(2)->create(['category_id' => $category2->id]);

        $response = $this
            ->actingAs($this->admin, 'admin')
            ->getJson('/api/products?category_id=' . $category1->id);

        $response->assertStatus(200);
        $products = $response->json('data.products.data');
        foreach ($products as $product) {
            $this->assertEquals($category1->id, $product['category_id']);
        }
    }

    public function test_can_show_single_product(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this
            ->actingAs($this->admin, 'admin')
            ->getJson('/api/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.product.id', $product->id)
            ->assertJsonPath('data.product.name', $product->name);
    }

    public function test_show_returns_404_for_nonexistent_product(): void
    {
        $response = $this
            ->actingAs($this->admin, 'admin')
            ->getJson('/api/products/99999');

        $response->assertStatus(404);
    }

    public function test_can_create_product(): void
    {
        $category = Category::factory()->create();

        $productData = [
            'name' => 'Test Product',
            'category_id' => $category->id,
            'description' => 'Test description',
            'price' => 9999,
            'stock' => 50,
            'is_enabled' => true,
        ];

        $response = $this
            ->actingAs($this->admin, 'admin')
            ->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJsonPath('data.product.name', 'Test Product')
            ->assertJsonPath('data.product.price', 9999);

        $this->assertDatabaseHas('products', ['name' => 'Test Product']);
    }

    public function test_create_product_validates_required_fields(): void
    {
        $response = $this
            ->actingAs($this->admin, 'admin')
            ->postJson('/api/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'category_id', 'price', 'stock']);
    }

    public function test_create_product_validates_category_exists(): void
    {
        $productData = [
            'name' => 'Test Product',
            'category_id' => 99999,
            'price' => 9999,
            'stock' => 50,
        ];

        $response = $this
            ->actingAs($this->admin, 'admin')
            ->postJson('/api/products', $productData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_can_update_product(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $updateData = [
            'name' => 'Updated Product Name',
            'category_id' => $category->id,
            'description' => 'Updated description',
            'price' => 19999,
            'stock' => 100,
            'is_enabled' => false,
        ];

        $response = $this
            ->actingAs($this->admin, 'admin')
            ->putJson('/api/products/' . $product->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.product.name', 'Updated Product Name')
            ->assertJsonPath('data.product.price', 19999);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Product Name']);
    }

    public function test_can_delete_product(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this
            ->actingAs($this->admin, 'admin')
            ->deleteJson('/api/products/' . $product->id, []);

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Product deleted successfully');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_can_bulk_delete_products(): void
    {
        $category = Category::factory()->create();
        $products = Product::factory()->count(3)->create(['category_id' => $category->id]);
        $ids = $products->pluck('id')->toArray();

        $response = $this
            ->actingAs($this->admin, 'admin')
            ->postJson('/api/products/bulk-delete', ['ids' => $ids]);

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Products deleted successfully');

        foreach ($ids as $id) {
            $this->assertSoftDeleted('products', ['id' => $id]);
        }
    }

    public function test_bulk_delete_validates_ids(): void
    {
        $response = $this
            ->actingAs($this->admin, 'admin')
            ->postJson('/api/products/bulk-delete', ['ids' => [99999]]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids.0']);
    }

    public function test_unauthenticated_user_cannot_access_products(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401);
    }

    public function test_can_export_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(5)->create(['category_id' => $category->id]);

        $response = $this
            ->actingAs($this->admin, 'admin')
            ->get('/api/products/export');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_product_includes_category_relationship(): void
    {
        $category = Category::factory()->create(['name' => 'Electronics']);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this
            ->actingAs($this->admin, 'admin')
            ->getJson('/api/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.product.category.id', $category->id)
            ->assertJsonPath('data.product.category.name', 'Electronics');
    }
}
