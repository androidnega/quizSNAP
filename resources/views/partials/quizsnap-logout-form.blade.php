@props([
    'action',
    'buttonClass' => 'profile-menu-item profile-menu-item--danger w-full touch-manipulation min-h-[44px] lg:min-h-0',
    'iconClass' => 'fas fa-sign-out-alt',
    'label' => 'Log out',
    'showIcon' => true,
    'formClass' => '',
    'menuItem' => true,
])

<form action="{{ $action }}" method="post" data-quizsnap-logout-form @if($formClass) class="{{ $formClass }}" @endif>
    @csrf
    <button type="submit" class="{{ $buttonClass }}" @if($menuItem) role="menuitem" @endif>
        @if($showIcon)
            <span class="profile-menu-item-icon" aria-hidden="true"><i class="{{ $iconClass }}"></i></span>
        @endif
        <span data-quizsnap-logout-label>{{ $label }}</span>
    </button>
</form>
