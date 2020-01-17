<?php


namespace robertogallea\LaravelLazyRest\Tests\Unit;


use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\LazyCollection;
use Orchestra\Testbench\TestCase;
use robertogallea\LaravelLazyRest\Facades\LazyRestFacade;
use robertogallea\LaravelLazyRest\LaravelLazyRest;

class LaravelLazyRestTest extends TestCase
{
    protected $client;

    /**
     * @test
     * @dataProvider mockHandlers
     */
    public function it_loads_resources_from_rest_endpoints($mockHandler, $dataField, $count)
    {
        config()->set('lazy_rest.fields.data', $dataField);

        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler]);

        $lazyRest = new LaravelLazyRest($client);

        $collection = $lazyRest->load('http://test-endpoint.it/api/bla');

        $this->assertInstanceOf(LazyCollection::class, $collection);
        $this->assertCount($count, $collection->all());
    }

    /** @test */
    public function it_can_use_facade()
    {
        $handler = HandlerStack::create($this->getSinglePageMockHandler());
        $client = new Client(['handler' => $handler]);

        $lazyRest = new LaravelLazyRest($client);

        $this->instance('lazyrest', $lazyRest);

        $collection = \LazyRest::load('http://test-endpoint.it/api/bla');

        $this->assertInstanceOf(LazyCollection::class, $collection);
        $this->assertEquals(3, $collection->count());
    }

    public function mockHandlers()
    {
        return [
            [$this->getSinglePageMockHandler(), 'data', 3],
            [$this->getMultiPageMockHandler(), 'data', 6],
            [$this->getRootDataMockHandler(), '_', 2]
        ];
    }

    private function getSinglePageMockHandler()
    {
        return new MockHandler([
            new Response(200, [], json_encode([
                'next_page_url' => null,
                'data' => [
                    ['id' => 1, 'text' => 'abc'],
                    ['id' => 2, 'text' => 'def'],
                    ['id' => 3, 'text' => 'ghi'],
                ]
            ]))
        ]);
    }

    private function getMultiPageMockHandler()
    {
        return new MockHandler([
            new Response(200, [], json_encode([
                'next_page_url' => 'some_url',
                'data' => [
                    ['id' => 1, 'text' => 'abc'],
                    ['id' => 2, 'text' => 'def'],
                    ['id' => 3, 'text' => 'ghi'],
                ]
            ])),
            new Response(200, [], json_encode([
                'next_page_url' => null,
                'data' => [
                    ['id' => 4, 'text' => 'jkl'],
                    ['id' => 5, 'text' => 'mno'],
                    ['id' => 6, 'text' => 'pqr'],
                ]
            ]))
        ]);
    }

    protected function getRootDataMockHandler()
    {
        return new MockHandler([
            new Response(200, [], json_encode([
                ['a' => 1],
                ['a' => 2]
            ]))
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            'robertogallea\LaravelLazyRest\LazyRestServiceProvider'
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'LazyRest' => LazyRestFacade::class
        ];
    }
}