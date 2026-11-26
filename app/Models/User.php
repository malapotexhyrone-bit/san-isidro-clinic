<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'birthdate',
        'address',
        'profile_photo_path',
        'role',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'password_ciphertext',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birthdate' => 'date',
            'is_active' => 'boolean',
        ];
    }

    protected function password(): Attribute
    {
        return Attribute::make(set: function (#[\SensitiveParameter] string $value): array {
            if (!Hash::isHashed($value)) {
                return $this->loginPasswordAttributes($value);
            }

            // Keep the encrypted copy during a hash upgrade only if it still matches.
            $ciphertext = $this->attributes['password_ciphertext'] ?? null;
            if ($ciphertext !== null) {
                try {
                    if (!Hash::check(Crypt::decryptString($ciphertext), $value)) {
                        $ciphertext = null;
                    }
                } catch (DecryptException $exception) {
                    $ciphertext = null;
                }
            }

            return ['password' => $value, 'password_ciphertext' => $ciphertext];
        });
    }

    public function setLoginPassword(#[\SensitiveParameter] string $password): void
    {
        // Form input is always a password, even if its text resembles a hash.
        foreach ($this->loginPasswordAttributes($password) as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    private function loginPasswordAttributes(#[\SensitiveParameter] string $password): array
    {
        return [
            'password' => Hash::make($password),
            'password_ciphertext' => Crypt::encryptString($password),
        ];
    }

    public function physician()
    {
        return $this->hasOne(Physician::class);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function accessDescription(): string
    {
        return match ($this->role) {
            'Receptionist' => 'Patient registration, appointment scheduling, arrival verification, and walk-in management.',
            'Nurse' => 'Patient visit review and vital-sign recording for arrived patients.',
            'Physician' => 'Assigned appointments, patient histories, vital signs, and consultation records.',
            'Administrator' => 'Clinic records, physician schedules, staff accounts, administrative reports, and activity logs.',
            default => 'Clinic workspace access based on the assigned role.',
        };
    }
}
