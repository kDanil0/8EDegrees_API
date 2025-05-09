<?php

namespace App\Modules\SupplyChain\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $suppliers = Supplier::all();
        return response()->json($suppliers, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'contactNum' => 'required|string|max:20',
            'address' => 'required|string|max:50',
        ]);

        $supplier = Supplier::create($validated);

        return response()->json($supplier, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        return response()->json($supplier, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'contactNum' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:50',
        ]);

        $supplier->update($validated);

        return response()->json($supplier, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
} 