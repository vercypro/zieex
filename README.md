# ⚡ Zieex Framework

A lightweight, AJAX-first PHP framework with MVC, REST API, and modern features — designed for shared hosting.

## Requirements
- PHP 8.2+
- Composer
- MySQL / PostgreSQL / SQLite
- Apache with `mod_rewrite`

## Installation

### Web Installer (Recommended)
1. Upload all files to your `public_html`
2. Visit your domain — the installer loads automatically
3. Fill in app name, database credentials, and admin account
4. Done!

### CLI Installer
```bash
composer install
cp .env.example .env
php core/CLI/zx key:generate
php core/CLI/zx migrate
```

## Directory Structure
```
├── app/
│   ├── Controllers/        # Your controllers
│   ├── Models/             # Your models
│   ├── Middleware/         # Custom middleware
├── core/                   # Framework internals
├── config/                 # Configuration files
├── routes/
│   ├── web/index.php       # Web routes
│   └── api/index.php       # API routes
├── resources/views/        # .ze.php templates
├── storage/                # Logs, cache
├── public/assets/          # JS, CSS
├── install/                # Web installer
└── index.php               # Entry point
```

## Quick Start

### Routes
```php
// routes/web/index.php
Router::get('/', [HomeController::class, 'index']);
Router::post('/login', [AuthController::class, 'login'])->rateLimit(5, 60);
Router::group(['prefix' => '/admin', 'middleware' => ['auth', 'role:admin']], function () {
    Router::get('/users', [UserController::class, 'index']);
});
Router::resource('/api/posts', PostController::class);
```

### Controllers
```php
class PostController extends Controller {
    public function index(Request $request): Response {
        return $this->view('posts.index', ['posts' => Post::all()]);
    }
    public function store(Request $request): Response {
        $data = $this->validate($request, [
            'title'   => 'required|max:255',
            'content' => 'required',
        ]);
        $post = Post::create($data);
        return $this->success($post, 'Post created.', 201);
    }
}
```

### Models
```php
class Post extends Model {
    protected static string $table = 'posts';
}

Post::find(1);
Post::where('status', 'published')->orderBy('created_at', 'DESC')->get();
Post::create(['title' => 'Hello', 'content' => 'World']);
```

### Templates (.ze.php)
```html
@extends('layouts.app')
@section('content')
  <h1>{{ $title }}</h1>
  @foreach($posts as $post)
    <p>{{ $post['title'] }}</p>
  @endforeach
  @auth
    <a href="/logout">Logout</a>
  @endauth
@endsection
```

### Auth
```php
Auth::attempt($email, $password);
Auth::check();
Auth::user();
Auth::logout();
Auth::hash($password);    // BCRYPT
Auth::verify($pw, $hash);
```

### Query Builder
```php
DB::table('users')
  ->where('role', 'admin')
  ->orderBy('created_at', 'DESC')
  ->limit(10)
  ->get();

DB::table('users')->paginate(15, $page);
DB::transaction(fn() => /* ... */);
```

### Logging
```php
Log::info('User logged in', ['user_id' => $id]);
Log::error('Payment failed', ['order' => $orderId]);
Log::warning('Rate limit approaching', ['ip' => $ip]);
```

### Mail
```php
Mail::driver('smtp')
    ->to('user@example.com')
    ->subject('Welcome!')
    ->html('<h1>Hello!</h1>')
    ->send();

Mail::driver('resend')->to($email)->subject('Auth Code')->view('emails.otp', ['code' => $code])->send();
```

### Events
```php
Event::on('user.registered', fn($user) => Mail::driver('resend')->to($user['email'])->...->send());
Event::emit('user.registered', $user);
```

### Helpers
```php
env('APP_NAME', 'default')
config('app.name')
view('home', $data)
redirect('/login')
response()->json($data)
base_path('storage/logs')
uuid()
now()
dd($var)
flash('success', 'Done!')
csrf_token()
```

## CLI Commands
```bash
php core/CLI/zx make:controller PostController
php core/CLI/zx make:model Post
php core/CLI/zx make:middleware AuthMiddleware
php core/CLI/zx make:migration create_posts_table
php core/CLI/zx migrate
php core/CLI/zx cache:clear
php core/CLI/zx key:generate
php core/CLI/zx serve
```

## Interaction.js / LiveComponent

All links and forms use AJAX by default (no page reload).

```html
<!-- Opt out of AJAX -->
<a href="/page" data-no-ajax>Hard link</a>

<!-- Button action -->
<button data-action="/posts/1" data-method="DELETE" data-confirm="Are you sure?">Delete</button>

<!-- Target specific element -->
<form data-target="#results">...</form>
```

### LiveComponent
```javascript
LiveComponent.register('counter', {
  data: () => ({ count: 0 }),
  methods: {
    increment() { this.count++; }
  },
  render() {
    return `<p>${this.count}</p><button data-on="click:increment">+</button>`;
  }
});
```
```html
<div data-live="counter"></div>
```

---
MIT License · Built with ❤️ using zero runtime dependencies.
