<?php

namespace Drupal\commerce_demo;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Path\AliasManagerInterface;

/**
 * Defines the content importer.
 *
 * @internal
 *   For internal usage by the Commerce Demo module.
 */
class ContentImporter {

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new ContentImporter object.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $aliasManager
   *   The path alias manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(AliasManagerInterface $aliasManager, EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $moduleHandler) {
    $this->aliasManager = $aliasManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Imports content.
   */
  public function importContent() {
    $store = $this->ensureStore();
  }

  /**
   * Ensures the existence of a store.
   *
   * @return \Drupal\commerce_store\StoreInterface
   *   The store.
   */
  protected function ensureStore() {
    $store_storage = $this->entityTypeManager->getStorage('commerce_store');
    $store = $store_storage->loadDefault();
    if (empty($store)) {
      $store = $store_storage->create([
        'type' => 'online',
        'name' => 'US Store',
        'mail' => 'admin@example.com',
        'default_currency' => 'USD',
        'address' => [
          'country_code' => 'US',
          'administrative_area' => 'SC',
          'locality' => 'Greenville',
          'postal_code' => '29616',
          'address_line1' => '12344 24th St',
        ],
        'billing_countries' => ['US'],
        'prices_include_tax' => FALSE,
      ]);
      $store->save();
      $store_storage->markAsDefault($store);
    }

    return $store;
  }

}
