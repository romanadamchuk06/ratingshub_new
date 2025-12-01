<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neuer Bug-Report</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #1e40af;
            font-size: 24px;
        }
        .type-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
        }
        .type-bug { background-color: #fee2e2; color: #991b1b; }
        .type-feature { background-color: #fef3c7; color: #92400e; }
        .type-improvement { background-color: #dbeafe; color: #1e40af; }
        .type-question { background-color: #e0e7ff; color: #3730a3; }

        .priority-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            margin-left: 8px;
        }
        .priority-critical { background-color: #fecaca; color: #991b1b; }
        .priority-high { background-color: #fed7aa; color: #9a3412; }
        .priority-medium { background-color: #fef3c7; color: #92400e; }
        .priority-low { background-color: #e5e7eb; color: #374151; }

        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .section-content {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            border-left: 3px solid #3b82f6;
        }
        .meta-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .meta-item {
            background-color: #f9fafb;
            padding: 12px;
            border-radius: 6px;
        }
        .meta-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .meta-value {
            font-size: 14px;
            color: #111827;
        }
        .button {
            display: inline-block;
            background-color: #3b82f6;
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
        }
        .button:hover {
            background-color: #2563eb;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🐛 Neuer Bug-Report</h1>
            @php
                $typeLabels = [
                    'bug' => '🐛 Bug',
                    'feature' => '💡 Feature Request',
                    'improvement' => '🔧 Verbesserung',
                    'question' => '❓ Frage',
                ];
                $priorityLabels = [
                    'critical' => 'Kritisch',
                    'high' => 'Hoch',
                    'medium' => 'Mittel',
                    'low' => 'Niedrig',
                ];
            @endphp
            <span class="type-badge type-{{ $bugReport->type }}">
                {{ $typeLabels[$bugReport->type] ?? $bugReport->type }}
            </span>
            <span class="priority-badge priority-{{ $bugReport->priority }}">
                {{ $priorityLabels[$bugReport->priority] ?? $bugReport->priority }}
            </span>
        </div>

        <!-- Titel -->
        <div class="section">
            <div class="section-title">Titel</div>
            <div class="section-content">
                <strong>{{ $bugReport->title }}</strong>
            </div>
        </div>

        <!-- Beschreibung -->
        <div class="section">
            <div class="section-title">Beschreibung</div>
            <div class="section-content">
                {{ $bugReport->description }}
            </div>
        </div>

        <!-- Schritte zum Reproduzieren (nur bei Bug) -->
        @if($bugReport->type === 'bug' && $bugReport->steps_to_reproduce)
        <div class="section">
            <div class="section-title">Schritte zum Reproduzieren</div>
            <div class="section-content">
                {!! nl2br(e($bugReport->steps_to_reproduce)) !!}
            </div>
        </div>
        @endif

        <!-- Meta-Informationen -->
        <div class="meta-info">
            <div class="meta-item">
                <div class="meta-label">Gemeldet von</div>
                <div class="meta-value">
                    {{ $bugReport->user->name }}<br>
                    <small style="color: #6b7280;">{{ $bugReport->user->email }}</small>
                </div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Zeitpunkt</div>
                <div class="meta-value">{{ $bugReport->created_at->format('d.m.Y H:i') }} Uhr</div>
            </div>
        </div>

        <!-- Technische Details -->
        <div class="section">
            <div class="section-title">Technische Details</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                @if($bugReport->browser)
                <div class="meta-item">
                    <div class="meta-label">Browser</div>
                    <div class="meta-value">{{ $bugReport->browser }}</div>
                </div>
                @endif

                @if($bugReport->os)
                <div class="meta-item">
                    <div class="meta-label">Betriebssystem</div>
                    <div class="meta-value">{{ $bugReport->os }}</div>
                </div>
                @endif
            </div>

            @if($bugReport->page_url)
            <div class="meta-item" style="margin-top: 10px;">
                <div class="meta-label">Seite</div>
                <div class="meta-value">
                    <a href="{{ $bugReport->page_url }}" style="color: #3b82f6;">{{ $bugReport->page_url }}</a>
                </div>
            </div>
            @endif
        </div>

        <!-- Button zum Admin-Panel -->
        <div style="text-align: center;">
            <a href="{{ url('/admin/bug-reports/' . $bugReport->id) }}" class="button">
                Im Admin-Panel öffnen →
            </a>
        </div>

        <div class="footer">
            <p>Diese Email wurde automatisch generiert.</p>
            <p><strong>RatingsHub</strong> - Bug-Report System</p>
        </div>
    </div>
</body>
</html>
