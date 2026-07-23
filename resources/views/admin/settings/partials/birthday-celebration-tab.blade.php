<div class="settings-tab-content p-6 hidden" data-tab-content="celebration" id="tab-content-celebration">
    <div class="space-y-6">
        <div class="rounded-lg border border-amber-200 bg-amber-50/60 p-5 space-y-2">
            <h3 class="text-sm font-semibold text-gray-900">Team birthday celebration</h3>
            <p class="text-sm text-gray-600">Turn this on for a colleague’s birthday. The public homepage shows a clean “celebrating” message with their photo (left copy, right image). Honoree staff see a surprise modal with balloons and optional birthday music when they open the dashboard.</p>
            @if($birthday_celebration_active_now ?? false)
                <p class="text-sm font-medium text-emerald-700">Status: live now (within your start/end dates).</p>
            @else
                <p class="text-sm text-gray-500">Status: off or outside the scheduled dates.</p>
            @endif
        </div>

        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-5 space-y-4">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="birthday_celebration_enabled" value="1" {{ old('birthday_celebration_enabled', $birthday_celebration_enabled ?? false) ? 'checked' : '' }} class="w-4 h-4 mt-0.5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 shrink-0">
                <span>
                    <span class="text-sm font-medium text-gray-800 block">Enable celebration</span>
                    <span class="text-xs text-gray-500">Uncheck to turn off immediately. Homepage and dashboard surprises revert to normal.</span>
                </span>
            </label>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="birthday_celebration_start" class="block text-sm font-medium text-gray-700 mb-1.5">Start date</label>
                    <input type="date" name="birthday_celebration_start" id="birthday_celebration_start" value="{{ old('birthday_celebration_start', $birthday_celebration_start ?? '') }}" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label for="birthday_celebration_end" class="block text-sm font-medium text-gray-700 mb-1.5">End date</label>
                    <input type="date" name="birthday_celebration_end" id="birthday_celebration_end" value="{{ old('birthday_celebration_end', $birthday_celebration_end ?? '') }}" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm">
                    <p class="text-xs text-gray-500 mt-1">Inclusive. After end date, the default homepage hero returns automatically.</p>
                </div>
            </div>

            <div>
                <label for="birthday_celebration_user_ids" class="block text-sm font-medium text-gray-700 mb-1.5">Honoree staff user IDs</label>
                <input type="text" name="birthday_celebration_user_ids" id="birthday_celebration_user_ids" value="{{ old('birthday_celebration_user_ids', $birthday_celebration_user_ids ?? '3,4') }}" placeholder="3, 4" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm font-mono">
                <p class="text-xs text-gray-500 mt-1">Comma-separated dashboard user IDs who receive the surprise modal (coordinator + examiner accounts).</p>
            </div>

            <div>
                <label for="birthday_celebration_honoree_name" class="block text-sm font-medium text-gray-700 mb-1.5">Full name (public)</label>
                <input type="text" name="birthday_celebration_honoree_name" id="birthday_celebration_honoree_name" value="{{ old('birthday_celebration_honoree_name', $birthday_celebration_honoree_name ?? '') }}" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm">
            </div>

            <div>
                <label for="birthday_celebration_dashboard_max_shows" class="block text-sm font-medium text-gray-700 mb-1.5">Dashboard surprise times per honoree</label>
                <input type="number" min="0" max="99" name="birthday_celebration_dashboard_max_shows" id="birthday_celebration_dashboard_max_shows" value="{{ old('birthday_celebration_dashboard_max_shows', $birthday_celebration_dashboard_max_shows ?? 1) }}" class="block w-full max-w-xs rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm">
                <p class="text-xs text-gray-500 mt-1">How many times the modal appears when they open the dashboard during this celebration. Use <strong>0</strong> for every visit. Default <strong>1</strong>.</p>
            </div>

            <div class="rounded-lg border border-dashed border-amber-300 bg-amber-50/40 p-4 space-y-3">
                <p class="text-sm font-medium text-gray-800">Reset honoree surprises</p>
                <p class="text-xs text-gray-600">If someone already saw the modal, reset so the next dashboard visit shows balloons and music again (does not change your dates or copy).</p>
                <p class="text-xs text-gray-500">Current reset generation: <span class="font-mono">{{ $birthday_celebration_reset_token ?? '1' }}</span></p>
                <form action="{{ route('dashboard.settings.birthday-celebration.reset') }}" method="post" class="pt-1" onsubmit="return confirm('Reset dashboard birthday surprises for all honorees? They will see the modal again on next visit.');">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-amber-500 bg-white px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-50">
                        Reset dashboard surprises
                    </button>
                </form>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 space-y-4">
            <h4 class="text-sm font-semibold text-gray-800">Homepage (public)</h4>
            <div>
                <label for="birthday_celebration_homepage_badge" class="block text-sm font-medium text-gray-700 mb-1.5">Badge</label>
                <input type="text" name="birthday_celebration_homepage_badge" id="birthday_celebration_homepage_badge" value="{{ old('birthday_celebration_homepage_badge', $birthday_celebration_homepage_badge ?? '') }}" class="block w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm">
            </div>
            <div>
                <label for="birthday_celebration_homepage_title" class="block text-sm font-medium text-gray-700 mb-1.5">Headline</label>
                <input type="text" name="birthday_celebration_homepage_title" id="birthday_celebration_homepage_title" value="{{ old('birthday_celebration_homepage_title', $birthday_celebration_homepage_title ?? '') }}" class="block w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm">
            </div>
            <div>
                <label for="birthday_celebration_homepage_message" class="block text-sm font-medium text-gray-700 mb-1.5">Message (left column)</label>
                <textarea name="birthday_celebration_homepage_message" id="birthday_celebration_homepage_message" rows="4" class="block w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm">{{ old('birthday_celebration_homepage_message', $birthday_celebration_homepage_message ?? '') }}</textarea>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 space-y-4">
            <h4 class="text-sm font-semibold text-gray-800">Dashboard surprise (honoree only)</h4>
            <div>
                <label for="birthday_celebration_dashboard_title" class="block text-sm font-medium text-gray-700 mb-1.5">Modal title</label>
                <input type="text" name="birthday_celebration_dashboard_title" id="birthday_celebration_dashboard_title" value="{{ old('birthday_celebration_dashboard_title', $birthday_celebration_dashboard_title ?? '') }}" class="block w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm">
            </div>
            <div>
                <label for="birthday_celebration_dashboard_message" class="block text-sm font-medium text-gray-700 mb-1.5">Personal message</label>
                <textarea name="birthday_celebration_dashboard_message" id="birthday_celebration_dashboard_message" rows="5" class="block w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm">{{ old('birthday_celebration_dashboard_message', $birthday_celebration_dashboard_message ?? '') }}</textarea>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="birthday_celebration_play_song" value="1" {{ old('birthday_celebration_play_song', $birthday_celebration_play_song ?? true) ? 'checked' : '' }} class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                <span class="text-sm text-gray-700">Play birthday music when the modal opens (loops until Continue)</span>
            </label>
            <div>
                <label for="birthday_celebration_song_url" class="block text-sm font-medium text-gray-700 mb-1.5">Song URL</label>
                <input type="text" name="birthday_celebration_song_url" id="birthday_celebration_song_url" value="{{ old('birthday_celebration_song_url', $birthday_celebration_song_url ?? \App\Services\BirthdayCelebrationService::DEFAULT_SONG_PATH) }}" placeholder="{{ \App\Services\BirthdayCelebrationService::DEFAULT_SONG_PATH }}" class="block w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm">
                <p class="text-xs text-gray-500 mt-1">Default file: <code class="px-1 py-0.5 bg-gray-100 rounded">{{ \App\Services\BirthdayCelebrationService::DEFAULT_SONG_PATH }}</code></p>
            </div>
            <div>
                <label for="birthday_celebration_song_file" class="block text-sm font-medium text-gray-700 mb-1.5">Or upload song (MP3)</label>
                <input type="file" name="birthday_celebration_song_file" id="birthday_celebration_song_file" accept="audio/mpeg,audio/mp3,audio/*" class="block w-full text-sm text-gray-600 file:mr-2 file:py-2 file:px-3 file:rounded-md file:border file:border-gray-200 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700">
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 space-y-4">
            <h4 class="text-sm font-semibold text-gray-800">Photo</h4>
            @if(!empty($birthday_celebration_image_preview ?? ''))
                <img src="{{ e($birthday_celebration_image_preview) }}" alt="Celebration photo preview" class="max-h-48 rounded-xl border border-gray-200 object-contain bg-white p-2">
            @endif
            <div>
                <label for="birthday_celebration_image_url" class="block text-sm font-medium text-gray-700 mb-1.5">Image URL</label>
                <input type="text" name="birthday_celebration_image_url" id="birthday_celebration_image_url" value="{{ old('birthday_celebration_image_url', $birthday_celebration_image ?? \App\Services\BirthdayCelebrationService::DEFAULT_IMAGE_PATH) }}" placeholder="{{ \App\Services\BirthdayCelebrationService::DEFAULT_IMAGE_PATH }}" class="block w-full rounded-md border border-gray-300 px-3 py-2.5 text-sm">
                <p class="text-xs text-gray-500 mt-1">Default WebP (desktop + mobile): <code class="px-1 py-0.5 bg-gray-100 rounded">{{ \App\Services\BirthdayCelebrationService::DEFAULT_IMAGE_PATH }}</code></p>
            </div>
            <div>
                <label for="birthday_celebration_image_file" class="block text-sm font-medium text-gray-700 mb-1.5">Upload / replace photo</label>
                <input type="file" name="birthday_celebration_image_file" id="birthday_celebration_image_file" accept="image/*" class="block w-full text-sm text-gray-600 file:mr-2 file:py-2 file:px-3 file:rounded-md file:border file:border-gray-200 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700">
                <p class="text-xs text-gray-500 mt-1">Drag a new photo here or pick a file. Shown on the homepage (right) and in the honoree modal.</p>
            </div>
        </div>
    </div>
</div>
