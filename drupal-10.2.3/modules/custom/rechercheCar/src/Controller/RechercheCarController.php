<?php

namespace Drupal\rechercheCar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for handling car search.
 */
class RechercheCarController extends ControllerBase {

  /**
   * Handles car search.
   */
  public function search(Request $request) {
    $content = json_decode($request->getContent(), TRUE);
    $nom = $content['nom'] ?? '';

    if (!$nom) {
      return new JsonResponse(['error' => 'Nom is required'], 400);
    }

    $cars = $this->searchCars($nom);

    return new JsonResponse($cars);
  }

  /**
   * Helper function to get cars by marque taxonomy term.
   */
  protected function searchCars($nom) {
    $nids = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->accessCheck(TRUE) 
      ->condition('type', 'cars')
      ->condition('title', $nom, 'CONTAINS')
      ->execute();
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
    $cars = [];
    foreach ($nodes as $node) {
      $cars[] = [
        'car_id' => $node->id(),
        'car_name' => $node->getTitle(),
        'marque_name' => $marque_name
      ];
    }

    return $cars;
  }

}
