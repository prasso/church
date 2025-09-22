<x-filament-widgets::widget class="filament-church-quick-actions-widget">
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Quick Actions
                </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Add New Member -->
                <a href="{{ route('filament.site-admin.resources.members.create') }}"
                   class="flex flex-col items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-blue-700 dark:text-blue-300">Add Member</span>
                </a>

                <!-- Schedule Event -->
                <a href="{{ route('filament.site-admin.resources.events.create') }}"
                   class="flex flex-col items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-sm font-medium text-green-700 dark:text-green-300">Schedule Event</span>
                </a>

                <!-- View Prayer Requests -->
                <a href="{{ route('filament.site-admin.resources.prayer-requests.index') }}"
                   class="flex flex-col items-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-purple-700 dark:text-purple-300">Prayer Requests</span>
                </a>

                <!-- Record Attendance -->
                <a href="{{ route('filament.site-admin.resources.attendance-events.index') }}"
                   class="flex flex-col items-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors">
                    <svg class="w-8 h-8 text-orange-600 dark:text-orange-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-orange-700 dark:text-orange-300">Record Attendance</span>
                </a>

                <!-- Manage Groups -->
                <a href="{{ route('filament.site-admin.resources.groups.index') }}"
                   class="flex flex-col items-center p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors">
                    <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">Manage Groups</span>
                </a>

                <!-- Volunteer Management -->
                <a href="{{ route('filament.site-admin.resources.volunteer-positions.index') }}"
                   class="flex flex-col items-center p-4 bg-teal-50 dark:bg-teal-900/20 rounded-lg hover:bg-teal-100 dark:hover:bg-teal-900/30 transition-colors">
                    <svg class="w-8 h-8 text-teal-600 dark:text-teal-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m8 0V8a2 2 0 01-2 2H8a2 2 0 01-2-2V6m8 0H8m0 0V4"></path>
                    </svg>
                    <span class="text-sm font-medium text-teal-700 dark:text-teal-300">Volunteers</span>
                </a>

                <!-- Pastoral Care -->
                <a href="{{ route('filament.site-admin.resources.pastoral-visits.index') }}"
                   class="flex flex-col items-center p-4 bg-pink-50 dark:bg-pink-900/20 rounded-lg hover:bg-pink-100 dark:hover:bg-pink-900/30 transition-colors">
                    <svg class="w-8 h-8 text-pink-600 dark:text-pink-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-pink-700 dark:text-pink-300">Pastoral Care</span>
                </a>

                <!-- Reports -->
                <a href="{{ route('filament.site-admin.resources.reports.index') }}"
                   class="flex flex-col items-center p-4 bg-gray-50 dark:bg-gray-900/20 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-900/30 transition-colors">
                    <svg class="w-8 h-8 text-gray-600 dark:text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Reports</span>
                </a>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
