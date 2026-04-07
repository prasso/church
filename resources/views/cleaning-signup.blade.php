<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Church Cleaning Signup</title>
    <link href="/js/google-fonts-inter.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="font-sans antialiased bg-gray-100">
@if (isset($error))
    <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                <p class="text-red-800">{{ $error }}</p>
                <a href="{{ route('church.member.dashboard') }}" class="mt-4 inline-block px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
@else
<div x-data="cleaningSignup()" class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Church Cleaning Signup</h1>
            <p class="text-lg text-gray-600">Sign up to help keep our church clean and welcoming</p>
        </div>

        <!-- Alert Messages -->
        <template x-if="successMessage">
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-green-800" x-text="successMessage"></p>
            </div>
        </template>

        <template x-if="errorMessage">
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-800" x-text="errorMessage"></p>
            </div>
        </template>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Schedule Section -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Weekly Schedule</h2>
                    <p class="text-gray-600 mb-6">Select an available week to sign up for cleaning:</p>

                    <!-- Schedule Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <template x-for="(week, index) in weeks" :key="index">
                            <button
                                @click="selectWeek(index)"
                                :disabled="week.taken || !isFormValid()"
                                :class="{
                                    'bg-blue-50 border-2 border-blue-500 cursor-pointer': selectedWeekIndex === index,
                                    'bg-gray-50 border-2 border-gray-300 hover:border-blue-300 cursor-pointer': selectedWeekIndex !== index && !week.taken,
                                    'bg-gray-100 border-2 border-gray-300 cursor-not-allowed opacity-50': week.taken
                                }"
                                class="p-4 rounded-lg text-left transition-all"
                            >
                                <div class="font-semibold text-gray-900" x-text="week.label"></div>
                                <div class="text-sm text-gray-600 mt-1" x-text="week.dateRange"></div>
                                <div class="text-sm mt-2">
                                    <template x-if="week.taken">
                                        <span class="inline-block bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-semibold">Taken</span>
                                    </template>
                                    <template x-if="!week.taken && selectedWeekIndex === index">
                                        <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-semibold">Selected</span>
                                    </template>
                                    <template x-if="!week.taken && selectedWeekIndex !== index">
                                        <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-semibold">Available</span>
                                    </template>
                                </div>
                            </button>
                        </template>
                    </div>

                    <!-- Selected Week Details -->
                    <template x-if="selectedWeekIndex !== null && weeks[selectedWeekIndex]">
                        <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-blue-900">
                                <strong>Selected Week:</strong> <span x-text="weeks[selectedWeekIndex].label"></span>
                                (<span x-text="weeks[selectedWeekIndex].dateRange"></span>)
                            </p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Signup Form Section -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Your Information</h2>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        <!-- CSRF Token -->
                        @csrf

                        <!-- Hidden Fields -->
                        <input type="hidden" name="template" value="church_cleaning_signup">
                        <input type="hidden" name="selected_week" x-bind:value="selectedWeekIndex !== null ? weeks[selectedWeekIndex].weekNumber : ''">
                        <input type="hidden" name="data_key" x-bind:value="dataKey">

                        <!-- Name Field -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                x-model="form.name"
                                required
                                placeholder="John Doe"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>

                        <!-- Phone Field -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                                Phone Number <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                x-model="form.phone"
                                required
                                placeholder="(555) 123-4567"
                                pattern="[0-9\-\+\(\)\s]+"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                            <p class="text-xs text-gray-500 mt-1">We'll send text reminders to this number</p>
                        </div>

                        <!-- Info Box -->
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-xs text-blue-900">
                                <strong>Note:</strong> You will receive SMS text message reminders for your assigned week.
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="!isFormValid()"
                            :class="{
                                'bg-blue-600 hover:bg-blue-700 text-white cursor-pointer': isFormValid(),
                                'bg-gray-400 text-gray-200 cursor-not-allowed': !isFormValid()
                            }"
                            class="w-full py-2 px-4 rounded-lg font-semibold transition-colors mt-6"
                        >
                            <template x-if="isSubmitting">
                                <span>Submitting...</span>
                            </template>
                            <template x-if="!isSubmitting">
                                <span>Sign Up for Cleaning</span>
                            </template>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function cleaningSignup() {
        return {
            form: {
                name: '',
                phone: ''
            },
            selectedWeekIndex: null,
            dataKey: '',
            isSubmitting: false,
            successMessage: '',
            errorMessage: '',
            weeks: [
                {
                    weekNumber: 1,
                    label: 'Week 1',
                    dateRange: 'Jan 5 - Jan 11',
                    taken: false
                },
                {
                    weekNumber: 2,
                    label: 'Week 2',
                    dateRange: 'Jan 12 - Jan 18',
                    taken: false
                },
                {
                    weekNumber: 3,
                    label: 'Week 3',
                    dateRange: 'Jan 19 - Jan 25',
                    taken: false
                },
                {
                    weekNumber: 4,
                    label: 'Week 4',
                    dateRange: 'Jan 26 - Feb 1',
                    taken: false
                },
                {
                    weekNumber: 5,
                    label: 'Week 5',
                    dateRange: 'Feb 2 - Feb 8',
                    taken: false
                },
                {
                    weekNumber: 6,
                    label: 'Week 6',
                    dateRange: 'Feb 9 - Feb 15',
                    taken: false
                }
            ],

            init() {
                this.loadScheduleData();
                this.dataKey = this.generateDataKey();
            },

            async loadScheduleData() {
                try {
                    const response = await fetch('{{ route("church.cleaning.signup.schedule") }}');
                    if (!response.ok) throw new Error('Failed to load schedule');
                    
                    const data = await response.json();
                    // Update weeks with taken status from server
                    data.forEach((item, index) => {
                        if (this.weeks[index]) {
                            this.weeks[index].taken = item.taken;
                        }
                    });
                } catch (error) {
                    console.error('Error loading schedule:', error);
                    // Continue with default schedule if load fails
                }
            },

            generateDataKey() {
                return 'cleaning_signup_' + Date.now();
            },

            selectWeek(index) {
                if (!this.weeks[index].taken && this.isFormValid()) {
                    this.selectedWeekIndex = this.selectedWeekIndex === index ? null : index;
                }
            },

            isFormValid() {
                return this.form.name.trim() !== '' && 
                       this.form.phone.trim() !== '' && 
                       this.selectedWeekIndex !== null;
            },

            async submitForm() {
                if (!this.isFormValid()) {
                    this.errorMessage = 'Please fill in all required fields and select a week.';
                    return;
                }

                this.isSubmitting = true;
                this.errorMessage = '';
                this.successMessage = '';

                try {
                    const formData = new FormData();
                    formData.append('_token', document.querySelector('input[name="_token"]').value);
                    formData.append('template', 'church_cleaning_signup');
                    formData.append('data_key', this.dataKey);
                    formData.append('name', this.form.name);
                    formData.append('phone', this.form.phone);
                    formData.append('selected_week', this.weeks[this.selectedWeekIndex].weekNumber);
                    formData.append('return_json', 'true');

                    const response = await fetch('{{ route("church.cleaning.signup.store") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }

                    const data = await response.json();

                    // Mark the week as taken
                    this.weeks[this.selectedWeekIndex].taken = true;

                    // Show success message
                    this.successMessage = `Success! You've signed up for ${this.weeks[this.selectedWeekIndex].label}. You'll receive SMS reminders at ${this.form.phone}.`;

                    // Reset form
                    this.form.name = '';
                    this.form.phone = '';
                    this.selectedWeekIndex = null;
                    this.dataKey = this.generateDataKey();

                    // Clear success message after 5 seconds
                    setTimeout(() => {
                        this.successMessage = '';
                    }, 5000);

                } catch (error) {
                    console.error('Error:', error);
                    this.errorMessage = 'An error occurred while submitting your signup. Please try again.';
                } finally {
                    this.isSubmitting = false;
                }
            }
        };
    }
</script>
</body>
</html>
@endif
