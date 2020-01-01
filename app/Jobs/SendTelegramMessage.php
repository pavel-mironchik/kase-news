<?php

namespace App\Jobs;

use App\News;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Telegram\Bot\Laravel\Facades\Telegram;

class SendTelegramMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;

    public $retryAfter = 30;

    public $timeout = 120;

    /**
     * @var News
     */
    private $news;

    /**
     * Create a new job instance.
     *
     * @param News $news
     */
    public function __construct(News $news)
    {
        $this->news = $news;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $news = $this->news;

        $title = $news->title;
        $dateTime = Carbon::create($news->date_time)->format('d.m.y, g:i');
        $content = $news->content;
        $url = 'https://kase.kz' . $news->url;

        $text = "<a href='$url'>$title</a>\n\n$dateTime\n\n$content";

        Telegram::sendMessage([
            'chat_id' => '@kase_news',
            'text' => $text,
            'parse_mode' => 'HTML'
        ]);
    }
}
