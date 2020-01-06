<?php

namespace App\Jobs;

use App\News;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetrieveLinks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 600;

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
        $client = new Client(['base_uri' => 'https://kase.kz/ru/news/']);

        $date = Carbon::now()->subHour()->format('d.m.Y');
        try {
            $response = $client->get($date.'/');
        } catch (RequestException $exception) {
            Log::error(Psr7\str($exception->getRequest()));
            if ($exception->hasResponse()) {
                Log::error(Psr7\str($exception->getResponse()));
            }

            return;
        }

        $content = $response->getBody()->getContents();

        if (strpos($content, 'Новостей не найдено')) {
            return;
        }

        $re = '|class="news-list__date">(.*?)</div>.*?<div class="news-list__title"><a href="(.*?)".*?>(.*?)</a>|ms';
        if (! preg_match_all($re, $content, $matches, PREG_SET_ORDER)) {
            return;
        }

        foreach ($matches as $match) {
            News::updateOrCreate([
                'date_time'  => Carbon::createFromFormat('d.m.y, H:i', $match[1]),
                'url'  => $match[2],
                'title'  => $match[3],
            ]);
        }
    }
}
