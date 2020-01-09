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

        $htmlContent = $response->getBody()->getContents();

        $re = '|<div class="news-block">(.*?)</div>|ms';
        if (preg_match($re, $htmlContent, $matches)) {
            $content = trim($matches[1]);

            // Update news.
            $news->update([
                'content' => $content,
            ]);

            // Save messages.
            $messages = self::extractMessages($news);
            foreach ($messages as $message) {
                $news->messages()->create([
                    'content' => $message,
                ]);
            }
        }
    }

    /**
     * Split news's content into messages.
     *
     * @param  News  $news
     * @return array
     */
    protected function extractMessages(News $news): array
    {
        $title = $news->title;
        $dateTime = Carbon::create($news->date_time)->format('d.m.y, H:i');
        $url = 'https://kase.kz'.$news->url;
        $header = "<a href='$url'>$title</a>\n\n$dateTime\n\n";
        $headerLength = mb_strlen($header);

        if ($headerLength + mb_strlen($news->content) <= 4096) {
            $messages = [$header.$news->content];
        } else {
            // Split news content into messages with a length less than 4088 characters.
            // 4087 + double new line + pagination === 4087 + 2 + 7 === 4096
            $maxChunkLength = 4087 - $headerLength;
            $messages = preg_split(
                '/(.{'.$maxChunkLength.'})/us',
                $news->content,
                -1,
                PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE
            );

            // Compose messages.
            for ($i = 0; $i < count($messages); $i++) {
                $pagination = sprintf('[%02d/%02d]', $i + 1, count($messages));
                $messages[$i] = $header.$messages[$i]."\n\n".$pagination;
            }
        }

        return $messages;
    }
}
