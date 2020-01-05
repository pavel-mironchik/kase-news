<?php

namespace App\Jobs;

use App\Events\NewsRetrieved;
use App\News;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetrieveContent implements ShouldQueue
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
        $client = new Client(['base_uri' => 'https://kase.kz']);

        $news = News::whereNull('content')
            ->orderBy('date_time')
            ->first();
        if (! $news) {
            return;
        }

        try {
            $response = $client->get($news->url);
        } catch (RequestException $exception) {
            Log::error(Psr7\str($exception->getRequest()));
            if ($exception->hasResponse()) {
                Log::error(Psr7\str($exception->getResponse()));
            }
            return;
        }

        $content = $response->getBody()->getContents();

        $re = '|<div class="news-block">(.*?)</div>|ms';
        if (preg_match($re, $content, $matches)) {
            $news->update([
                'content' => trim($matches[1])
            ]);

            event(new NewsRetrieved($news));
        }
    }
}
