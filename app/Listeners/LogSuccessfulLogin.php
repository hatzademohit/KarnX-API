<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;

class LogSuccessfulLogin
{
    public function handle(Login $event)
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'ip' => Request::ip(),
                'url' => Request::fullUrl(),
                'agent' => Request::header('User-Agent'),
            ])
            ->log('User logged in');
    }
}
