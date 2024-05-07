<?php

namespace Drupal\listuser\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;

class ListUserController extends ControllerBase {

  public function liste() {
    $header = [
      ['data' => $this->t('Nom d’utilisateur'), 'field' => 'name'],
      ['data' => $this->t('Dernière connexion'), 'field' => 'access'],
    ];

    // Création de la requête pour récupérer les utilisateurs.
    $query = \Drupal::entityQuery('user')  // query tjebli liste utiliszteur mtaa drupal koul mn base de donneee 
      ->condition('status', 1) // Optionnel : filtrer par utilisateurs actifs.
      ->sort('access', 'DESC') // Trier par la dernière date de connexion.
      ->accessCheck(TRUE);
    $uids = $query->execute();

    $rows = [];
    foreach ($uids as $uid) {
      $user = User::load($uid);
      // Vérification pour s'assurer que la date de la dernière connexion est disponible.
      $last_access = $user->getLastAccessedTime() ? date('Y-m-d H:i:s', $user->getLastAccessedTime()) : $this->t('Jamais connecté');
      $rows[] = [
        'data' => [
          $user->getDisplayName(), // Modifié pour utiliser getDisplayName() au lieu de getUsername()
          $last_access,
        ],
      ];
    }

    // Construction de la table de sortie.
    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('Aucun utilisateur trouvé.'),
    ];
  }
}
