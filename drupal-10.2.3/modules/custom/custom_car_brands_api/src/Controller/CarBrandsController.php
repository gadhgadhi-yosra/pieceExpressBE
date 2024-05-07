<?php

namespace Drupal\custom_car_brands_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Url;

/**
 * Controller for the Car Brands API.
 */
class CarBrandsController extends ControllerBase {
  
  /**
   * Handles the POST request to list car brands.
   */
  public function handlePost(Request $request) {
    // Load the vocabulary.
    $vid = 'marque'; // Replace with your vocabulary ID.
    $vocabulary = Vocabulary::load($vid);
    if (!$vocabulary) {
      return new JsonResponse(['error' => 'Vocabulary not found'], 404);
    }

    // Load the terms.
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree($vid);

    $brands = [];
    foreach ($terms as $term) {
      $term_entity = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->load($term->tid);

      // Fetch the image field for the term if it exists.
      $image_url = '';
      if ($term_entity->hasField('field_icons') && !$term_entity->get('field_icons')->isEmpty()) {
        $image_file = $term_entity->get('field_icons')->entity;
        $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($image_file->getFileUri());
      }

      $brands[] = [
        'name' => $term_entity->getName(),
        'id' => $term_entity->id(),
        'image_url' => $image_url, // This is the URL of the image.
      ];
    }

    return new JsonResponse($brands);
  }

}
