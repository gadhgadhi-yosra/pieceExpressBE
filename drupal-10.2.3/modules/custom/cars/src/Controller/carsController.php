<?php

namespace Drupal\cars\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Response;

class carsController extends ControllerBase {
  public function listercars() {
    $nids = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->accessCheck(TRUE) 
      ->condition('type', 'cars')
      ->execute();

    $List_cars = array();
    foreach ($nids as $nid) {
      $node = \Drupal\node\Entity\Node::load($nid); 
      $car = array();
      $car['title'] = $node->title->value;
      $car['body'] = $node->body->value;
      $car['boite'] = $node->field_boite->value;
      $car['disponibilite'] = $node->field_disponibilite->value;  
      $car['Connectivité'] = $node->field_connectivite->value;
      $car['Energie'] = $node->field_energie->value;
      $car['Gallerie'] = $node->field_gallerie->value;
      $car['Marque'] = $node->field_marque->value;
      $car['Nombre de cylindres'] = $node->	field_nombre_de_cylindres->value;
      $car['Nombre de place'] = $node->field_nombre_de_place->value;
      $car['Puissance'] = $node->field_puissance->value;
      $car['Réferences'] = $node->field_references->value;
      
      array_push($List_cars,$car);
    } 
    
    $response = new Response(json_encode($List_cars));
    $response->headers->set('Content-Type', 'application/json');

    return $response;
  
  }

  public function listCarsByMarque() {
    $nids = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->accessCheck(TRUE) 
      ->condition('type', 'cars')
      ->execute();

    $List_cars = array();
    foreach ($nids as $nid) {
      $node = \Drupal\node\Entity\Node::load($nid); 
      $car = array();
      $car['title'] = $node->title->value;
      $car['body'] = $node->body->value;
      //$car['boite'] = $node->field_boite->value;
      $car['disponibilite'] = $node->field_disponibilite->value;  
      $car['Connectivité'] = $node->field_connectivite->value;
      $car['Energie'] = $node->field_energie->value;
      $car['Gallerie'] = $node->field_gallerie->value;
      $car['Marque'] = $node->field_marque->value;
      $car['Nombre de cylindres'] = $node->	field_nombre_de_cylindres->value;
      $car['Nombre de place'] = $node->field_nombre_de_place->value;
      $car['Puissance'] = $node->field_puissance->value;
      $car['Réferences'] = $node->field_references->value;
      
      array_push($List_cars,$car);
    } 
    
    $response = new Response(json_encode($List_cars));
    $response->headers->set('Content-Type', 'application/json');

    return $response;
  
  }
   

  
}
