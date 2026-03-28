<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProviderRequest;
use App\Services\ProviderService;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function index(Request $request,ProviderService $providerService)
    {
        $providers = $providerService->getProviders($request);

        return ApiResponse::success(
            'fetched successfully',
            200,
            $providers
        );
    }
    public function getApprovedProviders(Request $request,ProviderService $providerService)
    { 
        $approvedProviders = $providerService->getApprovedProviders($request);

        return ApiResponse::success(
            'fetched successfully',
            200,
            $approvedProviders
        );
    }

    public function update(UpdateProviderRequest $request,ProviderService $providerService)
    {
        $account = auth()->user();
        if (!$account->provider) {
            return ApiResponse::error('Provider not found', 404);
        }

        $validatedData = $request->validated();

        $provider=$providerService->updateProvider($account,$validatedData);

        return ApiResponse::success(
            'Info updated successfully',
            200,
            $provider
        );
    }


    public function updateStatus(Request $request,$id,ProviderService $providerService){
        $validated = $request->validate(['status'=>"required|string|in:approved,rejected"]);
        $status = $validated['status'];
        $providerService->updateStatus($id,$status);
        
        return ApiResponse::success("Provider is {$status} successfuly");

    }
}
