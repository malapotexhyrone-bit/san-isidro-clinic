<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    private string $photoTestRoot;

    protected function setUp(): void
    {
        parent::setUp();

        // Isolate storage without creating test files under the clinic's real storage directory.
        $this->photoTestRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'clinic-private-photo-tests-'.bin2hex(random_bytes(8));
        foreach (['local', 'public'] as $disk) {
            $root = $this->photoTestRoot.DIRECTORY_SEPARATOR.$disk;
            File::makeDirectory($root, 0700, true);
            Storage::set($disk, Storage::build(['driver' => 'local', 'root' => $root, 'throw' => true]));
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->photoTestRoot)) {
            File::deleteDirectory($this->photoTestRoot);
        }
        parent::tearDown();
    }

    public function test_profile_upload_replace_and_remove_use_private_storage(): void
    {
        $user = $this->staff('Nurse', 'private-own');
        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'profile_photo' => $this->photo(),
        ])->assertRedirect('/profile');

        $first = $user->fresh()->profile_photo_path;
        $this->assertStringStartsWith('profile-photos/', $first);
        $this->assertTrue(Storage::disk('local')->exists($first));
        $this->assertFalse(Storage::disk('public')->exists($first));

        $this->put('/profile', ['name' => $user->name, 'profile_photo' => $this->photo()])->assertRedirect('/profile');
        $second = $user->fresh()->profile_photo_path;
        $this->assertNotSame($first, $second);
        $this->assertFalse(Storage::disk('local')->exists($first));
        $this->assertTrue(Storage::disk('local')->exists($second));

        $this->put('/profile', ['name' => $user->name, 'remove_profile_photo' => '1'])->assertRedirect('/profile');
        $this->assertNull($user->fresh()->profile_photo_path);
        $this->assertFalse(Storage::disk('local')->exists($second));
    }

    public function test_administrator_create_replace_and_remove_use_private_storage(): void
    {
        $admin = $this->staff('Administrator', 'private-admin-upload');
        $details = ['name' => 'Photo staff', 'username' => 'private-created', 'role' => 'Receptionist', 'is_active' => '1'];
        $this->actingAs($admin)->post('/users', $details + [
            'password' => 'TemporaryClinicPhoto1!',
            'password_confirmation' => 'TemporaryClinicPhoto1!',
            'profile_photo' => $this->photo(),
        ])->assertRedirect('/users');

        $user = User::where('username', $details['username'])->firstOrFail();
        $first = $user->profile_photo_path;
        $this->assertTrue(Storage::disk('local')->exists($first));
        $this->assertFalse(Storage::disk('public')->exists($first));

        $this->put('/users/'.$user->id, $details + ['profile_photo' => $this->photo()])->assertRedirect('/users');
        $second = $user->fresh()->profile_photo_path;
        $this->assertFalse(Storage::disk('local')->exists($first));
        $this->assertTrue(Storage::disk('local')->exists($second));
        $this->assertFalse(Storage::disk('public')->exists($second));

        $this->put('/users/'.$user->id, $details + ['remove_profile_photo' => '1'])->assertRedirect('/users');
        $this->assertNull($user->fresh()->profile_photo_path);
        $this->assertFalse(Storage::disk('local')->exists($second));
    }

    public function test_each_active_role_can_view_its_own_photo_and_only_admin_can_view_another(): void
    {
        $admin = $this->staff('Administrator', 'private-photo-admin');
        foreach (['Receptionist', 'Nurse', 'Physician', 'Administrator'] as $index => $role) {
            $user = $this->staff($role, 'private-owner-'.$index);
            $this->assignPhoto($user, 'profile-photos/owner-'.$index.'.png');

            $response = $this->actingAs($user)->get('/profile-photos/'.$user->id)->assertOk()
                ->assertHeader('Content-Type', 'image/png')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Pragma', 'no-cache');
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $this->assertSame($this->png(), file_get_contents($response->baseResponse->getFile()->getPathname()));

            $this->actingAs($admin)->get('/profile-photos/'.$user->id)->assertOk();
        }

        $owner = $this->staff('Nurse', 'private-other-owner');
        $this->assignPhoto($owner);
        foreach (['Receptionist', 'Nurse', 'Physician'] as $index => $role) {
            $other = $this->staff($role, 'private-denied-'.$index);
            $this->actingAs($other)->get('/profile-photos/'.$owner->id)->assertForbidden();
        }
    }

    public function test_guests_and_inactive_users_cannot_view_private_photos(): void
    {
        $owner = $this->staff('Nurse', 'private-guest-owner');
        $this->assignPhoto($owner);
        $this->get('/profile-photos/'.$owner->id)->assertRedirect('/');
        $this->getJson('/profile-photos/'.$owner->id)->assertUnauthorized();

        $owner->update(['is_active' => false]);
        $this->actingAs($owner)->get('/profile-photos/'.$owner->id)->assertForbidden();

        $admin = $this->staff('Administrator', 'private-inactive-admin');
        $admin->update(['is_active' => false]);
        $this->actingAs($admin)->get('/profile-photos/'.$owner->id)->assertForbidden();
    }

    public function test_profile_and_administrator_views_use_authorized_photo_urls(): void
    {
        $user = $this->staff('Nurse', 'private-view-owner');
        $this->assignPhoto($user);
        $photoUrl = route('profile.photo', $user);

        foreach (['/profile', '/profile/edit'] as $page) {
            $this->actingAs($user)->get($page)->assertOk()->assertSee($photoUrl, false)
                ->assertDontSee('storage/profile-photos', false);
        }
        $admin = $this->staff('Administrator', 'private-view-admin');
        $this->actingAs($admin)->get('/users/'.$user->id)->assertOk()->assertSee($photoUrl, false)
            ->assertDontSee('storage/profile-photos', false);
    }

    public function test_missing_photos_have_no_public_storage_fallback(): void
    {
        $user = $this->staff('Nurse', 'private-missing');
        $this->actingAs($user)->get('/profile-photos/'.$user->id)->assertNotFound();
        $path = 'profile-photos/public-only.png';
        Storage::disk('public')->put($path, $this->png());
        $user->forceFill(['profile_photo_path' => $path])->save();
        $this->get('/profile-photos/'.$user->id)->assertNotFound();
    }

    public function test_unsafe_paths_and_non_image_contents_cannot_be_served(): void
    {
        $user = $this->staff('Nurse', 'private-path-check');
        Storage::disk('local')->put('private-note.png', $this->png());
        Storage::disk('local')->put('profile-photos/not-image.png', '<html>Private content</html>');
        Storage::disk('local')->put('profile-photos/not-allowed.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        foreach (['profile-photos/../private-note.png', 'profile-photos\\private-note.png', 'profile-photos/%2e%2e/private-note.png', 'private-note.png', 'profile-photos/not-image.png', 'profile-photos/not-allowed.svg'] as $path) {
            $user->forceFill(['profile_photo_path' => $path])->save();
            $this->actingAs($user)->get('/profile-photos/'.$user->id)->assertNotFound();
        }
    }

    public function test_photo_content_type_comes_from_image_bytes(): void
    {
        $user = $this->staff('Nurse', 'private-mime-check');
        $this->assignPhoto($user, 'profile-photos/misleading.jpg');
        $this->actingAs($user)->get('/profile-photos/'.$user->id)->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    private function staff(string $role, string $username): User
    {
        return User::create(['name' => $username, 'username' => $username, 'role' => $role, 'is_active' => true, 'password' => 'OriginalClinicPhoto1!']);
    }

    private function assignPhoto(User $user, string $path = 'profile-photos/photo.png'): void
    {
        Storage::disk('local')->put($path, $this->png());
        $user->forceFill(['profile_photo_path' => $path])->save();
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('profile.png', $this->png());
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jU2cAAAAASUVORK5CYII=');
    }
}
