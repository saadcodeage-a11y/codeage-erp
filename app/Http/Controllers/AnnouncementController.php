<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $canCreateAnnouncements = $user->canAccessModule('announcements', 'create');
        $canEditAnnouncements = $user->canAccessModule('announcements', 'edit');
        $canManageAnnouncements = $canCreateAnnouncements || $canEditAnnouncements || $user->isSuperAdmin();

        $announcementsQuery = Announcement::query()
            ->with(['departments', 'creator'])
            ->visibleTo($user);

        if ($search = trim((string) $request->get('search'))) {
            $announcementsQuery->where(function (Builder $query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('departments', function (Builder $departmentQuery) use ($search) {
                        $departmentQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('creator', function (Builder $creatorQuery) use ($search) {
                        $creatorQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('department')) {
            $departmentId = (int) $request->get('department');
            $announcementsQuery->where(function (Builder $query) use ($departmentId) {
                $query->where('is_global', true)
                    ->orWhereHas('departments', function (Builder $departmentQuery) use ($departmentId) {
                        $departmentQuery->where('departments.id', $departmentId);
                    });
            });
        }

        if ($request->get('status') === 'active') {
            $announcementsQuery->where('is_active', true);
        } elseif ($request->get('status') === 'inactive') {
            $announcementsQuery->where('is_active', false);
        }

        $announcements = (clone $announcementsQuery)
            ->latest('published_at')
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        $statsBase = Announcement::query()->visibleTo($user);
        $stats = [
            'total' => (clone $statsBase)->count(),
            'global' => (clone $statsBase)->where('is_global', true)->count(),
            'department' => (clone $statsBase)->where('is_global', false)->count(),
            'active' => (clone $statsBase)->where('is_active', true)->count(),
        ];

        $departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('announcements.index', compact(
            'announcements',
            'departments',
            'stats',
            'canCreateAnnouncements',
            'canEditAnnouncements',
            'canManageAnnouncements'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'is_global' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'exists:departments,id',
        ]);

        $isGlobal = $request->boolean('is_global');
        $departmentIds = collect($validated['department_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (! $isGlobal && $departmentIds === []) {
            return response()->json([
                'message' => 'Select at least one department or mark the announcement as global.',
            ], 422);
        }

        $announcement = Announcement::create([
            'title' => $validated['title'],
            'message' => $validated['message'],
            'is_global' => $isGlobal,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $request->user()->id,
            'published_at' => now(),
        ]);

        $announcement->departments()->sync($isGlobal ? [] : $departmentIds);

        return response()->json([
            'success' => true,
            'message' => 'Announcement published successfully.',
        ]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'is_global' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'exists:departments,id',
        ]);

        $isGlobal = $request->boolean('is_global');
        $departmentIds = collect($validated['department_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (! $isGlobal && $departmentIds === []) {
            return response()->json([
                'message' => 'Select at least one department or mark the announcement as global.',
            ], 422);
        }

        $announcement->update([
            'title' => $validated['title'],
            'message' => $validated['message'],
            'is_global' => $isGlobal,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $announcement->departments()->sync($isGlobal ? [] : $departmentIds);

        return response()->json([
            'success' => true,
            'message' => 'Announcement updated successfully.',
        ]);
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Announcement deleted successfully.',
        ]);
    }
}
