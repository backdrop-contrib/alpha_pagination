<?php

/**
 * Class AlphaPaginationCharacter.
 */
class AlphaPaginationCharacter {

  /**
   * Flag determining if character is currently active.
   *
   * @var bool
   */
  protected $active = FALSE;

  /**
   * The AlphaPagination object reference.
   *
   * @var \AlphaPagination
   */
  protected $alphaPagination;

  /**
   * Flag determining if character is currently enabled.
   *
   * @var bool
   */
  protected $enabled = FALSE;

  /**
   * The human readable label of the character.
   *
   * @var string
   */
  protected $label;

  /**
   * The raw value of the character.
   *
   * @var string
   */
  protected $value;


  /**
   * AlphaPaginationCharacter constructor.
   *
   * @param \AlphaPagination $alpha_pagination
   * @param string $label
   *   The human readable label of the character.
   * @param string $value
   *   The raw value of the character.
   */
  public function __construct(\AlphaPagination $alpha_pagination, $label, $value) {
    $this->alphaPagination = $alpha_pagination;
    $this->label = $label;
    $this->value = $value;
  }

  /**
   * Builds a render array of the character.
   *
   * @param bool $render
   *   Flag determining whether to render the character.
   *
   * @return array|string
   *   A render array or a string of rendered markup if $render is TRUE.
   */
  public function build($render = FALSE) {
    // Render a link.
    if ($this->isLink()) {
      $build = $this->buildLink();
    }
    // Theme non-link item (a.k.a "inactive").
    //
    // Some themes require text that's in a "pagination" list to be wrapped
    // in another element. Typically, this would be a link, like above,
    // however since this is an inactive option, it should be wrapped in a
    // themeable element that can be targeted by preprocessors if needed.
    else {
      $build = [
        '#type' => 'head_tag',
        '#theme' => 'head_tag__alpha_pagination__inactive',
        '#tag' => 'span',
        '#value' => $this->getLabel(),
      ];
    }

    return $render ? backdrop_render($build) : $build;
  }

  /**
   * Builds a link render array based on current text, value and options.
   *
   * @param array $options
   *   Optional. Array of options to pass to url().
   *
   * @return array
   *   A render array for the link.
   */
  public function buildLink(array $options = []) {
    // Merge in options.
    $options = backdrop_array_merge_deep([
      'attributes' => [],
      'html' => FALSE,
      'query' => backdrop_get_query_parameters(),
    ], $options);

    // Merge in classes.
    $this->alphaPagination->addClasses($this->getOption('paginate_link_class'), $options['attributes']);

    $tokens = $this->alphaPagination->getTokens($this->getValue());
    $path = token_replace($this->getOption('paginate_link_path'), $tokens);

    // Determine if link is external (automatically enforcing for anchors).
    if ($this->getOption('paginate_link_external') || ($path && $path[0] === '#')) {
      $options['external'] = TRUE;
    }

    // Add in additional attributes.
    if ($this->getOption('paginate_link_attributes')) {
      $attributes = $this->alphaPagination->parseAttributes($this->getOption('paginate_link_attributes'), $tokens);
      // Remove any class attributes (should use the dedicated class option).
      unset($attributes['class']);

      // Merge in the attributes.
      $options['attributes'] = backdrop_array_merge_deep($options['attributes'], $attributes);
    }

    // Build link render array.
    return [
      '#theme' => 'link__alpha_pagination',
      '#text' => $this->getLabel(),
      '#path' => $path,
      '#options' => $options,
    ];
  }

  /**
   * Retrieves the set label for the character.
   *
   * @return string
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Retrieves an option from the view handler.
   *
   * @param string $name
   *   The option name to retrieve.
   * @param mixed $default
   *   The default value to return if not set.
   *
   * @return string
   *   The option value or $default if not set.
   *
   * @see \AlphaPagination::getOption()
   */
  protected function getOption($name, $default = '') {
    return $this->alphaPagination->getOption($name, $default);
  }

  /**
   * Retrieves the set value for the character.
   *
   * @return string
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Determines if character is currently active.
   *
   * @return bool
   */
  public function isActive() {
    return $this->active;
  }

  /**
   * Determines if character is the "all" type.
   *
   * @return bool
   */
  public function isAll() {
    return $this->getOption('paginate_all_value', 'all') === $this->value;
  }

  /**
   * Determines if character is currently enabled.
   *
   * @return bool
   */
  public function isEnabled() {
    return $this->isAll() || $this->isActive() || $this->enabled;
  }

  /**
   * Determines if character should render as a link.
   *
   * @return bool
   */
  public function isLink() {
    return !$this->active && ($this->isEnabled() || $this->isAll());
  }

  /**
   * Determines if character is numeric.
   *
   * @return bool
   */
  public function isNumeric() {
    return $this->alphaPagination->isNumeric($this->value);
  }

  /**
   * Sets whether character is enabled.
   *
   * @param bool $enabled
   *   TRUE or FALSE
   *
   * @return $this
   */
  public function setEnabled($enabled) {
    $this->enabled = $enabled;
    return $this;
  }

  /**
   * Sets whether character is active.
   *
   * @param bool $active
   *   TRUE or FALSE
   *
   * @return $this
   */
  public function setActive($active) {
    $this->active = $active;
    return $this;
  }

}
