<x-filament-panels::page>
    @if (!$this->position)
        <div class="rounded-lg bg-red-50 p-4 text-sm text-red-800">
            <p class="font-medium">The cleaning volunteer position is not available.</p>
            <p class="mt-1">Please create a volunteer position titled "Clean the Church" first.</p>
        </div>
    @else
        <div class="space-y-4">
            <div class="rounded-lg bg-blue-50 p-4">
                <p class="text-sm text-blue-800">
                    <span class="font-medium">Position:</span> {{ $this->position->title }}
                </p>
                <p class="mt-1 text-sm text-blue-800">
                    <span class="font-medium">Max Volunteers per Week:</span> {{ $this->position->max_volunteers ?? 'Unlimited' }}
                </p>
            </div>

            @if (empty($this->assignments))
                <div class="rounded-lg bg-gray-50 p-4 text-center text-sm text-gray-600">
                    <p>No cleaning signups yet.</p>
                </div>
            @else
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Cleaner</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Week</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Date Range</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($this->assignments as $assignment)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ $assignment['member_name'] }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        Week {{ $assignment['week_number'] }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $assignment['week_range'] }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                            @if ($assignment['status'] === 'active')
                                                bg-green-100 text-green-800
                                            @elseif ($assignment['status'] === 'pending')
                                                bg-yellow-100 text-yellow-800
                                            @else
                                                bg-gray-100 text-gray-800
                                            @endif
                                        ">
                                            {{ ucfirst($assignment['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $assignment['notes'] ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
