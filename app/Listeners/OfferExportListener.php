<?php

namespace App\Listeners;

use App\Events\OfferParsed;
use App\Jobs\ExportOffer;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Log;

class OfferExportListener
{

    use DispatchesJobs;

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
     * @param  OfferParsed $event
     * @return void
     */
    public function handle(OfferParsed $event)
    {
        $offer = $event->offer;
        $region = $offer->location()->first() ? $offer->location()->first()->region : null;
        if ($region and '---' !== $region) {
            $this->dispatch(
                (new ExportOffer($offer))
                    ->onQueue('export_offers')
            );
            Log::info('created export job for offer', ['olx_id' => $offer->olx_id]);
        }
        Log::info('Offer not exported, has no region', ['olx_id' => $offer->olx_id]);
    }
}
