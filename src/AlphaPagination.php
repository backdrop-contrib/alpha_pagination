<?php

/**
 * A base views handler for alpha pagination.
 */
class AlphaPagination {

  /**
   * @var \views_handler
   */
  protected $handler;

  /**
   * @param \views_handler $handler
   */
  public function __construct(views_handler $handler) {
    $this->handler = $handler;
  }

  /**
   * Add classes to an attributes array from a view option.
   *
   * @param string[]|string $classes
   *   An array of classes or a string of classes.
   * @param array $attributes
   *   An attributes array to add the classes to, passed by reference.
   *
   * @return array
   *   An array of classes to be used in a render array.
   */
  public function addClasses($classes, array &$attributes) {
    $processed = [];

    // Sanitize any classes provided for the item list.
    foreach ((array) $classes as $v) {
      foreach (array_filter(explode(' ', $v)) as $vv) {
        $processed[] = backdrop_clean_css_identifier($vv);
      }
    }

    // Don't add any classes if it's empty, which will add an empty attribute.
    if ($processed) {
      if (!isset($attributes['class'])) {
        $attributes['class'] = [];
      }
      $attributes['class'] = array_unique(array_merge($attributes['class'], $processed));
    }

    return $classes;
  }

  /**
   * Builds a render array for displaying tokens.
   *
   * @param string $fieldset
   *   The #fieldset name to assign.
   *
   * @return array
   *   The render array for the token info.
   */
  public function buildTokenTree($fieldset = NULL) {
    static $build;

    if (!isset($build)) {
      $build = [
        '#type' => 'container',
        '#title' => t('Browse available tokens'),
      ];
      $build['help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['alpha_pagination'],
        '#global_types' => TRUE,
        '#dialog' => TRUE,
      ];
    }

    return isset($fieldset) ? ['#fieldset' => $fieldset] + $build : $build;
  }

  /**
   * Extract the SQL query from the query information.
   *
   * Once extracted, place it into the options array so it is passed to the
   * render function. This code was lifted nearly verbatim from the views
   * module where the query is constructed for the ui to show the query in the
   * administrative area.
   *
   * @todo Need to make this better somehow?
   */
  public function ensureQuery() {
    if (!$this->getOption('query') && !empty($this->handler->view->build_info['query'])) {
      /** @var \SelectQuery $query */
      $query = $this->handler->view->build_info['query'];
      $quoted = $query->getArguments();
      $connection = Database::getConnection();
      foreach ($quoted as $key => $val) {
        if (is_array($val)) {
          $quoted[$key] = implode(', ', array_map([
            $connection,
            'quote',
          ], $val));
        }
        else {
          $quoted[$key] = $connection->quote($val);
        }
      }
      $this->handler->options['query'] = check_plain(strtr($query, $quoted));
    }
  }

  /**
   * Retrieves alphabet characters, based on langcode.
   *
   * Note: Do not use range(); always be explicit when defining an alphabet.
   * This is necessary as you cannot rely on the server language to construct
   * proper alphabet characters.
   *
   * @param string $langcode
   *   The langcode to return. If the langcode does not exist, it will default
   *   to English.
   *
   * @return array
   *   An indexed array of alphabet characters, based on langcode.
   *
   * @see hook_alpha_pagination_alphabet_alter()
   */
  public function getAlphabet($langcode = NULL) {
    global $language;

    // Default (English).
    static $default = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
    static $alphabets;

    // If the langcode is not explicitly specified, default to global langcode.
    if (!isset($langcode)) {
      $langcode = $language->langcode;
    }

    // Retrieve alphabets.
    if (!isset($alphabets)) {
      // Attempt to retrieve from database cache.
      $cid = "alpha_pagination:alphabets";
      if (($cache = cache_get($cid)) && !empty($cache->data)) {
        $alphabets = $cache->data;
      }
      // Build alphabets.
      else {
        // Arabic.
        $alphabets['ar'] = ['ا', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'و', 'ه', 'ي'];

        // English. Initially the default value, but can be modified in alter.
        $alphabets['en'] = $default;

        // Русский (Russian).
        $alphabets['ru'] = ['А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ы', 'Э', 'Ю', 'Я'];

        // Allow modules and themes to alter alphabets.
        backdrop_alter('alpha_pagination_alphabet', $alphabets, $this);

        // Cache the alphabets.
        cache_set($cid, $alphabets);
      }
    }

    // Return alphabet based on langcode.
    return isset($alphabets[$langcode]) ? $alphabets[$langcode] : $default;
  }

  /**
   * Retrieves all available alpha pagination areas.
   *
   * @param array $types
   *   The handler types to search.
   *
   * @return \views_handler_area_alpha_pagination[]
   *   An array of alpha pagination areas.
   */
  public function getAreaHandlers(array $types = ['header', 'footer']) {
    $areas = [];
    foreach ($types as $type) {
      foreach ($this->handler->view->display_handler->get_handlers($type) as $handler) {
        if ($handler instanceof \views_handler_area_alpha_pagination) {
          $areas[] = $handler;
        }
      }
    }
    return $areas;
  }

  /**
   * Retrieves the characters used to populate the pagination item list.
   *
   * @return \AlphaPaginationCharacter[]
   *   An associative array containing AlphaPaginationCharacter objects, keyed
   *   by its value.
   */
  public function getCharacters() {
    /** @var \AlphaPaginationCharacter[] $characters */
    static $characters;

    if (!isset($characters)) {
      $all = $this->getOption('paginate_all_display') === '1' ? $this->getOption('paginate_all_label', t('All')) : '';
      $all_value = $this->getOption('paginate_all_value', 'all');
      $numeric_label = $this->getOption('paginate_numeric_label');
      $numeric_type = $this->getOption('paginate_view_numbers', '0');
      $numeric_value = $this->getOption('paginate_numeric_value');
      $numeric_divider = $numeric_type !== '2' && $this->getOption('paginate_numeric_divider') ? ['-' => ''] : [];

      // Check to see if this query is cached. If it is, then just pull our
      // results set from it so that things can move quickly through here. We're
      // caching in the event the view has a very large result set.
      $cid = $this->getCid();
      if (($cache = cache_get($cid)) && !empty($cache->data)) {
        $characters = $cache->data;
      }
      else {
        // Add alphabet characters.
        foreach ($this->getAlphabet() as $value) {
          $characters[$value] = $value;
        }

        // Append or prepend numeric item(s).
        $numeric = [];
        if ($numeric_type !== '0') {

          // Determine type of numeric items.
          if ($numeric_type === '2') {
            $numeric[$numeric_value] = check_plain($numeric_label);
          }
          else {
            foreach ($this->getNumbers() as $value) {
              $numeric[$value] = $value;
            }
          }

          // Merge in numeric items.
          if ($this->getOption('paginate_numeric_position') === 'after') {
            $characters = array_merge($characters, $numeric_divider, $numeric);
          }
          else {
            $characters = array_merge($numeric, $numeric_divider, $characters);
          }
        }

        // Append or prepend the "all" item.
        if ($all) {
          if ($this->getOption('paginate_all_position') === 'before') {
            $characters = [$all_value => $all] + $characters;
          }
          else {
            $characters[$all_value] = $all;
          }
        }

        // Convert characters to objects.
        foreach ($characters as $value => $label) {
          $characters[$value] = new AlphaPaginationCharacter($this, $label, $value);
        }

        // Determine enabled prefixes.
        $prefixes = $this->getEntityPrefixes();
        foreach ($prefixes as $value) {
          // Ensure numeric prefixes use the numeric label, if necessary.
          if ($this->isNumeric($value) && $numeric_type === '2') {
            $value = $numeric_value;
          }
          if (isset($characters[$value])) {
            $characters[$value]->setEnabled(TRUE);
          }
        }

        // Remove all empty prefixes.
        if (!$this->getOption('paginate_toggle_empty')) {
          // Ensure "all" and numeric divider objects aren't removed.
          if ($all) {
            $prefixes[] = $all_value;
          }
          if ($numeric_divider) {
            $prefixes[] = '-';
          }
          // Determine if numeric results are not empty.
          $characters = array_filter(array_intersect_key($characters, array_flip($prefixes)));
        }

        // Remove all numeric values if they're all empty.
        if ($this->getOption('paginate_numeric_hide_empty')) {
          // Determine if numeric results are not empty.
          $numeric_results = array_filter(array_intersect_key($characters, array_flip($numeric)));
          if (!$numeric_results) {
            $characters = array_diff_key($characters, array_flip($numeric));
          }
        }

        // Cache the results.
        cache_set($cid, $characters, 'cache');
      }

      // Default current value to "all", if enabled, or the first character.
      $current = $all ? $all_value : '';

      // Attempt to determine if a valid argument was provided.
      $arg_count = count($this->handler->view->args);
      if ($arg_count) {
        $arg = $this->handler->view->args[$arg_count - 1];
        if ($arg && in_array($arg, array_keys($characters))) {
          $current = $arg;
        }
      }

      // Determine the first active character.
      if ($current) {
        foreach ($characters as $character) {
          if ($character->isNumeric() && $numeric_type === '2' ? $numeric_value === $current : $character->getValue() === $current) {
            $character->setActive(TRUE);
            break;
          }
        }
      }
    }

    return $characters;
  }

  /**
   * Retrieves a cache identifier for the view, display and query, if set.
   *
   * @return string
   *   A cache identifier.
   */
  public function getCid() {
    global $language;
    $this->ensureQuery();
    $data = [
      'langcode' => $language->langcode,
      'view' => $this->handler->view->name,
      'display' => $this->handler->view->current_display,
      'query' => $this->getOption('query') ? md5($this->getOption('query')) : '',
      'options' => $this->handler->options,
    ];
    return 'alpha_pagination:' .  backdrop_hash_base64(serialize($data));
  }

  /**
   * Construct the actual SQL query for the view being generated.
   *
   * Then parse it to short-circuit certain conditions that may exist and
   * make any alterations. This is not the most elegant of solutions, but it
   * is very effective.
   *
   * @return array
   *   An indexed array of entity identifiers.
   */
  public function getEntityIds() {
    $this->ensureQuery();
    $query_parts = explode("\n", $this->getOption('query'));

    // Get the base field. This will change depending on the type of view we
    // are putting the paginator on.
    $base_field = $this->handler->view->base_field;

    // If we are dealing with a substring, then short circuit it as we are most
    // likely dealing with a glossary contextual filter.
    foreach ($query_parts as $k => $part) {
      if ($position = strpos($part, "SUBSTRING")) {
        $part = substr($part, 0, $position) . " 1 OR " . substr($part, $position);
        $query_parts[$k] = $part;
      }
    }

    // Evaluate the last line looking for anything which may limit the result
    // set as we need results against the entire set of data and not just what
    // is configured in the view.
    $last_line = array_pop($query_parts);
    if (substr($last_line, 0, 5) != "LIMIT") {
      $query_parts[] = $last_line;
    }

    // Construct the query from the array and change the single quotes from
    // HTML special characters back into single quotes.
    $query = join("\n", $query_parts);
    $query = str_replace("&#039;", '\'', $query);
    $query = str_replace("&amp;", '&', $query);
    $query = str_replace("&lt;", '<', $query);
    $query = str_replace("&gt;", '>', $query);

    // Based on our query, get the list of entity identifiers that are affected.
    // These will be used to generate the pagination items.
    $entity_ids = [];
    $result = db_query($query);
    while ($data = $result->fetchObject()) {
      $entity_ids[] = $data->$base_field;
    }
    return $entity_ids;
  }

  /**
   * Retrieve the distinct first character prefix from the field tables.
   *
   * Mark them as TRUE so their pagination item is represented properly.
   *
   * Note that the node title is a special case that we have to take from the
   * node table as opposed to the body or any custom fields.
   *
   * @todo This should be cleaned up more and fixed "properly".
   *
   * @return array
   *   An indexed array containing a unique array of entity prefixes.
   */
  public function getEntityPrefixes() {
    $prefixes = [];

    if ($entity_ids = $this->getEntityIds()) {
      switch ($this->getOption('paginate_view_field')) {
        case 'name':
          $table = $this->handler->view->base_table;
          $where = $this->handler->view->base_field;

          // Extract the "name" field from the entity property info.
          $table_data = views_fetch_data($table);
          $entity_info = entity_plus_get_property_info($table_data['table']['entity type']);
          $field = isset($entity_info['properties']['name']['schema field']) ? $entity_info['properties']['name']['schema field'] : 'name';
          break;

        case 'title':
          $table = $this->handler->view->base_table;
          $where = $this->handler->view->base_field;

          // Extract the "title" field from the entity property info.
          $table_data = views_fetch_data($table);
          $entity_info = entity_plus_get_property_info($table_data['table']['entity type']);
          $field = isset($entity_info['properties']['title']['schema field']) ? $entity_info['properties']['title']['schema field'] : 'title';
          break;

        default:
          if (strpos($this->getOption('paginate_view_field'), ':') === FALSE) {
            // Format field name and table for single value fields
            $field = $this->getOption('paginate_view_field') . '_value';
            $table = 'field_data_' . $this->getOption('paginate_view_field');
          }
          else {
            // Format field name and table for compound value fields
            $field = str_replace(':', '_', $this->getOption('paginate_view_field'));
            $field_name_components = explode(':', $this->getOption('paginate_view_field'));
            $table = 'field_data_' . $field_name_components[0];
          }
          $where = 'entity_id';
          break;
      }
      $result = db_query('SELECT DISTINCT(SUBSTR(' . $field . ', 1, 1)) AS prefix
                          FROM {' . $table . '}
                          WHERE ' . $where . ' IN ( :nids )', [':nids' => $entity_ids]);
      while ($data = $result->fetchObject()) {
        $prefixes[] = is_numeric($data->prefix) ? $data->prefix : backdrop_strtoupper($data->prefix);
      }
    }
    return array_unique(array_filter($prefixes));
  }

  /**
   * Retrieves the proper label for a character.
   *
   * @param $value
   *   The value of the label to retrieve.
   *
   * @return string
   *   The label.
   */
  public function getLabel($value) {
    $characters = $this->getCharacters();

    // Return an appropriate numeric label.
    if ($this->getOption('paginate_view_numbers') === '2' && $this->isNumeric($value)) {
      return $characters[$this->getOption('paginate_numeric_value')]->getLabel();
    }
    elseif (isset($characters[$value])) {
      return $characters[$value]->getLabel();
    }

    // Return the original value.
    return $value;
  }

  /**
   * Retrieves numeric characters, based on langcode.
   *
   * Note: Do not use range(); always be explicit when defining numbers.
   * This is necessary as you cannot rely on the server language to construct
   * proper numeric characters.
   *
   * @param string $langcode
   *   The langcode to return. If the langcode does not exist, it will default
   *   to English.
   *
   * @return array
   *   An indexed array of numeric characters, based on langcode.
   *
   * @see hook_alpha_pagination_numbers_alter()
   */
  public function getNumbers($langcode = NULL) {
    global $language;

    // Default (English).
    static $default = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    static $numbers;

    // If the langcode is not explicitly specified, default to global langcode.
    if (!isset($langcode)) {
      $langcode = $language->langcode;
    }

    // Retrieve numbers.
    if (!isset($numbers)) {
      // Attempt to retrieve from database cache.
      $cid = "alpha_pagination:numbers";
      if (($cache = cache_get($cid)) && !empty($cache->data)) {
        $numbers = $cache->data;
      }
      // Build numbers.
      else {
        // English. Initially the default value, but can be modified in alter.
        $numbers['en'] = $default;

        // Allow modules and themes to alter numbers.
        backdrop_alter('alpha_pagination_numbers', $numbers, $this);

        // Cache the numbers.
        cache_set($cid, $numbers);
      }
    }

    // Return numbers based on langcode.
    return isset($numbers[$langcode]) ? $numbers[$langcode] : $default;
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
   */
  public function getOption($name, $default = '') {
    return (string) (isset($this->handler->options[$name]) ? $this->handler->options[$name] : $default);
  }

  /**
   * Provides the token data that is passed when during replacement.
   *
   * @param string $value
   *   The current character value being processed.
   *
   * @return array
   *   Token data.
   *
   * @see alpha_pagination_token_info()
   * @see alpha_pagination_tokens()
   */
  public function getTokens($value = NULL) {
    return [
      'alpha_pagination' => [
        'path' => $this->getUrl(),
        'value' => $value,
      ],
    ];
  }

  /**
   * Retrieves the URL for the current view.
   *
   * Note: this follows very similarly to \view::get_url to process arguments,
   * however it is in fact severely modified to account for characters appended
   * by this module.
   *
   * @return string
   *   The URL for the view or current_path().
   */
  public function getUrl() {
    static $url;

    if (!isset($url)) {
      if (!empty($this->handler->view->override_url)) {
        return $this->handler->view->override_url;
      }

      $path = $this->handler->view->get_path();
      $args = $this->handler->view->args;

      // Exclude arguments that were computed, not passed on the URL.
      $position = 0;
      if (!empty($this->handler->view->argument)) {
        foreach ($this->handler->view->argument as $argument_id => $argument) {
          if (!empty($argument->options['default_argument_skip_url'])) {
            unset($args[$position]);
          }
          $position++;
        }
      }

      // Don't bother working if there's nothing to do:
      if (empty($path) || (empty($args) && strpos($path, '%') === FALSE)) {
        $path = current_path();
        $pieces = explode('/', $path);
        if (array_key_exists(end($pieces), $this->getCharacters())) {
          array_pop($pieces);
        }
        $url = implode('/', $pieces);
        return $url;
      }

      $pieces = [];
      $argument_keys = isset($this->handler->view->argument) ? array_keys($this->handler->view->argument) : [];
      $id = current($argument_keys);
      foreach (explode('/', $path) as $piece) {
        if ($piece != '%') {
          $pieces[] = $piece;
        }
        else {
          if (empty($args)) {
            // Try to never put % in a url; use the wildcard instead.
            if ($id && !empty($this->handler->view->argument[$id]->options['exception']['value'])) {
              $pieces[] = $this->handler->view->argument[$id]->options['exception']['value'];
            }
            else {
              $pieces[] = '*'; // gotta put something if there just isn't one.
            }

          }
          else {
            $pieces[] = array_shift($args);
          }

          if ($id) {
            $id = next($argument_keys);
          }
        }
      }

      // Just return the computed pieces, don't merge any extra remaining args.
      $url = implode('/', $pieces);
    }
    return $url;
  }

  /**
   * Retrieves the proper value for a character.
   *
   * @param $value
   *   The value to retrieve.
   *
   * @return string
   *   The value.
   */
  public function getValue($value) {
    $characters = $this->getCharacters();

    // Return an appropriate numeric label.
    if ($this->getOption('paginate_view_numbers') === '2' && $this->isNumeric($value)) {
      return $characters[$this->getOption('paginate_numeric_value')]->getValue();
    }
    elseif (isset($characters[$value])) {
      return $characters[$value]->getLabel();
    }

    // Return the original value.
    return $value;
  }

  /**
   * Determines if value is "numeric".
   *
   * @param string $value
   *   The value to test.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isNumeric($value) {
    return ($this->getOption('paginate_view_numbers') === '2' && $value === $this->getOption('paginate_numeric_value')) || in_array($value, $this->getNumbers());
  }

  /**
   * Parses an attribute string saved in the UI.
   *
   * @param string $string
   *   The attribute string to parse.
   * @param array $tokens
   *   An associative array of token data to use.
   *
   * @return array
   *   A parsed attributes array.
   */
  public function parseAttributes($string = NULL, array $tokens = []) {
    $attributes = [];
    if (!empty($string)) {
      $parts = explode(',', $string);
      foreach ($parts as $attribute) {
        if (strpos($attribute, '|') !== FALSE) {
          list($key, $value) = explode('|', token_replace($attribute, $tokens, ['clear' => TRUE]));
          $attributes[$key] = $value;
        }
      }
    }
    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function ui_name() {
    if ($ui_name = $this->getOption('ui_name')) {
      return check_plain($ui_name);
    }
    return t('Alpha Pagination');
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $name = $this->handler->view->name;
    $display_id = $this->handler->view->current_display;

    // Immediately return if display doesn't have the handler in question.
    $items = $this->handler->view->get_items($this->handler->handler_type, $display_id);
    $field = $this->handler->real_field ?: $this->handler->field;
    if (!isset($items[$field])) {
      return [];
    }

    static $errors = [];
    if (!isset($errors["$name:$display_id"])) {
      $errors["$name:$display_id"] = [];

      // Show an error if not found.
      $areas = $this->getAreaHandlers();
      if (!$areas) {
        $errors["$name:$display_id"][] = t('The view "@name:@display" must have at least one configured alpha pagination area in either the header or footer to use "@field".', [
          '@field' => $this->handler->real_field,
          '@name' => $this->handler->view->name,
          '@display' => $this->handler->view->current_display,
        ]);
      }
      // Show an error if there is more than one instance.
      elseif (count($areas) > 1) {
        $errors["$name:$display_id"][] = t('The view "@name:@display" can only have one configured alpha pagination area in either the header or footer.', [
          '@name' => $this->handler->view->name,
          '@display' => $this->handler->view->current_display,
        ]);
      }

    }

    return $errors["$name:$display_id"];
  }

}
