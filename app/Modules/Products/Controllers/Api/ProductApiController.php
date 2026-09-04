<?php

namespace App\Modules\Products\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Modules\Products\Requests\StoreProductRequest;
use App\Modules\Products\Requests\UpdateProductRequest;
use App\Modules\Products\Resources\ProductResource;
use App\Modules\Products\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Same ProductService and the same StoreProductRequest/UpdateProductRequest
 * rules as the web ProductController — this controller only adapts the
 * request/response shape to JSON, per the API-controllers-share-Services
 * rule in docs/ARCHITECTURE.md.
 */
class ProductApiController extends Controller
{
    public function __construct(private readonly ProductService $productService)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection($this->productService->paginate());
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated(), $request->file('photo'));

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        return new ProductResource(
            $this->productService->update($product, $request->validated(), $request->file('photo'))
        );
    }
}
