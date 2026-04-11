<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
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
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-4">
                    
                    <div>
                        <h1 class="text-4xl font-bold text-gray-900 mb-2">Church Cleaning Signup</h1>
                        <p class="text-lg text-gray-600">Sign up to help keep our church clean and welcoming</p>
                    </div>
                </div>
                <a href="{{ route('church.cleaning.checklist') }}" 
                   target="_blank" 
                   class="teambutton inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 shadow-md transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                    Cleaning Checklist
                </a>
            </div>
        </div>

        <!-- Alert Messages -->
        <template x-if="successMessage">
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-green-800" x-text="successMessage"></p>
            </div>
        </template>

        <template x-if="registrationUrl">
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-blue-900 text-sm">
                    <template x-if="registrationMode === 'invitation'">
                        <span>This site is invitation-only. Request an invitation to manage your volunteer schedule online.</span>
                    </template>
                    <template x-if="registrationMode !== 'invitation'">
                        <span>Want to manage your volunteer schedule online? Complete your registration to create your member account.</span>
                    </template>
                </p>
                <a
                    :href="registrationUrl"
                    class="mt-3 inline-flex items-center text-sm font-semibold text-blue-700 hover:text-blue-900"
                >
                    <span x-text="registrationMode === 'invitation' ? 'Request invitation →' : 'Finish registration →'"></span>
                </a>
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
                                :disabled="week.taken"
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

                        <!-- Reminder Preference -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Reminder Preference <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center cursor-pointer">
                                    <input
                                        type="radio"
                                        name="reminder_type"
                                        value="sms"
                                        x-model="form.reminderType"
                                        class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                    >
                                    <span class="ml-3 text-sm text-gray-700">SMS Text Message</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input
                                        type="radio"
                                        name="reminder_type"
                                        value="email"
                                        x-model="form.reminderType"
                                        class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                    >
                                    <span class="ml-3 text-sm text-gray-700">Email</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input
                                        type="radio"
                                        name="reminder_type"
                                        value="both"
                                        x-model="form.reminderType"
                                        class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                    >
                                    <span class="ml-3 text-sm text-gray-700">Both SMS and Email</span>
                                </label>
                            </div>
                        </div>

                        <!-- Phone Field (conditional) -->
                        <template x-if="['sms', 'both'].includes(form.reminderType)">
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
                                <p class="text-xs text-gray-500 mt-1">Required for SMS reminders</p>
                            </div>
                        </template>

                        <!-- Email Field (conditional) -->
                        <template x-if="['email', 'both'].includes(form.reminderType)">
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    x-model="form.email"
                                    required
                                    placeholder="john@example.com"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                <p class="text-xs text-gray-500 mt-1">Required for email reminders</p>
                            </div>
                        </template>

                        @if($isAuthenticated)
                        <!-- Authentication Status -->
                        <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-xs text-green-900">
                                <strong>Logged in:</strong> Your information has been prefilled.
                            </p>
                        </div>
                        @endif

                        <!-- Info Box -->
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-xs text-blue-900">
                                <strong>Note:</strong> You will receive reminders for your assigned week via your selected method.
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
                name: '{{ $userData['name'] ?? '' }}',
                phone: '{{ $userData['phone'] ?? '' }}',
                email: '{{ $userData['email'] ?? '' }}',
                reminderType: 'sms'
            },
            selectedWeekIndex: null,
            dataKey: '',
            isSubmitting: false,
            successMessage: '',
            errorMessage: '',
            registrationUrl: '',
            registrationMode: '',
            weeks: [],

            init() {
                this.dataKey = this.generateDataKey();
                // Load schedule data and wait for it to complete
                this.loadScheduleData().then(() => {
                    console.log('Schedule data loaded successfully');
                });
                
                // Preserve week selection when form changes
                this.$watch('form.reminderType', () => {
                    // Week selection is preserved - no action needed
                });
                this.$watch('form.name', () => {
                    // Week selection is preserved - no action needed
                });
                this.$watch('form.phone', () => {
                    // Week selection is preserved - no action needed
                });
                this.$watch('form.email', () => {
                    // Week selection is preserved - no action needed
                });
            },

            async loadScheduleData() {
                try {
                    const response = await fetch('{{ route("church.cleaning.signup.schedule") }}');
                    if (!response.ok) throw new Error('Failed to load schedule');
                    
                    const data = await response.json();
                    
                    // Generate weeks starting from today forward
                    const today = new Date();
                    const currentWeekOfYear = this.getWeekNumber(today);
                    
                    // Show next 12 weeks from today
                    const weeksToShow = 12;
                    const newWeeks = [];
                    
                    for (let i = 0; i < weeksToShow; i++) {
                        const weekNum = currentWeekOfYear + i;
                        const weekData = data[weekNum - 1] || { taken: false, count: 0, maxVolunteers: 1 };
                        
                        // Calculate date range for this week
                        const weekStart = this.getDateOfWeek(today.getFullYear(), weekNum);
                        const weekEnd = new Date(weekStart);
                        weekEnd.setDate(weekEnd.getDate() + 6);
                        
                        const dateRange = this.formatDateRange(weekStart, weekEnd);
                        
                        newWeeks.push({
                            weekNumber: weekNum,
                            label: `Week ${weekNum}`,
                            dateRange: dateRange,
                            taken: weekData.taken,
                            count: weekData.count,
                            maxVolunteers: weekData.maxVolunteers
                        });
                    }
                    
                    // Store current selection before updating
                    const currentSelection = this.selectedWeekIndex;
                    
                    // Update weeks
                    this.weeks = newWeeks;
                    
                    // Restore selection after update
                    this.selectedWeekIndex = currentSelection;
                } catch (error) {
                    console.error('Error loading schedule:', error);
                    // Continue with empty schedule if load fails
                }
            },
            
            getWeekNumber(date) {
                const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
                const dayNum = d.getUTCDay() || 7;
                d.setUTCDate(d.getUTCDate() + 4 - dayNum);
                const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
                return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
            },
            
            getDateOfWeek(year, week) {
                const simple = new Date(year, 0, 1 + (week - 1) * 7);
                const dow = simple.getDay();
                const ISOweekStart = simple;
                if (dow <= 4)
                    ISOweekStart.setDate(simple.getDate() - simple.getDay() + 1);
                else
                    ISOweekStart.setDate(simple.getDate() + 8 - simple.getDay());
                return ISOweekStart;
            },
            
            formatDateRange(start, end) {
                const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const startMonth = monthNames[start.getMonth()];
                const endMonth = monthNames[end.getMonth()];
                const startDay = start.getDate();
                const endDay = end.getDate();
                
                if (startMonth === endMonth) {
                    return `${startMonth} ${startDay} - ${endDay}`;
                } else {
                    return `${startMonth} ${startDay} - ${endMonth} ${endDay}`;
                }
            },

            generateDataKey() {
                return 'cleaning_signup_' + Date.now();
            },

            selectWeek(index) {
                if (!this.weeks[index].taken) {
                    this.selectedWeekIndex = this.selectedWeekIndex === index ? null : index;
                }
            },

            isFormValid() {
                // Name, reminder type, and week are always required
                if (this.form.name.trim() === '' || this.form.reminderType === '' || this.selectedWeekIndex === null) {
                    return false;
                }

                // Phone is required only if SMS or both is selected
                if (['sms', 'both'].includes(this.form.reminderType)) {
                    if (this.form.phone.trim() === '') {
                        return false;
                    }
                }

                // Email is required only if email or both is selected
                if (['email', 'both'].includes(this.form.reminderType)) {
                    if (this.form.email.trim() === '') {
                        return false;
                    }
                }

                return true;
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
                    formData.append('email', this.form.email);
                    formData.append('reminder_type', this.form.reminderType);
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

                    // Refresh schedule data to get accurate availability
                    await this.loadScheduleData();

                    // Show success message based on reminder type
                    let reminderMsg = '';
                    if (this.form.reminderType === 'sms') {
                        reminderMsg = `You'll receive SMS reminders at ${this.form.phone}.`;
                    } else if (this.form.reminderType === 'email') {
                        reminderMsg = `You'll receive email reminders at ${this.form.email}.`;
                    } else {
                        reminderMsg = `You'll receive SMS reminders at ${this.form.phone} and email reminders at ${this.form.email}.`;
                    }
                    this.successMessage = `Success! You've signed up for ${this.weeks[this.selectedWeekIndex].label}. ${reminderMsg}`;
                    this.registrationUrl = data.registration_url || '';
                    this.registrationMode = data.registration_mode || '';

                    // Reset form
                    this.form.name = '';
                    this.form.phone = '';
                    this.form.email = '';
                    this.form.reminderType = 'sms';
                    this.selectedWeekIndex = null;
                    this.dataKey = this.generateDataKey();
                    if (!this.registrationUrl) {
                        this.registrationUrl = '';
                        this.registrationMode = '';
                    }

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
    </div>
</div>
@endif
</div>
