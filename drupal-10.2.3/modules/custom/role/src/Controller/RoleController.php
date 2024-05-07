<?php

namespace Drupal\role\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Access\AccessResult;
use Drupal\role\Form\RoleFilterForm;
use Symfony\Component\HttpFoundation\Request;

class RoleController extends ControllerBase {
  public function users($role = NULL) {
    // Récupère la liste des utilisateurs et leur dernière date d’accès
    $query = \Drupal::database()->select('users_field_data', 'u');
    $query->fields('u', ['uid', 'name', 'access']);
    $query->leftJoin('user__roles', 'ur', 'ur.entity_id = u.uid');
    $query->fields('ur', ['roles_target_id']);
    $query->orderBy('access', 'DESC');

    // Ajoute une condition à la requête si un rôle est sélectionné
    if (!is_null($role) && $role !== 'all') {
      $query->condition('ur.roles_target_id', $role);
    }

    $result = $query->execute()->fetchAll();

    // Construit le tableau HTML pour afficher la liste des utilisateurs
    $header = ['ID', 'Nom Utilisateur', 'Rôles', 'Dernière date d\'accès'];
    $rows = [];
    foreach ($result as $row) {
      $user_roles = \Drupal::service('entity_type.manager')->getStorage('user')->load($row->uid)->getRoles();
      $rows[] = [$row->uid, $row->name, implode(', ', $user_roles), date('Y-m-d H:i:s', $row->access)];
    }
    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('Aucun utilisateur trouvé.'),
    ];

    // Ajoute le formulaire de filtre par rôle au-dessus du tableau HTML
    $filter_form = $this->formBuilder()->getForm(RoleFilterForm::class);
    $render_array = [
      'filter_form' => $filter_form,
      'table' => $table,
      'selected_role' => $role,
    ];

    return $render_array;
  }
}
