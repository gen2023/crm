<?php

namespace App\Modules\Products\Services;

use App\Models\Product;
use App\Support\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    public function paginate(): LengthAwarePaginator
    {
        return Product::query()->orderBy('name')->paginate(20);
    }

    /**
     * @param  array{name: string, sku: string, price: float|string, stock: int, description?: string|null, category?: string|null}  $data
     */
    public function create(array $data, ?UploadedFile $photo = null): Product
    {
        $product = Product::create([
            'name' => $data['name'],
            'sku' => $data['sku'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'photo_path' => $photo ? $this->storePhoto($photo) : null,
        ]);

        $this->auditLogger->log('product.created', $product, [
            'sku' => $product->sku,
        ]);

        return $product;
    }

    /**
     * @param  array{name: string, sku: string, price: float|string, stock: int, description?: string|null, category?: string|null}  $data
     */
    public function update(Product $product, array $data, ?UploadedFile $photo = null): Product
    {
        $oldPhotoPath = $product->photo_path;

        $product->fill([
            'name' => $data['name'],
            'sku' => $data['sku'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
        ]);

        if ($photo) {
            $product->photo_path = $this->storePhoto($photo);
        }

        $product->save();

        if ($photo && $oldPhotoPath) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        $this->auditLogger->log('product.updated', $product, [
            'changes' => $product->getChanges(),
        ]);

        return $product;
    }

    public function delete(Product $product): void
    {
        if ($product->orderItems()->exists()) {
            throw ValidationException::withMessages([
                'product' => 'Нельзя удалить товар, который уже есть в заказах.',
            ]);
        }

        if ($product->photo_path) {
            Storage::disk('public')->delete($product->photo_path);
        }

        $this->auditLogger->log('product.deleted', $product, [
            'sku' => $product->sku,
        ]);

        $product->delete();
    }

    private function storePhoto(UploadedFile $photo): string
    {
        return $photo->store('products', 'public');
    }
}
