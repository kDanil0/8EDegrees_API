<?php

namespace App\Modules\Customer\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RewardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rewards = Reward::all();
        return response()->json($rewards, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string',
            'pointsNeeded' => 'required|integer|min:1',
        ]);

        $reward = Reward::create($validated);

        return response()->json($reward, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Reward $reward)
    {
        return response()->json($reward, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reward $reward)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:50',
            'description' => 'nullable|string',
            'pointsNeeded' => 'sometimes|integer|min:1',
        ]);

        $reward->update($validated);

        return response()->json($reward, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reward $reward)
    {
        $reward->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
} 