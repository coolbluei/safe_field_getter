<?php

namespace Drupal\safe_field_getter;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\Core\TypedData\ListInterface;
use Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemInterface;

/**
 * A class containing functions to make it easy to safely get data from fields.
 */
class SafeFieldGetter {

  /**
   * Get all field values for a field.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to get a field from.
   * @param string $field_name
   *   The name of the field we should get.
   * @param mixed|null $default
   *   The default value for the field.
   *
   * @return mixed|null
   *   The value of the field, or the default value if one does not exist. Note
   *   the default can be NULL.
   */
  public static function all(FieldableEntityInterface $entity, $field_name, $default = NULL) {
    $answer = $default;

    try {
      $answer = $entity->get($field_name)->getValue();
    }
    // If we couldn't find a field with the name we wanted (i.e.: from get()),
    // don't change our answer.
    catch (\InvalidArgumentException $e) {
      // No-op.
    }

    return $answer;
  }

  /**
   * Get all referenced entities in an entity reference field.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to get a field from.
   * @param string $field_name
   *   The name of the field we should get.
   * @param mixed|null $default
   *   The default value for the field.
   *
   * @return mixed|\Drupal\Core\Entity\EntityInterface[]|null
   *   An array of entity objects keyed by field item deltas, or the default
   *   value if the field does not exist. Note the default can be NULL.
   */
  public static function allReferences(FieldableEntityInterface $entity, $field_name, $default = NULL) {
    $answer = $default;

    try {
      /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $field */
      $field = $entity->get($field_name);
      $answer = $field->referencedEntities();
    }
    // If we couldn't find a field with the name we wanted (i.e.: from get()),
    // don't change our answer.
    catch (\InvalidArgumentException $e) {
      // No-op.
    }

    return $answer;
  }

  /**
   * Try to get the data from a boolean field in an entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to get the data from.
   * @param string $field_name
   *   The name of the field to get data from.
   * @param bool|null $default
   *   The default value for the field.
   *
   * @return bool|null
   *   The data from the field. If it could not be found, then this will be set
   *   to $default.
   */
  public static function firstBoolean(FieldableEntityInterface $entity, $field_name, $default = NULL) {
    return (bool) self::firstSimple($entity, $field_name, $default);
  }

  /**
   * Try to get the data from an integer field in an entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to get the data from.
   * @param string $field_name
   *   The name of the field to get data from.
   * @param bool|null $default
   *   The default value for the field.
   *
   * @return int|null
   *   The data from the field. If it could not be found, then this will be set
   *   to $default.
   */
  public static function firstInteger(FieldableEntityInterface $entity, $field_name, $default = NULL) {
    return (int) self::firstSimple($entity, $field_name, $default);
  }

  /**
   * Get the first field value for a datetime field; fallback to default on err.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to get a field from.
   * @param string $field_name
   *   The name of the field we should get.
   * @param mixed|null $default
   *   The default value for the field.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The first date in the field, or the default if one does not exist. Note
   *   the default can be NULL.
   */
  public static function firstDateTime(FieldableEntityInterface $entity, $field_name, $default = NULL) {
    $answer = $default;

    try {
      /** @var \Drupal\Core\Field\FieldItemInterface $first_field */
      $first_field = $entity->get($field_name)->first();
      if (is_object($first_field)) {
        if (property_exists($first_field, 'date')
          || array_key_exists('date', $first_field->getProperties(TRUE))) {
          $answer = $first_field->date;
        }
      }
    }
    // If we couldn't find a field with the name we wanted (i.e.: from get()),
    // don't change our answer.
    catch (\InvalidArgumentException $e) {
      // No-op.
    }
    // If we couldn't find any data in the field (i.e.: from first()), don't
    // change our answer.
    catch (MissingDataException $e) {
      // No-op.
    }

    return $answer;
  }

  /**
   * Get the first field value for an entity reference field.
   *
   * Fall back to $default if there are any errors.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to get a field from.
   * @param string $field_name
   *   The name of the field we should get.
   * @param mixed|null $default
   *   The default value for the field.
   *
   * @return mixed|null
   *   The value in the field, or the default if one does not exist. Note the
   *   default can be NULL.
   */
  public static function firstReference(FieldableEntityInterface $entity, $field_name, $default = NULL) {
    $answer = $default;

    try {
      /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $field */
      $field = $entity->get($field_name);
      $field_value = $field->referencedEntities();
      if (count($field_value) > 0) {
        $answer = reset($field_value);
      }
    }
    // If we couldn't find a field with the name we wanted (i.e.: from get()),
    // don't change our answer.
    catch (\InvalidArgumentException $e) {
      // No-op.
    }
    // If we couldn't find any data in the field (i.e.: from first()), don't
    // change our answer.
    catch (MissingDataException $e) {
      // No-op.
    }

    return $answer;
  }

  /**
   * Get the first field value for a plugin reference field.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to get a field from.
   * @param string $field_name
   *   The name of the field we should get.
   * @param mixed|null $default
   *   The default value for the field.
   *
   * @return mixed|null
   *   The value in the field, or the default if one does not exist. Note the
   *   default can be NULL.
   */
  public static function firstPluginReference(FieldableEntityInterface $entity, $field_name, $default = NULL) {
    $answer = $default;

    try {
      /** @var \Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItem $field */
      $field = $entity->get($field_name);
      if ($field instanceof ListInterface) {
        $first_value = $field->get(0);
        if ($first_value instanceof PluginCollectionItemInterface) {
          $answer = $first_value->getContainedPluginInstance();
        }
      }
    }
    // If we couldn't find a field with the name we wanted (i.e.: from get()),
    // don't change our answer.
    catch (\InvalidArgumentException $e) {
      // No-op.
    }
    // If we couldn't find any data in the field (i.e.: from get(0)), don't
    // change our answer.
    catch (MissingDataException $e) {
      // No-op.
    }

    return $answer;
  }

  /**
   * Get the first field value for a simple field; fallback to default on error.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to get a field from.
   * @param string $field_name
   *   The name of the field we should get.
   * @param mixed|null $default
   *   The default value for the field.
   *
   * @return mixed|null
   *   The value in the field, or the default if one does not exist. Note the
   *   default can be NULL. In most cases, this will be a bool, int, string, or
   *   float.
   */
  public static function firstSimple(FieldableEntityInterface $entity, $field_name, $default = NULL) {
    $answer = $default;

    try {
      $first_field = $entity->get($field_name)->first();
      if (!is_null($first_field)) {
        $field_value = $first_field->getValue();
        if (isset($field_value['value'])) {
          $answer = $field_value['value'];
        }
      }
    }
    // If we couldn't find a field with the name we wanted (i.e.: from get()),
    // don't change our answer.
    catch (\InvalidArgumentException $e) {
      // No-op.
    }
    // If we couldn't find any data in the field (i.e.: from first()), don't
    // change our answer.
    catch (MissingDataException $e) {
      // No-op.
    }

    return $answer;
  }

  /**
   * Get first field value for a complex field; fallback to default on error.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to get a field from.
   * @param string $field_name
   *   The name of the field we should get.
   * @param mixed|null $default
   *   The default value for the field.
   *
   * @return array|mixed|null
   *   The value in the field, or the default if one does not exist. Note the
   *   default can be NULL. In most cases, this will be an associative array
   *   with a data structure that varies based on the type of field.
   */
  public static function firstComplex(FieldableEntityInterface $entity, $field_name, $default = NULL) {
    $answer = $default;

    try {
      $first_field = $entity->get($field_name)->first();
      if (!is_null($first_field)) {
        $answer = $first_field->getValue();
      }
    }
    // If we couldn't find a field with the name we wanted (i.e.: from get()),
    // don't change our answer.
    catch (\InvalidArgumentException $e) {
      // No-op.
    }
    // If we couldn't find any data in the field (i.e.: from first()), don't
    // change our answer.
    catch (MissingDataException $e) {
      // No-op.
    }

    return $answer;
  }

}
