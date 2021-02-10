<?php

namespace Drupal\event_subscriber_report\Controller;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\currentRequestInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Event Subscriber Report routes.
 */
class EventSubscriberReportController extends ControllerBase implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * EventSubscriberReportController constructor.
   */
  public function __construct(ContainerAwareEventDispatcher $dispatcher,
                              RequestStack $requestStack) {
    $this->dispatcher = $dispatcher;
    $this->currentRequest = $requestStack->getCurrentRequest();
  }

  /**
   * @inheritdoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('request_stack')
    );
  }
  /**
   * Builds the response.
   *
   * @throws \ReflectionException
   */
  public function build() {
    $listeners = $this->dispatcher->getListeners();
    $moduleFilter = !empty($this->currentRequest->get('module')) ? $this->currentRequest->get('module') : FALSE;
    $eventFilter = !empty($this->currentRequest->get('event')) ? $this->currentRequest->get('event') : FALSE;
    $build = [];
    $build['filters'] = $this->formBuilder()
      ->getForm('Drupal\event_subscriber_report\Form\ReportFilter');
    foreach ($listeners as $eventName => $implementations) {
      // If we have event filter, and the name doesn't match, keep going.
      if ($eventFilter && strpos($eventName, $eventFilter) === FALSE) {
        continue;
      }
      $event = [
        'title' => $eventName,
        'implementations' => [],
      ];

      foreach ($implementations as $listener) {
        $className = get_class($listener[0]);
        if (preg_match('/Drupal\\\([a-zA-Z]*)/', $className, $output)) {
          $moduleName = $output[1];
        }

        // If we have module filter, and the name doesn't match, keep going.
        if ($moduleFilter && strpos($moduleName, $moduleFilter) === FALSE) {
          continue;
        }

        $header = [
          $this->t('Service ID'),
          $this->t('Module'),
          $this->t('Callback'),
          $this->t('Weight'),
          $this->t('PhpDocs'),
        ];

        $method = $listener[1];
        $ref = new \ReflectionMethod($className, $listener[1]);
        $comment = $ref->getDocComment();
        $summary = '';
        $description = '';
        if ($comment) {
          $factory = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
          $docblock = $factory->create($comment);
          $summary = $docblock->getSummary();
          $description = $docblock->getDescription()->render();
        }

        $event['implementations'][] = [
          'service_id' => $listener[0]->_serviceId,
          'module' => $moduleName,
          'callable' => $className . '::' . $method,
          'weight' => $this->dispatcher->getListenerPriority($eventName, $listener),
          'docs' => Markup::create('<p>' . $summary . '</p><p>' . $description . '</p>'),
        ];
      }

      $table = [];
      if (!empty($event['implementations'])) {
        $table = [
          '#type' => 'table',
          '#header' => $header,
          '#rows' => $event['implementations'],
        ];
      }

      $build[$eventName] = [
        '#type' => 'details',
        '#title' => $eventName,
        '#open' => empty($event['implementations']) ? FALSE : TRUE,
        'table' => $table,
      ];
    }

    return $build;
  }

}
