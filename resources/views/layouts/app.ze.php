<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @csrf
  <title>{{ $title ?? config('app.name') }}</title>
  <link rel="stylesheet" href="/assets/css/zieex.css">
</head>
<body>
  <nav style="background:#1a1a2e;padding:.75rem 1.5rem;display:flex;align-items:center;justify-content:space-between">
    <a href="/" style="color:#818cf8;font-weight:800;text-decoration:none;font-size:1.1rem">⚡ {{ config('app.name') }}</a>
    <div style="display:flex;gap:1rem;align-items:center">
      @auth
        <a href="/dashboard" style="color:#cdd6f4;text-decoration:none;font-size:.9rem">Dashboard</a>
        <form method="POST" action="/logout" style="display:inline">
          @csrf
          <button type="submit" style="background:none;border:1px solid #f38ba8;color:#f38ba8;padding:.35rem .85rem;border-radius:5px;cursor:pointer;font-size:.85rem">Logout</button>
        </form>
      @endauth
      @guest
        <a href="/login" style="color:#cdd6f4;text-decoration:none;font-size:.9rem">Login</a>
        <a href="/register" style="background:#6366f1;color:#fff;text-decoration:none;padding:.35rem .85rem;border-radius:5px;font-size:.85rem">Register</a>
      @endguest
    </div>
  </nav>

  <main style="max-width:1100px;margin:2rem auto;padding:0 1.5rem">
    @flash('success')
    @flash('error')
    @yield('content')
  </main>

  <script src="/assets/js/interaction.js"></script>
</body>
</html>
