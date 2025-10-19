<?php

namespace App\Services;

use App\Contracts\OlxParserInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class JsonOlxParser implements OlxParserInterface
{
    const BASE_IMAGE_WIDTH = 400;
    const BASE_IMAGE_HEIGHT = 400;

    // List of required parameters
    private array $parameters = ['floor', 'total_floors', 'number_of_rooms_string', 'total_area', 'layout', 'heating', 'repair', 'appliances_2'];

    /**
     * @param string $url
     * @return array
     */
    public function parseOffers(string $url): array
    {
        $response = Http::connectTimeout(30)->get(env('OLX_API_SCAN_URL'))->json();

        if(isset($response['error'])) {
            return $response;
        }

        return array_map(function ($offer) {
            return [
                'id'                => $offer['id'],
                'url'               => $offer['url'],
                'title'             => $offer['title'],
                'description'       => $offer['description'],
                'price'             => $this->getParameterValueByKey($offer,'price'),
                'parameters'        => $this->adaptParameters($offer['params']),
                'images'            => $this->adaptImages($offer['photos']),
                'contact'           => $offer['contact']['name'],
                'created_time'      => $this->formatDateString($offer['created_time']),
                'last_refresh_time' => $this->formatDateString($offer['last_refresh_time']),
            ];
        }, $response['data']);
    }

    /**
     * @param array $parameters
     * @return array
     */
    private function adaptParameters(array $parameters): array
    {
        // Delete the extra parameters
        $parameters = array_filter($parameters, fn ($parameter) => in_array($parameter['key'],$this->parameters));

        return array_map(function ($parameter) {
            return [
                'key'   => $parameter['key'],
                'name'  => $parameter['name'],
                'value' => $parameter['type'] !== 'checkbox' ? $parameter['value']['label'] : $parameter['value']['key'],
            ];
        }, $parameters);
    }

    /**
     * @param array $images
     * @return array
     */
    private function adaptImages(array $images): array
    {
        return array_map(function ($image) {
            return [
                'type'  => 'photo',
                'media' =>  str_replace(['{height}', '{width}'], [self::BASE_IMAGE_HEIGHT, self::BASE_IMAGE_WIDTH], $image['link']),
            ];
        }, $images);
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
     * @return mixed
     */
    private function getParameterValueByKey(array $offer, string $key): string
    {
        $paramIndex = array_search($key, array_column($offer['params'], 'key'));
        return $paramIndex !== false ? $offer['params'][$paramIndex]['value']['label'] : '-';
    }
}
