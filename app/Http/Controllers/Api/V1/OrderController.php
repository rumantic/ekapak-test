<?php

namespace App\Http\Controllers\Api\V1;

use App\DTO\CreateOrderDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Http\Requests\Api\V1\UpdateOrderStatusRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Order::with(['items.product', 'customer']);

        if ($request->filled('status')) {
            $query->byStatus($request->input('status'));
        }

        if ($request->filled('customer_id')) {
            $query->byCustomer((int) $request->input('customer_id'));
        }

        $query->byDateRange(
            $request->input('date_from'),
            $request->input('date_to'),
        );

        $orders = $query->latest()->paginate($request->input('per_page', 15));

        return OrderResource::collection($orders);
    }

    public function show(Order $order): OrderResource
    {
        $order->load(['items.product', 'customer']);

        return new OrderResource($order);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $dto = new CreateOrderDTO(
            customerId: $request->validated('customer_id'),
            items: $request->validated('items'),
        );

        $order = $this->orderService->createOrder($dto);

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): OrderResource
    {
        $order = $this->orderService->updateStatus($order, $request->validated('status'));

        return new OrderResource($order);
    }
}
