<?php

namespace Drupal\chat_ai;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Connection;
use Drupal\chat_ai\Http\OpenAiClientFactory;

/**
 * Chat AI service.
 */
class ChatAI {

  /**
   * The open_ai.client service.
   *
   * @var \OpenAI\Client
   */
  protected $client;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Supabase service.
   *
   * @var \Drupal\chat_ai\Supabase
   */
  protected $supabase;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a ChatAIService object.
   *
   * @param \Drupal\chat_ai\Http\OpenAiClientFactory $open_ai_factory
   *   The open_ai.client_factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\chat_ai\Supabase $supabase,
   *   The supabase service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    OpenAiClientFactory $open_ai_factory,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $account,
    LoggerChannelFactoryInterface $logger,
    Connection $database,
    Supabase $supabase,
    ConfigFactoryInterface $config_factory
    ) {
    $this->client = $open_ai_factory->create();
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $account;
    $this->logger = $logger;
    $this->database = $database;
    $this->supabase = $supabase;
    $this->configFactory = $config_factory;
  }

  /**
   * Undocumented function.
   *
   * @return array
   */
  public function listModels(): array {
    $response = $this->client->models()->list();
    return $response->toArray()['data'];
  }

  /**
   * Chat with the website visitors and answer their questions under the given context.
   *
   * @param string $question
   *   The question asked by the website visitor.
   * @param string $context
   *   The context of the chat.
   *
   * @return array
   *   An array of possible answers to the question.
   */
  public function chat(string $question, string $context): array {
    // /v1/chat/completions
    // gpt-4, gpt-4-0613, gpt-4-32k, gpt-4-32k-0613,
    // gpt-3.5-turbo, gpt-3.5-turbo-0613, gpt-3.5-turbo-16k, gpt-3.5-turbo-16k-0613
    $context = <<<EOD
    You are a website chat bot. If you are unsure just respond with "I don't know".
    Context:  """
    $context
    """
    Answer questions under the given context.
    EOD;
    $model = $this->configFactory->get('chat_ai.settings')->get('model');
    $response = $this->client->chat()->create([
      'model' => $model,
      'messages' => [
          [
            'role' => 'system',
            'content' => $context,
          ],
          [
            'role' => 'user',
            'content' => $question,
          ],
      ],
    ]);
    $choices = [];
    foreach ($response->choices as $result) {
      $choices[] = $result->message->content;
    }
    return $choices;
  }

  /**
   * Generate a completion for the given question under the given context.
   *
   * @param string $question
   *   The question to generate a completion for.
   * @param string $context
   *   The context of the question.
   *
   * @return array
   *   An array of possible completions for the question.
   */
  public function completion(string $question, string $context): array {
    // /v1/completions    text-davinci-003, text-davinci-002, text-curie-001, text-babbage-001, text-ada-001
    $prompt = <<<EOD
    You are a website chat bot. If you are unsure just respond with "I don't know"
    Context:  """
    $context
    """
    Question: """
    $question
    """
    Answer the question under the given context.
    EOD;
    $response = $this->client->completions()->create([
      'model' => 'text-davinci-003',
      'prompt' => $prompt,
      'max_tokens' => 240,
      'temperature' => 0,
    ]);
    $choices = [];
    foreach ($response->choices as $result) {
      $choices[] = $result->text;
    }
    return $choices;
  }

  /**
   * Inserts a chat history record into the 'chat_ai_history' table.
   *
   * @param string $query
   *   The user's query.
   * @param string $response
   *   The chatbot's response.
   *
   * @return int
   *   The number of rows affected by the insert operation.
   *
   * @throws \Exception
   */
  public function chatHistoryInsert(string $query, string $response) {
    $result = $this->database->insert('chat_ai_history')
      ->fields([
        'created' => \Drupal::time()->getRequestTime(),
        'uid' => $this->account->id(),
        'user_query' => $query,
        'chat_response' => $response,
      ])
      ->execute();
    return $result;
  }

  /**
   * Clears all chat history records from the 'chat_ai_history' table.
   *
   * @return int
   *   The number of rows affected by the delete operation.
   */
  public function chatHistoryClear() {
    return $this->database->delete('chat_ai_history')->execute();
  }

  /**
   * Retrieves the chatbot's response from the chat history for the given user query.
   *
   * @param string $prompt
   *   The user's query to retrieve the chatbot's response for.
   *
   * @return array
   *   The chatbot's response for the given user query, or NULL if not found.
   */
  public function chatHistoryRetrieve(string $prompt) {
    $query = $this->database->select('chat_ai_history', 'c');
    $query->addField('c', 'chat_response');
    $query->condition('c.user_query', '%' . trim($prompt) . '%', 'LIKE');
    $result = $query->execute()->fetchCol();
    return $result;
  }

}
