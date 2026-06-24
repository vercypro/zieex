<?php

declare(strict_types=1);

namespace Zieex\Template;

class View
{
    private static string  $viewPath     = '';
    private static string  $cachePath    = '';
    private static bool    $cacheEnabled = true;
    private static array   $sections     = [];
    private static ?string $layoutName   = null;
    private static array $bufferStack = [];

    public static function init(): void
    {
        self::$viewPath     = BASE_PATH . '/resources/views';
        self::$cachePath    = BASE_PATH . '/storage/cache/views';
        self::$cacheEnabled = env('APP_ENV', 'local') !== 'local';

        if (!is_dir(self::$cachePath)) {
            mkdir(self::$cachePath, 0755, true);
        }
    }

    private static function includeFileRaw(string $__path, array $__data): string
    {
        $__run = static function (string $__f, array $__d): string {
            extract($__d, EXTR_SKIP);
            ob_start();
            include $__f;
            return ob_get_clean();
        };

        return $__run($__path, $__data);
    }

    public static function render(string $view, array $data = []): string
    {
        self::init();

        $viewFile = self::$viewPath . '/' . str_replace('.', '/', $view) . '.ze.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View [{$view}] not found at {$viewFile}");
        }

        // Reset state ONCE at the start
        self::$layoutName = null;
        self::$sections   = [];

        $compiled = self::compile($viewFile);

        // Run child view — populates self::$sections and self::$layoutName
        self::includeFile($compiled, $data);

        // If @extends was used, render the layout WITHOUT resetting sections
        if (self::$layoutName !== null) {
            $layoutFile = self::$viewPath . '/' . self::$layoutName . '.ze.php';
            if (!file_exists($layoutFile)) {
                throw new \RuntimeException("Layout [" . self::$layoutName . "] not found.");
            }
            // Compile and run layout — sections are already populated from child
            $layoutCompiled = self::compile($layoutFile);
            return self::includeFileRaw($layoutCompiled, $data);
        }

        return self::$sections['__raw'] ?? '';
    }

    private static function includeFile(string $__path, array $__data): void
    {
        $__run = static function (string $__f, array $__d): void {
            extract($__d, EXTR_SKIP);
            include $__f;
        };

        $__run($__path, $__data);
    }

    // Called by compiled @extends
    public static function startLayout(string $name): void
    {
        self::$layoutName = $name;
    }

    // Called by compiled @section
    public static function startSection(string $name): void
    {
        self::$sections['__current'] = $name;
        self::$bufferStack[] = ob_get_level();
        ob_start();
    }

    // Called by compiled @endsection
    public static function endSection(): void
    {
        $name = self::$sections['__current'] ?? '__unnamed';
        unset(self::$sections['__current']);
        self::$sections[$name] = ob_get_clean();
        array_pop(self::$bufferStack);
    }

    // Called by compiled @yield
    public static function yieldSection(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }

    private static function compile(string $file): string
    {
        $hash       = md5($file . filemtime($file));
        $cachedFile = self::$cachePath . '/' . $hash . '.php';

        if (self::$cacheEnabled && file_exists($cachedFile)) {
            return $cachedFile;
        }

        $source   = file_get_contents($file);
        $compiled = self::compileDirectives($source);

        file_put_contents($cachedFile, $compiled);
        return $cachedFile;
    }

    private static function compileDirectives(string $source): string
    {
        $replacements = [
            // {{ $var }} - escaped
            '/\{\{\s*(.+?)\s*\}\}/'           => '<?= htmlspecialchars((string)($1), ENT_QUOTES, \'UTF-8\') ?>',
            // {!! $var !!} - unescaped
            '/\{!!\s*(.+?)\s*!!\}/'           => '<?= $1 ?>',
            // @extends
            '/@extends\([\'"](.+?)[\'"]\)/'   => '<?php \Zieex\Template\View::startLayout(str_replace(".", "/", "$1")); ?>',
            // @section / @endsection
            '/@section\([\'"](.+?)[\'"]\)/'   => '<?php \Zieex\Template\View::startSection("$1"); ?>',
            '/@endsection/'                    => '<?php \Zieex\Template\View::endSection(); ?>',
            // @yield
            '/@yield\([\'"](.+?)[\'"]\)/'     => '<?= \Zieex\Template\View::yieldSection("$1") ?>',
            // @include
            '/@include\([\'"](.+?)[\'"]\)/'   => '<?php echo \Zieex\Template\View::render("$1", get_defined_vars()); ?>',
            // @if / @elseif / @else / @endif
            '/@if\((.+?)\)/'                  => '<?php if ($1): ?>',
            '/@elseif\((.+?)\)/'              => '<?php elseif ($1): ?>',
            '/@else/'                          => '<?php else: ?>',
            '/@endif/'                         => '<?php endif; ?>',
            // @foreach / @endforeach
            '/@foreach\((.+?)\)/'             => '<?php foreach ($1): ?>',
            '/@endforeach/'                    => '<?php endforeach; ?>',
            // @for / @endfor
            '/@for\((.+?)\)/'                 => '<?php for ($1): ?>',
            '/@endfor/'                        => '<?php endfor; ?>',
            // @while / @endwhile
            '/@while\((.+?)\)/'               => '<?php while ($1): ?>',
            '/@endwhile/'                      => '<?php endwhile; ?>',
            // @php / @endphp
            '/@php/'                           => '<?php',
            '/@endphp/'                        => '?>',
            // @csrf
            '/@csrf/'                          => '<?= \Zieex\Auth\CSRF::field() ?>',
            // @method
            '/@method\([\'"](.+?)[\'"]\)/'    => '<input type="hidden" name="_method" value="$1">',
            // @auth / @endauth
            '/@auth/'                          => '<?php if (\Zieex\Auth\Auth::check()): ?>',
            '/@endauth/'                       => '<?php endif; ?>',
            // @guest / @endguest
            '/@guest/'                         => '<?php if (\Zieex\Auth\Auth::guest()): ?>',
            '/@endguest/'                      => '<?php endif; ?>',
            // @empty / @endempty
            '/@empty/'                         => '<?php if (empty($__last)): ?>',
            '/@endempty/'                      => '<?php endif; ?>',
            // @dd
            '/@dd\((.+?)\)/'                  => '<?php dd($1); ?>',
            // @flash
            '/@flash\([\'"](.+?)[\'"]\)/'     => '<?php if ($msg = flash_get("$1")): ?><div class="flash flash--$1"><?= htmlspecialchars($msg) ?></div><?php endif; ?>',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $source = preg_replace($pattern, $replacement, $source);
        }

        return $source;
    }
}
