<?php

namespace Drupal\list_cars_by_marque\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for listing cars by marque.
 */
class CarsByMarqueController extends ControllerBase {

  /**
   * List cars by marque.
   */
  public function listCars(Request $request) {
    $content = json_decode($request->getContent(), TRUE);
    $marque_id = $content['marque_id'] ?? '';

    if (!$marque_id) {
      return new JsonResponse(['error' => 'Marque ID is required'], 400);
    }

    $cars = $this->getCarsByMarque($marque_id);

    return new JsonResponse($cars);
  }

  /**
   * Helper function to get cars by marque taxonomy term.
   */
  protected function getCarsByMarque($marque_id) {
    $nids = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->accessCheck(TRUE) 
      ->condition('type', 'cars')
      ->condition('field_marque', $marque_id)
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
