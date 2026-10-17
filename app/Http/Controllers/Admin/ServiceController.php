<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Layanan publik dan Standar Pelayanan.
 */
class ServiceController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Service::class);

        return Inertia::render('admin/services/Index', [
            'services' => Service::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (Service $service) => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'slug' => $service->slug,
                    'processing_time' => $service->processing_time,
                    'cost' => $service->cost,
                    'is_published' => $service->is_published,
                ]),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Service::class);

        return Inertia::render('admin/services/Form', ['service' => null]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        Service::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Layanan ditambahkan.']);

        return to_route('admin.services.index');
    }

    public function edit(Service $service): Response
    {
        Gate::authorize('update', $service);

        return Inertia::render('admin/services/Form', [
            'service' => [
                ...$service->only([
                    'id', 'name', 'slug', 'description', 'processing_time', 'cost',
                    'product', 'complaint_channel', 'sort_order', 'is_published',
                ]),
                // Ditampilkan sebagai textarea: satu baris satu butir.
                'requirements' => implode("\n", $service->requirements),
                'procedure' => implode("\n", $service->procedure),
                'updated_at' => $service->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Layanan diperbarui.']);

        return to_route('admin.services.index');
    }

    public function destroy(Service $service): RedirectResponse
    {
        Gate::authorize('delete', $service);

        $service->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Layanan dihapus.']);

        return to_route('admin.services.index');
    }
}
