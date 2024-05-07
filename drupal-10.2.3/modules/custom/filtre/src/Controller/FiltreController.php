<?php

namespace Drupal\filtre\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class FiltreController extends ControllerBase {

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  public function listerFiltresPR() {
    $request = \Drupal::request();
    $content = json_decode($request->getContent(), true);
    $car_id = $content['car_id'] ?? '';
    $category = $content['category'] ?? '';

    $list_pieces = [];

    $node_storage = $this->entityTypeManager->getStorage('node');
    // Uncomment the line below for debugging node storage
    // dump($node_storage);

    if($category) {
      $nids = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->accessCheck(true) 
      ->condition('type', 'pieces_de_rechange')
      ->condition('field_categorie', $category)
      ->condition('field_car', $car_id)
      ->execute();
    }else {
      $nids = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->accessCheck(true) 
      ->condition('type', 'pieces_de_rechange')
      ->condition('field_car', $car_id)
      ->execute();
    }
    
    
    if (!empty($nids)) {
      $nodes = $node_storage->loadMultiple($nids);

      foreach ($nodes as $node) {
        // Assuming 'field_categorie' is a taxonomy term reference, get the term name
        $term = $node->field_categorie->entity;
        $categorie_name = $term ? $term->getName() : '';

        $piece = [
          'title' => $node->label(), // The node title
          
        ];

        $list_pieces[] = $piece;
      }
    }

    return new Response(json_encode($list_pieces));
  }
}
