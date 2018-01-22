<?php

namespace Drupal\commerce_demo;

use Drupal\commerce_product\Entity\ProductAttributeValueInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines the content exporter.
 *
 * @internal
 *   For internal usage by the Commerce Demo module.
 */
class ContentExporter {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ContentExporter object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Exports all entities of the given type, restricted by bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string[] $bundles
   *   The bundles.
   *
   * @return array
   *   The exported entities, keyed by UUID.
   */
  public function exportAll($entity_type_id, array $bundles) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if (!$entity_type->entityClassImplements(ContentEntityInterface::class)) {
      throw new \InvalidArgumentException(sprintf('The %s entity type is not a content entity type.', $entity_type_id));
    }

    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $query = $storage->getQuery();
    if ($bundle_key = $entity_type->getKey('bundle')) {
      $query->condition($bundle_key, $bundles, 'IN');
    }
    $ids = $query->execute();
    if (!$ids) {
      return [];
    }

    $export = [];
    $entities = $storage->loadMultiple($ids);
    foreach ($entities as $entity) {
      $export[$entity->uuid()] = $this->export($entity);
      // The array is keyed by UUID, no need to have it in the export too.
      unset($export[$entity->uuid()]['uuid']);
    }

    return $export;
  }

  /**
   * Exports the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The export array.
   */
  public function export(ContentEntityInterface $entity) {
    $id_key = $entity->getEntityType()->getKey('id');
    $skip_fields = [
      $id_key, 'langcode', 'default_langcode',
      'uid', 'created', 'changed',
    ];

    $export = [];
    foreach ($entity->getFields() as $field_name => $items) {
      if (in_array($field_name, $skip_fields)) {
        continue;
      }
      $items->filterEmptyItems();
      if ($items->isEmpty()) {
        continue;
      }

      $storage_definition = $items->getFieldDefinition()->getFieldStorageDefinition();;
      $list = $items->getValue();
      foreach ($list as $delta => $item) {
        // Remove calculated path values.
        if ($storage_definition->getType() == 'path') {
          $item = array_intersect_key($item, ['alias' => 'alias']);
        }
        // Simplify items with a single key (such as "value").
        $main_property_name = $storage_definition->getMainPropertyName();
        if ($main_property_name && isset($item[$main_property_name]) && count($item) === 1) {
          $item = $item[$main_property_name];
        }
        $list[$delta] = $item;
      }
      // Remove the wrapping array if the field is single-valued.
      if ($storage_definition->getCardinality() === 1) {
        $list = reset($list);
      }

      if (!empty($list)) {
        $export[$field_name] = $list;
      }
    }
    // Process by entity type ID.
    $entity_type_id = $entity->getEntityTypeId();
    if ($entity_type_id == 'commerce_product') {
      $export = $this->processProduct($export, $entity);
    }
    elseif ($entity_type_id == 'commerce_product_attribute_value') {
      $export = $this->processAttributeValue($export, $entity);
    }

    return $export;
  }

  /**
   * Process the exported product.
   *
   * @param array $export
   *   The export array.
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   *
   * @return array
   *   The processed export array.
   */
  protected function processProduct(array $export, ProductInterface $product) {
    // The imported products are always assigned to the default store.
    unset($export['stores']);
    return $export;
  }

  /**
   * Process the exported attribute value.
   *
   * @param array $export
   *   The export array.
   * @param \Drupal\commerce_product\Entity\ProductAttributeValueInterface $attribute_value
   *   The attribute value.
   *
   * @return array
   *   The processed export array.
   */
  protected function processAttributeValue(array $export, ProductAttributeValueInterface $attribute_value) {
    // Don't export the weight for now.
    unset($export['weight']);
    return $export;
  }

}
