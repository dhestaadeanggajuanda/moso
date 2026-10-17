<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServiceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ServiceResource::collection(Service::query()->published()->get());
    }

    public function show(Service $service): ServiceResource
    {
        // Layanan yang belum terbit diperlakukan seperti tidak ada.
        abort_unless($service->is_published, 404);

        return new ServiceResource($service);
    }
}
