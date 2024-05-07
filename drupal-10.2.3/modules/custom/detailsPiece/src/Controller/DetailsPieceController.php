<?php

namespace Drupal\detailsPiece\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Controller for listing cars by piece.
 */
class DetailsPieceController extends ControllerBase {

  /**
   * List cars by piece.
   */
  public function listdetailPiece(Request $request) {
    $content = json_decode($request->getContent(), TRUE);
    $piece_id = $content['piece_id'] ?? '';

    if (!$piece_id) {
      return new JsonResponse(['error' => 'Piece ID is required'], 400);
    }
    $nids = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'pieces_de_rechange')
        ->condition('nid', $piece_id)
      ->accessCheck(true)
      ->execute();
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
    $pieces = [];
    foreach ($nodes as $node) {
        $piece['title'] = $node->title->value;
        $piece['body'] = $node->body->value;
        $piece['Catégorie'] = $node->field_categorie->value;
        $piece['Compabilité'] = $node->field_compabilite->value;  
        $piece['Diamètre extérieur [mm]'] = $node->field_diametre_exterieur_mm->value;  
        $piece['Diamètre intérieur [mm]'] = $node->field_diametre_interieur_mm->value;  
        $piece['Gallerie'] = $node->field_photo->value;    
        $piece['Nom'] = $node->field_nom1->value;  
        $piece['Références'] = $node->field_references1->value; 
        $pieces[] = $piece;
    }
    return new JsonResponse($pieces);
  }

}
