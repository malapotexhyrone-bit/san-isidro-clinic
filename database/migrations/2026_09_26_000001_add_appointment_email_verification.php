<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Nullable preserves legacy patients; the application requires it for new/edited records.
            $table->string('email')->nullable()->unique()->after('contact_number');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('verification_code', 10)->nullable()->unique()->after('appointment_number');
            $table->string('notification_email')->nullable()->after('verification_code');
            $table->timestamp('verified_at')->nullable()->after('notification_email');
            $table->timestamp('email_sent_at')->nullable()->after('verified_at');
        });

        DB::table('appointments')
            ->whereIn('status', ['Scheduled', 'Rescheduled'])
            ->whereNull('verification_code')
            ->orderBy('id')
            ->each(function ($appointment): void {
                DB::table('appointments')->where('id', $appointment->id)->update([
                    'verification_code' => $this->uniqueVerificationCode(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique(['verification_code']);
            $table->dropColumn(['verification_code', 'notification_email', 'verified_at', 'email_sent_at']);
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn('email');
        });
    }

    private function uniqueVerificationCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $code = 'SIC-'.$suffix;
        } while (DB::table('appointments')->where('verification_code', $code)->exists());

        return $code;
    }
};
