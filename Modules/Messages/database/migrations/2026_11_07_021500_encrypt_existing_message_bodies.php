<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('messages')
            ->whereNotNull('body')
            ->where('body', '!=', '')
            ->orderBy('id')
            ->chunk(100, function ($messages) {
                foreach ($messages as $message) {
                    try {
                        // If already encrypted, decrypting will succeed
                        Crypt::decryptString($message->body);
                    } catch (DecryptException) {
                        // Message is plaintext, encrypt it
                        DB::table('messages')
                            ->where('id', $message->id)
                            ->update([
                                'body' => Crypt::encryptString($message->body),
                            ]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('messages')
            ->whereNotNull('body')
            ->where('body', '!=', '')
            ->orderBy('id')
            ->chunk(100, function ($messages) {
                foreach ($messages as $message) {
                    try {
                        $decrypted = Crypt::decryptString($message->body);
                        DB::table('messages')
                            ->where('id', $message->id)
                            ->update([
                                'body' => $decrypted,
                            ]);
                    } catch (DecryptException) {
                        // Already plaintext or cannot decrypt
                    }
                }
            });
    }
};
