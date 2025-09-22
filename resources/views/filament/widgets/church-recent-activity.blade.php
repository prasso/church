<x-filament-widgets::widget class="filament-church-recent-activity-widget">
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Recent Activity
                </h3>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Recent Attendance -->
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                        <svg class="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Recent Attendance
                    </h4>
                    <div class="space-y-2">
                        @forelse($this->getRecentAttendance() as $record)
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                        {{ $record->member?->full_name ?? 'Unknown Member' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $record->event?->name ?? 'Event' }}
                                    </p>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $record->check_in_time?->format('M j, g:i A') ?? 'N/A' }}
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No recent attendance</p>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Prayer Requests -->
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                        <svg class="w-4 h-4 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                        Prayer Requests
                    </h4>
                    <div class="space-y-2">
                        @forelse($this->getRecentPrayerRequests() as $request)
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                        {{ $request->member?->full_name ?? 'Anonymous' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ Str::limit($request->subject ?? $request->description, 30) }}
                                    </p>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $request->created_at->diffForHumans() }}
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No recent requests</p>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Pastoral Visits -->
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                        <svg class="w-4 h-4 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Pastoral Visits
                    </h4>
                    <div class="space-y-2">
                        @forelse($this->getRecentVisits() as $visit)
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                        {{ $visit->member?->full_name ?? 'Unknown Member' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $visit->location_type ?? 'Visit' }}
                                    </p>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $visit->scheduled_for?->format('M j') ?? 'N/A' }}
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No recent visits</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
