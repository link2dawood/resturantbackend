@extends('layouts.tabler')

@section('title', 'Notifications')

@section('content')
<div class="container-xl mt-4 mb-5" style="max-width: 800px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="mb-0">Notifications</h1>
            <p class="text-muted mb-0">
                @if($unreadCount > 0)
                    {{ $unreadCount }} unread
                @else
                    All caught up
                @endif
            </p>
        </div>
        @if($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button class="btn btn-outline-secondary">Mark all as read</button>
        </form>
        @endif
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card">
        <div class="card-body p-0">
            @forelse($notifications as $notification)
            @php $data = $notification->data; @endphp
            <div class="d-flex justify-content-between align-items-start p-3 {{ $loop->last ? '' : 'border-bottom' }}"
                 style="{{ $notification->read_at ? '' : 'background: #f4f8ff;' }}">
                <div class="pe-3">
                    <div style="font-weight: {{ $notification->read_at ? '400' : '500' }};">
                        @if(! $notification->read_at)
                            <span class="badge bg-primary" style="width: 8px; height: 8px; padding: 0; border-radius: 50%;"
                                  aria-label="Unread"></span>
                        @endif
                        <a href="{{ $data['url'] ?? route('notifications.index') }}" class="text-decoration-none">
                            {{ $data['title'] ?? 'Notification' }}
                        </a>
                    </div>
                    @if(filled($data['body'] ?? null))
                        <div class="text-muted small">{{ $data['body'] }}</div>
                    @endif
                </div>
                <div class="text-muted small text-nowrap">{{ $notification->created_at->diffForHumans() }}</div>
            </div>
            @empty
            <div class="text-center text-muted py-5">No notifications yet.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-3">{{ $notifications->links() }}</div>
</div>
@endsection
