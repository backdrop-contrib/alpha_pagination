<?php

/**
 * A handler to provide a field that generates alpha pagination values.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_alpha_pagination_group extends \views_handler_field {

  /**
   * @var \AlphaPagination
   */
  private $alphaPagination;

  /**
   * {@inheritdoc}
   */
  public function construct() {
    $this->alphaPagination = new AlphaPagination($this);
    parent::construct();
  }

  /**
   * {@inheritdoc}
   */
  function option_definition() {
    $options = parent::option_definition();
    $options['label'] = '';
    $options['element_label_colon'] = FALSE;
    $options['exclude']['default'] = TRUE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    // Since this is an automated field, hide all form elements.
    foreach (element_children($form) as $child) {
      // Allow the custom admin label since that's purely UI related.
      if ($child === 'more' || $child === 'ui_name') {
        continue;
      }
      $form[$child]['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  function query() {
    // Intentionally left empty because this doesn't actually alter the query.
  }

  /**
   * {@inheritdoc}
   */
  function render($values) {
    $areas = $this->alphaPagination->getAreaHandlers();

    // Immediately return if there is no handler.
    if (!$areas) {
      return '';
    }

    $alpha_pagination = new AlphaPagination($areas[0]);
    $path = $alpha_pagination->getOption('paginate_link_path');
    $field_name = $alpha_pagination->getOption('paginate_view_field');

    if (!isset($this->view->field[$field_name])) {
      return '';
    }

    /** @var \views_handler_field $field */
    $field = $this->view->field[$field_name];

    // Render field if it hasn't already.
    if (empty($field->last_render_text)) {
      $field->render($values);
    }

    // Return just the first character of the value.
    $value = $alpha_pagination->getValue(!empty($field->last_render_text) ? backdrop_ucfirst(substr(strip_tags($field->last_render_text), 0, 1)) : '');
    $label = $alpha_pagination->getLabel($value);

    // Prepend an anchor if the link path starts with #. Using the "link"
    // element will cause an empty href attribute to be printed. This can cause
    // issues with certain JavaScript or CSS styling that targets if that
    // attribute is simply present, regardless if it's empty. To avoid that,
    // use the "head_tag" type so only the name attribute is printed.
    // @todo find a replacement. https://docs.backdropcms.org/change-records/theme_html_tag-becomes-theme_head_tag
    // Officially, head_tag is only supposed to be used for the header, but there
    // is no alternative at the moment if we keep the theme function.
    if ($value && $path && $path[0] === '#') {
      $name = token_replace(substr($path, 1), $alpha_pagination->getTokens($value));
      $label = [
        '#type' => 'head_tag',
        '#theme' => 'head_tag__alpha_pagination__anchor',
        '#tag' => 'a',
        '#value' => '',
        '#attributes' => ['name' => $name],
        '#suffix' => $label,
      ];
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function ui_name($short = FALSE) {
    return $this->alphaPagination->ui_name();
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    return $this->alphaPagination->validate();
  }

}
