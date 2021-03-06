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

    private array $uniqueCard = [];
    private string $errorMessage = '';

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

        if (!$this->isValidInput($rows, $columns)) {
            $response = [
                'meta' => [
                    'success' => false,
                    'message' => $this->errorMessage,
                ],
                'data' => [],
            ];

            return new ModifiedResourceResponse($response);
        }

        $rows = (int) $rows;
        $columns = (int) $columns;

        $cardCount = $rows * $columns;

        for ($i = 0; $i < $rows; $i++) {
            $cards[$i] = $this->createRandomRow($columns);
        }

        $uniqueCards = array_keys($this->uniqueCard);
        sort($uniqueCards);
        $uniqueCardCount = count($uniqueCards);

        $response = [
            'meta' => [
                'success' => true,
                'cardCount' => $cardCount,
                'uniqueCardCount' => $uniqueCardCount,
                'uniqueCards' => $uniqueCards,
            ],
            'data' => [
                'cards' => $cards,
            ]
        ];

        return new ModifiedResourceResponse($response);
    }

    /**
     * Returns true if inputs are OK
     */
    private function isValidInput($rows, $columns): bool {
        $min = 1;
        $max = 6;

        $options = [
            'options' => [
                'min_range' => $min,
                'max_range' => $max,
            ]
        ];

        $rows = filter_var($rows, FILTER_VALIDATE_INT, $options);
        if (false === $rows) {
            $this->errorMessage = "rows value is not valid or is missing, must be an integer from $min to $max";
            return false;
        }

        $columns = filter_var($columns, FILTER_VALIDATE_INT, $options);
        if (false === $columns) {
            $this->errorMessage = "columns value is not valid or is missing, must be an integer from $min to $max";
            return false;
        }

        if ($rows % 2 && $columns % 2) {
            $this->errorMessage = "at least one of rows or columns value must be EVEN, both cannot be ODD";
            return false;
        }

        return true;
    }

    private function createRandomRow(int $columns): array {
        $count = 0;
        $row = [];

        while ($count < $columns) {
            $randomLetter = chr(64 + rand(1, 18));
            if (!in_array($randomLetter, $row)) {
                $row[] = $randomLetter;
                $this->uniqueCard[$randomLetter] = true;
            }
            $count = count($row);
        }

        return $row;
    }
}
