<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelSettings\Settings;
use Spatie\Backup\Tasks\Backup\BackupJobFactory;
use Spatie\Backup\BackupDestination\BackupDestinationFactory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json(app(Settings::class)->toArray(), 200);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'icon' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp,avif|max:2048',
            'logo' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp,avif|max:2048',
        ]);

        $settings = app(\App\Settings\AppSettings::class);

        // Handle icon upload
        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('app-settings', 'public'); // Save to 'storage/app/public/app-settings'
            $settings->icon = $iconPath; // Save the relative path to the database
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('app-settings', 'public'); // Save to 'storage/app/public/app-settings'
            $settings->logo = $logoPath; // Save the relative path to the database
        }

        // Update other settings
        $settings->name = $validated['name'];
        $settings->description = $validated['description'];

        // Save settings
        $settings->save();

        return response()->json([
            'message' => 'Settings updated successfully.',
            'settings' => [
                'name' => $settings->name,
                'description' => $settings->description,
                'icon_url' => $settings->icon ? Storage::disk('public')->url($settings->icon) : null, // Generate public URL
                'logo_url' => $settings->logo ? Storage::disk('public')->url($settings->logo) : null, // Generate public URL
            ],
        ]);
    }

    public function style(Request $request)
    {
        $validated = $request->validate([
            // Navbar
            'navbar_text_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'navbar_bg_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'navbar_hover_bg_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'navbar_hover_text_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'navbar_active_bg_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'navbar_active_text_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',

            // Sidebar
            'sidebar_bg_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'sidebar_text_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'sidebar_hover_bg_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'sidebar_hover_text_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'sidebar_active_bg_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'sidebar_active_text_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',

            // Button
            'button_primary_bg_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'button_primary_text_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',

            // Input
            'input_active_bg_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',

            // Theme Colors
            'primary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'success_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'danger_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'warning_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'info_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $settings = app(\App\Settings\AppSettings::class);

        foreach ($validated as $key => $value) {
            $settings->{$key} = $value;
        }

        $settings->save();

        return response()->json([
            'message' => 'Settings updated successfully.',
        ]);
    }

    public function environment(Request $request)
    {
        $validated = $request->validate([
            // PUSHER
            'pusher_app_id' => 'nullable|string',
            'pusher_app_key' => 'nullable|string',
            'pusher_app_secret' => 'nullable|string',
            'pusher_app_cluster' => 'nullable|string',

            // GOOGLE
            'google_client_id' => 'nullable|string',
            'google_client_secret' => 'nullable|string',
            'google_redirect_uri' => 'nullable|string|url',

            // STRIPE
            'stripe_key' => 'nullable|string',
            'stripe_secret' => 'nullable|string',
            'stripe_webhook_secret' => 'nullable|string',

            // OPENAI
            'openai_api_url' => 'nullable|string|url',
            'openai_api_key' => 'nullable|string',

            // CLAUDE
            'claude_api_url' => 'nullable|string|url',
            'claude_api_key' => 'nullable|string',
        ]);

        $settings = app(\App\Settings\AppSettings::class);

        // Update PUSHER settings
        $settings->pusher_app_id = $validated['pusher_app_id'];
        $settings->pusher_app_key = $validated['pusher_app_key'];
        $settings->pusher_app_secret = $validated['pusher_app_secret'];
        $settings->pusher_app_cluster = $validated['pusher_app_cluster'];

        // Update GOOGLE settings
        $settings->google_client_id = $validated['google_client_id'];
        $settings->google_client_secret = $validated['google_client_secret'];
        $settings->google_redirect_uri = $validated['google_redirect_uri'];

        // Update STRIPE settings
        $settings->stripe_key = $validated['stripe_key'];
        $settings->stripe_secret = $validated['stripe_secret'];
        $settings->stripe_webhook_secret = $validated['stripe_webhook_secret'];

        // Update OPENAI settings
        $settings->openai_api_url = $validated['openai_api_url'];
        $settings->openai_api_key = $validated['openai_api_key'];

        // Update CLAUDE settings
        $settings->claude_api_url = $validated['claude_api_url'];
        $settings->claude_api_key = $validated['claude_api_key'];

        // Save settings
        $settings->save();

        return response()->json([
            'message' => 'Settings updated successfully.',
        ]);
    }

    public function updateSchedule(Request $request)
    {
        $request->validate([
            'frequency' => 'required|in:hourly,daily,weekly,yearly',
            'isEnabled' => 'required|boolean',
        ]);

        // Save settings to database or AppSettings (via Spatie App Settings)
        app('settings')->set('scheduling.frequency', $request->frequency);
        app('settings')->set('scheduling.isEnabled', $request->isEnabled);

        return response()->json(['message' => 'Scheduling updated successfully.']);
    }
    public function exportDatabase()
    {
        try {
            // Run the database backup using Artisan command
            Artisan::call('backup:run', ['--only-db' => true]);

            // Get all files from the backup directory
            $backupFiles = Storage::disk('local')->files('backups');

            if (empty($backupFiles)) {
                return response()->json(['error' => 'No backup file found.'], 404);
            }

            // Get the latest backup file
            $latestBackup = end($backupFiles);

            // Provide the file for download
            return response()->download(storage_path('app/' . $latestBackup));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to export the database: ' . $e->getMessage()], 500);
        }
    }

    public function importDatabase(Request $request)
    {
        $request->validate([
            'database_file' => 'required|file|mimes:sql',
        ]);

        try {
            $file = $request->file('database_file');

            // Move uploaded file to a temporary location
            $filePath = $file->storeAs('temp', 'imported_database.sql');

            // Execute the SQL file (for MySQL)
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            $dbHost = config('database.connections.mysql.host');
            $command = "mysql -u $dbUser -p$dbPass -h $dbHost $dbName < " . storage_path("app/$filePath");

            exec($command, $output, $return);

            if ($return !== 0) {
                throw new \Exception('Error executing SQL file.');
            }

            // Clean up temporary file
            Storage::delete($filePath);

            return response()->json(['message' => 'Database imported successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to import database: ' . $e->getMessage()], 500);
        }
    }
}
