<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AgendaRequest;
use App\Models\Agenda;
use App\Services\Storage\SecureUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AgendaController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Agenda::class);

        return Inertia::render('admin/agendas/Index', [
            'agendas' => Agenda::query()
                ->orderByDesc('start_at')
                ->orderByDesc('id')
                ->paginate(15)
                ->through(fn (Agenda $agenda) => [
                    'id' => $agenda->id,
                    'title' => $agenda->title,
                    'location' => $agenda->location,
                    'start_at' => $agenda->start_at->toIso8601String(),
                    'end_at' => $agenda->end_at?->toIso8601String(),
                    'is_published' => $agenda->is_published,
                ]),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Agenda::class);

        return Inertia::render('admin/agendas/Form', ['agenda' => null]);
    }

    public function store(AgendaRequest $request, SecureUploadService $uploads): RedirectResponse
    {
        $agenda = new Agenda($request->safe()->except(['image', 'remove_image']));
        $agenda->created_by = $request->user()?->id;

        if ($request->hasFile('image')) {
            $agenda->image_path = $uploads->storeImage($request->file('image'), 'agendas');
        }

        $agenda->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agenda ditambahkan.']);

        return to_route('admin.agendas.index');
    }

    public function edit(Agenda $agenda): Response
    {
        Gate::authorize('update', $agenda);

        return Inertia::render('admin/agendas/Form', [
            'agenda' => [
                ...$agenda->only(['id', 'title', 'slug', 'description', 'location', 'organizer', 'is_published']),
                // Format untuk <input type="datetime-local"> dalam zona waktu aplikasi.
                'start_at' => $agenda->start_at->format('Y-m-d\TH:i'),
                'end_at' => $agenda->end_at?->format('Y-m-d\TH:i'),
                'image_url' => $agenda->image_url,
            ],
        ]);
    }

    public function update(AgendaRequest $request, Agenda $agenda, SecureUploadService $uploads): RedirectResponse
    {
        $agenda->fill($request->safe()->except(['image', 'remove_image']));

        $oldImage = $agenda->image_path;

        if ($request->hasFile('image')) {
            $agenda->image_path = $uploads->storeImage($request->file('image'), 'agendas');
        } elseif ($request->boolean('remove_image')) {
            $agenda->image_path = null;
        }

        $agenda->save();

        if ($oldImage !== $agenda->image_path) {
            $uploads->delete($oldImage);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agenda diperbarui.']);

        return to_route('admin.agendas.index');
    }

    public function destroy(Agenda $agenda, SecureUploadService $uploads): RedirectResponse
    {
        Gate::authorize('delete', $agenda);

        $agenda->delete();
        $uploads->delete($agenda->image_path);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Agenda dihapus.']);

        return to_route('admin.agendas.index');
    }
}
