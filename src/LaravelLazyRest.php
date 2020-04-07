<?php


namespace robertogallea\LaravelLazyRest;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;

class LaravelLazyRest
{
    protected $options;

    /**
     * @param $endpoint
     * @param array $options
     * @return LazyCollection
     */
    public function load(string $endpoint, array $options = [])
    {
        return LazyCollection::make(function () use ($endpoint, $options) {
            $count = 0;

            $urlData = parse_url($endpoint);

            $baseUri = $urlData['scheme'] . '://' . $urlData['host'] . (isset($urlData['port']) ? ':' . $urlData['port'] : '') . ($urlData['path'] ?? '');

            $nextPage = $baseUri;

            while (!is_null($nextPage)) {
                list($data, $nextPage) = $this->getNextPage($nextPage, $options);

                $data = $this->offsetKeys($data, $count);

                $count+=sizeof($data);

                yield from $data;
            }
        });
    }

    private function getNextPage(string $nextPage, array $options): array
    {
        $response = Http::withOptions($options)->get($nextPage);

        $data = $response->json();

        $nextPage = $data[config('lazy_rest.fields.next_page_url')] ?? null;

        if (config('lazy_rest.fields.data') == '_') {
            return array($data, $nextPage);
        }

        return array($data[config('lazy_rest.fields.data')], $nextPage);
    }

    private function offsetKeys(array $data, int $count)
    {
        $newData = [];

        foreach ($data as $key => $value) {
            $newData[$key + $count] = $value;
        }

        return $newData;
    }
}