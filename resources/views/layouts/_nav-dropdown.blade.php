<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle {{ collect($items)->contains('active', true) ? 'active' : '' }}"
       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">{{ $label }}</a>
    <ul class="dropdown-menu">
        @foreach (collect($items)->groupBy(fn ($i) => $i['group'] ?? '') as $group => $groupItems)
            @unless ($loop->first)
                <li><hr class="dropdown-divider"></li>
            @endunless
            @if ($group !== '')
                <li><h6 class="dropdown-header">{{ $group }}</h6></li>
            @endif
            @foreach ($groupItems as $item)
                <li>
                    <a class="dropdown-item {{ $item['active'] ? 'active' : '' }}" href="{{ $item['url'] }}">{{ $item['item'] ?? $item['label'] }}</a>
                </li>
            @endforeach
        @endforeach
    </ul>
</li>