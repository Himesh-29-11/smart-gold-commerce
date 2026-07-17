@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<section class="page-hero compact"><span class="kicker">Account updates</span><h1>Notifications</h1><p>Order, delivery and financing updates in one place.</p></section>
<section class="section narrow notification-page">
    <div class="notification-heading"><div><h2>Recent updates</h2><p>{{ auth()->user()->unreadNotifications()->count() }} unread</p></div>@if(auth()->user()->unreadNotifications()->exists())<form method="POST" action="{{ route('account.notifications.read-all') }}">@csrf<button class="button button-outline" type="submit">Mark all read</button></form>@endif</div>
    <div class="notification-list">
        @forelse($notifications as $notification)
            <form method="POST" action="{{ route('account.notifications.read', $notification->id) }}">@csrf<button type="submit" class="notification-item {{ $notification->read_at ? '' : 'unread' }}"><span class="notification-icon">{{ match(data_get($notification->data,'category')){'shipment'=>'⌖','loan'=>'₹',default=>'✓'} }}</span><span><b>{{ data_get($notification->data,'title','Account update') }}</b><small>{{ data_get($notification->data,'message') }}</small><time>{{ $notification->created_at->diffForHumans() }}</time></span><i>→</i></button></form>
        @empty
            <div class="empty-state"><span>◇</span><h2>No notifications yet</h2><p>Your account updates will appear here.</p></div>
        @endforelse
    </div>
    {{ $notifications->links() }}
</section>
@endsection
