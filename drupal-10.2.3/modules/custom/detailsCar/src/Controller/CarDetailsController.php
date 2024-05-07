<?php

namespace Drupal\detailsCar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for car details.
 */
class CarDetailsController extends ControllerBase {

  /**
   * Returns a JSON response with car details including all images.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response with the car details.
   */
  public function getCarDetails(Request $request) {
  
    $content = json_decode($request->getContent(), TRUE);
    $car_id = $content['car_id'] ?? '';

    if (!$car_id) {
      return new JsonResponse(['error' => 'Piece ID is required'], 400);
    }
    $nids = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'cars')
        ->condition('nid', $car_id)
      ->accessCheck(true)
      ->execute();
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
    $cars = [];
    foreach ($nodes as $node) {
        $car = [
            'Nom' => $node->title->value,
            'Gallerie' => $node->field_gallerie[1]
        ];
        $cars[] = $car;
    }
    return new JsonResponse($cars);
  }

}
