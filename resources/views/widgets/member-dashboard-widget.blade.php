@if ($this->shouldRender())
    @php
        $data = $this->getData();
        $member = $data['member'] ?? null;
        $myAssignments = $data['myAssignments'] ?? [];
        $availablePositions = $data['availablePositions'] ?? [];
        $memberDashboardUrl = $data['memberDashboardUrl'] ?? '#';
    @endphp

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Member Dashboard</h2>
                    <p class="text-sm text-gray-600">Your church member portal</p>
                </div>
            </div>
            <a href="{{ $memberDashboardUrl }}" 
               class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                View Full Dashboard
            </a>
        </div>

        @if ($member)
            <!-- Member Info & Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Name</p>
                    <p class="font-semibold text-gray-900">{{ $member->full_name }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Status</p>
                    <p class="font-semibold text-gray-900 capitalize">{{ $member->membership_status ?? 'visitor' }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Active Roles</p>
                    <p class="font-semibold text-gray-900">{{ count($myAssignments) }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">Open Roles</p>
                    <p class="font-semibold text-gray-900">{{ count($availablePositions) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Current Assignments -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Your Current Assignments</h3>
                    @if (count($myAssignments) > 0)
                        <div class="space-y-3">
                            @foreach ($myAssignments as $assignment)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $assignment['position_title'] }}</p>
                                        <p class="text-sm text-gray-600">
                                            Since: {{ $assignment['start_date'] }}
                                            @if ($assignment['end_date'])
                                                <span class="ml-2">Until: {{ $assignment['end_date'] }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                        Active
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-600">No active volunteer assignments</p>
                        </div>
                    @endif
                </div>

                <!-- Available Opportunities -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Available Opportunities</h3>
                    @if (count($availablePositions) > 0)
                        <div class="space-y-3">
                            @foreach ($availablePositions as $position)
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="flex items-start justify-between mb-2">
                                        <h4 class="font-medium text-gray-900">{{ $position['title'] }}</h4>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                            Open
                                        </span>
                                    </div>
                                    @if ($position['description'])
                                        <p class="text-sm text-gray-600 mb-2">{{ Str::limit($position['description'], 80) }}</p>
                                    @endif
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm text-gray-600">
                                            @if ($position['time_commitment'])
                                                <span class="mr-3">Time: {{ $position['time_commitment'] }}</span>
                                            @endif
                                            @if ($position['max_volunteers'])
                                                <span>Spots: {{ $position['current_volunteers'] }}/{{ $position['max_volunteers'] }}</span>
                                            @endif
                                        </div>
                                        <button wire:click="signUpForPosition({{ $position['id'] }})"
                                                class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                            Sign Up
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if (count($availablePositions) >= 5)
                            <p class="mt-3 text-sm text-gray-600 text-center">
                                Showing 5 of {{ VolunteerPosition::where('is_active', true)->count() }} opportunities. 
                                <a href="{{ $memberDashboardUrl }}" class="text-blue-600 hover:text-blue-800 font-medium">View all</a>
                            </p>
                        @endif
                    @else
                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-600">No volunteer opportunities available</p>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <!-- No Member Record -->
            <div class="text-center py-8 bg-yellow-50 rounded-lg border border-yellow-200">
                <svg class="mx-auto h-12 w-12 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-yellow-800">Member Profile Not Found</h3>
                <p class="mt-1 text-sm text-yellow-700">Your member profile hasn't been created yet. Please contact the church office to get set up.</p>
            </div>
        @endif
    </div>
@endif
