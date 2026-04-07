<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-slate-900">Member Dashboard</h1>
            @if ($member)
                <p class="text-lg text-slate-600 mt-2">Welcome, {{ $member->first_name }}!</p>
            @else
                <p class="text-lg text-slate-600 mt-2">Welcome to your member portal</p>
            @endif
        </div>

        @if (!$member)
            <!-- No Member Record Alert -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-yellow-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Member Profile Not Found</h3>
                        <p class="mt-2 text-sm text-yellow-700">Your member profile hasn't been created yet. Please contact the church office to get set up.</p>
                    </div>
                </div>
            </div>
        @else
            <!-- Tab Navigation -->
            <div class="mb-8 border-b border-slate-200">
                <nav class="flex space-x-8" aria-label="Tabs">
                    <button 
                        wire:click="$set('tab', 'overview')"
                        :class="{ 'border-blue-500 text-blue-600': tab === 'overview', 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300': tab !== 'overview' }"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                    >
                        Overview
                    </button>
                    <button 
                        wire:click="$set('tab', 'volunteer')"
                        :class="{ 'border-blue-500 text-blue-600': tab === 'volunteer', 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300': tab !== 'volunteer' }"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                    >
                        Volunteer Opportunities
                    </button>
                    <button 
                        wire:click="$set('tab', 'profile')"
                        :class="{ 'border-blue-500 text-blue-600': tab === 'profile', 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300': tab !== 'profile' }"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                    >
                        Profile
                    </button>
                </nav>
            </div>

            <!-- Overview Tab -->
            @if ($tab === 'overview')
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Member Info Card -->
                    <div class="bg-white rounded-lg shadow-sm p-6 border border-slate-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-slate-900">Member Info</h3>
                            <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-slate-900">{{ $member->full_name }}</p>
                        <p class="text-sm text-slate-600 mt-2">Status: <span class="font-semibold text-slate-900 capitalize">{{ $member->membership_status ?? 'visitor' }}</span></p>
                        <p class="text-sm text-slate-600 mt-1">Member since: <span class="font-semibold text-slate-900">{{ $member->membership_date?->format('M d, Y') ?? 'N/A' }}</span></p>
                    </div>

                    <!-- Active Assignments Card -->
                    <div class="bg-white rounded-lg shadow-sm p-6 border border-slate-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-slate-900">Active Roles</h3>
                            <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                            </svg>
                        </div>
                        <p class="text-3xl font-bold text-slate-900">{{ count($myAssignments) }}</p>
                        <p class="text-sm text-slate-600 mt-2">volunteer position{{ count($myAssignments) !== 1 ? 's' : '' }}</p>
                    </div>

                    <!-- Available Opportunities Card -->
                    <div class="bg-white rounded-lg shadow-sm p-6 border border-slate-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-slate-900">Open Roles</h3>
                            <svg class="w-8 h-8 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12 1a1 1 0 011 1v6h6a1 1 0 110 2h-6v6a1 1 0 11-2 0v-6H5a1 1 0 110-2h6V2a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <p class="text-3xl font-bold text-slate-900">{{ count($availablePositions) }}</p>
                        <p class="text-sm text-slate-600 mt-2">available to join</p>
                    </div>
                </div>

                <!-- Current Assignments -->
                @if (count($myAssignments) > 0)
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-8">
                        <h2 class="text-2xl font-bold text-slate-900 mb-6">Your Current Assignments</h2>
                        <div class="space-y-4">
                            @foreach ($myAssignments as $assignment)
                                <div class="flex items-start justify-between p-4 bg-slate-50 rounded-lg border border-slate-200">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-slate-900">{{ $assignment['position_title'] }}</h3>
                                        <p class="text-sm text-slate-600 mt-1">
                                            <span class="inline-block mr-4">📅 Started: {{ $assignment['start_date'] }}</span>
                                            @if ($assignment['end_date'])
                                                <span class="inline-block">Ends: {{ $assignment['end_date'] }}</span>
                                            @endif
                                        </p>
                                        @if ($assignment['notes'])
                                            <p class="text-sm text-slate-600 mt-2">{{ $assignment['notes'] }}</p>
                                        @endif
                                    </div>
                                    <button 
                                        wire:click="cancelAssignment({{ $assignment['id'] }})"
                                        class="ml-4 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg border border-red-200 transition-colors"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Cleaning Signup Card -->
                    <a href="{{ route('church.cleaning.signup.show') }}" class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg shadow-sm border border-blue-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-blue-900">Church Cleaning</h3>
                            <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" />
                            </svg>
                        </div>
                        <p class="text-sm text-blue-800">Sign up to help keep our church clean and welcoming</p>
                        <div class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            Sign Up Now
                        </div>
                    </a>

                    <!-- Volunteer Opportunities Card -->
                    <button wire:click="$set('tab', 'volunteer')" class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg shadow-sm border border-green-200 p-6 hover:shadow-md transition-shadow text-left">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-green-900">Volunteer Opportunities</h3>
                            <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12 1a1 1 0 011 1v6h6a1 1 0 110 2h-6v6a1 1 0 11-2 0v-6H5a1 1 0 110-2h6V2a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <p class="text-sm text-green-800">Explore available volunteer positions and sign up</p>
                        <div class="mt-4 inline-block px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                            View Opportunities
                        </div>
                    </button>
                </div>
            @endif

            <!-- Volunteer Opportunities Tab -->
            @if ($tab === 'volunteer')
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                    <h2 class="text-2xl font-bold text-slate-900 mb-6">Available Volunteer Opportunities</h2>
                    
                    @if (count($availablePositions) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach ($availablePositions as $position)
                                <div class="border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <h3 class="text-xl font-semibold text-slate-900">{{ $position['title'] }}</h3>
                                            @if ($position['location'])
                                                <p class="text-sm text-slate-600 mt-1">📍 {{ $position['location'] }}</p>
                                            @endif
                                        </div>
                                        <span class="ml-2 px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                            Open
                                        </span>
                                    </div>

                                    @if ($position['description'])
                                        <p class="text-slate-700 text-sm mb-4">{{ $position['description'] }}</p>
                                    @endif

                                    <div class="space-y-2 mb-4 text-sm text-slate-600">
                                        @if ($position['time_commitment'])
                                            <p>⏱️ <strong>Time:</strong> {{ $position['time_commitment'] }}</p>
                                        @endif
                                        @if ($position['max_volunteers'])
                                            <p>👥 <strong>Volunteers:</strong> {{ $position['current_volunteers'] }}/{{ $position['max_volunteers'] }}</p>
                                        @endif
                                    </div>

                                    <button 
                                        wire:click="signUpForPosition({{ $position['id'] }})"
                                        class="w-full px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors"
                                    >
                                        Sign Up
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-slate-900">No open opportunities</h3>
                            <p class="mt-1 text-sm text-slate-500">Check back soon for new volunteer opportunities!</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Profile Tab -->
            @if ($tab === 'profile')
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-slate-900">Contact Information</h2>
                        <button 
                            wire:click="toggleEditProfile"
                            class="px-4 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded-lg border border-blue-200 transition-colors"
                        >
                            {{ $editingProfile ? 'Cancel' : 'Edit' }}
                        </button>
                    </div>

                    @if (!$editingProfile)
                        <!-- View Mode -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">First Name</label>
                                <p class="mt-1 text-slate-900">{{ $memberData['first_name'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Last Name</label>
                                <p class="mt-1 text-slate-900">{{ $memberData['last_name'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Email</label>
                                <p class="mt-1 text-slate-900">{{ $memberData['email'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Phone</label>
                                <p class="mt-1 text-slate-900">{{ $memberData['phone'] ?? 'N/A' }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">Address</label>
                                <p class="mt-1 text-slate-900">{{ $memberData['address'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">City</label>
                                <p class="mt-1 text-slate-900">{{ $memberData['city'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">State</label>
                                <p class="mt-1 text-slate-900">{{ $memberData['state'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Postal Code</label>
                                <p class="mt-1 text-slate-900">{{ $memberData['postal_code'] ?? 'N/A' }}</p>
                            </div>
                        </div>
                    @else
                        <!-- Edit Mode -->
                        <form wire:submit="updateProfile" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">First Name</label>
                                    <input 
                                        type="text" 
                                        wire:model="memberData.first_name"
                                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Last Name</label>
                                    <input 
                                        type="text" 
                                        wire:model="memberData.last_name"
                                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                                    <input 
                                        type="email" 
                                        wire:model="memberData.email"
                                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Phone</label>
                                    <input 
                                        type="tel" 
                                        wire:model="memberData.phone"
                                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Address</label>
                                    <input 
                                        type="text" 
                                        wire:model="memberData.address"
                                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">City</label>
                                    <input 
                                        type="text" 
                                        wire:model="memberData.city"
                                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">State</label>
                                    <input 
                                        type="text" 
                                        wire:model="memberData.state"
                                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Postal Code</label>
                                    <input 
                                        type="text" 
                                        wire:model="memberData.postal_code"
                                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                </div>
                            </div>

                            <div class="flex justify-end space-x-4">
                                <button 
                                    type="button"
                                    wire:click="toggleEditProfile"
                                    class="px-6 py-2 text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button 
                                    type="submit"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                >
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            @endif
        @endif
    </div>
</div>
