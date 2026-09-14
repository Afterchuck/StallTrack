<x-layouts.app :title="$title" :active="$active">
    <div class="page-heading"><div><h1>{{ $title }}</h1><p>{{ $description }}</p></div></div>
    <section class="empty-panel"><div class="empty-icon">▤</div><h2>{{ $title }} workspace</h2><p>This section is ready for your records and reporting data.</p><a class="black-button" href="{{ route('dashboard') }}">Back to vendors <span>→</span></a></section>
</x-layouts.app>
