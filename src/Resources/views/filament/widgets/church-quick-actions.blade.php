<x-filament::section>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('filament.site-admin.resources.members.index') }}" class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-all">
            <div class="flex items-center">
                <div class="rounded-full bg-primary-500/10 p-3 mr-4">
                    <x-heroicon-o-users class="w-6 h-6 text-primary-500" />
                </div>
                <div>
                    <h3 class="text-lg font-medium">Members</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Manage church members</p>
                </div>
            </div>
        </a>

        <a href="{{ route('filament.site-admin.resources.events.index') }}" class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-all">
            <div class="flex items-center">
                <div class="rounded-full bg-success-500/10 p-3 mr-4">
                    <x-heroicon-o-calendar class="w-6 h-6 text-success-500" />
                </div>
                <div>
                    <h3 class="text-lg font-medium">Events</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Manage church events</p>
                </div>
            </div>
        </a>

        <a href="{{ route('filament.site-admin.resources.groups.index') }}" class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-all">
            <div class="flex items-center">
                <div class="rounded-full bg-warning-500/10 p-3 mr-4">
                    <x-heroicon-o-user-group class="w-6 h-6 text-warning-500" />
                </div>
                <div>
                    <h3 class="text-lg font-medium">Groups</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Manage church groups</p>
                </div>
            </div>
        </a>

        <a href="{{ route('filament.site-admin.resources.prayer-requests.index', ['tableFilters[from_sms]' => true]) }}" class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-all">
            <div class="flex items-center">
                <div class="rounded-full bg-danger-500/10 p-3 mr-4">
                    <x-heroicon-o-chat-bubble-left-ellipsis class="w-6 h-6 text-danger-500" />
                </div>
                <div>
                    <h3 class="text-lg font-medium">SMS Prayer Requests</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">View prayer requests from SMS</p>
                </div>
            </div>
        </a>
    </div>
</x-filament::section>

<div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
    <x-filament::section>
        <x-slot name="heading">Recent Prayer Requests</x-slot>
        
        <div class="space-y-4">
            @php
                $recentPrayerRequests = \Prasso\Church\Models\PrayerRequest::latest()->take(5)->get();
            @endphp
            
            @forelse($recentPrayerRequests as $request)
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-medium">{{ $request->title }}</h4>
                            <p class="text-sm text-gray-500 truncate">{{ \Illuminate\Support\Str::limit($request->description, 100) }}</p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full {{ $request->status == 'pending' ? 'bg-warning-500/10 text-warning-700' : 'bg-success-500/10 text-success-700' }}">
                            {{ ucfirst($request->status) }}
                        </span>
                    </div>
                    <div class="mt-2 flex justify-between text-xs text-gray-500">
                        <span>{{ $request->created_at->diffForHumans() }}</span>
                        @if(isset($request->metadata['source']) && $request->metadata['source'] == 'sms')
                            <span class="px-2 py-0.5 bg-warning-500/10 text-warning-700 rounded-full">SMS</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-gray-500 dark:text-gray-400">No recent prayer requests.</p>
            @endforelse
            
            <div class="mt-2">
                <a href="{{ route('filament.church.resources.prayer-requests.index') }}" class="text-primary-600 hover:text-primary-500 text-sm font-medium">
                    View all prayer requests →
                </a>
            </div>
        </div>
    </x-filament::section>
    
    <x-filament::section>
        <x-slot name="heading">SMS Prayer Requests</x-slot>
        
        <div class="space-y-4">
            @php
                $smsPrayerRequests = \Prasso\Church\Models\PrayerRequest::fromSms()->latest()->take(5)->get();
                $pendingCount = \Prasso\Church\Models\PrayerRequest::fromSms()->where('status', 'pending')->count();
            @endphp
            
            <div class="flex justify-between items-center mb-4">
                <div>
                    <span class="text-2xl font-bold">{{ $smsPrayerRequests->count() }}</span>
                    <span class="text-sm text-gray-500 ml-2">recent requests</span>
                </div>
                <div>
                    <span class="px-2 py-1 rounded-full {{ $pendingCount > 0 ? 'bg-warning-500/10 text-warning-700' : 'bg-success-500/10 text-success-700' }}">
                        {{ $pendingCount }} pending
                    </span>
                </div>
            </div>
            
            @forelse($smsPrayerRequests as $request)
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-medium">{{ $request->title }}</h4>
                            <p class="text-sm text-gray-500 truncate">{{ \Illuminate\Support\Str::limit($request->description, 100) }}</p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full {{ $request->status == 'pending' ? 'bg-warning-500/10 text-warning-700' : 'bg-success-500/10 text-success-700' }}">
                            {{ ucfirst($request->status) }}
                        </span>
                    </div>
                    <div class="mt-2 flex justify-between text-xs text-gray-500">
                        <span>{{ $request->created_at->diffForHumans() }}</span>
                        <span>{{ isset($request->metadata['phone']) ? $request->metadata['phone'] : 'Unknown' }}</span>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 dark:text-gray-400">No SMS prayer requests.</p>
            @endforelse
            
            <div class="mt-4 flex justify-between">
                <a href="{{ route('filament.site-admin.resources.prayer-requests.index', ['tableFilters[from_sms]' => true]) }}" class="text-primary-600 hover:text-primary-500 text-sm font-medium">
                    View all SMS requests →
                </a>
                <div>
                    <a href="{{ route('church.prayer-requests.print-sms') }}" target="_blank" class="text-primary-600 hover:text-primary-500 text-sm font-medium flex items-center mr-4 inline-block">
                        <x-heroicon-o-printer class="w-4 h-4 mr-1" />
                        Print
                    </a>
                    <a href="{{ route('church.prayer-requests.print-sms', ['format' => 'text']) }}" target="_blank" class="text-primary-600 hover:text-primary-500 text-sm font-medium flex items-center inline-block">
                        <x-heroicon-o-document-text class="w-4 h-4 mr-1" />
                        Download Text
                    </a>
                </div>
            </div>
        </div>
    </x-filament::section>
</div>
