<?php

namespace App\Jobs;

use App\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Telegram\Bot\Laravel\Facades\Telegram;

class SendTelegramMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $messages = Message::orderBy('id')
            ->whereNull('message_id')
            ->get();

        foreach ($messages as $index => $message) {
            $telegramMessage = Telegram::sendMessage([
                'chat_id' => '@kase_news',
                'text' => $message->content,
                'parse_mode' => 'HTML',
            ]);

            $message->update([
                'message_id' => $telegramMessage->getMessageId(),
            ]);

            // 10 seconds pause before next message.
            if ($index < $messages->count() - 1) {
                sleep(10);
            }
        }
    }
}
