@php $search = $search ?? ''; @endphp
                        @forelse($users as $u)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 text-sm font-medium text-gray-900 break-words" title="{{ $u->username }}">{!! \App\Support\SearchHighlight::mark($u->username, $search ?? '') !!}</td>
                                <td class="px-3 py-2 text-sm text-gray-600 break-words uppercase" title="{{ $u->name ?? '-' }}">{!! $u->name ? \App\Support\SearchHighlight::mark(Str::upper($u->name), $search ?? '') : '—' !!}</td>
                                <td class="px-3 py-2">
                                    @php
                                        $roleLabels = [
                                            'super_admin' => ['label' => 'Admin', 'class' => 'bg-primary-100 text-primary-800'],
                                            'system_admin' => ['label' => 'System Monitor', 'class' => 'bg-purple-100 text-purple-800'],
                                            'examiner' => ['label' => 'Examiner', 'class' => 'bg-success-100 text-success-800'],
                                            'coordinator' => ['label' => 'Coordinator', 'class' => 'bg-indigo-100 text-indigo-800'],
                                            'student' => ['label' => 'Student', 'class' => 'bg-gray-100 text-gray-800'],
                                            'leader' => ['label' => 'Leader', 'class' => 'bg-amber-100 text-amber-800'],
                                        ];
                                        $r = $roleLabels[$u->role] ?? ['label' => $u->role, 'class' => 'bg-gray-100 text-gray-700'];
                                    @endphp
                                    <span class="inline-block w-fit px-2 py-1 text-xs font-semibold rounded-md whitespace-nowrap {{ $r['class'] }}">{{ $r['label'] }}</span>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-600 break-words uppercase" title="{{ $u->institution?->name ?? '—' }}">{{ $u->institution?->name ? Str::upper($u->institution->name) : '—' }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600">
                                    <div class="flex flex-wrap gap-1.5 min-w-0">
                                        @if($u->courses->isNotEmpty())
                                            @foreach($u->courses->take(3) as $course)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200 truncate max-w-[140px] uppercase" title="{{ $course->name }}">
                                                    {{ Str::upper($course->name) }}
                                                </span>
                                            @endforeach
                                            @if($u->courses->count() > 3)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-50 text-gray-500 border border-gray-200">
                                                    +{{ $u->courses->count() - 3 }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </div>
                                </td>
                                @if(isset($isSuperAdmin) && $isSuperAdmin)
                                <td class="px-3 py-2 text-sm">
                                    @if($u->role === 'examiner' || $u->role === \App\Models\User::ROLE_COORDINATOR)
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-600" id="sms-display-{{ $u->id }}">
                                            {{ $u->sms_allocation ?? 0 }} <span class="text-xs text-gray-500">({{ $u->sms_remaining ?? 0 }} left)</span>
                                        </span>
                                        <button type="button" onclick="openSmsModal({{ $u->id }}, @json($u->username), {{ $u->sms_allocation ?? 0 }}, {{ $u->sms_used ?? 0 }})" class="inline-flex p-1 rounded text-gray-500 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="Add SMS credits">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        </button>
                                    </div>
                                    @else
                                    <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                @endif
                                @if(!empty($canManageAiTokens))
                                <td class="px-3 py-2 text-sm">
                                    @if($u->isExaminer() || (!empty($isSuperAdmin) && $u->role === \App\Models\User::ROLE_COORDINATOR))
                                    @php
                                        try {
                                            $aiRemaining = app(\App\Services\AiQuizTokenService::class)->getRemaining($u);
                                        } catch (\Throwable $e) {
                                            $aiRemaining = 0;
                                        }
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-600" id="ai-display-{{ $u->id }}">
                                            <span class="font-medium">{{ $aiRemaining }}</span> <span class="text-xs text-gray-500">left</span>
                                        </span>
                                        <button type="button" onclick="openAiModal({{ $u->id }}, @json($u->username), {{ $aiRemaining }}, {{ $u->ai_quiz_tokens_allocation ?? 0 }}, {{ $u->ai_quiz_tokens_used ?? 0 }})" class="inline-flex p-1 rounded text-gray-500 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="Assign AI tokens">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                        </button>
                                    </div>
                                    @else
                                    <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                @endif
                                <td class="px-3 py-2 text-right text-sm">
                                    <div class="flex justify-end gap-1">
                                        @if(isset($isSuperAdmin) && $isSuperAdmin)
                                            @if($u->role === 'super_admin')
                                            <form action="{{ route('dashboard.users.reset-password', $u) }}" method="post" class="inline" onsubmit="return confirm('Reset password for {{ $u->username }}? A new temporary password will be generated.');">
                                                @csrf
                                                <button type="submit" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="Reset password">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('dashboard.users.reset-password', $u) }}" method="post" class="inline" onsubmit="return confirm('Reset password for {{ $u->username }}? A new temporary password will be generated.');">
                                                @csrf
                                                <button type="submit" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="Reset password">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                                </button>
                                            </form>
                                            @endif
                                            <form action="{{ route('dashboard.users.revoke', $u) }}" method="post" class="inline" onsubmit="return confirm('Revoke access? User will need to log in again.');">
                                                @csrf
                                                <button type="submit" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-amber-600 hover:bg-amber-50 transition-colors" title="Revoke access">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                </button>
                                            </form>
                                            <a href="{{ route('dashboard.users.edit', $u) }}" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="Edit">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </a>
                                            @if($u->role !== 'super_admin')
                                            <form action="{{ route('dashboard.users.destroy', $u) }}" method="post" class="inline" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-danger-600 hover:bg-danger-50 transition-colors" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                            @endif
                                        @else
                                            <a href="{{ route('dashboard.users.edit', $u) }}" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-primary-600 hover:bg-primary-50 transition-colors" title="Edit">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 6 + (isset($isSuperAdmin) && $isSuperAdmin ? 1 : 0) + (!empty($canManageAiTokens) ? 1 : 0) }}" class="px-3 py-12 text-center text-gray-500">{{ trim((string)($search ?? '')) !== '' ? 'No users match your search.' : (!empty($isCoordinatorManager) ? 'No examiners in your area yet.' : 'No users yet. Add a user.') }}</td>
                            </tr>
                        @endforelse
