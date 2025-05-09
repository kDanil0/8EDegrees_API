<?php

namespace App\Modules\Customer\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RewardsHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RewardsHistoryController extends Controller
{
    /**
     * Display a listing of rewards history.
     */
    public function index()
    {
        $rewardsHistory = RewardsHistory::with(['customer', 'reward'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'customer' => $item->customer->name,
                    'reward' => $item->reward->name,
                    'date' => $item->created_at->format('M d, Y'),
                ];
            });
            
        return response()->json($rewardsHistory, Response::HTTP_OK);
    }
} 