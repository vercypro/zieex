@extends('layouts.app')

@section('content')
<div style="text-align:center;padding:5rem 1rem">
  <h1 style="font-size:3rem;font-weight:900;background:linear-gradient(135deg,#6366f1,#818cf8);-webkit-background-clip:text;-webkit-text-fill-color:transparent">
    ⚡ Zieex
  </h1>
  <p style="color:#6c7086;font-size:1.2rem;margin-top:.75rem">Lightweight PHP Framework · MVC · REST · AJAX-First</p>
  <div style="display:flex;gap:1rem;justify-content:center;margin-top:2rem">
    <a href="/register" style="background:#6366f1;color:#fff;padding:.75rem 1.75rem;border-radius:7px;text-decoration:none;font-weight:700">Get Started</a>
    <a href="/login" style="border:1px solid #313244;color:#cdd6f4;padding:.75rem 1.75rem;border-radius:7px;text-decoration:none;font-weight:700">Login</a>
  </div>
</div>
@endsection
