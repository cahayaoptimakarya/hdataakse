@php
    use App\Models\Menu as MenuModel;
    use App\Support\Permission as Perm;
    use Illuminate\Support\Facades\Route as RouteFacade;
    use Illuminate\Support\Str;

    $user = auth()->user();
    $allowed = $user ? Perm::viewableMenuIds($user) : collect();
    $allMenus = MenuModel::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    $currentRouteName = RouteFacade::currentRouteName();
    $currentBase = $currentRouteName ? Perm::resolveBaseRoute($currentRouteName) : null;
    $isActive = function ($menu) use ($currentBase) {
        return $menu->route && (
            request()->routeIs($menu->route)
            || ($currentBase && $menu->route === $currentBase)
            || (Str::endsWith($menu->route, '.index') && request()->routeIs(Str::beforeLast($menu->route, '.index').'.*'))
        );
    };
@endphp
<aside class="ui-side" id="ui_side">
    <a href="{{ route('admin.dashboard') }}" class="ui-logo"><img src="{{ asset('metronic/media/logos/logo-mark.png') }}" alt="Logo">Kontrol Biaya</a>
    @foreach($allMenus->whereNull('parent_id') as $top)
        @php
            $children = $allMenus->where('parent_id', $top->id)->filter(fn ($c) => $allowed->contains($c->id) && $c->route && RouteFacade::has($c->route));
            $showSelf = $top->route && RouteFacade::has($top->route) && $allowed->contains($top->id);
        @endphp
        @if($children->isNotEmpty())
            <div class="ui-grp">{{ $top->name }}</div>
            @if($showSelf)
                <a class="ui-nav {{ $isActive($top) ? 'on' : '' }}" href="{{ route($top->route) }}">
                    <span class="ic"><i class="{{ $top->icon ?: 'fa-solid fa-circle' }}"></i></span>{{ $top->name }}
                </a>
            @endif
            @foreach($children as $child)
                <a class="ui-nav {{ $isActive($child) ? 'on' : '' }}" href="{{ route($child->route) }}">
                    <span class="ic"><i class="{{ $child->icon ?: 'fa-solid fa-circle' }}"></i></span>{{ $child->name }}
                </a>
            @endforeach
        @elseif($showSelf)
            <a class="ui-nav {{ $isActive($top) ? 'on' : '' }}" href="{{ route($top->route) }}">
                <span class="ic"><i class="{{ $top->icon ?: 'fa-solid fa-circle' }}"></i></span>{{ $top->name }}
            </a>
        @endif
    @endforeach
</aside>
