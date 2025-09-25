<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? 'Prayer Requests' }}</title>
    <style>
        :root { color-scheme: light; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin: 24px; color: #111827; }
        header { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 16px; }
        h1 { font-size: 22px; margin: 0; }
        .meta { color: #6b7280; font-size: 12px; }
        .actions { margin: 8px 0 16px; }
        .btn { display: inline-block; background: #111827; color: #fff; border: 0; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; }
        @media print {
            .actions { display: none; }
            body { margin: 0.5in; }
            .card { page-break-inside: avoid; }
        }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin: 12px 0; }
        .title { font-weight: 600; font-size: 16px; margin-bottom: 4px; }
        .badges { margin: 6px 0 10px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 12px; margin-right: 6px; border: 1px solid #e5e7eb; color: #374151; }
        .body { white-space: pre-wrap; line-height: 1.45; margin-top: 8px; }
        .muted { color: #6b7280; font-size: 12px; }
        .divider { border: 0; border-top: 1px dashed #e5e7eb; margin: 12px 0; }
    </style>
</head>
<body>
    <header>
        <h1>{{ $title ?? 'Prayer Requests' }}</h1>
        <div class="meta">Generated {{ ($generatedAt ?? now())->format('F j, Y g:i A') }} • Total: {{ count($requests ?? []) }}</div>
    </header>

    <div class="actions">
        <button class="btn" onclick="window.print()">Print</button>
    </div>

    @forelse(($requests ?? []) as $req)
        <section class="card">
            <div class="title">{{ $req->title }}</div>
            <div class="muted">
                @if($req->member)
                    For: {{ $req->member->full_name }} •
                @endif
                @if($req->requestedBy)
                    Requested By: {{ $req->requestedBy->full_name }} •
                @endif
                Date: {{ optional($req->created_at)->format('M j, Y') }}
            </div>
            <div class="badges">
                <span class="badge">Status: {{ ucfirst($req->status) }}</span>
                @php($src = data_get($req->metadata, 'source'))
                @if($src)
                    <span class="badge">Source: {{ strtoupper($src) }}</span>
                @endif
                @php($phone = data_get($req->metadata, 'phone'))
                @if($phone)
                    <span class="badge">Phone: {{ $phone }}</span>
                @endif
            </div>
            <div class="body">{{ $req->description }}</div>

            @if($req->status === 'answered' && !empty($req->answer))
                <hr class="divider" />
                <div class="title">Answer</div>
                <div class="body">{{ $req->answer }}</div>
                <div class="muted">Answered on: {{ optional($req->answered_at)->format('M j, Y') }}</div>
            @endif
        </section>
    @empty
        <p class="muted">No prayer requests found.</p>
    @endforelse
</body>
</html>
