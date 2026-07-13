@php
    $search = $search ?? ($q ?? '');
    $activeTab = ($activeTab ?? 'active') === 'ended' ? 'ended' : 'active';
@endphp
@forelse($quizzes as $quizItem)
    @php
        $q = $quizItem;
        if (!$q->hasEnoughApprovedQuestions()) {
            $statusLabel = 'Pending';
            $statusClass = 'bg-amber-50 text-amber-700 ring-amber-100';
        } elseif ($q->hasEnded()) {
            $statusLabel = 'Ended';
            $statusClass = 'bg-gray-100 text-gray-600 ring-gray-200';
        } elseif ($q->is_published || $q->isActive()) {
            $statusLabel = 'Active';
            $statusClass = 'bg-emerald-50 text-emerald-700 ring-emerald-100';
        } else {
            $statusLabel = 'Inactive';
            $statusClass = 'bg-gray-100 text-gray-600 ring-gray-200';
        }
    @endphp
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 px-4 sm:px-5 py-3.5 border-b border-gray-100 last:border-b-0 hover:bg-gray-50/70 transition-colors">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2 mb-1">
                <a href="{{ route('dashboard.quizzes.show', $q) }}" class="text-[15px] font-semibold text-gray-900 truncate hover:text-gray-700 tracking-tight" title="{{ $q->title }}">{!! \App\Support\SearchHighlight::mark($q->title, $search) !!}</a>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $statusClass }}">{{ $statusLabel }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
                @if($q->classGroup)
                    <span class="truncate max-w-[14rem]" title="{{ $q->classGroup->name }}">{!! \App\Support\SearchHighlight::mark($q->classGroup->name, $search) !!}</span>
                    @if($q->classGroup->level)
                        <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium uppercase {{ $q->classGroup->level_tag_classes ?? 'bg-gray-100 text-gray-700' }}">{{ $q->classGroup->level->label }}</span>
                    @endif
                @endif
                @if($q->course)
                    <span class="truncate max-w-[12rem]" title="{{ $q->course->name }}">{!! \App\Support\SearchHighlight::mark($q->course->name, $search) !!}</span>
                @endif
                @if($q->topics)
                    <span class="truncate max-w-[10rem] text-gray-400" title="{{ $q->topics }}">{!! \App\Support\SearchHighlight::mark(\Illuminate\Support\Str::limit($q->topics, 36), $search) !!}</span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-4 sm:gap-5 shrink-0 text-xs text-gray-500 tabular-nums">
            <span title="Questions per student"><span class="font-semibold text-gray-800">{{ $q->getQuestionsPerStudent() }}</span> Q</span>
            <span title="Duration"><span class="font-semibold text-gray-800">{{ $q->duration_minutes }}</span> min</span>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('dashboard.quizzes.show', $q) }}" class="inline-flex items-center rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-colors">View</a>
            <a href="{{ route('dashboard.quizzes.edit', $q) }}" class="inline-flex items-center rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-colors">Edit</a>
            @if(!$q->hasStarted())
                <form action="{{ route('dashboard.quizzes.destroy', $q) }}" method="post" class="inline" onsubmit="return confirm('Delete this quiz?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center rounded-full border border-rose-100 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100 transition-colors">Delete</button>
                </form>
            @endif
        </div>
    </div>
@empty
    <div class="px-6 py-16 text-center">
        @if(trim((string) $search) !== '')
            <p class="text-sm text-gray-500 mb-1">No quizzes match your search.</p>
            <p class="text-xs text-gray-400">Try another title, course, or class group.</p>
        @elseif($activeTab === 'ended')
            <p class="text-sm text-gray-500 mb-1">No ended quizzes yet.</p>
            <p class="text-xs text-gray-400">Finished assessments will appear here.</p>
        @else
            <p class="text-sm text-gray-500 mb-4">No active quizzes yet.</p>
            <a href="{{ route('dashboard.quizzes.create') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-2 transition-colors">Create your first quiz</a>
        @endif
    </div>
@endforelse
