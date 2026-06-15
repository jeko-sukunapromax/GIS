@extends('layouts.admin')

@section('content')
<style>
    .activity-description {
        font-weight: 700;
        color: var(--text-main);
        line-height: 1.35;
    }

    .activity-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    .activity-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        max-width: 260px;
        padding: 4px 8px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.09);
        border: 1px solid rgba(148, 163, 184, 0.14);
        color: var(--text-muted);
        font-size: 11.5px;
        white-space: nowrap;
    }

    .activity-chip strong {
        color: #cbd5e1;
        font-weight: 800;
    }

    .activity-chip span {
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; margin-bottom: 24px;">
    <div>
        <h1 class="page-title" style="margin-bottom: 8px;">Activity Logs</h1>
        <p style="color: var(--text-muted); font-size: 14px;">Audit trail for login, upload, barangay, map feature, and boundary changes.</p>
    </div>
</div>

<div class="card" style="margin-bottom: 22px;">
    <form method="GET" action="{{ route('admin.activity-logs.index') }}" style="display: grid; grid-template-columns: minmax(220px, 1fr) 220px auto; gap: 12px; align-items: end;">
        <div>
            <label class="form-label" for="search">Search</label>
            <input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="User, event, record, description">
        </div>
        <div>
            <label class="form-label" for="event">Event</label>
            <select class="form-control" id="event" name="event">
                <option value="">All events</option>
                @foreach($events as $event)
                    <option value="{{ $event }}" @selected(request('event') === $event)>{{ ucwords(str_replace(['.', '_'], ' ', $event)) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-filter"></i> Filter
        </button>
    </form>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 14px; margin-bottom: 18px;">
        <h3>Recent Activity</h3>
        <span style="color: var(--text-muted); font-size: 13px;">Showing latest {{ $logs->count() }} records</span>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>User</th>
                    <th>Event</th>
                    <th>Record</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    @php
                        $properties = collect($log->properties ?? []);
                        $displayProperties = $properties
                            ->except(['ip_address', 'user_agent', 'payload'])
                            ->filter(fn ($value) => $value !== null && $value !== '');
                        
                        // Safe subject handling without triggering morph relationship
                        $subject = null;
                        $subjectType = $log->subject_type ? class_basename($log->subject_type) : null;
                        
                        // Only load subject if the model class exists
                        if ($log->subject_type && class_exists($log->subject_type)) {
                            try {
                                $subject = $log->subject;
                            } catch (\Exception $e) {
                                $subject = null;
                            }
                        }
                        
                        $recordType = match ($subjectType) {
                            'Barangay' => 'Barangay',
                            'MapFeature' => 'Map Feature',
                            'MapUpload' => 'Upload',
                            'User' => 'User',
                            'BoundaryVersion' => 'Boundary Version',
                            'Finding' => 'Finding',
                            'Audit' => 'Audit',
                            default => $subjectType,
                        };
                        $recordName = $subject?->name
                            ?? $subject?->file_name
                            ?? ($subject ? '#'.$subject->getKey() : null);
                        $recordLabel = $recordType && $recordName ? "{$recordType}: {$recordName}" : ($recordType ?? 'System');
                    @endphp
                    <tr>
                        <td style="white-space: nowrap;">
                            <div style="font-weight: 700; color: var(--text-heading);">{{ $log->created_at->format('M d, Y') }}</div>
                            <div style="color: var(--text-muted); font-size: 12px;">{{ $log->created_at->format('h:i A') }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: var(--text-heading);">{{ $log->causer?->name ?? 'System' }}</div>
                            <div style="color: var(--text-muted); font-size: 12px;">{{ $log->causer?->email ?? 'No account attached' }}</div>
                        </td>
                        <td>
                            <span style="display: inline-flex; align-items: center; gap: 7px; padding: 6px 10px; border-radius: 999px; background: rgba(56,189,248,0.12); color: #7dd3fc; border: 1px solid rgba(56,189,248,0.22); font-size: 12px; font-weight: 800;">
                                <i class="fa-solid fa-circle-info"></i>
                                {{ ucwords(str_replace(['.', '_'], ' ', $log->event)) }}
                            </span>
                        </td>
                        <td style="min-width: 160px;">
                            <div style="font-weight: 700; color: var(--text-heading);">{{ $recordLabel }}</div>
                            @if($log->subject_id)
                                <div style="color: var(--text-muted); font-size: 12px;">ID: {{ $log->subject_id }}</div>
                            @else
                                <div style="color: var(--text-muted); font-size: 12px;">No linked record</div>
                            @endif
                        </td>
                        <td>
                            <div class="activity-description">{{ $log->description }}</div>
                            @if($displayProperties->isNotEmpty())
                                <div class="activity-meta">
                                    @foreach($displayProperties->take(4) as $key => $value)
                                        @php
                                            $label = ucwords(str_replace('_', ' ', $key));

                                            if (is_bool($value)) {
                                                $formattedValue = $value ? 'Yes' : 'No';
                                            } elseif (is_array($value)) {
                                                if ($key === 'fields') {
                                                    $formattedValue = count($value).' fields';
                                                } elseif ($key === 'files') {
                                                    $formattedValue = count($value).' file'.(count($value) === 1 ? '' : 's');
                                                } elseif (array_is_list($value)) {
                                                    $formattedValue = count($value).' items';
                                                } else {
                                                    $formattedValue = count($value).' details';
                                                }
                                            } else {
                                                $formattedValue = \Illuminate\Support\Str::limit((string) $value, $key === 'token' ? 18 : 42);
                                            }
                                        @endphp
                                        <span class="activity-chip" title="{{ is_scalar($value) ? $value : json_encode($value) }}">
                                            <strong>{{ $label }}:</strong>
                                            <span>{{ $formattedValue }}</span>
                                        </span>
                                    @endforeach
                                    @if($displayProperties->count() > 4)
                                        <span class="activity-chip">
                                            <strong>More:</strong>
                                            <span>{{ $displayProperties->count() - 4 }} details</span>
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 38px;">
                            No activity recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
