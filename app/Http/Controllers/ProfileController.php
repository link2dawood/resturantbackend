<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the user's profile.
     */
    public function show()
    {
        return view('profile.show', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Show the form for editing the user's profile.
     */
    public function edit()
    {
        return view('profile.edit', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = Auth::user();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Security: invalidate every other session for this account and keep the
        // current one alive (re-stamps this session with the new password hash so
        // AuthenticateSession doesn't log the user out of the device they're on).
        Auth::logoutOtherDevices($request->password);

        return redirect()->route('profile.show')
            ->with('success', 'Password updated successfully. You have been signed out on all other devices.');
    }

    /**
     * Update the user's picture securely without Intervention Image.
     */
    public function updateAvatar(Request $request)
    {
        try {
            // Validate file
            $request->validate([
                'avatar' => [
                    'required',
                    'image',
                    'mimes:jpeg,png,jpg,gif',
                    'max:2048',
                    'dimensions:min_width=50,min_height=50,max_width=2000,max_height=2000',
                ],
            ]);

            $user = Auth::user();
            $file = $request->file('avatar');

            // Extra security check (extension + mime + content scan)
            if (! $this->isSecureImageFile($file)) {
                Log::warning('Insecure file upload attempt', [
                    'user_id' => $user->id,
                    'filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                ]);

                return back()->withErrors(['avatar' => 'File failed security validation']);
            }

            // Delete old avatar if it exists
            if ($user->avatar && Storage::disk('public')->exists('avatars/'.$user->avatar)) {
                Storage::disk('public')->delete('avatars/'.$user->avatar);
            }

            // Generate secure filename
            $avatarName = $user->id.'_'.time().'_'.bin2hex(random_bytes(8)).'.'.$file->getClientOriginalExtension();

            // Store file directly into storage/app/public/avatars
            $file->storeAs('avatars', $avatarName, 'public');

            // Update user record
            $user->update(['avatar' => $avatarName]);

            Log::info('Picture uploaded successfully', [
                'user_id' => $user->id,
                'filename' => $avatarName,
            ]);

            return redirect()->route('profile.show')->with('success', 'Picture updated successfully.');

        } catch (\Exception $e) {
            Log::error('Picture upload failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['avatar' => 'Upload failed. Please try again.']);
        }
    }

    /**
     * Process and store image with security measures.
     */
    private function processAndStoreImage($file, $filename)
    {
        try {
            // Process image with Intervention Image
            $image = Image::make($file->getPathname());

            // Remove EXIF data and resize if needed
            $image->resize(400, 400, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // Save processed image
            $path = storage_path('app/public/avatars/'.$filename);
            $image->save($path, 85); // 85% quality

        } catch (\Exception $e) {
            // Fallback to simple file storage if image processing fails
            $file->storeAs('avatars', $filename, 'public');
        }
    }

    /**
     * Enhanced security check for uploaded files.
     */
    private function isSecureImageFile($file)
    {
        // Check file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, $allowedExtensions)) {
            return false;
        }

        // Check MIME type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
        if (! in_array($file->getMimeType(), $allowedMimes)) {
            return false;
        }

        // Check file signature (magic bytes)
        $handle = fopen($file->getPathname(), 'rb');
        $header = fread($handle, 8);
        fclose($handle);

        $signatures = [
            'jpeg' => ["\xFF\xD8\xFF"],
            'png' => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
            'gif' => ['GIF87a', 'GIF89a'],
        ];

        $isValid = false;
        foreach ($signatures as $type => $sigs) {
            foreach ($sigs as $sig) {
                if (substr($header, 0, strlen($sig)) === $sig) {
                    $isValid = true;
                    break 2;
                }
            }
        }

        if (! $isValid) {
            return false;
        }

        // Check for suspicious content
        $content = file_get_contents($file->getPathname());
        $suspiciousPatterns = [
            '/<\?php/i',
            '/<script/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove the user's picture.
     */
    public function removeAvatar()
    {
        $user = Auth::user();

        if ($user->avatar && Storage::disk('public')->exists('avatars/'.$user->avatar)) {
            Storage::disk('public')->delete('avatars/'.$user->avatar);
        }

        $user->update([
            'avatar' => null,
        ]);

        return redirect()->route('profile.show')->with('success', 'Picture removed successfully.');
    }

    /**
     * Business logo — shown in the dashboard navbar in place of the business name.
     */
    public function updateLogo(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->isOwner() || $user->isAdmin() || $user->isFranchisor(), 403);

        try {
            $request->validate([
                'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            ]);

            $file = $request->file('logo');

            if (! $this->isSecureImageFile($file)) {
                Log::warning('Insecure logo upload attempt', [
                    'user_id' => $user->id,
                    'filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                ]);

                return back()->withErrors(['logo' => 'File failed security validation']);
            }

            if ($user->logo && Storage::disk('public')->exists('logos/'.$user->logo)) {
                Storage::disk('public')->delete('logos/'.$user->logo);
            }

            $logoName = $user->id.'_'.time().'_'.bin2hex(random_bytes(8)).'.'.$file->getClientOriginalExtension();
            $file->storeAs('logos', $logoName, 'public');

            $user->update(['logo' => $logoName]);

            return redirect()->route('profile.show')->with('success', 'Business logo updated successfully.');
        } catch (\Exception $e) {
            Log::error('Business logo upload failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['logo' => 'Upload failed. Please try again.']);
        }
    }

    public function removeLogo()
    {
        $user = Auth::user();
        abort_unless($user->isOwner() || $user->isAdmin() || $user->isFranchisor(), 403);

        if ($user->logo && Storage::disk('public')->exists('logos/'.$user->logo)) {
            Storage::disk('public')->delete('logos/'.$user->logo);
        }

        $user->update(['logo' => null]);

        return redirect()->route('profile.show')->with('success', 'Business logo removed successfully.');
    }
}
