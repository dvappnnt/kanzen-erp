<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Mail\GeneralMail;
use Illuminate\Support\Facades\Mail;

class CompanyController extends Controller
{
    protected $modelClass;
    protected $modelName;

    public function __construct()
    {
        $this->modelClass = \App\Models\Company::class;
        $this->modelName = class_basename($this->modelClass);
    }

    public function index()
    {
        return $this->modelClass::latest()->paginate(perPage: 10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|string|max:255',
            'mobile' => 'required|string|max:255',
            'landline' => 'nullable|string|max:255',
            'address' => 'required|string|max:255',
            'description' => 'nullable|string|max:1024',
            'website' => 'nullable|url|string|max:255',
            'avatar' => 'nullable|file|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('companies/avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        $validated['created_by_user_id'] = auth()->user()->id;
        $company = $this->modelClass::create($validated);

        return response()->json([
            'modelData' => $company,
            'message' => "{$this->modelName} '{$company->name}' created successfully.",
        ]);
    }

    public function show($id)
    {
        $model = $this->modelClass::findOrFail($id);
        return $model;
    }

    public function update(Request $request, $id)
    {
        $model = $this->modelClass::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|string|max:255',
            'mobile' => 'required|string|max:255',
            'landline' => 'nullable|string|max:255',
            'address' => 'required|string|max:255',
            'description' => 'nullable|string|max:1024',
            'website' => 'nullable|url|string|max:255',
            'avatar' => 'nullable|file|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($model->avatar && \Storage::disk('public')->exists($model->avatar)) {
                \Storage::disk('public')->delete($model->avatar);
            }

            $avatarPath = $request->file('avatar')->store('companies/avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        $model->update($validated);

        return response()->json([
            'modelData' => $model,
            'message' => "{$this->modelName} '{$model->name}' updated successfully.",
        ]);
    }

    public function destroy($id)
    {
        $model = $this->modelClass::findOrFail($id);
        $model->delete();

        return response()->json(['message' => "{$this->modelName} deleted successfully."], 200);
    }

    public function restore($id)
    {
        $model = $this->modelClass::withTrashed()->findOrFail($id);
        $model->restore();

        return response()->json([
            'message' => "{$this->modelName} restored successfully."
        ], 200);
    }

    public function autocomplete(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:1',
        ]);

        $searchTerm = $request->input('search');

        $models = $this->modelClass::where('name', 'like', "%{$searchTerm}%")
            ->take(10)
            ->get();

        if ($models->isEmpty()) {
            return response()->json([
                'message' => "No {$this->modelName}s found.",
            ], 404);
        }

        return response()->json([
            'data' => $models,
            'message' => "{$this->modelName}s retrieved successfully."
        ], 200);
    }

    public function invite(Request $request)
    {
        $request->validate([
            'emails' => 'required|array',
            'emails.*' => 'email',
            'companyId' => 'required|exists:companies,id',
        ]);

        $emails = $request->input('emails');
        $company = $this->modelClass::findOrFail($request->companyId);

        foreach ($emails as $email) {
            $token = \Str::random(32); // Generate a unique token for the invitation
            \DB::table('company_invitations')->insert([
                'email' => $email,
                'company_id' => $company->id,
                'token' => $token,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Prepare the email data
            $emailData = [
                'company' => $company,
                'buttonLink' => route('api.companies.invite.accept', ['token' => $token]), // Use the token in the link
            ];

            // Send the invitation email
            Mail::to($email)->send(
                new GeneralMail(
                    'emails.companies.invite', // Blade view
                    $emailData,              // Data for the email
                    'You’re Invited to Join ' . $company->name // Subject
                )
            );
        }

        return response()->json(['message' => 'Invites sent successfully.']);
    }

    public function inviteAccept($token)
    {
        // Retrieve the invitation by token
        $invitation = \DB::table('company_invitations')->where('token', $token)->first();
        $user = \DB::table('users')->where('email', $invitation->email)->first();

        if (!$invitation) {
            return response()->json(['message' => 'Invalid or expired invitation token.'], 400);
        }

        // Check if the user is already a member
        $userId = $user->id;
        $isAlreadyMember = \DB::table('company_members')
            ->where('user_id', $userId)
            ->where('company_id', $invitation->company_id)
            ->exists();

        if ($isAlreadyMember) {
            return response()->json(['message' => 'User is already a member of the company.'], 400);
        }

        // Add the user as a member of the company
        \DB::table('company_members')->insert([
            'user_id' => $userId,
            'company_id' => $invitation->company_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Delete the token to prevent reuse
        \DB::table('company_invitations')->where('token', $token)->delete();

        return response()->json(['message' => 'Invite accepted successfully.'], 201);
    }
}
