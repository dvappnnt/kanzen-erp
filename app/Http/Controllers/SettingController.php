<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Settings\AppSettings;

class SettingController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Index', [
            'settings' => app(AppSettings::class)->toArray(),
        ]);
    }

    public function styleKit()
    {
        return Inertia::render('Settings/StyleKit', [
            'settings' => app(AppSettings::class)->toArray(),
        ]);
    }

    public function typography()
    {
        return Inertia::render('Settings/Typography', [
            'settings' => app(AppSettings::class)->toArray(),
        ]);
    }

    public function environment()
    {
        return Inertia::render('Settings/Environment', [
            'settings' => app(AppSettings::class)->toArray(),
        ]);
    }

    public function database()
    {
        return Inertia::render('Settings/Database', [
            'settings' => app(AppSettings::class)->toArray(),
        ]);
    }
}
