<x-layouts.admin title="Announcements" active="announcements">
    <section class="page-heading">
        <h1>Announcements</h1>
        <p>Share market news and reminders on every vendor's dashboard.</p>
    </section>

    @if (session('success'))
        <div class="success-alert" role="status">{{ session('success') }}</div>
    @endif

    <section class="form-panel">
        <form method="GET" action="{{ route('announcements') }}" class="mb-5 flex flex-wrap items-end gap-3">
            <label class="field">Search announcements<input type="search" name="search" maxlength="100" value="{{ request('search') }}" placeholder="Title or message"></label>
            <label class="field">Visibility<select name="visibility"><option value="">All announcements</option>@foreach (['Published', 'Draft', 'Expired', 'Scheduled'] as $visibility)<option @selected(request('visibility') === $visibility)>{{ $visibility }}</option>@endforeach</select></label>
            <label class="field">Pin status<select name="pinned"><option value="">All</option><option value="1" @selected(request('pinned') === '1')>Pinned</option><option value="0" @selected(request('pinned') === '0')>Not pinned</option></select></label>
            <button type="submit" class="app-btn-primary">Filter</button><a href="{{ route('announcements') }}">Reset</a>
        </form>
        <h2 class="text-lg font-bold text-slate-900">{{ $announcement->exists ? 'Edit announcement' : 'New announcement' }}</h2>
        @if ($errors->any())
            <div class="form-alert" role="alert">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <form class="portal-form" method="POST" action="{{ $announcement->exists ? route('announcements.update', $announcement) : route('announcements.store') }}">
            @csrf
            @if ($announcement->exists) @method('PUT') @endif
            <label class="field">Title
                <input name="title" value="{{ old('title', $announcement->title) }}" maxlength="150" required>
            </label>
            <label class="field">Message
                <textarea name="message" rows="6" maxlength="10000" required>{{ old('message', $announcement->message) }}</textarea>
            </label>
            <div class="form-grid">
                <label class="field">Visibility
                    <select name="status" required>
                        <option value="Draft" @selected(old('status', $announcement->published_at ? 'Published' : 'Draft') === 'Draft')>Draft — only admins can see it</option>
                        <option value="Published" @selected(old('status', $announcement->published_at ? 'Published' : 'Draft') === 'Published')>Published — visible to all vendors</option>
                    </select>
                </label>
                <label class="field">Expiry date (optional)
                    <input type="date" name="expires_at" min="{{ today()->toDateString() }}" value="{{ old('expires_at', $announcement->expires_at?->toDateString()) }}">
                    <small>Visible through the end of this date ({{ config('app.timezone') }}). Leave blank for no expiry.</small>
                </label>
            </div>
            <input type="hidden" name="is_pinned" value="0">
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $announcement->is_pinned))>
                Pin to the top of the vendor announcements
            </label>
            <div class="form-actions">
                @if ($announcement->exists)<a href="{{ route('announcements') }}">Cancel editing</a>@endif
                <button type="submit" class="app-btn-primary">Save announcement</button>
            </div>
        </form>
    </section>

    <section class="vendor-directory-card mt-5">
        <div class="vendor-directory-scroll">
            <table class="vendor-directory-table">
                <thead><tr><th>Announcement</th><th>Status</th><th>Author</th><th>Published</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($announcements as $post)
                        <tr>
                            <td><strong>{{ $post->title }}</strong>@if ($post->is_pinned)<small>Pinned</small>@endif</td>
                            <td>{{ ! $post->published_at ? 'Draft' : ($post->expires_at?->isPast() ? 'Expired' : 'Published') }}</td>
                            <td>{{ $post->user?->name ?? 'Former administrator' }}</td>
                            <td>{{ $post->published_at?->format('M d, Y H:i') ?? 'Not published' }}</td>
                            <td>
                                <div class="flex flex-wrap items-center gap-3">
                                    <a class="font-semibold text-emerald-700" href="{{ route('announcements.edit', $post) }}">Edit</a>
                                    @if ($post->published_at)
                                        <form method="POST" action="{{ route('announcements.unpublish', $post) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="app-btn-cancel">Unpublish</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No announcements yet. Create a draft or publish your first message above.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-4">{{ $announcements->links() }}</div>
</x-layouts.admin>
