<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('conversation_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('sender_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('receiver_id')->nullable()->after('sender_id')->constrained('users')->nullOnDelete();

            $table->index('conversation_id');
            $table->index(['sender_id', 'receiver_id']);
        });

        $this->backfillLegacyMessages();
    }

    /**
     * Convert the pre-conversation "ticket + single reply" rows into real
     * conversations so existing support threads keep their history.
     */
    private function backfillLegacyMessages(): void
    {
        $adminId = User::query()->where('role', User::ROLE_ADMIN)->orderBy('id')->value('id');

        Message::query()
            ->whereNotNull('user_id')
            ->where('subject', '!=', 'Partnership / Business')
            ->orderBy('created_at')
            ->get()
            ->groupBy('user_id')
            ->each(function ($messages, $userId) use ($adminId) {
                $conversation = Conversation::query()->firstOrCreate(
                    ['user_id' => $userId],
                    ['admin_id' => $adminId],
                );

                foreach ($messages as $message) {
                    if ($message->conversation_id !== null) {
                        continue;
                    }

                    if ($message->from_admin) {
                        $message->sender_id = $conversation->admin_id;
                        $message->receiver_id = $message->user_id;
                    } else {
                        $message->sender_id = $message->user_id;
                        $message->receiver_id = $conversation->admin_id;
                    }

                    $message->conversation_id = $conversation->id;
                    $message->saveQuietly();

                    if ($message->reply !== null && $conversation->admin_id !== null) {
                        $reply = new Message([
                            'user_id' => $message->user_id,
                            'conversation_id' => $conversation->id,
                            'sender_id' => $conversation->admin_id,
                            'receiver_id' => $message->user_id,
                            'subject' => $message->subject,
                            'message' => $message->reply,
                            'from_admin' => true,
                            'admin_read_at' => $message->replied_at ?? now(),
                            'read_at' => $message->read_at,
                        ]);

                        $reply->created_at = $message->replied_at ?? $message->created_at;
                        $reply->updated_at = $message->replied_at ?? $message->created_at;
                        $reply->saveQuietly();
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['conversation_id']);
            $table->dropIndex(['sender_id', 'receiver_id']);
            $table->dropConstrainedForeignId('conversation_id');
            $table->dropConstrainedForeignId('sender_id');
            $table->dropConstrainedForeignId('receiver_id');
        });
    }
};
