<?php

namespace Drupal\event_subscriber_report\Controller;

use Drupal\Component\Annotation\Doctrine\DocParser;
use Drupal\Component\Annotation\Doctrine\SimpleAnnotationReader;
use Drupal\Component\DependencyInjection\Container;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Event Subscriber Report routes.
 */
class EventSubscriberReportController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * EventSubscriberReportController constructor.
   */
  public function __construct(ContainerAwareEventDispatcher $dispatcher,
                              Container $container) {
    $this->dispatcher = $dispatcher;
    $this->container = $container;
    $this->docBlockFactory = DocBlockFactory::createInstance();
  }

  /**
   * @inheritdoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('service_container'),
    );
  }
  /**
   * Builds the response.
   *
   * @throws \ReflectionException
   */
  public function build() {
    $listeners = $this->dispatcher->getListeners();
    $build = [];
    foreach ($listeners as $eventName => $implementations) {
      $event = [
        'title' => $eventName,
        'implementations' => [],
      ];

      foreach ($implementations as $listener) {
        $header = [
          $this->t('Service ID'),
          $this->t('Callback'),
          $this->t('Weight'),
          $this->t('PhpDocs'),
        ];
        $className = get_class($listener[0]);
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
          'callable' => $className . '::' . $method,
          'weight' => $this->dispatcher->getListenerPriority($eventName, $listener),
          'docs' => Markup::create('<p>' . $summary . '</p><p>' . $description . '</p>'),
        ];
      }

      $table = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $event['implementations'],
      ];

      $build[$eventName] = [
        '#type' => 'details',
        '#title' => $eventName,
        '#open' => TRUE,
        'table' => $table,
      ];
    }

    return $build;
  }

}
