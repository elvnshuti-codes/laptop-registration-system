<li class="nav-item">
    <a class="nav-link {{ $item['active'] ? 'active' : '' }}" href="{{ $item['url'] }}">{{ $item['flat'] ?? $item['label'] }}</a>
</li>