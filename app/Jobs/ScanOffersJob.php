<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Attributes\ParseMode;

class ScanOffersJob extends Job
{
    private Nutgram $telegram;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
       $this->telegram = new Nutgram(env('TELEGRAM_TOKEN'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $response = Http::get(env('OLX_API_SCAN_URL'))->json();

        if(isset($response['error'])) {
            $this->telegram->sendMessage("Parsing was completed with error {$response['error']['status']} ({$response['error']['title']})", [
                'chat_id' => env('TELEGRAM_CHAT_ID'),
            ]);
        }

        $newOffers = [];
        foreach ($response['data'] as $offer) {
            if(!Redis::sismember('offers', $offer['id'])) {
                Redis::sadd('offers', $offer['id']);
                $newOffers[] = $offer;
            }
        }

        if(count($newOffers) >= 1) {
            $message = "✅ Знайдено нові оголошення [" . count($newOffers) . "]\n";

            for ($i = 0; $i < count($newOffers); $i++) {
                $message .= ($i + 1) . ". <a href='" . $newOffers[$i]['url'] . "'>" . $newOffers[$i]['title'] . "</a> | " .  $this->getOfferPrice($newOffers[$i]) . "\n";
            }

            $this->telegram->sendMessage($message, [
                'chat_id' => env('TELEGRAM_CHAT_ID'),
                'parse_mode' => ParseMode::HTML,
                'disable_web_page_preview' => true,
            ]);
        }
    }

    /**
     * @param array $offer
     * @return mixed
     */
    private function getOfferPrice(array $offer)
    {
        $priceIndex = array_search('price', array_column($offer['params'], 'key'));
        return $offer['params'][$priceIndex]['value']['label'];
    }
}
