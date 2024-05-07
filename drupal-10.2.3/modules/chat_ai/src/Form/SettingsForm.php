<?php

namespace Drupal\chat_ai\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\chat_ai\Http\OpenAiClientFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Chat AI embeddings settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  private const DEFAULT_MODEL = 'gpt-3.5-turbo';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'chat_ai_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['chat_ai.settings'];
  }

  /**
   * The open_ai.client service.
   *
   * @var \OpenAI\Client
   */
  protected $client;

  /**
   * Constructs new FieldBlockDeriver.
   *
   * @param \Drupal\chat_ai\Http\OpenAiClientFactory $open_ai_factory
   *   The open_ai.client_factory service.
   */
  public function __construct(
    OpenAiClientFactory $open_ai_factory,
    ) {
    $this->client = $open_ai_factory->create();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('open_ai.client_factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form["container"] = [
      '#title' => $this->t('Chat settings'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['container']['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Select chat model:'),
      '#options' => $this->getGptModels(),
      '#default_value' => $this->config('chat_ai.settings')->get('model') ?? self::DEFAULT_MODEL,
      '#required' => TRUE,
    ];

    $form['container']['info'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Information about the models can be found on <a href="@url" target="_blank">OpenAI website</a>', [
        '@url' => 'https://platform.openai.com/docs/models',
      ]),
    ];

    return parent::buildForm($form, $form_state);
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
    $this->config('chat_ai.settings')
      ->set('model', $form_state->getValue('model'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Retrieves a list of GPT models available from the OpenAI API.
   *
   * @return array
   *   An array of GPT model IDs, keyed by the ID.
   */
  private function getGptModels() {
    $models = $this->client->models()->list();
    $options = [];
    foreach ($models['data'] as $model) {
      $id = $model['id'];
      if (str_starts_with($id, 'gpt-')) {
        $options[$id] = $id;
      }
    }
    return $options;
  }

}
