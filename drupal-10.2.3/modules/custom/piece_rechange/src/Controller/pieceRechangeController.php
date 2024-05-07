<?php

namespace Drupal\pieceRechange\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;

class pieceRechangeController extends ControllerBase {
  public function listerpieceRechange() {
    $request = \Drupal::request();
    $content = json_decode($request->getContent(), TRUE);
    $car_id = $content['car_id'] ?? '';

    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->condition('type', 'pieces_de_rechange')
      ->condition('field_car', $car_id);

    $nids = $query->execute();

    $List_pieceRechange = array();
    foreach ($nids as $nid) {
      $node = Node::load($nid);
      $piece = array();
      $piece['title'] = $node->title->value;
      $piece['body'] = $node->body->value;
      $piece['Catégorie'] = $node->field_categorie->value;
      $piece['Compabilité'] = $node->field_compabilite->value;  
      $piece['Diamètre extérieur [mm]'] = $node->field_diametre_exterieur_mm->value;  
      $piece['Diamètre intérieur [mm]'] = $node->field_diametre_interieur_mm->value;  
      $piece['Gallerie'] = $node->field_photo->value;    
      $piece['Nom'] = $node->field_nom1->value;  
      $piece['Références'] = $node->field_references1->value;  
      array_push($List_pieceRechange, $piece);
    } 
    
    return new JsonResponse($List_pieceRechange);
  }
}
