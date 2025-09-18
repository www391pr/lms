<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AuthController
{
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'user_name' => 'required|string|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'role' => 'required|in:' . implode(',', [User::ROLE_STUDENT, User::ROLE_INSTRUCTOR]),
            'avatar' => 'file|image|max:10240',
        ]);
        if($request->role == User::ROLE_INSTRUCTOR) {
            $request->validate([
               'bio' => 'required',
            ]);
        }
        $code = rand(100000, 999999);
        $avatarData = null;
        $avatarExt = null;
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $avatarData = base64_encode(file_get_contents($file->getRealPath()));
            $avatarExt = $file->getClientOriginalExtension();
        }
        $cachedData = $request->only(['first_name', 'last_name', 'user_name', 'email', 'password', 'role']);
        if ($request->role === User::ROLE_INSTRUCTOR) {
            $cachedData['bio'] = $request->input('bio');
        }
        $cachedData['avatar_data'] = $avatarData;
        $cachedData['avatar_ext'] = $avatarExt;
        Cache::put('register_' . $request->email, [
            'data' => $cachedData,
            'code' => $code,
        ], now()->addMinutes(15));

//        Mail::to($request->email)->send(new VerificationCodeMail($code));
        return response()->json(['message' => 'Verification code sent to your email.']);

    }

    public function verifyRegister(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
        ]);

        $cached = Cache::get('register_' . $request->email);

        if (!$cached || $cached['code'] != $request->code) {
            return response()->json(['error' => 'Invalid or expired code.'], 422);
        }

        $data = $cached['data'];
        if (!empty($data['avatar_data']) && !empty($data['avatar_ext'])) {
            $filename = Str::uuid() . '.' . $data['avatar_ext'];
            Storage::disk('public')->put('images/avatars/' . $filename, base64_decode($data['avatar_data']));
            $data['avatar'] = 'storage/images/avatars/' . $filename;
        }
        unset($data['avatar_data'], $data['avatar_ext']);
        $data['password'] = bcrypt($data['password']);
        DB::transaction(function () use ($data, $request, &$user) {
            $user = User::create($data);

            Cache::forget('register_' . $request->email);

            if ($user->isStudent()) {
                $user->student()->create([
                    'full_name' => $user->first_name . ' ' . $user->last_name,
                ]);
            } elseif ($user->isInstructor()) {
                $user->instructor()->create([
                    'full_name' => $user->first_name . ' ' . $user->last_name,
                    'views'     => 0,
                    'bio'       => $data['bio'] ?? null,
                    'rating'    => 0,
                ]);
            }
        });
        if($user->isStudent())
            $user->load('student');
        else if ($user->isInstructor())
            $user->load('instructor');

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'Registration completed.',
            'user'    => $user,
            'token'   => $token,
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        // check if instructor is disabled
        if ($user->isInstructor() && !$user->instructor->enabled) {
            Auth::logout();
            return response()->json(['message' => 'Your instructor account is disabled.'], 403);
        }

        if ($user->isStudent()) {
            $user->load('student');
        } elseif ($user->isInstructor()) {
            $user->load('instructor');
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token'   => $token,
            'user'    => $user,
        ], 200);
    }

    // GOOGLE AUTH
    public function googleSignIn(Request $request)
    {
        $request->validate([
            'id_token' => 'required',
        ]);
        $idToken = $request->input('id_token');

        $client = new \Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]); // client ID from Google Console
        $payload = $client->verifyIdToken($idToken);

        if (!$payload) {
            return response()->json(['error' => 'Invalid ID token'], 401);
        }

        $email = $payload['email'];
        $user_name = $payload['name'];
        $user = User::where('email', $email)->first();

        if (!$user) {
            $request->validate([
                'first_name' => 'required',
                'last_name' => 'required',
                'password' => 'required|min:6|confirmed',
                'role' => 'required|in:' . implode(',', [User::ROLE_STUDENT, User::ROLE_INSTRUCTOR]),
            ]);

            DB::transaction(function () use ($user_name, $email, $request, &$user) {
                $user = User::create([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'user_name' => $user_name,
                    'email' => $email,
                    'password' => bcrypt($request->password),
                    'role' => $request->role,
                ]);

                Cache::forget('register_' . $request->email);

                if ($user->isStudent()) {
                    $user->student()->create([
                        'full_name' => $user->first_name . ' ' . $user->last_name,
                    ]);
                } elseif ($user->isInstructor()) {
                    $user->instructor()->create([
                        'full_name' => $user->first_name . ' ' . $user->last_name,
                        'views'     => 0,
                        'bio'       => $data['bio'] ?? null,
                        'rating'    => 0,
                    ]);
                }
            });
        }
        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $user->createToken('mobile')->plainTextToken,
        ] , 200);
    }


    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        if (!password_verify($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $user->password = bcrypt($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password changed successfully']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function sendResetCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $code = rand(100000, 999999);

        Cache::put('reset_' . $request->email, [
            'code' => $code,
        ], now()->addMinutes(15));

        Mail::to($request->email)->send(new \App\Mail\PasswordResetCodeMail($code));

        return response()->json(['message' => 'Reset code sent to your email.']);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|digits:6',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cached = Cache::get('reset_' . $request->email);

        if (!$cached || $cached['code'] != $request->code) {
            return response()->json(['message' => 'Invalid or expired reset code'], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->password = bcrypt($request->password);
        $user->save();

        Cache::forget('reset_' . $request->email);

        return response()->json(['message' => 'Password reset successfully']);
    }
    public function getInfo()
    {
        $user = Auth::user();
        if($user->isStudent())
            $user->load('student');
        else if ($user->isInstructor())
            $user->load('instructor');
        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json([
            'token'   => $token,
            'user'    => $user,
        ], 200);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = auth()->user();

        if ($request->filled('password')) {
            if (!$request->filled('old_password')) {
                return response()->json([
                    'message' => 'Old password is required to set a new password.'
                ], 422);
            }

            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'message' => 'The old password you entered is incorrect.'
                ], 422);
            }

            $user->password = bcrypt($request->password);
        }


        // update base user data
        $data = $request->only(['first_name', 'last_name', 'user_name']);
        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }
        if ($request->hasFile('avatar')) {
            $name = Storage::disk('public')->put('images/avatars', $request->file('avatar'));
            $data['avatar'] = 'storage/' . $name;
        }
        $user->update($data);

        // update related student/instructor data
        if ($user->role === 'student') {
            $user->student->update([
                'full_name' => $request->input('full_name', $user->student->full_name),
            ]);
        }

        if ($user->role === 'instructor') {
            $user->instructor->update([
                'full_name' => $request->input('full_name', $user->instructor->full_name),
                'bio'       => $request->input('bio', $user->instructor->bio),
            ]);
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => $user->load('student', 'instructor'),
        ]);
    }
}
