<?php

namespace App\Modules\SupplyChain\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['supplier', 'items.product'])->get();
        return response()->json($purchaseOrders, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'order_number' => 'required|string|max:20|unique:purchase_orders',
            'quantity' => 'required|integer',
            'totalAmount' => 'required|numeric',
            'orderDate' => 'required|date',
            'status' => 'required|string|max:20',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer',
            'items.*.unit_price' => 'required|numeric',
        ]);

        DB::beginTransaction();

        try {
            // Create purchase order
            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $validated['supplier_id'],
                'order_number' => $validated['order_number'],
                'quantity' => $validated['quantity'],
                'totalAmount' => $validated['totalAmount'],
                'orderDate' => $validated['orderDate'],
                'status' => $validated['status'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create purchase order items
            foreach ($validated['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            DB::commit();

            return response()->json($purchaseOrder->load(['supplier', 'items.product']), Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        return response()->json($purchaseOrder->load(['supplier', 'items.product']), Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'quantity' => 'sometimes|integer',
            'totalAmount' => 'sometimes|numeric',
            'orderDate' => 'sometimes|date',
            'status' => 'sometimes|string|max:20',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $purchaseOrder->update($validated);

        return response()->json($purchaseOrder->load(['supplier', 'items.product']), Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Receive a purchase order.
     */
    public function receiveOrder(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:purchase_order_items,id',
            'items.*.received_quantity' => 'required|integer',
            'items.*.rejected_quantity' => 'nullable|integer',
            'items.*.rejection_notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $hasRejectedItems = false;
            
            foreach ($validated['items'] as $item) {
                $orderItem = PurchaseOrderItem::find($item['id']);
                $orderItem->received_quantity = $item['received_quantity'];
                
                // Handle rejected quantities if provided
                if (isset($item['rejected_quantity']) && $item['rejected_quantity'] > 0) {
                    $orderItem->rejected_quantity = $item['rejected_quantity'];
                    $hasRejectedItems = true;
                }
                
                // Handle rejection notes if provided
                if (isset($item['rejection_notes'])) {
                    $orderItem->rejection_notes = $item['rejection_notes'];
                }
                
                $orderItem->save();

                // Update product quantity with only the good (received) products
                $product = Product::find($orderItem->product_id);
                $product->quantity += $item['received_quantity'];
                $product->save();
            }

            // Update purchase order status based on rejections
            if ($hasRejectedItems) {
                $purchaseOrder->status = 'Partially Received';
            } else {
                $purchaseOrder->status = 'Received';
            }
            $purchaseOrder->save();

            DB::commit();

            return response()->json($purchaseOrder->load(['supplier', 'items.product']), Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Record discrepancies for a purchase order.
     */
    public function recordDiscrepancies(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:purchase_order_items,id',
            'items.*.rejection_notes' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            foreach ($validated['items'] as $item) {
                $orderItem = PurchaseOrderItem::find($item['id']);
                
                // Only update rejection notes - quantities were set during inspection
                $orderItem->rejection_notes = $item['rejection_notes'];
                $orderItem->save();
            }

            // Update purchase order status to indicate documented discrepancies
            $purchaseOrder->status = 'Discrepancy Reported';
            $purchaseOrder->save();

            DB::commit();

            return response()->json($purchaseOrder->load(['supplier', 'items.product']), Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get purchase order delivery history (received or with discrepancies).
     */
    public function deliveryHistory()
    {
        try {
            $purchaseOrders = PurchaseOrder::with(['supplier', 'items.product'])
                ->whereIn('status', ['Received', 'Partially Received', 'Discrepancy Reported'])
                ->orderBy('updated_at', 'desc')
                ->get();
            
            // Transform the data to include summary information
            $result = $purchaseOrders->map(function ($order) {
                $receivedQuantity = $order->items->sum('received_quantity');
                $rejectedQuantity = $order->items->sum('rejected_quantity');
                $totalQuantity = $order->items->sum('quantity');
                
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'supplier' => $order->supplier ? $order->supplier->name : 'N/A',
                    'products' => $order->items->pluck('product.name')->filter()->implode(', '),
                    'received_quantity' => $receivedQuantity,
                    'rejected_quantity' => $rejectedQuantity,
                    'total_quantity' => $totalQuantity,
                    'total_price' => $order->totalAmount,
                    'date_received' => $order->updated_at->format('Y-m-d'),
                    'status' => $order->status,
                    'items' => $order->items
                ];
            });
            
            return response()->json($result, Response::HTTP_OK);
        } catch (\Exception $e) {
            \Log::error('Delivery history error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 