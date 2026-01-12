<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * The currently authenticated user.
     *
     * @var User|null
     */
    protected $currentUser;

    public function __construct()
    {
        $this->currentUser = Auth::user();
    }

    /**
     * Ensure the current user has one of the allowed roles.
     *
     * @param  array<string>  $roles
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function allowOnly(array $roles): void
    {
        if (! in_array($this->currentUser?->role, $roles)) {
            abort(403, 'Access denied');
        }
    }
}
