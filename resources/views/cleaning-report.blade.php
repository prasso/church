<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cleaning Schedule Report</title>
    <link href="/js/google-fonts-inter.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Cleaning Schedule</h1>
                    <p class="text-gray-600 mt-1">Current list of cleaners and weeks taken</p>
                </div>
                <div class="mt-4 md:mt-0 flex items-center gap-3">
                    <a href="{{ route('church.cleaning.signup.show') }}" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        Sign Up
                    </a>
                    <a href="{{ route('church.member.dashboard') }}" class="px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                        Back to Dashboard
                    </a>
                </div>
            </div>

            @if (isset($error))
                <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                    <p class="text-red-800">{{ $error }}</p>
                </div>
            @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cleaner</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Week</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date Range</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($assignments as $assignment)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $assignment['member_name'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $assignment['week_number'] ? 'Week ' . $assignment['week_number'] : 'Pending' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $assignment['week_range'] ?? 'Pending schedule' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $statusClasses = [
                                                    'active' => 'bg-green-100 text-green-800',
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'inactive' => 'bg-gray-100 text-gray-700',
                                                    'completed' => 'bg-blue-100 text-blue-800',
                                                ];
                                                $statusClass = $statusClasses[$assignment['status']] ?? 'bg-gray-100 text-gray-700';
                                            @endphp
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusClass }}">
                                                {{ ucfirst($assignment['status']) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                                            No cleaning assignments yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
