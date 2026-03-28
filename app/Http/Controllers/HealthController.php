<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Mail;


class HealthController extends Controller
{
    public function alive(){
        return ApiResponse::success('alive');
    }

       public function ready()
    {
        $services = [
            'database' => 'ok',
            'cache' => 'ok',
            'queue' => 'ok',
        ];

        $statusCode = 200;

        // DB check
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $services['database'] = 'down';
            $statusCode = 503;
        }

        // Cache check
        try {
            Cache::put('health_check', true, 10);
            if (Cache::get('health_check') !== 'ok') {
                throw new \Exception();
            }
        } catch (\Exception $e) {
            $services['cache'] = 'down';
            $statusCode = 503;
        }

        // Queue check (light check)
        try {
            Queue::dispatch(function (){}); // or dispatch a test job if needed
        } catch (\Exception $e) {
            $services['queue'] = 'down';
            $statusCode = 503;
        }

        // if ($statusCode !== 200){
        //     Mail::to('fikryayoub24@gmail.com')->send(new YouAreApprovedMail($businessName));
        // }

        return response()->json([
            'status' => $statusCode === 200 ? 'ready' : 'not_ready',
            'services' => $services,
            'timestamp' => now()
        ], $statusCode);
    }
}

