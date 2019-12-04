<?php


namespace robertogallea\LaravelLazyRest\Tests\unit;


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

    /** @test */
    public function it_loads_resource_from_single_page_rest_endpoints()
    {
        $handler = HandlerStack::create($this->getSinglePageMockHandler());
        $client = new Client(['handler' => $handler]);

        $lazyRest = new LaravelLazyRest($client);

        $collection = $lazyRest->load('http://test-endpoint.it/api/bla');

        $this->assertInstanceOf(LazyCollection::class, $collection);
        $this->assertEquals(3, $collection->count());
    }

    /** @test */
    public function it_loads_resource_from_multiple_page_rest_endpoints()
    {
        $handler = HandlerStack::create($this->getMultiPageMockHandler());
        $client = new Client(['handler' => $handler]);

        $lazyRest = new LaravelLazyRest($client);

        $collection = $lazyRest->load('http://test-endpoint.it');

        $this->assertEquals(6, $collection->count());
    }

    /** @test */
    public function it_offsets_elements_loaded_from_multiple_page_rest_endpoints()
    {
        /*
         * This test is required because each page returns always the same element keys, so proper offsets are required
         * in order not to overwrite previous page elements
         */

        $handler = HandlerStack::create($this->getMultiPageMockHandler());
        $client = new Client(['handler' => $handler]);

        $lazyRest = new LaravelLazyRest($client);

        $collection = $lazyRest->load('http://test-endpoint.it');

        $this->assertCount(6, $collection->all());
    }


    /** @test */
    public function it_loads_resource_with_root_data()
    {
        config()->set('lazy_rest.fields.data', '_');

        $handler = HandlerStack::create($this->getRootDataMockHandler());
        $client = new Client(['handler' => $handler]);

        $lazyRest = new LaravelLazyRest($client);

        $collection = $lazyRest->load('http://some-url');

        $this->assertCount(2, $collection->all());
    }

    /** @test */
    public function it_has_facade_access()
    {
        $handler = HandlerStack::create($this->getSinglePageMockHandler());
        $client = new Client(['handler' => $handler]);

        $lazyRest = new LaravelLazyRest($client);

        $this->instance('lazyrest', $lazyRest);

        $collection = \LazyRest::load('http://test-endpoint.it/api/bla');

        $this->assertInstanceOf(LazyCollection::class, $collection);
        $this->assertEquals(3, $collection->count());
    }

    private function getSinglePageMockHandler()
    {
        return new MockHandler([
            new Response(200, [], json_encode([
                'current_page' => 1,
                'last_page' => 1,
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
                'current_page' => 1,
                'last_page' => 2,
                'next_page_url' => 'some_url',
                'data' => [
                    ['id' => 1, 'text' => 'abc'],
                    ['id' => 2, 'text' => 'def'],
                    ['id' => 3, 'text' => 'ghi'],
                ]
            ])),
            new Response(200, [], json_encode([
                'current_page' => 2,
                'last_page' => 2,
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
                [
                    'a' => 1
                ],
                [
                    'a' => 2
                ]
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