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
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserAuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|numeric|unique:users',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'All fields are Required',
                'error' => $validator->messages(),
            ], 422);
        }

        $otp = rand(100000, 999999);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'email_verification_code' => $otp,
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;
        Mail::to($user->email)->send(new EmailVerificationMail($otp));
        return response()->json([
            'message' => 'User created successfully.Verify your email with code sent',
            'user' => $user,
            'token' => $token,
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

        return response()->json(['message' => 'Email verified successfully.']);
    }

    public function completeProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'address' => 'required|string',
            'location' => 'required|json',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'All fields are Required',
                'error' => $validator->messages(),
            ], 422);
        }

        $user = Auth::user();

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('profile_images', 'public');
            $user->image = $imagePath;
        }

        $user->address = $request->address;
        $user->location = json_decode($request->location, true);
        $user->save();

        return response()->json([
            'message' => 'Profile completed successfully!',
            'user' => $user
        ], 200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'All fields are Required',
                'error' => $validator->messages(),
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'User loggedin successfully',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ], 200);
    }


    public function forgetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'All fields are Required',
                'error' => $validator->messages(),
            ], 422);
        }

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json([
                'message' => 'Password reset link sent to your email.'
            ], 200)
            : response()->json(['message' => 'Error sending password reset link.'], 400);
    }


    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'All fields are Required',
                'error' => $validator->messages(),
            ], 422);
        }
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
            return response()->json(['message' => 'Password has been successfully reset.'], 200);
        }

        return response()->json(['error' => 'Invalid token or email.'], 400);
    }
}
