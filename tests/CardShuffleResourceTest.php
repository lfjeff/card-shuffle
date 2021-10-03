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

        echo "r=".json_encode($r, JSON_PRETTY_PRINT)."\n";  // jjj

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


}
