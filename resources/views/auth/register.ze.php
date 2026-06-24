@extends('layouts.app')

@section('content')
<div style="max-width:420px;margin:3rem auto">
  <h2 style="font-weight:800;margin-bottom:1.5rem">Create Account</h2>

  <form method="POST" action="/register" style="background:#1a1a2e;border:1px solid #313244;border-radius:10px;padding:2rem">
    @csrf
    <div style="margin-bottom:1rem">
      <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:.4rem">Username</label>
      <input type="text" name="username" value="{{ old('username') }}" required style="width:100%;background:#0f0f1a;border:1px solid #313244;color:#cdd6f4;padding:.65rem .9rem;border-radius:6px;font-size:.9rem">
    </div>
    <div style="margin-bottom:1rem">
      <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:.4rem">Email</label>
      <input type="email" name="email" value="{{ old('email') }}" required style="width:100%;background:#0f0f1a;border:1px solid #313244;color:#cdd6f4;padding:.65rem .9rem;border-radius:6px;font-size:.9rem">
    </div>
    <div style="margin-bottom:1rem">
      <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:.4rem">Password</label>
      <input type="password" name="password" minlength="8" required style="width:100%;background:#0f0f1a;border:1px solid #313244;color:#cdd6f4;padding:.65rem .9rem;border-radius:6px;font-size:.9rem">
    </div>
    <div style="margin-bottom:1.5rem">
      <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:.4rem">Confirm Password</label>
      <input type="password" name="password_confirmation" required style="width:100%;background:#0f0f1a;border:1px solid #313244;color:#cdd6f4;padding:.65rem .9rem;border-radius:6px;font-size:.9rem">
    </div>
    <button type="submit" style="width:100%;background:#6366f1;color:#fff;border:none;padding:.75rem;border-radius:6px;font-size:1rem;font-weight:700;cursor:pointer" data-loading="Creating account...">Register</button>
  </form>

  <p style="text-align:center;margin-top:1rem;font-size:.9rem;color:#6c7086">
    Have an account? <a href="/login" style="color:#818cf8">Login</a>
  </p>
</div>
@endsection
