<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Attributes\ParseMode;

class ScanOffersJob extends Job
{
    private Nutgram $telegram;

    const IMAGES_LIMIT = 4;
    const BASE_IMAGE_WIDTH = 400;
    const BASE_IMAGE_HEIGHT = 400;

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

        foreach ($response['data'] as $offer) {
            $hash = md5("{$offer['id']}:{$offer['title']}");

            if(!Redis::sismember('offers', $hash)) {
                // Add offer_id to offers
                Redis::sadd('offers', $hash);

                $message = "<a href='{$offer['url']}'>{$offer['title']}</a> | {$this->getValueByKey($offer, 'price')} \n";
                $message .= "<strong>Планування:</strong> {$this->getValueByKey($offer, 'layout')} \n";
                $message .= "<strong>Опалення:</strong> {$this->getValueByKey($offer, 'heating')} \n";
                $message .= "<strong>Ремонт:</strong> {$this->getValueByKey($offer, 'repair')} \n";
                $message .= "<strong>Побутова техніка:</strong> {$this->getValueByKey($offer, 'appliances_2')} \n";
                $message .= "<strong>Контакти:</strong> {$offer['contact']['name']} \n";
                $message .= "<strong>Створено|Оновлено:</strong> {$this->formatDateString($offer['created_time'])} | {$this->formatDateString($offer['last_refresh_time'])}";

                // Get images from offer
                $images = array_slice($this->parseImages($offer), 0, self::IMAGES_LIMIT);

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
     * @param array $offer
     * @return mixed
     */
    private function getValueByKey(array $offer, string $key): string
    {
        $paramIndex = array_search($key, array_column($offer['params'], 'key'));
        return $paramIndex !== false ? $offer['params'][$paramIndex]['value']['label'] : '-';
    }

    /**
     * @param string $dateString
     * @return string
     */
    private function formatDateString(string $dateString): string
    {
        return Carbon::parse($dateString)->format('d.m.y G:i');
    }

    /**
     * @param array $offer
     * @return array
     */
    private function parseImages(array $offer): array
    {
        return array_map(function ($image) {
            return [
                'type'  => 'photo',
                'media' =>  str_replace(['{height}', '{width}'], [self::BASE_IMAGE_HEIGHT, self::BASE_IMAGE_WIDTH], $image['link']),
            ];
        }, $offer['photos']);
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
