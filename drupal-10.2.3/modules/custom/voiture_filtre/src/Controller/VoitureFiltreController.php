<?php

namespace Drupal\voiture_filtre\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class VoitureFiltreController extends ControllerBase {
  public function listerVoituresParMarque($marque) {
    /*$path = \Drupal::request()->getPathInfo();
    $arg = explode('/', $path);
    print_r($arg); */

    $list_voitures = [];

    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->condition('type', 'cars')
      ->condition('field_marque', $marque);
    
    $nids = $query->execute();

    foreach ($nids as $nid) {
      $node = Node::load($nid);
      $cars = [
        'title' => $node->getTitle(),
        'Gallerie' => $node->field_gallerie->value
      ];

      $list_voitures[] = $cars;
    }

    $response = new Response(json_encode($list_voitures));
    //$response->headers->set('Content-Type', 'application/json');

    return $response;
  }
}
