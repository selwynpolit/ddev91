<?php

declare(strict_types = 1);

namespace Drupal\groupmedia_paragraphs\Plugin\MediaFinder;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\groupmedia\Plugin\MediaFinder\MediaFinderBase;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Plugin for searching media in media paragraphs entity reference fields.
 *
 * @MediaFinder(
 *   id = "paragraphs_media_reference",
 *   label = @Translation("Media paragraphs in entity reference field"),
 *   description = @Translation("Tracks relationships created with media entity reference fields in paragraphs."),
 *   field_types = {"entity_reference_revisions"},
 * )
 */
class ParagraphsMediaReference extends MediaFinderBase {

  /**
   * {@inheritdoc}
   */
  public function process(EntityInterface $entity): array {
    $paragraphs = $this->getParagraphs($entity);

    // Get media items from the paragraphs.
    $items = [];
    foreach ($paragraphs as $paragraph) {
      if ($paragraph instanceof ParagraphInterface) {
        // Loop through all fields on the entity.
        foreach ($paragraph->getFieldDefinitions() as $key => $field) {
          // Check if the field is an entity reference, referencing media
          // entities.
          if ($field->getType() === 'entity_reference'
            && $field->getSetting('target_type') === 'media'
            && !$paragraph->get($key)->isEmpty()) {
            foreach ($paragraph->get($key)->getIterator() as $item) {
              if ($item->entity) {
                $items[] = $item->entity;
              }
            }
          }
        }
      }
    }

    return $items;
  }

  /**
   * Get all paragraphs by entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The paragraphs.
   */
  protected function getParagraphs(EntityInterface $entity): array {
    if (!$entity instanceof ContentEntityInterface) {
      return [];
    }

    $paragraphs = empty($paragraphs) ? [] : $paragraphs;

    // Loop through all fields on the entity.
    foreach ($entity->getFieldDefinitions() as $key => $field) {
      // Check if the field is an entity reference revisions, referencing
      // paragraph entities.
      if (in_array($field->getType(), $this->getApplicableFieldTypes())
        && $field->getSetting('target_type') === 'paragraph'
        && !$entity->get($key)->isEmpty()) {
        foreach ($entity->get($key)->getIterator() as $item) {
          if ($paragraph = $item->entity) {
            $has_paragraph_ref = FALSE;
            foreach ($paragraph->getFieldDefinitions() as $field) {
              if ($field->getType() === 'entity_reference_revisions') {
                $has_paragraph_ref = TRUE;
                break;
              }
            }

            // Add the paragraph even if it is a parent paragraph because it
            // might have a media reference field.
            $paragraphs[] = $paragraph;

            // Extract and add every paragraph item of the nested paragraphs.
            if ($has_paragraph_ref) {
              array_push($paragraphs, ...$this->getParagraphs($paragraph));
            }
          }
        }
      }
    }

    return $paragraphs;
  }

}
