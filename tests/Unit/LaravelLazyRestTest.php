<?php


namespace robertogallea\LaravelLazyRest\Tests\Unit;


use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Http;
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
    public function it_loads_resources_from_rest_endpoints($mockResponses, $dataField, $count)
    {
        config()->set('lazy_rest.fields.data', $dataField);

        $mockSequence = Http::sequence();

        foreach ($mockResponses as $mockResponse) {
            $responses[] = $mockSequence->push($mockResponse);
        }

        Http::fake([
            'http://test-endpoint.it/api/bla*' => $mockSequence
        ]);

        $lazyRest = new LaravelLazyRest();

        $collection = $lazyRest->load('http://test-endpoint.it/api/bla');

        $this->assertInstanceOf(LazyCollection::class, $collection);
        $this->assertCount($count, $collection->all());
    }

    /**
     * @test
     * @dataProvider mockHandlers
     */
    public function it_can_add_options($mockResponses, $dataField, $count)
    {
        config()->set('lazy_rest.fields.data', $dataField);

        $mockSequence = Http::sequence();

        foreach ($mockResponses as $mockResponse) {
            $responses[] = $mockSequence->push($mockResponse);
        }

        Http::fake([
            'http://test-endpoint.it/api/bla*' => $mockSequence
        ]);

        $lazyRest = new LaravelLazyRest();

        $collection = $lazyRest->load('http://test-endpoint.it/api/bla', [
            'headers' => [
                'User-Agent' => 'testing/1.0',
                'Accept'     => 'application/json',
            ],
            'query' => ['foo' => 'bar'],
        ]);

        $this->assertCount($count, $collection->all());

        Http::assertSent(function ($request) {
            $this->assertTrue($request->hasHeader('User-Agent', 'testing/1.0'));
            $this->assertTrue($request->hasHeader('Accept', 'application/json'));
            $this->assertEquals('http://test-endpoint.it/api/bla?foo=bar', $request->url());
            return true;
        });
    }
    
    public function mockHandlers()
    {
        return [
            [$this->getSinglePageMockHandler(), 'data', 3],
            [$this->getMultiPageMockHandler(), 'data', 6],
            [$this->getRootDataMockHandler(), '_', 2]
        ];
    }

    /** @test */
    public function it_can_use_facade()
    {
        Http::fake([
            'http://test-endpoint.it/api/bla*' => Http::sequence()
                ->push($this->getSinglePageMockHandler()[0])
        ]);

        $collection = \LazyRest::load('http://test-endpoint.it/api/bla');

        $this->assertInstanceOf(LazyCollection::class, $collection);
        $this->assertEquals(3, $collection->count());
    }

    private function getSinglePageMockHandler()
    {
        return [
            [
                'next_page_url' => null,
                'data' => [
                    ['id' => 1, 'text' => 'abc'],
                    ['id' => 2, 'text' => 'def'],
                    ['id' => 3, 'text' => 'ghi'],
                ]
            ]
        ];
    }

    private function getMultiPageMockHandler()
    {
        return [
            [
                'next_page_url' => 'http://test-endpoint.it/api/bla?page=2',
                'data' => [
                    ['id' => 1, 'text' => 'abc'],
                    ['id' => 2, 'text' => 'def'],
                    ['id' => 3, 'text' => 'ghi'],
                ]
            ],
            [
                'next_page_url' => null,
                'data' => [
                    ['id' => 4, 'text' => 'jkl'],
                    ['id' => 5, 'text' => 'mno'],
                    ['id' => 6, 'text' => 'pqr'],
                ]
            ]
        ];
    }

    protected function getRootDataMockHandler()
    {
        return [
            [
                ['a' => 1],
                ['a' => 2]
            ]
        ];
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