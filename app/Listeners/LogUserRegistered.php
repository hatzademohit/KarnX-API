<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Request;

class LogUserRegistered
{
    public function handle(Registered $event)
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'ip' => Request::ip(),
                'url' => Request::fullUrl(),
                'agent' => Request::header('User-Agent'),
            ])
            ->log('User registered');
    }
}

