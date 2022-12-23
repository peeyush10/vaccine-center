<?php

namespace Drupal\vaccine_center_register\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\NodeInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides a data for other city.
 *
 * @Block(
 *   id = "vaccine_center_other_city_block",
 *   admin_label = @Translation("Vaccine Center Other City Block"),
 *   category = @Translation("Vaccine Center Other City")
 * )
 */
class VaccineCenterOtherCityBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

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
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $routeMatch, AliasManagerInterface $aliasManager, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
    $this->aliasManager = $aliasManager;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $currentUser;
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
      $container->get('entity_type.manager'),
      $container->get('current_user'),
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
    $current_city = '';
    $data = [];
    $data_sort = [];
    if ($this->currentUser->isAuthenticated()) {
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      $current_city = $user->get('field_location')->getValue();
      $current_city = $current_city[0]['target_id'];
    }

    $taxonomy_arr = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'vid' => 'location',
    ]);

    if ($current_city) {
      unset($taxonomy_arr[$current_city]);
    }

    foreach ($taxonomy_arr as $taxonomy_arr_val) {
      $query = $this->entityTypeManager->getStorage('node')->getQuery();
      $query->condition('type', 'vaccination_centre');
      $query->condition('field_location', $taxonomy_arr_val->id());
      $query->condition('status', '1');
      $nid_ids = $query->count()->execute();
      $data_sort[$taxonomy_arr_val->id()] = $nid_ids;
      $data[$taxonomy_arr_val->id()] = $taxonomy_arr_val->getName();
    }
    if ($data_sort) {
      arsort($data_sort);
    }

    foreach ($data_sort as $key => $data_sort_val) {
      $data_sort[$key] = $data[$key];
    }
    $build = [
      '#theme' => 'block__vaccine_center_others_city',
      '#items' => $data_sort,
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
