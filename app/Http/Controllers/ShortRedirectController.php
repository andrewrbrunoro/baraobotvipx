<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\RedirectUriRepository;
use Illuminate\Http\RedirectResponse;

class ShortRedirectController extends Controller
{
    protected RedirectUriRepository $redirectUriRepository;

    public function __construct()
    {
        $this->redirectUriRepository = RedirectUriRepository::make();
    }

    public function __invoke(string $code): RedirectResponse
    {
        $redirect = $this->redirectUriRepository
            ->findByCode($code);

        if (!$redirect)
            abort(404);

        if ($redirect->max_read_times === $redirect->read_times)
            abort(400);

        $redirect->read_times = $redirect->read_times + 1;
        $redirect->save();

        return redirect($redirect->uri);
    }
}
