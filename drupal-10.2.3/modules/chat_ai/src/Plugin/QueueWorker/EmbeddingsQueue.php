<?php

namespace Drupal\chat_ai\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Defines 'embeddings_queue' queue worker.
 *
 * @QueueWorker(
 *   id = "embeddings_queue",
 *   title = @Translation("Embeddings Queue Worker"),
 *   cron = {"time" = 60}
 * )
 */
class EmbeddingsQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $entity = $data->entity;
    $embeddings = \Drupal::service('chat_ai.embeddings');
    $embeddings->createEmbedding($entity, 'default');
    \Drupal::logger('chat_ai')->info($entity->label() . ' indexed successfully');
  }

}
