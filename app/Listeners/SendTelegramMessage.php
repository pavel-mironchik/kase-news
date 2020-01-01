<?php

namespace App\Listeners;

use App\Events\NewsRetrieved;

class SendTelegramMessage
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  NewsRetrieved  $event
     * @return void
     */
    public function handle(NewsRetrieved $event)
    {
        \App\Jobs\SendTelegramMessage::dispatch($event->news);
    }
}
