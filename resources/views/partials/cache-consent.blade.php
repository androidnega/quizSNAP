<div id="quizsnap-cache-consent" class="hidden fixed inset-x-0 bottom-0 z-[99990] p-4 sm:p-6 pointer-events-none" aria-hidden="true" role="dialog" aria-labelledby="quizsnap-cache-consent-title">
    <div class="pointer-events-auto mx-auto max-w-2xl rounded-xl border border-gray-200 bg-white shadow-lg p-4 sm:p-5">
        <h2 id="quizsnap-cache-consent-title" class="text-base font-semibold text-gray-900 mb-1">Faster loading</h2>
        <p class="text-sm text-gray-600 mb-4 leading-relaxed">
            QuizSnap can store common page files on your device so pages open faster on your next visit.
            Quiz answers and personal data are not stored in this cache. You can change this anytime by clearing your browser data.
        </p>
        <div class="flex flex-col-reverse sm:flex-row gap-2 sm:justify-end">
            <button type="button" id="quizsnap-cache-decline" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Not now
            </button>
            <button type="button" id="quizsnap-cache-accept" class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                Accept caching
            </button>
        </div>
    </div>
</div>
<script src="{{ asset('js/cache-consent.js') }}" defer></script>
