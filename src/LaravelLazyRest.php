<?php


namespace robertogallea\LaravelLazyRest;


use GuzzleHttp\Client;
use Illuminate\Support\LazyCollection;

class LaravelLazyRest
{
    protected $client;
    protected $options;

    /**
     * LaravelLazyRest constructor.
     * @param Client|null $client
     */
    public function __construct(Client $client = null)
    {
        $this->client = $client;
    }

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

            if (!$this->client) {
                $this->client = new Client([
                    'base_uri' => $baseUri,
                    'timeout' => config('lazy_rest.timeout'),
                ]);
            }

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
        $response = $this->client->request('GET', $nextPage, $options);

        $data = json_decode($response->getBody());

        $nextPage = $data->{config('lazy_rest.fields.next_page_url')} ?? null;

        if (config('lazy_rest.fields.data') == '_') {
            return array($data, $nextPage);
        }

        return array($data->{config('lazy_rest.fields.data')}, $nextPage);
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