<?php
declare(strict_types=1);

namespace Drupal\card_shuffle\Plugin\rest\resource\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient;

class CardShuffleResourceTest extends TestCase {

    private HttpClientInterface $httpClient;

    private string $endPointBase = 'http://localhost';

    private string $url;
    private $auth_basic;
    private array $headers;

    public function setup(): void {
        $this->httpClient = HttpClient::create();

        $host = getenv('DRUPAL_HOST') ?: $_ENV['DRUPAL_HOST'];
        if (!$host) {
            throw new \InvalidArgumentException("environment variable DRUPAL_HOST is not set");
        }

        $username = getenv('DRUPAL_USERNAME') ?: $_ENV['DRUPAL_USERNAME'];
        if (!$username) {
            throw new \InvalidArgumentException("environment variable DRUPAL_USERNAME is not set");
        }

        $password = getenv('DRUPAL_PASSWORD') ?: $_ENV['DRUPAL_PASSWORD'];
        if (!$password) {
            throw new \InvalidArgumentException("environment variable DRUPAL_PASSWORD is not set");
        }

        $this->headers = [
            'Host' => $host,
            'Accept' => 'application/json',
        ];

        $this->auth_basic = [$username, $password];

        $this->url = sprintf("%s/code-challenge/card-grid", $this->endPointBase);
    }

    public function testBasicCardShuffle(): void {
        $rows = 2;
        $columns = 3;
        $cardCount = $rows * $columns;

        $response = $this->httpClient->request('GET', $this->url, [
            'auth_basic' => $this->auth_basic,
            'headers' => $this->headers,
            'query' => [
                'rows' => $rows,
                'columns' => $columns,
            ]
        ]);

        $r = $response->toArray();

        $meta = $r['meta'];
        $cards = $r['data']['cards'];

        $this->assertTrue($meta['success']);
        $this->assertSame($cardCount, $meta['cardCount']);
        $this->assertIsInt($meta['uniqueCardCount']);
        $this->assertGreaterThan(($columns - 1), $meta['uniqueCardCount']);
        $this->assertLessThan(($cardCount + 1), $meta['uniqueCardCount']);
        $this->assertIsArray($meta['uniqueCards']);

        $this->assertIsArray($cards);
        $this->assertSame($rows, count($cards));
        $this->assertSame($columns, count($cards[0]));
        $this->assertSame($columns, count($cards[1]));
    }

    public function testCardShuffleWithInvalidInputs(): void {
        $inputs = [
            [0, 1, "rows value is not valid or is missing"],
            [7, 1, "rows value is not valid or is missing"],
            ['', 1, "rows value is not valid or is missing"],

            [1, 0, "columns value is not valid or is missing"],
            [1, 7, "columns value is not valid or is missing"],
            [1, '', "columns value is not valid or is missing"],

            [1, 1, "at least one of rows or columns value must be EVEN"],
            [3, 3, "at least one of rows or columns value must be EVEN"],
        ];

        foreach ($inputs as $input) {
            $rows = $input[0];
            $columns = $input[1];
            $message = $input[2];

            $response = $this->httpClient->request('GET', $this->url, [
                'auth_basic' => $this->auth_basic,
                'headers' => $this->headers,
                'query' => [
                    'rows' => $rows,
                    'columns' => $columns,
                ]
            ]);

            $r = $response->toArray();

            $meta = $r['meta'];

            $this->assertFalse($meta['success']);
            $this->assertStringStartsWith($message, $meta['message']);
            $this->assertIsArray($r['data']);
            $this->assertEmpty($r['data']);
        }
    }
}
