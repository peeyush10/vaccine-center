<?php

namespace Drupal\vaccine_center_register\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\NodeInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a data for other center in current city.
 *
 * @Block(
 *   id = "vaccine_center_other_center_block",
 *   admin_label = @Translation("Vaccine Center Other Center Block"),
 *   category = @Translation("Vaccine Center Other Center")
 * )
 */
class VaccineCenterOtherCenterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The alias manager.
   *
   * @var \Drupal\path_alias\AliasManager
   */
  protected $aliasManager;

  /**
   * Entity type Manager Service.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Article Next Previous Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager
   *   The database service object.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager Service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $routeMatch, AliasManagerInterface $aliasManager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
    $this->aliasManager = $aliasManager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this object should use.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('path_alias.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Validate the route is node and article bundle.
   */
  protected function getNode() {
    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface) {
      return $node;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $data = [];
    $city = '';

    // Get the vaccination_centre node details.
    $node = $this->getNode();
    if (!empty($node) && $node->bundle() == 'vaccination_centre') {
      $currentPageNid = $node->id();
      $current_city = $node->get('field_location')->getValue();
      $current_city = $current_city[0]['target_id'];
      $query = $this->entityTypeManager->getStorage('node')->getQuery();
      $query->condition('type', 'vaccination_centre');
      $query->condition('field_location', $current_city);
      $query->condition('nid', $currentPageNid, '<>');
      $query->condition('status', '1');
      $query->sort('field_available_slots', 'DESC');
      $nid_ids = $query->execute();

      if ($nid_ids) {
        $items = $this->entityTypeManager->getStorage('node')->loadMultiple($nid_ids);
        foreach ($items as $item) {
          $data[$item->id()] = $item->getTitle();
        }
      }

      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($current_city);
      $city = $term->name->value;
    }

    $build = [
      '#theme' => 'block__vaccine_center_others',
      '#items' => $data,
      '#city' => $city,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
