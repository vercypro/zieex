@extends('layouts.app')

@section('content')
<div>
  <h2 style="font-weight:800;margin-bottom:1.5rem">Dashboard</h2>
  <div style="background:#1a1a2e;border:1px solid #313244;border-radius:10px;padding:1.5rem">
    <p style="color:#a6adc8">Welcome back, <strong style="color:#cdd6f4">{{ $user['username'] ?? 'User' }}</strong>!</p>
    <p style="color:#6c7086;font-size:.9rem;margin-top:.5rem">Role: {{ $user['role'] ?? 'user' }}</p>
  </div>
</div>
@endsection
