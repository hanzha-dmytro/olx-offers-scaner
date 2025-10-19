<?php

namespace App\Jobs;

use App\Contracts\OlxParserInterface;
use Illuminate\Support\Facades\Redis;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Attributes\ParseMode;

class ScanOffersJob extends Job
{
    private Nutgram $telegram;

    const IMAGES_LIMIT = 4;

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
    public function handle(OlxParserInterface $olxParser)
    {

        $offers = $olxParser->parseOffers(env('OLX_API_SCAN_URL'));

        if(isset($offers['error'])) {
            $this->telegram->sendMessage("Parsing was completed with error {$offers['error']['status']} ({$offers['error']['title']})", [
                'chat_id' => env('TELEGRAM_CHAT_ID'),
            ]);
        }

        foreach ($offers as $offer) {
            $hash = md5("{$offer['id']}::{$offer['title']}");

            if(!Redis::sismember('offers', $hash)) {
                // Add offer_id to offers
                Redis::sadd('offers', $hash);

                $message = "<a href='{$offer['url']}'>{$offer['title']}</a> | {$offer['price']} \n";

                foreach ($offer['parameters'] as $parameter) {
                    $message .= "<strong>{$parameter['name']}:</strong> {$parameter['value']} \n";
                }

                $message .= "<strong>Контакти:</strong> {$offer['contact']} \n";
                $message .= "<strong>Створено|Оновлено:</strong> {$offer['created_time']} | {$offer['last_refresh_time']}";

                // Get images from offer
                $images = array_slice($offer['images'], 0, self::IMAGES_LIMIT);

                if(count($images)) {
                    // Add a caption to first image in the group
                    $this->addCaptionToImage($images, $message);

                    $this->telegram->sendMediaGroup($images, [
                        'chat_id' => env('TELEGRAM_CHAT_ID'),
                        'parse_mode' => ParseMode::HTML,
                    ]);
                } else {
                    $this->telegram->sendMessage($message, [
                        'chat_id' => env('TELEGRAM_CHAT_ID'),
                        'parse_mode' => ParseMode::HTML,
                        'disable_web_page_preview' => true,
                    ]);
                }
            }
        }
    }

    /**
     * @param array $images
     * @param string $message
     * @return void
     */
    private function addCaptionToImage(array &$images, string $message)
    {
        $firstImage = array_shift($images);

        // Add a caption to first image
        $firstImage['caption'] = $message;
        $firstImage['parse_mode'] = ParseMode::HTML;

        $images = [$firstImage, ...$images ?? []];
    }
}
