<?php
/**
 * @file
 * Contains \Drupal\pdfpreview\Plugin\Field\FieldFormatter\PDFPreviewFormatter.
 */

namespace Drupal\pdfpreview\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;


/**
 * Plugin implementation of the 'pdfpreview' formatter.
 *
 * @FieldFormatter(
 *   id = "pdfpreview",
 *   label = @Translation("PDF Preview"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class PDFPreviewFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $config = \Drupal::config('pdfpreview.settings');
    return array(
      'show_description' => $config->get('show_description'),
      'tag' => $config->get('tag'),
      'fallback_formatter' => $config->get('fallback_formatter'),
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['show_description'] = array(
      '#type' => 'checkbox',
      '#title' => t('Description'),
      '#description' => t('Show file description beside image'),
      '#options' => array(0 => t('No'), 1 => t('Yes')),
      '#default_value' => $this->getSetting('show_description'),
    );
    $form['tag'] = array(
      '#type' => 'radios',
      '#title' => t('HTML tag'),
      '#description' => t('Select which kind of HTML element will be used to theme elements'),
      '#options' => array('span' => 'span', 'div' => 'div'),
      '#default_value' => $this->getSetting('tag'),
    );
    $form['fallback_formatter'] = array(
      '#type' => 'checkbox',
      '#title' => t('Fallback to default file formatter'),
      '#description' => t('When enabled, non-PDF files will be formatted using a default file formatter.'),
      '#default_value' => (boolean) $this->getSetting('fallback_formatter'),
      '#return_value' => \Drupal::config('pdfpreview.settings')->get('fallback_formatter'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = t('Separator tag: @tag', array(
      '@tag' => $this->getSetting('tag'),
    ));
    $summary[] = t('Descriptions: @visibility', array(
      '@visibility' => $this->getSetting('show_description') ? t('Visible') : t('Hidden'),
    ));
    if ($this->getSetting('fallback_formatter')) {
      $summary[] = t('Using the default file formatter for non-PDF files');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    $generator = \Drupal::service('pdfpreview.generator');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;
      $preview_uri = $generator->getPDFPreview($file);
      $preview = \Drupal::service('image.factory')->get($preview_uri);
      $elements[$delta] = array(
        '#theme' => 'image',
        '#uri' => $preview_uri,
      );
//      $elements[$delta]['#theme'] = 'image_style';
//      $elements[$delta]['#style_name'] = 'large';
      if ($preview->isValid()) {
        $elements[$delta]['#width'] = $preview->getWidth();
        $elements[$delta]['#height'] = $preview->getHeight();
      }

      // Pass field item attributes to the theme function.
      if (isset($item->_attributes)) {
        $elements[$delta] += array('#attributes' => array());
        $elements[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    return $elements;
  }

}
