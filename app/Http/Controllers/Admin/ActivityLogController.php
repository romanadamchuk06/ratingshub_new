<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserActivityLog;
use App\Models\PlanActivityLog;
use App\Models\SubscriptionActivityLog;
use App\Models\PromoCodeActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ACTIVITY LOG CONTROLLER (READ-ONLY)
 * ====================================
 *
 * Zeigt alle Activity Logs im Admin-Bereich.
 *
 * WICHTIG: Logs sind IMMUTABLE (unveränderlich)!
 * - Keine Edit-Funktionen
 * - Keine Delete-Funktionen
 * - Nur Anzeigen und Filtern
 *
 * Features:
 * - Alle Logs zusammen anzeigen
 * - Nach Typ filtern (User, Plan, Subscription, PromoCode)
 * - Nach Datum filtern
 * - Nach Aktion filtern
 * - Nach User filtern
 * - Suche
 *
 * Zweck:
 * - Nachvollziehbarkeit: Was wurde wann von wem geändert?
 * - Audit: Compliance-Anforderungen erfüllen
 * - Support: Bei Problemen Historie ansehen
 * - Security: Verdächtige Aktivitäten erkennen
 */
class ActivityLogController extends Controller
{
    /**
     * Zeige alle Activity Logs
     */
    public function index(Request $request): Response
    {
        // Filter-Parameter
        $type = $request->input('type', 'all'); // all, user, plan, subscription, promo_code
        $action = $request->input('action');
        $userId = $request->input('user_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $search = $request->input('search');

        // Logs sammeln basierend auf Typ
        $logs = collect();

        if ($type === 'all' || $type === 'user') {
            $userLogs = UserActivityLog::with(['performedBy', 'targetUser'])
                ->when($action, fn($q) => $q->where('action', $action))
                ->when($userId, fn($q) => $q->where('performed_by_user_id', $userId))
                ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->where('created_at', '<=', $dateTo))
                ->when($search, fn($q) => $q->where('description', 'like', "%{$search}%"))
                ->latest()
                ->limit($type === 'all' ? 50 : 100)
                ->get()
                ->map(fn($log) => [
                    'id' => $log->id,
                    'type' => 'user',
                    'type_label' => 'Benutzer',
                    'performed_by' => $log->performedBy?->name ?? 'System',
                    'target' => $log->targetUser?->name ?? 'Unbekannt',
                    'action' => $log->action,
                    'action_label' => $this->getActionLabel($log->action),
                    'changes' => $log->changes,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at,
                ]);

            $logs = $logs->concat($userLogs);
        }

        if ($type === 'all' || $type === 'plan') {
            $planLogs = PlanActivityLog::with('performedBy')
                ->when($action, fn($q) => $q->where('action', $action))
                ->when($userId, fn($q) => $q->where('performed_by_user_id', $userId))
                ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->where('created_at', '<=', $dateTo))
                ->when($search, fn($q) => $q->where('description', 'like', "%{$search}%"))
                ->latest()
                ->limit($type === 'all' ? 50 : 100)
                ->get()
                ->map(fn($log) => [
                    'id' => $log->id,
                    'type' => 'plan',
                    'type_label' => 'Plan',
                    'performed_by' => $log->performedBy?->name ?? 'Unbekannt',
                    'target' => $log->plan_name,
                    'action' => $log->action,
                    'action_label' => $this->getActionLabel($log->action),
                    'changes' => $log->changes,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at,
                ]);

            $logs = $logs->concat($planLogs);
        }

        if ($type === 'all' || $type === 'subscription') {
            $subLogs = SubscriptionActivityLog::with(['performedBy', 'targetUser'])
                ->when($action, fn($q) => $q->where('action', $action))
                ->when($userId, fn($q) => $q->where('performed_by_user_id', $userId))
                ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->where('created_at', '<=', $dateTo))
                ->when($search, fn($q) => $q->where('description', 'like', "%{$search}%"))
                ->latest()
                ->limit($type === 'all' ? 50 : 100)
                ->get()
                ->map(fn($log) => [
                    'id' => $log->id,
                    'type' => 'subscription',
                    'type_label' => 'Subscription',
                    'performed_by' => $log->performedBy?->name ?? 'System',
                    'target' => $log->targetUser?->name ?? 'Unbekannt',
                    'action' => $log->action,
                    'action_label' => $this->getActionLabel($log->action),
                    'changes' => $log->changes,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at,
                ]);

            $logs = $logs->concat($subLogs);
        }

        if ($type === 'all' || $type === 'promo_code') {
            $promoLogs = PromoCodeActivityLog::with(['performedBy', 'usedBy'])
                ->when($action, fn($q) => $q->where('action', $action))
                ->when($userId, fn($q) => $q->where('performed_by_user_id', $userId))
                ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->where('created_at', '<=', $dateTo))
                ->when($search, fn($q) => $q->where('description', 'like', "%{$search}%"))
                ->latest()
                ->limit($type === 'all' ? 50 : 100)
                ->get()
                ->map(fn($log) => [
                    'id' => $log->id,
                    'type' => 'promo_code',
                    'type_label' => 'Promo-Code',
                    'performed_by' => $log->performedBy?->name ?? ($log->usedBy?->name ?? 'System'),
                    'target' => $log->promo_code,
                    'action' => $log->action,
                    'action_label' => $this->getActionLabel($log->action),
                    'changes' => $log->changes,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at,
                ]);

            $logs = $logs->concat($promoLogs);
        }

        // Nach Datum sortieren (neueste zuerst)
        $logs = $logs->sortByDesc('created_at')->take(100)->values();

        return Inertia::render('Admin/ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => [
                'type' => $type,
                'action' => $action,
                'user_id' => $userId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Action Labels für bessere Lesbarkeit
     */
    private function getActionLabel(string $action): string
    {
        return match($action) {
            'created' => 'Erstellt',
            'updated' => 'Bearbeitet',
            'deleted' => 'Gelöscht',
            'toggled_active' => 'Aktiviert/Deaktiviert',
            'toggled_popular' => 'Als Beliebt markiert/entfernt',
            'plan_changed' => 'Plan gewechselt',
            'admin_toggled' => 'Admin-Status geändert',
            'subscribed' => 'Abonniert',
            'cancelled' => 'Gekündigt',
            'resumed' => 'Wieder aktiviert',
            'cancelled_now' => 'Sofort beendet',
            'payment_method_updated' => 'Zahlungsmethode geändert',
            'used' => 'Verwendet',
            default => ucfirst($action),
        };
    }
}
