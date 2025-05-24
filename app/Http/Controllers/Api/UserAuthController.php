<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailVerificationMail;
use App\Models\Listing;
use App\Models\Transaction;
use App\Models\Rating;
use App\Models\Review;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class UserAuthController extends Controller
{
    use ApiResponseTrait;
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $otp = rand(100000, 999999);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'email_verification_code' => $otp,
        ]);
        Mail::to($user->email)->send(new EmailVerificationMail($otp));
        return response()->json([
            'message' => 'User created successfully. Verify your email with code sent',
            'user' => $user,
        ], 200);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)
            ->where('email_verification_code', $request->otp)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid OTP.'], 400);
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->email_verification_code = null; // Remove OTP after verification
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Email verified successfully.',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    public function completeProfile(Request $request)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'address' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $user = Auth::user();

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $this->storeImage($request->file('image'));
            $user->image = $imagePath;
        }

        $user->address = $request->address;
        $user->latitude = $request->latitude;
        $user->longitude = $request->longitude;
        $user->save();

        return response()->json([
            'message' => 'Profile completed successfully!',
            'user' => $user
        ], 200);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        $user = Auth::user();
        if ($user->email_verified_at === null) {
            $otp = rand(100000, 999999);

            Mail::to($request->email)->send(new EmailVerificationMail($otp));

            $user->email_verification_code = $otp;
            $user->save();
            return response()->json([
                'verified' => false,
                'message' => 'Please verify your email before logging in. A new email has been sent with the verification code.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'User loggedin successfully',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ], 200);
    }




    public function forgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $status = Password::sendResetLink([
            'email' => $request->only('email')
        ]);

        return $status === Password::RESET_LINK_SENT
            ? response()->json([
                'message' => 'Password reset link sent to your email.'
            ], 200)
            : response()->json(['message' => 'Error sending password reset link.'], 400);
    }


    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => bcrypt($request->password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return back()->with('message', "Password reset done successfully!");
        }

        return back()->with('message', "Invalid email or token!");
    }

    public function me()
    {
        return response()->json(
            [
                'user' => Auth::user()
            ],
            200
        );
    }

    public function showResetPasswordForm(Request $request)
    {
        return view(
            'auth.forgetPasswordLink',
            [
                'token' => $request->query('token'),
                'email' => $request->query('email')
            ]
        );
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();

        // Check if current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);


        return response()->json(['message' => 'Password updated successfully.'], 200);
    }

    public function editProfile(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|unique:users,phone_number,' . $user->id,
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Handle Image Upload

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('profile_images', 'public');
            $user->image = $imagePath;
        }
        $user->update([
            'name' => $request->name ?? $user->name,
            'phone_number' => $request->phone_number ?? $user->phone_number,
            'email' => $request->email ?? $user->email,
            'address' => $request->address ?? $user->address,
            'latitude' => $request->latitude ?? $user->latitude,
            'longitude' => $request->longitude ?? $user->longitude,
        ]);

        return response()->json([
            'message' => 'Profile updated successfully!',
            'user' => $user
        ], 200);
    }
}
