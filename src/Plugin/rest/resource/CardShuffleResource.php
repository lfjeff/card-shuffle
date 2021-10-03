<?php

namespace Drupal\card_shuffle\Plugin\rest\resource;

use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a Card Shuffle Resource
 *
 * @RestResource(
 *   id = "card_shuffle_resource",
 *   label = @Translation("Card Shuffle Resource"),
 *   uri_paths = {
 *      "canonical" = "/code-challenge/card-grid"
 *   }
 * )
 */
class CardShuffleResource extends ResourceBase {

    protected Request $request;

    /**
     * Responds to GET requests
     * @return \Drupal\rest\ResourceResponse
     */
    public function get() {

        // there should be a better way to get the Request object,
        // but Drupal makes it very obscure.
        $request = Request::createFromGlobals();

        $rows = $request->query->get('rows');
        $columns = $request->query->get('columns');

        $success = true;
        $cardCount = 4;

        $uniqueCards = ["D", "G"];
        $uniqueCardCount = count($uniqueCards);

        $cards = [
            ["G", "D"],
            ["D", "G"],
        ];

        $response = [
            'meta' => [
                'rows' => $rows,
                'columns' => $columns,
                'success' => $success,
                'cardCount' => $cardCount,
                'uniqueCardCount' => $uniqueCardCount,
            ],
            'data' => [
                'cards' => $cards,
            ]
        ];

        return new ModifiedResourceResponse($response);
    }
}
