<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $query = Announcement::with('user');

        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        if ($request->filled('audience')) {
            $query->where('audience', $request->audience);
        }

        $announcements = $query->latest()->paginate(15)->withQueryString();

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create()
    {
        return view('admin.announcements.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'audience' => ['required', 'string', 'in:students,parents,teachers,all'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $data['user_id'] = auth()->id();
        $data['is_published'] = $request->boolean('is_published');

        if ($data['is_published']) {
            $data['published_at'] = now();
        }

        Announcement::create($data);

        return redirect()->route('admin.announcements.index')
            ->with('success', __('messages.created_successfully'));
    }

    public function show(Announcement $announcement)
    {
        $announcement->load('user');

        return view('admin.announcements.show', compact('announcement'));
    }

    public function edit(Announcement $announcement)
    {
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'audience' => ['required', 'string', 'in:students,parents,teachers,all'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $data['is_published'] = $request->boolean('is_published');

        if ($data['is_published'] && ! $announcement->published_at) {
            $data['published_at'] = now();
        } elseif (! $data['is_published']) {
            $data['published_at'] = null;
        }

        $announcement->update($data);

        return redirect()->route('admin.announcements.index')
            ->with('success', __('messages.updated_successfully'));
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return back()->with('success', __('messages.deleted_successfully'));
    }
}
