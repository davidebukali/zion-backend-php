<x-layouts.app>
    <x-slot:title>
        Request Account &amp; Data Deletion — Tsiyon
    </x-slot:title>

    <div class="py-10 md:py-16 px-gutter">
        <div class="max-w-3xl mx-auto">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-on-surface-variant/80 mb-6">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">home</span>
                    <span>Home</span>
                </a>
                <span class="text-outline-variant">/</span>
                <span class="text-on-surface">Delete Account</span>
            </nav>

            <!-- Main Card Container -->
            <article class="bg-surface-container-low/60 backdrop-blur-md border border-outline-variant/15 rounded-3xl p-6 sm:p-10 md:p-12 shadow-sm text-on-surface">
                <!-- Header -->
                <header class="border-b border-outline-variant/15 pb-8 mb-8">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400 text-xs font-semibold tracking-wide uppercase mb-4">
                        <span class="material-symbols-outlined text-[15px]">delete_forever</span>
                        <span>Account &amp; Data Deletion</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tight text-on-surface mb-3">
                        Request Account &amp; Data Deletion
                    </h1>
                    <p class="text-sm sm:text-base text-on-surface-variant leading-relaxed">
                        In accordance with Apple App Store, Google Play, and international privacy regulations, you have the right to request the permanent deletion of your Tsiyon account and associated personal data at any time.
                    </p>
                </header>

                @if (session('success'))
                    <!-- Success Notification Banner -->
                    <div class="mb-8 p-5 rounded-2xl bg-primary/10 border border-primary/20 text-on-surface flex items-start gap-3.5">
                        <span class="material-symbols-outlined text-primary text-[24px] shrink-0 mt-0.5">check_circle</span>
                        <div>
                            <h3 class="text-base font-semibold text-primary mb-1">Request Received</h3>
                            <p class="text-sm text-on-surface-variant leading-relaxed">
                                {{ session('success') }}
                            </p>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <!-- Error Notification Banner -->
                    <div class="mb-8 p-5 rounded-2xl bg-red-500/10 border border-red-500/20 text-on-surface flex items-start gap-3.5">
                        <span class="material-symbols-outlined text-red-600 dark:text-red-400 text-[24px] shrink-0 mt-0.5">error</span>
                        <div>
                            <h3 class="text-base font-semibold text-red-600 dark:text-red-400 mb-1">Please check the form</h3>
                            <ul class="text-xs sm:text-sm text-on-surface-variant list-disc pl-4 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <!-- Informational Callout -->
                <div class="mb-8 p-4 sm:p-5 rounded-2xl bg-surface-container border border-outline-variant/15 text-xs sm:text-sm text-on-surface-variant space-y-2">
                    <div class="flex items-center gap-2 font-semibold text-on-surface">
                        <span class="material-symbols-outlined text-primary text-[18px]">info</span>
                        <span>What happens when you delete your account:</span>
                    </div>
                    <ul class="list-disc pl-5 space-y-1 text-on-surface-variant/90 leading-relaxed">
                        <li>Your account credentials, profile details, and authentication tokens are revoked and purged.</li>
                        <li>User-generated content (posts, comments, uploaded photos/videos, and direct messages) will be permanently deleted from our primary servers and media storage.</li>
                        <li>Requests are processed within <strong>30 days</strong> in accordance with our <a href="{{ route('privacy') }}" class="text-primary hover:underline font-medium">Privacy Policy</a>.</li>
                    </ul>
                </div>

                <!-- Form -->
                <form action="{{ route('account.delete.submit') }}" method="POST" class="space-y-6"
                    x-data="{
                        scopeType: '{{ old('data_scope_type', 'all') }}',
                        isSubmitting: false
                    }"
                    @submit="isSubmitting = true">
                    @csrf

                    <!-- Anti-spam Honeypot (Hidden from genuine users) -->
                    <div class="hidden" aria-hidden="true">
                        <label for="website_url">Do not fill this field</label>
                        <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off">
                    </div>

                    <!-- Email Input -->
                    <div>
                        <label for="email" class="block text-xs sm:text-sm font-semibold text-on-surface mb-2">
                            Account Email Address <span class="text-red-600 dark:text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-on-surface-variant/70">
                                <span class="material-symbols-outlined text-[20px]">mail</span>
                            </span>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required
                                placeholder="you@example.com"
                                class="w-full pl-11 pr-4 py-3 rounded-xl bg-surface-container border border-outline-variant/30 text-on-surface placeholder:text-on-surface-variant/40 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary text-sm transition-colors @error('email') border-red-500 @enderror">
                        </div>
                        <p class="text-xs text-on-surface-variant/80 mt-1.5">
                            Enter the email address registered with your Tsiyon account. We will send a confirmation receipt here.
                        </p>
                        @error('email')
                            <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Deletion Scope Selector -->
                    <div class="space-y-3">
                        <label class="block text-xs sm:text-sm font-semibold text-on-surface">
                            Data Deletion Scope <span class="text-red-600 dark:text-red-400">*</span>
                        </label>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <!-- Full Deletion Option -->
                            <label
                                :class="scopeType === 'all' ? 'border-red-500/60 bg-red-500/5 ring-1 ring-red-500/50' : 'border-outline-variant/20 bg-surface-container hover:border-outline-variant/40'"
                                class="flex items-start gap-3 p-4 rounded-2xl border cursor-pointer transition-all duration-200">
                                <input type="radio" name="data_scope_type" value="all" x-model="scopeType" class="mt-1 text-red-600 focus:ring-red-500">
                                <div>
                                    <div class="font-semibold text-sm text-on-surface flex items-center gap-1.5">
                                        <span>Full Account &amp; All Data</span>
                                    </div>
                                    <p class="text-xs text-on-surface-variant mt-1 leading-relaxed">
                                        Permanently delete your profile, posts, media, messages, and entire account history.
                                    </p>
                                </div>
                            </label>

                            <!-- Custom Scope Option -->
                            <label
                                :class="scopeType === 'custom' ? 'border-primary/60 bg-primary/5 ring-1 ring-primary/50' : 'border-outline-variant/20 bg-surface-container hover:border-outline-variant/40'"
                                class="flex items-start gap-3 p-4 rounded-2xl border cursor-pointer transition-all duration-200">
                                <input type="radio" name="data_scope_type" value="custom" x-model="scopeType" class="mt-1 text-primary focus:ring-primary">
                                <div>
                                    <div class="font-semibold text-sm text-on-surface">
                                        <span>Select Specific Data Only</span>
                                    </div>
                                    <p class="text-xs text-on-surface-variant mt-1 leading-relaxed">
                                        Choose specific categories of personal data to purge while keeping your account.
                                    </p>
                                </div>
                            </label>
                        </div>

                        <!-- Specific Data Checkboxes (Shown when custom is selected) -->
                        <div x-show="scopeType === 'custom'" x-collapse x-cloak class="p-4 rounded-2xl bg-surface-container border border-outline-variant/20 space-y-3 mt-3">
                            <span class="block text-xs font-semibold text-on-surface uppercase tracking-wider mb-2">Select data categories to delete:</span>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs sm:text-sm">
                                <label class="flex items-center gap-2.5 p-2.5 rounded-lg bg-surface-container-low/70 border border-outline-variant/10 cursor-pointer hover:bg-surface-container-high transition-colors">
                                    <input type="checkbox" name="data_scope_items[]" value="posts"
                                        {{ in_array('posts', old('data_scope_items', [])) ? 'checked' : '' }}
                                        class="rounded text-primary focus:ring-primary">
                                    <span class="text-on-surface">Posts &amp; Comments</span>
                                </label>

                                <label class="flex items-center gap-2.5 p-2.5 rounded-lg bg-surface-container-low/70 border border-outline-variant/10 cursor-pointer hover:bg-surface-container-high transition-colors">
                                    <input type="checkbox" name="data_scope_items[]" value="media"
                                        {{ in_array('media', old('data_scope_items', [])) ? 'checked' : '' }}
                                        class="rounded text-primary focus:ring-primary">
                                    <span class="text-on-surface">Uploaded Photos &amp; Media</span>
                                </label>

                                <label class="flex items-center gap-2.5 p-2.5 rounded-lg bg-surface-container-low/70 border border-outline-variant/10 cursor-pointer hover:bg-surface-container-high transition-colors">
                                    <input type="checkbox" name="data_scope_items[]" value="interactions"
                                        {{ in_array('interactions', old('data_scope_items', [])) ? 'checked' : '' }}
                                        class="rounded text-primary focus:ring-primary">
                                    <span class="text-on-surface">Likes, Bookmarks &amp; Follows</span>
                                </label>

                                <label class="flex items-center gap-2.5 p-2.5 rounded-lg bg-surface-container-low/70 border border-outline-variant/10 cursor-pointer hover:bg-surface-container-high transition-colors">
                                    <input type="checkbox" name="data_scope_items[]" value="messages"
                                        {{ in_array('messages', old('data_scope_items', [])) ? 'checked' : '' }}
                                        class="rounded text-primary focus:ring-primary">
                                    <span class="text-on-surface">Direct Messages &amp; Chats</span>
                                </label>
                            </div>
                            @error('data_scope_items')
                                <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Reason for Deletion -->
                    <div>
                        <label for="reason" class="block text-xs sm:text-sm font-semibold text-on-surface mb-2">
                            Reason for Deletion <span class="text-on-surface-variant/60 font-normal">(Optional)</span>
                        </label>
                        <select id="reason" name="reason"
                            class="w-full px-4 py-3 rounded-xl bg-surface-container border border-outline-variant/30 text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary text-sm transition-colors">
                            <option value="">Select a reason (optional)</option>
                            <option value="no_longer_using" {{ old('reason') === 'no_longer_using' ? 'selected' : '' }}>No longer using the app</option>
                            <option value="privacy_concerns" {{ old('reason') === 'privacy_concerns' ? 'selected' : '' }}>Privacy or data collection concerns</option>
                            <option value="technical_issues" {{ old('reason') === 'technical_issues' ? 'selected' : '' }}>Technical issues or app performance</option>
                            <option value="too_many_notifications" {{ old('reason') === 'too_many_notifications' ? 'selected' : '' }}>Too many notifications or distractions</option>
                            <option value="alternative_platform" {{ old('reason') === 'alternative_platform' ? 'selected' : '' }}>Switched to an alternative platform</option>
                            <option value="temporary_break" {{ old('reason') === 'temporary_break' ? 'selected' : '' }}>Taking a temporary break</option>
                            <option value="other" {{ old('reason') === 'other' ? 'selected' : '' }}>Other reason</option>
                        </select>
                    </div>

                    <!-- Additional Details / Comments -->
                    <div>
                        <label for="details" class="block text-xs sm:text-sm font-semibold text-on-surface mb-2">
                            Additional Details or Feedback <span class="text-on-surface-variant/60 font-normal">(Optional)</span>
                        </label>
                        <textarea id="details" name="details" rows="3"
                            placeholder="Help us improve or specify any details regarding your data request..."
                            class="w-full px-4 py-3 rounded-xl bg-surface-container border border-outline-variant/30 text-on-surface placeholder:text-on-surface-variant/40 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary text-sm transition-colors">{{ old('details') }}</textarea>
                    </div>

                    <!-- Permanent Deletion Acknowledgment Checkbox -->
                    <div class="pt-2">
                        <label class="flex items-start gap-3 p-3.5 rounded-xl bg-surface-container/60 border border-outline-variant/20 cursor-pointer">
                            <input type="checkbox" name="confirm_deletion" value="1" required
                                {{ old('confirm_deletion') ? 'checked' : '' }}
                                class="mt-0.5 rounded text-red-600 focus:ring-red-500">
                            <span class="text-xs sm:text-sm text-on-surface leading-snug">
                                I understand that once processed, account deletion and data removal are <strong>permanent and cannot be reversed</strong>. <span class="text-red-600 dark:text-red-400">*</span>
                            </span>
                        </label>
                        @error('confirm_deletion')
                            <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <a href="{{ route('privacy') }}" class="text-xs sm:text-sm text-on-surface-variant hover:text-primary transition-colors flex items-center gap-1 order-2 sm:order-1">
                            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                            <span>Back to Privacy Policy</span>
                        </a>

                        <button type="submit"
                            :disabled="isSubmitting"
                            class="w-full sm:w-auto px-7 py-3.5 rounded-xl bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-bold text-sm shadow-md shadow-red-900/20 hover:shadow-lg hover:shadow-red-900/30 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-surface active:scale-[0.98] transition-all duration-150 flex items-center justify-center gap-2 order-1 sm:order-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]" x-show="!isSubmitting">delete_forever</span>
                            <span class="material-symbols-outlined text-[18px] animate-spin" x-show="isSubmitting" x-cloak>progress_activity</span>
                            <span x-text="isSubmitting ? 'Submitting Request...' : 'Submit Deletion Request'">Submit Deletion Request</span>
                        </button>
                    </div>
                </form>
            </article>
        </div>
    </div>
</x-layouts.app>

