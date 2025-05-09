<?php

namespace App\Modules\Customer\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SystemConfigController extends Controller
{
    /**
     * Get all system configurations
     */
    public function index()
    {
        $configs = SystemConfig::all();
        return response()->json($configs, Response::HTTP_OK);
    }

    /**
     * Get a specific configuration by key
     */
    public function show($key)
    {
        $config = SystemConfig::where('key', $key)->first();
        
        if (!$config) {
            return response()->json(['error' => 'Configuration not found'], Response::HTTP_NOT_FOUND);
        }
        
        return response()->json($config, Response::HTTP_OK);
    }

    /**
     * Update a configuration value
     */
    public function update(Request $request, $key)
    {
        $config = SystemConfig::where('key', $key)->first();
        
        if (!$config) {
            return response()->json(['error' => 'Configuration not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Validate based on specific key requirements
        if ($key === 'points_exchange_rate') {
            $request->validate([
                'value' => 'required|numeric|min:1|max:1000',
            ]);
        } else {
            $request->validate([
                'value' => 'required',
            ]);
        }
        
        $config->value = $request->value;
        
        if ($request->has('description')) {
            $config->description = $request->description;
        }
        
        $config->save();
        
        return response()->json($config, Response::HTTP_OK);
    }

    /**
     * Get points exchange rate
     */
    public function getPointsExchangeRate()
    {
        $config = SystemConfig::where('key', 'points_exchange_rate')->first();
        
        if (!$config) {
            // Create default if not exists
            $defaultValue = json_encode([
                'php_amount' => 100,
                'points' => 10
            ]);
            
            $config = SystemConfig::setConfig(
                'points_exchange_rate', 
                $defaultValue, 
                'Points earned for PHP spent (e.g., ₱100 = 10 points)'
            );
        }
        
        // Decode JSON value
        $value = json_decode($config->value, true);
        
        if (!is_array($value) || !isset($value['php_amount']) || !isset($value['points'])) {
            // Handle legacy format or invalid data
            $value = [
                'php_amount' => 100,
                'points' => 10
            ];
        }
        
        return response()->json([
            'key' => 'points_exchange_rate',
            'value' => $value,
            'description' => $config->description
        ], Response::HTTP_OK);
    }

    /**
     * Update points exchange rate
     */
    public function updatePointsExchangeRate(Request $request)
    {
        $validated = $request->validate([
            'php_amount' => 'required|numeric|min:1|max:10000',
            'points' => 'required|numeric|min:1|max:10000',
        ]);
        
        $value = json_encode([
            'php_amount' => (float) $validated['php_amount'],
            'points' => (int) $validated['points']
        ]);
        
        $config = SystemConfig::setConfig(
            'points_exchange_rate', 
            $value, 
            'Points earned for PHP spent (e.g., ₱'.$validated['php_amount'].' = '.$validated['points'].' points)'
        );
        
        return response()->json([
            'key' => 'points_exchange_rate',
            'value' => json_decode($config->value, true),
            'description' => $config->description
        ], Response::HTTP_OK);
    }
} 