<?php
declare(strict_types=1);

namespace Zieex\Http;

use Zieex\Auth\CSRF;
use Zieex\Validation\Validator;
use Zieex\Validation\ValidationException;

abstract class Controller
{
    protected function view(string $template, array $data = []): Response
    {
        return view($template, $data);
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return (new Response())->json($data, $status);
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return redirect($url, $status);
    }

    protected function back(): Response
    {
        return back();
    }

    protected function validate(Request $request, array $rules): array
    {
        try {
            return $request->validate($rules);
        } catch (ValidationException $e) {
            $_SESSION['_old_input']  = $request->all();
            $_SESSION['_errors']     = $e->getErrors();

            if ($request->isAjax()) {
                (new Response())->json([
                    'success' => false,
                    'errors'  => $e->getErrors(),
                ], 422)->send();
                exit;
            }

            back()->send();
            exit;
        }
    }

    protected function success(mixed $data = null, string $message = 'Success', int $status = 200): Response
    {
        return (new Response())->success($data, $message, $status);
    }

    protected function error(string $message, int $status = 400, mixed $errors = null): Response
    {
        return (new Response())->error($message, $status, $errors);
    }
}
