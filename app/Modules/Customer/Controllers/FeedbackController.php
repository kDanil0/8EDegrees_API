<?php

namespace App\Modules\Customer\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FeedbackController extends Controller
{
    /**
     * Store a newly created feedback in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'ratings' => 'required|numeric|min:1|max:5',
            'comments' => 'nullable|string',
            'is_critical' => 'boolean',
        ]);

        // Set the date_submitted to the current date
        $validated['date_submitted'] = Carbon::now()->toDateString();
        
        // Default is_critical to false if not provided
        $validated['is_critical'] = $validated['is_critical'] ?? false;

        $feedback = Feedback::create($validated);

        return response()->json($feedback, 201);
    }

    /**
     * Display a listing of feedback.
     */
    public function index()
    {
        $feedback = Feedback::with('customer')->latest()->get();
        return response()->json($feedback);
    }

    /**
     * Display the specified feedback.
     */
    public function show(string $id)
    {
        $feedback = Feedback::with('customer')->findOrFail($id);
        return response()->json($feedback);
    }

    /**
     * Update the specified feedback in storage.
     */
    public function update(Request $request, string $id)
    {
        $feedback = Feedback::findOrFail($id);
        
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'ratings' => 'numeric|min:1|max:5',
            'comments' => 'nullable|string',
            'is_critical' => 'boolean',
            'date_submitted' => 'date',
        ]);

        $feedback->update($validated);

        return response()->json($feedback);
    }

    /**
     * Remove the specified feedback from storage.
     */
    public function destroy(string $id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->delete();

        return response()->json(null, 204);
    }
} 