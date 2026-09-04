<?php

namespace App\Modules\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Modules\Orders\Requests\StoreOrderRequest;
use App\Modules\Orders\Requests\UpdateOrderRequest;
use App\Modules\Orders\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService)
    {
    }

    public function index(): View
    {
        return view('orders.index', [
            'orders' => $this->orderService->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('orders.create', [
            'customers' => Customer::orderBy('name')->get(),
            'products' => Product::orderBy('name')->get(),
            'statuses' => Order::STATUS_LABELS,
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $this->orderService->create($request->validated());

        return redirect()->route('orders.index')->with('status', 'Заказ создан.');
    }

    public function show(Order $order): View
    {
        return view('orders.show', [
            'order' => $order->load('items.product', 'customer'),
        ]);
    }

    public function edit(Order $order): View
    {
        return view('orders.edit', [
            'order' => $order->load('items'),
            'customers' => Customer::orderBy('name')->get(),
            'products' => Product::orderBy('name')->get(),
            'statuses' => Order::STATUS_LABELS,
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $this->orderService->update($order, $request->validated());

        return redirect()->route('orders.index')->with('status', 'Заказ обновлён.');
    }
}
