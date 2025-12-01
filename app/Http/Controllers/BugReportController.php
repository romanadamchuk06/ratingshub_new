<?php

namespace App\Http\Controllers;

use App\Mail\BugReportCreated;
use App\Models\BugReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

/**
 * BUG REPORT CONTROLLER
 * =====================
 *
 * Handles:
 * - User Bug-Reports (erstellen, anzeigen)
 * - Admin Bug-Management (status ändern, zuweisen, notes)
 */
class BugReportController extends Controller
{
    /**
     * Zeige Bug-Report Formular (User)
     */
    public function create()
    {
        return Inertia::render('BugReport/Create');
    }

    /**
     * Speichere neuen Bug-Report
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:10',
            'type' => 'required|in:bug,feature,improvement,question',
            'steps_to_reproduce' => 'nullable|string',
        ]);

        // Browser & OS automatisch erkennen
        $userAgent = $request->header('User-Agent');
        $browser = $this->detectBrowser($userAgent);
        $os = $this->detectOS($userAgent);

        // Page URL (Referer)
        $pageUrl = $request->header('Referer') ?? url()->previous();

        $bugReport = BugReport::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'steps_to_reproduce' => $validated['steps_to_reproduce'] ?? null,
            'page_url' => $pageUrl,
            'browser' => $browser,
            'os' => $os,
            'priority' => 'medium', // Default
            'status' => 'open',
        ]);

        // Email an Admin(s) senden
        // BugReport mit User-Relation laden für Email-Template
        $bugReport->load('user:id,name,email');

        // 1. Versuche ADMIN_EMAIL aus .env
        $adminEmail = config('app.admin_email') ?? env('ADMIN_EMAIL');

        if ($adminEmail) {
            // Email an konfigurierte Admin-Email senden
            Mail::to($adminEmail)->send(new BugReportCreated($bugReport));
        } else {
            // Fallback: An alle Admin-User senden
            $admins = \App\Models\User::where('is_admin', true)->get();
            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new BugReportCreated($bugReport));
            }
        }

        return redirect()->route('bug-reports.my-reports')
            ->with('success', 'Vielen Dank! Dein Bug-Report wurde eingereicht.');
    }

    /**
     * Zeige User's eigene Bug-Reports
     */
    public function myReports()
    {
        $bugReports = BugReport::where('user_id', auth()->id())
            ->with('assignedAdmin:id,name')
            ->recent()
            ->paginate(20);

        return Inertia::render('BugReport/MyReports', [
            'bugReports' => $bugReports,
        ]);
    }

    /**
     * ADMIN: Alle Bug-Reports anzeigen
     */
    public function index(Request $request)
    {
        $query = BugReport::with(['user:id,name,email', 'assignedAdmin:id,name']);

        // Filter: Status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter: Type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter: Priority
        if ($request->has('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        $bugReports = $query->byPriority()
            ->recent()
            ->paginate(20)
            ->appends($request->all());

        // Stats
        $stats = [
            'total' => BugReport::count(),
            'open' => BugReport::where('status', 'open')->count(),
            'in_progress' => BugReport::where('status', 'in_progress')->count(),
            'resolved' => BugReport::where('status', 'resolved')->count(),
        ];

        return Inertia::render('Admin/BugReports/Index', [
            'bugReports' => $bugReports,
            'stats' => $stats,
            'filters' => $request->only(['status', 'type', 'priority', 'search']),
        ]);
    }

    /**
     * ADMIN: Bug-Report Details anzeigen & bearbeiten
     */
    public function show(BugReport $bugReport)
    {
        $bugReport->load(['user:id,name,email', 'assignedAdmin:id,name']);

        // Admins holen (für Zuweisung)
        $admins = \App\Models\User::where('is_admin', true)->get(['id', 'name']);

        return Inertia::render('Admin/BugReports/Show', [
            'bugReport' => $bugReport,
            'admins' => $admins,
        ]);
    }

    /**
     * ADMIN: Update Bug-Report (Status, Priority, Zuweisen, Notes)
     */
    public function update(Request $request, BugReport $bugReport)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:open,in_progress,resolved,closed',
            'priority' => 'sometimes|in:low,medium,high,critical',
            'assigned_to' => 'nullable|exists:users,id',
            'admin_notes' => 'nullable|string',
        ]);

        // Leerer String zu NULL konvertieren für assigned_to
        if (isset($validated['assigned_to']) && $validated['assigned_to'] === '') {
            $validated['assigned_to'] = null;
        }

        // Wenn Status auf "resolved" gesetzt wird, resolved_at setzen
        if (isset($validated['status']) && $validated['status'] === 'resolved' && !$bugReport->resolved_at) {
            $validated['resolved_at'] = now();
        }

        $bugReport->update($validated);

        // Für Inertia: Redirect mit proper status
        return to_route('admin.bug-reports.show', $bugReport->id)
            ->with('success', 'Bug-Report wurde aktualisiert.');
    }

    /**
     * ADMIN: Bug-Report löschen
     */
    public function destroy(BugReport $bugReport)
    {
        $bugReport->delete();

        return redirect()->route('admin.bug-reports.index')
            ->with('success', 'Bug-Report wurde gelöscht.');
    }

    /**
     * Detect Browser from User-Agent
     */
    private function detectBrowser(string $userAgent): string
    {
        if (str_contains($userAgent, 'Firefox')) {
            return 'Firefox';
        } elseif (str_contains($userAgent, 'Edg')) {
            return 'Edge';
        } elseif (str_contains($userAgent, 'Chrome')) {
            return 'Chrome';
        } elseif (str_contains($userAgent, 'Safari')) {
            return 'Safari';
        } elseif (str_contains($userAgent, 'Opera') || str_contains($userAgent, 'OPR')) {
            return 'Opera';
        }

        return 'Unknown';
    }

    /**
     * Detect OS from User-Agent
     */
    private function detectOS(string $userAgent): string
    {
        if (str_contains($userAgent, 'Windows')) {
            return 'Windows';
        } elseif (str_contains($userAgent, 'Mac')) {
            return 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            return 'Linux';
        } elseif (str_contains($userAgent, 'Android')) {
            return 'Android';
        } elseif (str_contains($userAgent, 'iOS') || str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            return 'iOS';
        }

        return 'Unknown';
    }
}
