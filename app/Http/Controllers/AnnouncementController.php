<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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

        if ($request->filled('type')) {
            $announcementsQuery->where('announcement_type', $request->get('type'));
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
            'user',
            'canCreateAnnouncements',
            'canEditAnnouncements',
            'canManageAnnouncements'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validateAnnouncement($request);

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
            'announcement_type' => $validated['announcement_type'],
            'date_mode' => $validated['announcement_type'] === Announcement::TYPE_OFFICIAL_HOLIDAY ? $validated['date_mode'] : null,
            'event_date' => $validated['announcement_type'] === Announcement::TYPE_OFFICIAL_HOLIDAY && $validated['date_mode'] === Announcement::DATE_MODE_SINGLE ? $validated['event_date'] : null,
            'event_start_date' => $validated['announcement_type'] === Announcement::TYPE_OFFICIAL_HOLIDAY && $validated['date_mode'] === Announcement::DATE_MODE_RANGE ? $validated['event_start_date'] : null,
            'event_end_date' => $validated['announcement_type'] === Announcement::TYPE_OFFICIAL_HOLIDAY && $validated['date_mode'] === Announcement::DATE_MODE_RANGE ? $validated['event_end_date'] : null,
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
        $validated = $this->validateAnnouncement($request);

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
            'announcement_type' => $validated['announcement_type'],
            'date_mode' => $validated['announcement_type'] === Announcement::TYPE_OFFICIAL_HOLIDAY ? $validated['date_mode'] : null,
            'event_date' => $validated['announcement_type'] === Announcement::TYPE_OFFICIAL_HOLIDAY && $validated['date_mode'] === Announcement::DATE_MODE_SINGLE ? $validated['event_date'] : null,
            'event_start_date' => $validated['announcement_type'] === Announcement::TYPE_OFFICIAL_HOLIDAY && $validated['date_mode'] === Announcement::DATE_MODE_RANGE ? $validated['event_start_date'] : null,
            'event_end_date' => $validated['announcement_type'] === Announcement::TYPE_OFFICIAL_HOLIDAY && $validated['date_mode'] === Announcement::DATE_MODE_RANGE ? $validated['event_end_date'] : null,
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

    protected function validateAnnouncement(Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'announcement_type' => 'required|in:' . implode(',', array_keys(Announcement::types())),
            'date_mode' => 'nullable|in:' . implode(',', array_keys(Announcement::dateModes())),
            'event_date' => 'nullable|date',
            'event_start_date' => 'nullable|date',
            'event_end_date' => 'nullable|date|after_or_equal:event_start_date',
            'is_global' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'exists:departments,id',
        ]);

        if ($validated['announcement_type'] === Announcement::TYPE_OFFICIAL_HOLIDAY) {
            if (! in_array($validated['date_mode'] ?? null, array_keys(Announcement::dateModes()), true)) {
                throw ValidationException::withMessages([
                    'date_mode' => 'Select whether the holiday announcement is for a single date or a date range.',
                ]);
            }

            if (($validated['date_mode'] ?? null) === Announcement::DATE_MODE_SINGLE && empty($validated['event_date'])) {
                throw ValidationException::withMessages([
                    'event_date' => 'Select the holiday date for a single-date holiday announcement.',
                ]);
            }

            if (($validated['date_mode'] ?? null) === Announcement::DATE_MODE_RANGE && (empty($validated['event_start_date']) || empty($validated['event_end_date']))) {
                throw ValidationException::withMessages([
                    'event_start_date' => 'Select both start and end dates for a holiday date-range announcement.',
                ]);
            }
        }

        return $validated;
    }
}
