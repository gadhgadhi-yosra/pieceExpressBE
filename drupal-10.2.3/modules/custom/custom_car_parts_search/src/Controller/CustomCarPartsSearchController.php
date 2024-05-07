<?php

namespace Drupal\custom_car_parts_search\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Custom Car Parts Search module.
 */
class CustomCarPartsSearchController extends ControllerBase {

  /**
   * Handler for the car parts search API.
   */
  public function searchCarParts(Request $request) {
   
    $request = \Drupal::request();
    $data = json_decode($request->getContent(), TRUE);
    $car_id = $content['car_id'] ?? '';

  

    $keyword = $data['keyword'];

    // Load the entity type manager service.
    $entity_type_manager = \Drupal::entityTypeManager();

    // Load the entity storage for the "node" entity type.
    $node_storage = $entity_type_manager->getStorage('node');

    // Query nodes of type "car_part" that contain the keyword in their title.
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'pieces_de_rechange')
      ->accessCheck(TRUE)
      ->condition('title', '%' . $keyword . '%', 'LIKE')
      ->condition('field_car', $car_id);
    
      $nids = $query->execute();

    // Load the entities.
    $entities = $node_storage->loadMultiple($entity_ids);

    // Prepare the results array.
    $results = [];
    foreach ($entities as $entity) {
      $results[] = [
        'title' => $entity->label(),
        // Add more fields as needed.
      ];
    }

    return new JsonResponse($results);
  }

}
