<?php

/**
 * Views area handler to display alphabetic pagination.
 *
 * @ingroup views_area_handlers
 */
class views_handler_area_alpha_pagination extends \views_handler_area {

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
  public function label() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  function option_definition() {
    $options = parent::option_definition();

    // Global.
    $options['paginate_view_field'] = [
      'default' => 'title',
      'translatable' => TRUE,
    ];

    // Link.
    $options['paginate_link_path'] = [
      'default' => '[alpha_pagination:path]/[alpha_pagination:value]',
      'translatable' => FALSE,
    ];
    $options['paginate_link_external'] = [
      'default' => 0,
      'translatable' => FALSE,
    ];
    $options['paginate_link_class'] = [
      'default' => '',
      'translatable' => FALSE,
    ];
    $options['paginate_link_attributes'] = [
      'default' => '',
      'translatable' => FALSE,
    ];

    // Classes.
    $options['paginate_class'] = [
      'default' => 'alpha-pagination',
      'translatable' => FALSE,
    ];
    $options['paginate_list_class'] = [
      'default' => 'alpha-pagination-list',
      'translatable' => FALSE,
    ];
    $options['paginate_active_class'] = [
      'default' => 'active',
      'translatable' => FALSE,
    ];
    $options['paginate_inactive_class'] = [
      'default' => 'inactive',
      'translatable' => FALSE,
    ];

    // All.
    $options['paginate_all_display'] = [
      'default' => 1,
      'translatable' => FALSE,
    ];
    $options['paginate_all_class'] = [
      'default' => 'all',
      'translatable' => FALSE,
    ];
    $options['paginate_all_label'] = [
      'default' => t('All'),
      'translatable' => TRUE,
    ];
    $options['paginate_all_value'] = [
      'default' => 'all',
      'translatable' => FALSE,
    ];
    $options['paginate_all_position'] = [
      'default' => 'after',
      'translatable' => FALSE,
    ];
    $options['paginate_toggle_empty'] = [
      'default' => 1,
      'translatable' => FALSE,
    ];

    // Numeric.
    $options['paginate_view_numbers'] = [
      'default' => 0,
      'translatable' => FALSE,
    ];
    $options['paginate_numeric_class'] = [
      'default' => 'numeric',
      'translatable' => FALSE,
    ];
    $options['paginate_numeric_divider'] = [
      'default' => 1,
      'translatable' => FALSE,
    ];
    $options['paginate_numeric_divider_class'] = [
      'default' => 'numeric-divider',
      'translatable' => FALSE,
    ];
    $options['paginate_numeric_hide_empty'] = [
      'default' => 1,
      'translatable' => FALSE,
    ];
    $options['paginate_numeric_label'] = [
      'default' => '#',
      'translatable' => FALSE,
    ];
    $options['paginate_numeric_position'] = [
      'default' => 'before',
      'translatable' => FALSE,
    ];
    $options['paginate_numeric_value'] = [
      'default' => implode('+', $this->alphaPagination->getNumbers()),
      'translatable' => FALSE,
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  function options_submit(&$form, &$form_state) {
    // Need to clear cache when options have changed.
    cache_clear_all($this->alphaPagination->getCid(), 'cache', TRUE);

    // Filter attributes for any XSS vulnerabilities before saving.
    if (!empty($form_state['values']['options']['paginate_link_attributes'])) {
      $form_state['values']['options']['paginate_link_attributes'] = filter_xss_admin($form_state['values']['options']['paginate_link_attributes']);
    }
  }

  /**
   * {@inheritdoc}
   */
  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    // Hide unnecessary label.
    $form['label']['#access'] = FALSE;

    // Global options.
    if ($this->view->base_table == 'taxonomy_term_data') {
      // Get an array list of all non-image, non-entity or other assorted reference fields.
      $fields = ['name' => 'name'];
    }
    else {
      // Get an array list of all non-image, non-entity or other assorted reference fields.
      $fields = ['title' => 'title'];
    }

    $compound_field_types = ['name'];
    $single_field_types = ['text', 'text_long', 'text_with_summary'];
    $all_field_types = array_merge($single_field_types, $compound_field_types);
    foreach (field_info_field_map() as $field_name => $field_definition) {
      if (in_array($field_definition['type'], $all_field_types)) {
        if (in_array($field_definition['type'], $compound_field_types)) {
          $field_info = field_info_field($field_name);
          foreach (array_keys($field_info['columns']) as $compoundFieldKey) {
            $compound_field_field_name = sprintf('%s:%s', $field_name, $compoundFieldKey);
            $fields[$compound_field_field_name] = $compound_field_field_name;
          }
        }
        else {
          $fields[$field_name] = $field_name;
        }
      }
    }
    $form['paginate_view_field'] = [
      '#title' => t('View field to paginate against'),
      '#type' => 'select',
      '#options' => $fields,
      '#default_value' => $this->alphaPagination->getOption('paginate_view_field'),
      '#description' => t('This will be the content field that drives the pagination.'),
    ];

    $form['paginate_toggle_empty'] = [
      '#type' => 'checkbox',
      '#title' => t('Show options without results'),
      '#default_value' => $this->alphaPagination->getOption('paginate_toggle_empty'),
      '#description' => t('Show or hide letters without results'),
    ];

    // Link.
    $form['paginate_link'] = [
      '#type' => 'fieldset',
      '#title' => t('Link'),
      '#collapsible' => TRUE,
    ];

    $form['paginate_link_path'] = [
      '#title' => t('Path'),
      '#type' => 'textfield',
      '#size' => 60,
      '#default_value' => $this->alphaPagination->getOption('paginate_link_path'),
      '#description' => t('This is the path the link will be rendered with. No beginning or ending slashes.'),
      '#fieldset' => 'paginate_link',
    ];

    $form['paginate_link_external'] = [
      '#type' => 'checkbox',
      '#title' => t('External'),
      '#default_value' => $this->alphaPagination->getOption('paginate_link_external'),
      '#description' => t('Indicates whether this is an external link (not processed). If the above path starts with a hash symbol (#), then this option will automatically enable so it can render as a relative link to an anchor on the current page.'),
      '#fieldset' => 'paginate_link',
    ];

    $form['paginate_link_class'] = [
      '#title' => t('Classes'),
      '#type' => 'textfield',
      '#default_value' => $this->alphaPagination->getOption('paginate_link_class'),
      '#description' => t('CSS classes for the link, separated by spaces.'),
      '#fieldset' => 'paginate_link',
    ];

    $form['paginate_link_attributes'] = [
      '#type' => 'textfield',
      '#title' => t('Attributes'),
      '#description' => 'E.g. id|custom-id,role|navigation,data-key|value',
      '#default_value' => $this->alphaPagination->getOption('paginate_link_attributes'),
      '#fieldset' => 'paginate_link',
    ];

    $form['paginate_link_tokens'] = $this->alphaPagination->buildTokenTree('paginate_link');

    // Class options.
    $form['paginate_classes'] = [
      '#type' => 'fieldset',
      '#title' => t('Classes'),
      '#description' => t('Provide additional CSS classes on elements in the pagination; separated by spaces.'),
      '#collapsible' => TRUE,
    ];
    $form['paginate_class'] = [
      '#title' => t('Wrapper'),
      '#type' => 'textfield',
      '#default_value' => $this->alphaPagination->getOption('paginate_class'),
      '#description' => t('The wrapper around the item list.'),
      '#fieldset' => 'paginate_classes',
    ];
    $form['paginate_list_class'] = [
      '#title' => t('Item List'),
      '#type' => 'textfield',
      '#default_value' => $this->alphaPagination->getOption('paginate_list_class'),
      '#description' => t('The item list.'),
      '#fieldset' => 'paginate_classes',
    ];
    $form['paginate_active_class'] = [
      '#title' => t('Active item'),
      '#type' => 'textfield',
      '#default_value' => $this->alphaPagination->getOption('paginate_active_class'),
      '#description' => t('The active list item.'),
      '#fieldset' => 'paginate_classes',
    ];
    $form['paginate_inactive_class'] = [
      '#title' => t('Inactive item'),
      '#type' => 'textfield',
      '#default_value' => $this->alphaPagination->getOption('paginate_inactive_class'),
      '#description' => t('The inactive list item(s) that are not links, a.k.a. "no results".'),
      '#fieldset' => 'paginate_classes',
    ];

    // "All" options.
    $form['paginate_all_options'] = [
      '#type' => 'fieldset',
      '#title' => t('"All" item'),
      '#collapsible' => TRUE,
    ];
    $form['paginate_all_display'] = [
      '#type' => 'select',
      '#title' => t('Display the "All" item'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
      '#default_value' => $this->alphaPagination->getOption('paginate_all_display'),
      '#description' => t('Displays the "All" link in the pagination.'),
      '#fieldset' => 'paginate_all_options',
    ];
    $form['paginate_all_position'] = [
      '#type' => 'select',
      '#title' => t('Position'),
      '#options' => [
        'before' => t('Before'),
        'after' => t('After'),
      ],
      '#default_value' => $this->alphaPagination->getOption('paginate_all_position'),
      '#description' => t('Determines where the "All" item will show up in the pagination.'),
      '#dependency' => ['edit-options-paginate-all-display' => [1]],
      '#fieldset' => 'paginate_all_options',
    ];
    $form['paginate_all_label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => $this->alphaPagination->getOption('paginate_all_label'),
      '#description' => t('The label to use for display the "All" item in the pagination.'),
      '#dependency' => ['edit-options-paginate-all-display' => [1]],
      '#fieldset' => 'paginate_all_options',
    ];
    $form['paginate_all_value'] = [
      '#type' => 'textfield',
      '#title' => t('Value'),
      '#default_value' => $this->alphaPagination->getOption('paginate_all_value'),
      '#description' => t('The value to use to represent all items.'),
      '#dependency' => ['edit-options-paginate-all-display' => [1]],
      '#fieldset' => 'paginate_all_options',
    ];
    $form['paginate_all_class'] = [
      '#title' => t('Classes'),
      '#type' => 'textfield',
      '#default_value' => $this->alphaPagination->getOption('paginate_all_class'),
      '#description' => t('CSS classes for "All" item (on <code>&lt;li&gt;</code> element); separated by spaces.'),
      '#dependency' => ['edit-options-paginate-all-display' => [1]],
      '#fieldset' => 'paginate_all_options',
    ];

    // "Numeric" options.
    $form['paginate_numeric_options'] = [
      '#type' => 'fieldset',
      '#title' => t('Numeric items'),
      '#collapsible' => TRUE,
    ];

    $form['paginate_view_numbers'] = [
      '#title' => t('Display numeric items'),
      '#type' => 'select',
      '#options' => [
        0 => t('No'),
        1 => t('Individual numbers (0-9)'),
        2 => t('Single label (#)'),
      ],
      '#default_value' => $this->alphaPagination->getOption('paginate_view_numbers'),
      '#description' => t('Displays numeric item(s) in the pagination.'),
      '#fieldset' => 'paginate_numeric_options',
    ];

    // Global numeric options.
    $form['paginate_numeric_class'] = [
      '#title' => t('Classes'),
      '#type' => 'textfield',
      '#default_value' => $this->alphaPagination->getOption('paginate_numeric_class'),
      '#description' => t('CSS classes for numeric item (on <code>&lt;li&gt;</code> element); separated by spaces.'),
      '#fieldset' => 'paginate_numeric_options',
      '#dependency' => [
        'edit-options-paginate-view-numbers' => [1, 2],
      ],
    ];

    $form['paginate_numeric_position'] = [
      '#type' => 'select',
      '#title' => t('Position'),
      '#options' => [
        'before' => t('Before'),
        'after' => t('After'),
      ],
      '#default_value' => $this->alphaPagination->getOption('paginate_numeric_position'),
      '#description' => t('Determines whether numeric items are shown before or after alphabetical links in the pagination.'),
      '#fieldset' => 'paginate_numeric_options',
      '#dependency' => [
        'edit-options-paginate-view-numbers' => [1, 2],
      ],
    ];

    $form['paginate_numeric_hide_empty'] = [
      '#title' => t('Hide all numeric item(s) if empty'),
      '#description' => t('Will not render any numeric item(s) if there are no results that start with numeric values.'),
      '#type' => 'checkbox',
      '#default_value' => $this->alphaPagination->getOption('paginate_numeric_hide_empty'),
      '#fieldset' => 'paginate_numeric_options',
      '#dependency' => [
        'edit-options-paginate-view-numbers' => [1, 2],
      ],
    ];

    // Individual numeric items.
    $form['paginate_numeric_divider'] = [
      '#type' => 'checkbox',
      '#title' => t('Show divider'),
      '#default_value' => $this->alphaPagination->getOption('paginate_numeric_divider'),
      '#description' => t('Will render a specific divider item before or after the numeric items have been render, based on position.'),
      '#fieldset' => 'paginate_numeric_options',
      '#dependency' => [
        'edit-options-paginate-view-numbers' => [1],
      ],
    ];

    $form['paginate_numeric_divider_class'] = [
      '#title' => t('Divider class'),
      '#type' => 'textfield',
      '#default_value' => $this->alphaPagination->getOption('paginate_numeric_divider_class'),
      '#description' => t('The class to use for the numeric divider list item.'),
      '#fieldset' => 'paginate_numeric_options',
      '#dependency' => [
        'edit-options-paginate-view-numbers' => [1],
        'edit-options-paginate-numeric-divider' => [1],
      ],
      '#dependency_count' => 2,
    ];

    // Single numeric item.
    $form['paginate_numeric_label'] = [
      '#title' => t('Label'),
      '#type' => 'textfield',
      '#default_value' => $this->alphaPagination->getOption('paginate_numeric_label'),
      '#description' => t('The label to use to represent all numeric values.'),
      '#fieldset' => 'paginate_numeric_options',
      '#dependency' => ['edit-options-paginate-view-numbers' => [2]],
    ];

    $form['paginate_numeric_value'] = [
      '#title' => t('Value'),
      '#type' => 'textfield',
      '#default_value' => $this->alphaPagination->getOption('paginate_numeric_value'),
      '#description' => t('The value to use to represent all numeric values (i.e. URL value).'),
      '#fieldset' => 'paginate_numeric_options',
      '#dependency' => ['edit-options-paginate-view-numbers' => [2]],
    ];

  }

  /**
   * {@inheritdoc}
   */
  function post_execute(&$values) {
    $this->alphaPagination->ensureQuery();
  }

  /**
   * Render the alphabetic pagination.
   *
   * @param bool $empty
   *   If this area should be empty, then return it as empty.
   *
   * @return string
   *   A string representing the complete pagination including linked and
   *   unlinked options.
   */
  function render($empty = FALSE) {
    // Create the wrapper.
    $wrapper = [
      '#theme_wrappers' => ['container__alpha_pagination__wrapper'],
      '#attributes' => [],
      '#attached' => [
        'library' => [
          ['alpha_pagination', 'alpha_pagination'],
        ],
      ],
    ];

    $this->alphaPagination->addClasses($this->alphaPagination->getOption('paginate_class'), $wrapper['#attributes']);

    // Iterate over the alphabet and populate the items for the item list.
    $items = [];
    foreach ($this->alphaPagination->getCharacters() as $character) {
      // Add special numeric divider.
      if ($character->getValue() === '-' && $this->alphaPagination->getOption('paginate_view_numbers') !== '2' && $this->alphaPagination->getOption('paginate_numeric_divider')) {
        // Add an empty list item.
        $item = ['data' => ''];
        $this->alphaPagination->addClasses($this->alphaPagination->getOption('paginate_numeric_divider_class'), $item);
        $items[] = $item;
      }
      // Unfortunately, the implementation of item_list in core does not
      // allow render arrays to be passed as data and requires premature
      // rendering here by passing TRUE.
      elseif ($build = $character->build(TRUE)) {
        $item = ['data' => $build];

        // Add the necessary classes for item.
        if ($character->isAll()) {
          $this->alphaPagination->addClasses($this->alphaPagination->getOption('paginate_all_class'), $item);
        }

        if ($character->isNumeric()) {
          $this->alphaPagination->addClasses($this->alphaPagination->getOption('paginate_numeric_class'), $item);
        }

        if ($character->isActive()) {
          $this->alphaPagination->addClasses($this->alphaPagination->getOption('paginate_active_class'), $item);
        }
        elseif (!$character->isEnabled()) {
          $this->alphaPagination->addClasses($this->alphaPagination->getOption('paginate_inactive_class'), $item);
        }

        // Add the constructed item to the list.
        $items[] = $item;
      }
    }

    // Sanitize any classes provided for the item list.
    $item_list = [
      '#theme' => 'item_list__alpha_pagination',
      '#attributes' => [],
      '#items' => $items,
    ];
    $this->alphaPagination->addClasses($this->alphaPagination->getOption('paginate_list_class'), $item_list['#attributes']);

    // Append the item list to the wrapper.
    $wrapper[] = $item_list;

    return backdrop_render($wrapper);
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
  function validate() {
    return $this->alphaPagination->validate();
  }

}
