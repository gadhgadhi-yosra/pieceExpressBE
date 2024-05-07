<?php

namespace Drupal\chat_ai\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\FocusFirstCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\chat_ai\Embeddings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Chat AI form.
 */
class ChatBlockForm extends FormBase {

  private static $messages = [];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'chat_ai_block';
  }

  /**
   * The embedding service.
   *
   * @var \Drupal\chat_ai\Embeddings
   */
  protected $embeddings;

  /**
   * Constructs new FieldBlockDeriver.
   *
   * @param \Drupal\chat_ai\Embeddings $embeddings
   *   The embeddings service.
   */
  public function __construct(
    Embeddings $embeddings
    ) {
    $this->embeddings = $embeddings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('chat_ai.embeddings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['chat_container'] = [
      '#type' => 'container',
      '#markup' => '',
      '#attributes' => [
        'class' => 'chat--area-container edit-chat-container',
      ],
    ];

    $form['message'] = [
      '#type' => 'textfield',
      '#placeholder' => $this->t('Message'),
      '#required' => TRUE,
    ];

    $form['send'] = [
      '#type' => 'button',
      '#value' => $this->t('Send'),
      '#attributes' => [
        'class' => ['chat-ai--send-button'],
      ],
      '#ajax' => [
        'callback' => '::myAjaxCallback',
        // Or TRUE to prevent re-focusing on the triggering element.
        'disable-refocus' => FALSE,
        'event' => 'click',
        'wrapper' => '.edit-chat-container',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('...'),
        ],
      ],
    ];

    $form['#attached']['library'][] = 'chat_ai/chat_ai';
    $form['#attached']['library'][] = 'core/drupal.ajax';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('The message has been sent.'));
    $form_state->setRedirect('<front>');
  }

  /**
   * Ajax callback function for the chat form.
   *
   * @param array $form
   *   The chat form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response object.
   */
  public function myAjaxCallback(array &$form, FormStateInterface $form_state) {

    // Debug
    // $message = "<p>{$form_state->getValue('message')}</p>";
    // $choices = "<p class='chat-gpt'>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took.</p>";
    // $response = new AjaxResponse();
    // $response->addCommand(new AppendCommand('.edit-chat-container', $message));
    // $response->addCommand(new AppendCommand('.edit-chat-container', $choices));
    // $response->addCommand(new InvokeCommand('#edit-message', 'val', ['']));
    // $response->addCommand(new FocusFirstCommand('#edit-message'));
    // return $response;
    // ....
    $message = "<p>{$form_state->getValue('message')}</p>";
    // @todo Add DI
    $chat = \Drupal::service('chat_ai.service');
    $history = $chat->chatHistoryRetrieve(strip_tags(trim($message)));
    if (is_array($history) && !empty($history)) {
      $choices = reset($history);
    }
    else {
      $context = \Drupal::service('chat_ai.supabase')->getMatchingChunks($message);
      $context = implode('\n', $context);
      // $choices = \Drupal::service('chat_ai.service')->completion($message, $context);
      $choices = \Drupal::service('chat_ai.service')->chat($message, $context);
      // @todo Split in separate answers
      $choices = implode('<br />', $choices);
      $choices = "<p class='chat-gpt'>{$choices}</p>";
      $chat->chatHistoryInsert(strip_tags(trim($message)), $choices);
    }

    $response = new AjaxResponse();
    $response->addCommand(new AppendCommand('#edit-chat-container', $message));
    $response->addCommand(new AppendCommand('#edit-chat-container', $choices));
    $response->addCommand(new InvokeCommand('#edit-message', 'val', ['']));
    $response->addCommand(new FocusFirstCommand('#edit-message'));
    return $response;
  }

}
