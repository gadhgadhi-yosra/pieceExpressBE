<?php

namespace Drupal\custom_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class SearchController extends ControllerBase {
  public function search(Request $request) {
    $keywords = $request->query->get('keywords');
    $type = $request->query->get('type'); // 'cars' or 'pieces_de_rechange'

    if (!in_array($type, ['cars', 'pieces_de_rechange'])) {
      return new JsonResponse(['error' => 'Invalid type specified'], 400);
    }

    $nids = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', $type)
      ->condition('title', '%' . $keywords . '%', 'LIKE')
      ->execute();

    $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
    $results = [];
    foreach ($nodes as $node) {
      $result = [
        'title' => $node->get('title')->value,
        'description' => $node->get('body')->value,
        'marque' => $node->get('field_marque')->value,
        'année' => $node->get('field_annee')->value,
                // Ajoutez d'autres champs si nécessaire
      ];
      $results[] = $result;
    }

    return new JsonResponse($results);
  }
}
