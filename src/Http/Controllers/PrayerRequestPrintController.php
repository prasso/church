<?php

namespace Prasso\Church\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Prasso\Church\Models\PrayerRequest;

class PrayerRequestPrintController extends Controller
{
    /**
     * Display a printable version of a single prayer request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function printSingle(Request $request, $id)
    {
        $prayerRequest = PrayerRequest::findOrFail($id);
        
        if ($request->has('format') && $request->format === 'text') {
            return $this->downloadAsText(collect([$prayerRequest]), 'prayer-request-' . $id . '.txt');
        }
        
        return view('church::prayer-requests.print', [
            'prayerRequests' => collect([$prayerRequest]),
            'title' => 'Prayer Request: ' . $prayerRequest->title,
        ]);
    }
    
    /**
     * Display a printable version of multiple prayer requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function printMultiple(Request $request)
    {
        $ids = explode(',', $request->input('ids'));
        $prayerRequests = PrayerRequest::whereIn('id', $ids)->get();
        
        if ($request->has('format') && $request->format === 'text') {
            return $this->downloadAsText($prayerRequests, 'prayer-requests-' . implode('-', $ids) . '.txt');
        }
        
        return view('church::prayer-requests.print', [
            'prayerRequests' => $prayerRequests,
            'title' => 'Prayer Requests (' . $prayerRequests->count() . ')',
        ]);
    }
    
    /**
     * Display a printable version of all prayer requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function printAll(Request $request)
    {
        $query = PrayerRequest::query();
        
        // Apply filters
        if ($request->has('source') && $request->input('source') === 'sms') {
            $query->fromSms();
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->has('days')) {
            $days = (int) $request->input('days', 7);
            $query->where('created_at', '>=', now()->subDays($days));
        }
        
        $prayerRequests = $query->orderBy('created_at', 'desc')->get();
        
        if ($request->has('format') && $request->format === 'text') {
            return $this->downloadAsText($prayerRequests, 'all-prayer-requests.txt');
        }
        
        return view('church::prayer-requests.print', [
            'prayerRequests' => $prayerRequests,
            'title' => 'All Prayer Requests (' . $prayerRequests->count() . ')',
        ]);
    }
    
    /**
     * Display a printable version of SMS prayer requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function printSmsRequests(Request $request)
    {
        $query = PrayerRequest::fromSms();
        
        if ($request->has('days')) {
            $days = (int) $request->input('days', 7);
            $query->where('created_at', '>=', now()->subDays($days));
        }
        
        $prayerRequests = $query->orderBy('created_at', 'desc')->get();
        
        if ($request->has('format') && $request->format === 'text') {
            return $this->downloadAsText($prayerRequests, 'sms-prayer-requests.txt');
        }
        
        return view('church::prayer-requests.print', [
            'prayerRequests' => $prayerRequests,
            'title' => 'SMS Prayer Requests (' . $prayerRequests->count() . ')',
        ]);
    }
    
    /**
     * Generate and download prayer requests as a plain text file.
     *
     * @param  \Illuminate\Support\Collection  $prayerRequests
     * @param  string  $filename
     * @return \Illuminate\Http\Response
     */
    protected function downloadAsText($prayerRequests, $filename)
    {
        $content = "PRAYER REQUESTS\n";
        $content .= "Generated: " . now()->format('F j, Y g:i A') . "\n";
        $content .= "Total: " . $prayerRequests->count() . "\n\n";
        
        foreach ($prayerRequests as $index => $request) {
            $content .= "#" . ($index + 1) . " - " . $request->title . "\n";
            $content .= "Status: " . ucfirst($request->status) . "\n";
            $content .= "Date: " . $request->created_at->format('M j, Y') . "\n";
            
            if ($request->member) {
                $content .= "For: " . $request->member->full_name . "\n";
            }
            
            if ($request->requestedBy) {
                $content .= "Requested By: " . $request->requestedBy->full_name . "\n";
            }
            
            if (isset($request->metadata['source']) && $request->metadata['source'] === 'sms') {
                $content .= "Source: SMS\n";
                
                if (isset($request->metadata['phone'])) {
                    $content .= "Phone: " . $request->metadata['phone'] . "\n";
                }
                
                if (isset($request->metadata['sender_name'])) {
                    $content .= "Sender: " . $request->metadata['sender_name'] . "\n";
                }
            }
            
            $content .= "\nRequest:\n" . $request->description . "\n\n";
            
            if ($request->status === 'answered' && !empty($request->answer)) {
                $content .= "Answer: " . $request->answer . "\n";
                $content .= "Answered on: " . $request->answered_at->format('M j, Y') . "\n";
            }
            
            $content .= "\n" . str_repeat('-', 40) . "\n\n";
        }
        
        return Response::make($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
