<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfilePhotoController extends Controller
{
    public function __invoke(Request $request, User $user): BinaryFileResponse
    {
        $viewer = $request->user();
        abort_unless($viewer && $viewer->is_active && ($viewer->is($user) || $viewer->hasRole('Administrator')), 403);

        $path = $user->profile_photo_path;
        abort_unless(is_string($path) && preg_match('/\Aprofile-photos\/[a-zA-Z0-9_-]+\.(?:jpe?g|png|webp)\z/i', $path), 404);

        $disk = Storage::disk('local');
        $root = realpath($disk->path(''));
        $file = realpath($disk->path($path));
        abort_unless($root && $file && is_file($file) && is_readable($file), 404);

        // Resolve the path before checking it so links cannot expose files outside the photo folder.
        $normalize = static function (string $value): string {
            $value = str_replace('\\', '/', $value);
            return DIRECTORY_SEPARATOR === '\\' ? strtolower($value) : $value;
        };
        $photoDirectory = rtrim($normalize($root), '/').'/profile-photos/';
        abort_unless(str_starts_with($normalize($file), $photoDirectory), 404);

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file);
        $image = @getimagesize($file);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        abort_unless(isset($extensions[$mime]) && $image && ($image['mime'] ?? null) === $mime, 404);

        return response()->file($file, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="profile-photo.'.$extensions[$mime].'"',
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
