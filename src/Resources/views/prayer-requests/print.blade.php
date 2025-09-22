<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .btn-print, .btn-back, .btn-download {
            display: inline-block;
            padding: 8px 16px;
            margin-right: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-print:hover, .btn-back:hover, .btn-download:hover {
            background-color: #e9ecef;
        }
        .btn-download {
            background-color: #e7f3ff;
            border-color: #b6d4fe;
            color: #0a58ca;
        }
        .btn-download:hover {
            background-color: #d0e5ff;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .header h1 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
            font-size: 14px;
        }
        .prayer-request {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            page-break-inside: avoid;
        }
        .prayer-request h2 {
            margin-top: 0;
            color: #444;
            font-size: 18px;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }
        .meta {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .meta span {
            margin-right: 15px;
        }
        .description {
            margin-top: 10px;
            white-space: pre-line;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .badge-pending { background-color: #f0ad4e; }
        .badge-in-progress { background-color: #5bc0de; }
        .badge-answered { background-color: #5cb85c; }
        .badge-closed { background-color: #777; }
        .badge-sms { background-color: #f0ad4e; }
        .badge-email { background-color: #5cb85c; }
        .badge-manual { background-color: #337ab7; }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
            .prayer-request {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Generated on {{ now()->format('F j, Y g:i A') }}</p>
        <div class="no-print">
            <button onclick="window.print()" class="btn-print">Print</button>
            <button onclick="window.history.back()" class="btn-back">Back</button>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'text']) }}" class="btn-download">Download as Text</a>
        </div>
    </div>

    @foreach($prayerRequests as $prayerRequest)
        <div class="prayer-request">
            <h2>{{ $prayerRequest->title }}</h2>
            <div class="meta">
                <span>
                    <strong>Status:</strong> 
                    <span class="badge badge-{{ $prayerRequest->status }}">
                        {{ ucfirst($prayerRequest->status) }}
                    </span>
                </span>
                
                @if($prayerRequest->member)
                    <span><strong>For:</strong> {{ $prayerRequest->member->full_name }}</span>
                @endif
                
                @if($prayerRequest->requestedBy)
                    <span><strong>Requested By:</strong> {{ $prayerRequest->requestedBy->full_name }}</span>
                @endif
                
                <span><strong>Date:</strong> {{ $prayerRequest->created_at->format('M j, Y') }}</span>
                
                @if(isset($prayerRequest->metadata['source']))
                    <span>
                        <strong>Source:</strong> 
                        <span class="badge badge-{{ $prayerRequest->metadata['source'] }}">
                            {{ ucfirst($prayerRequest->metadata['source']) }}
                        </span>
                    </span>
                @endif
                
                @if(isset($prayerRequest->metadata['phone']))
                    <span><strong>Phone:</strong> {{ $prayerRequest->metadata['phone'] }}</span>
                @endif
                
                @if(isset($prayerRequest->metadata['sender_name']))
                    <span><strong>Sender:</strong> {{ $prayerRequest->metadata['sender_name'] }}</span>
                @endif
            </div>
            
            <div class="description">
                {{ $prayerRequest->description }}
            </div>
        </div>
    @endforeach

    <div class="footer">
        <p>Prayer Requests - {{ config('app.name') }}</p>
    </div>
</body>
</html>
