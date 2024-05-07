<?php

namespace Drupal\otp_service\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event Subscriber for OTP Service.
 */
class OTPRouteSubscriber implements EventSubscriberInterface {

  /**
   * OTPRouteSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The factory for private temporary storage.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PrivateTempStoreFactory $tempStoreFactory) {
    $this->configFactory = $config_factory;
    $this->tempStoreFactory = $tempStoreFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest'];
    return $events;
  }

  /**
   * Restrict access to URLs defined.
   */
  public function onRequest(RequestEvent $event) {
    $request = $event->getRequest();
    $node = $request->get('node');

    if ($node) {
      $config = $this->configFactory->get('otp_service.settings')->get('content_types') ? : [];
      $content_types = array_values(array_filter($config));
      // @todo Check if full page
      $node_type = $node->getType();
      if (in_array($node_type, $content_types)) {
        $node_id = $node->id();
        $tempstore = $this->tempStoreFactory->get('otp_service');
        $data = $tempstore->get('allowed_nids');
        if (!$data) {
          $data = [];
        }
        if (!in_array($node_id, $data)) {
          $response = new RedirectResponse('/otp/validation?node_id=' . $node_id);
          $event->setResponse($response);
        }
      }
    }
  }

}
