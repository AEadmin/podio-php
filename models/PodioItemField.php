<?php
/**
 * @see https://developers.podio.com/doc/items
 */
class PodioItemField extends PodioObject {
  public function __construct($attributes = array(), $force_type = null) {
    $this->property('field_id', 'integer', array('id' => true));
    $this->property('type', 'string');
    $this->property('external_id', 'string');
    $this->property('label', 'string');
    $this->property('values', 'array');
    $this->property('config', 'hash');
    $this->property('status', 'string');

    $this->init($attributes);

    $this->set_type_from_class_name();
  }

  /**
   * Saves the value of the field
   */
  public function save($options = array()) {
    $relationship = $this->relationship();
    if (!$relationship) {
      throw new PodioMissingRelationshipError('{"error_description":"Field is missing relationship to item"}', null, null);
    }
    if (!$this->id && !$this->external_id) {
      throw new PodioDataIntegrityError('Field must have id or external_id set.');
    }
    $attributes = $this->as_json(false);
    return self::update($relationship['instance']->id, $this->id ? $this->id : $this->external_id, $attributes, $options);
  }

  /**
   * Calling parent so we get all field attributes printed instead of only api_friendly_values
   */
  public function __toString() {
    return print_r(parent::as_json(false), true);
  }

  /**
   * Overwrites normal as_json to use api_friendly_values
   */
  public function as_json($encoded = true) {
    $result = $this->api_friendly_values();
    return $encoded ? json_encode($result) : $result;
  }

  /**
   * Set the value of the field
   */
  public function set_value($values) {
    if (is_null($values) || $values === false) {
      $this->values = array();
    }
    else {
      switch ($this->type) {
        case 'question': // id
        case 'category': // id
          // Ensure that we have an array of values
          if (!is_array($values) || (is_array($values) && !empty($values['id']))) {
            $values = array($values);
          }

          $this->values = array_map(function($value) use ($id_key) {
            if (is_object($value)) {
              return array('value' => array($id_key => (int)$value->{$id_key}));
            }
            elseif (is_array($value)) {
              return array('value' => array($id_key => (int)$value[$id_key]));
            }
            else {
              return array('value' => array($id_key => (int)$value));
            }
          }, $values);
          break;
        // Single value fields with integer value
        case 'progress':
        case 'duration':
          $this->values = is_array($values) ? array(array('value' => (int)$values[0])) : array(array('value' => (int)$values));
          break;
        // Single value fields with string value
        case 'number':
        case 'money':
        case 'state':
        case 'calculation':
        default:
          $this->values = is_array($values) ? array(array('value' => $values[0])) : array(array('value' => $values));
          break;
      }
    }
  }

  /**
   * Returns API friendly values for item field for use when saving item
   */
  public function api_friendly_values() {
    if (!$this->values) {
      return array();
    }
    switch ($this->type) {
      case 'question': // id
      case 'category': //id
        $list = array();
        foreach ($this->values as $value) {
          if (!empty($value['value']['id'])) {
            $list[] = $value['value']['id'];
          }
        }
        return $list;
        break;
      case 'date':
        if (empty($this->values[0]['end'])) {
          return array('start' => $this->values[0]['start']);
        }
        return array('start' => $this->values[0]['start'], 'end' => $this->values[0]['end']);
        break;
      case 'number':
      case 'money':
      case 'progress':
      case 'state':
      case 'duration':
      case 'calculation':
      default:
        return $this->values;
        break;
    }
  }

  /**
   * Displays a human-friendly value for the field
   */
  public function humanized_value() {
    return $this->values[0]['value'];
  }

  /**
   * @see https://developers.podio.com/doc/items/update-item-field-values-22367
   */
  public static function update($item_id, $field_id, $attributes = array(), $options = array()) {
    $url = Podio::url_with_options("/item/{$item_id}/value/{$field_id}", $options);
    return Podio::put($url, $attributes)->json_body();
  }

  /**
   * @see https://developers.podio.com/doc/calendar/get-item-field-calendar-as-ical-10195681
   */
  public static function ical($item_id, $field_id) {
    return Podio::get("/calendar/item/{$item_id}/field/{$field_id}/ics/")->body;
  }

  /**
   * @see https://developers.podio.com/doc/calendar/get-item-field-calendar-as-ical-10195681
   */
  public static function ical_field($item_id, $field_id) {
    return Podio::get("/calendar/item/{$item_id}/field/{$field_id}/ics/")->body;
  }

  public function set_type_from_class_name() {
    switch (get_class($this)) {
      case 'PodioTextItemField':
        $this->type = 'text';
        break;
      case 'PodioEmbedItemField':
        $this->type = 'embed';
        break;
      case 'PodioLocationItemField':
        $this->type = 'location';
        break;
      case 'PodioDateItemField':
        $this->type = 'date';
        break;
      case 'PodioContactItemField':
        $this->type = 'contact';
        break;
      case 'PodioAppItemField':
        $this->type = 'app';
        break;
      case 'PodioQuestionItemField':
        $this->type = 'question';
        break;
      case 'PodioCategoryItemField':
        $this->type = 'category';
        break;
      case 'PodioImageItemField':
        $this->type = 'image';
        break;
      case 'PodioVideoItemField':
        $this->type = 'video';
        break;
      case 'PodioFileItemField':
        $this->type = 'file';
        break;
      case 'PodioNumberItemField':
        $this->type = 'number';
        break;
      case 'PodioProgressItemField':
        $this->type = 'progress';
        break;
      case 'PodioStateItemField':
        $this->type = 'state';
        break;
      case 'PodioDurationItemField':
        $this->type = 'duration';
        break;
      case 'PodioCalculationItemField':
        $this->type = 'calculation';
        break;
      case 'PodioMoneyItemField':
        $this->type = 'money';
        break;
      default:
        break;
    }
  }

}

/**
 * Text field
 */
class PodioTextItemField extends PodioItemField {

  /**
   * Override __set to use field specific method for setting values property
   */
  public function __set($name, $value) {
    if ($name == 'values' && $value !== null) {
      return $this->set_value($value);
    }
    return parent::__set($name, $value);
  }

  /**
   * Override __get to provide values as a string
   */
  public function __get($name) {
    $attribute = parent::__get($name);
    if ($name == 'values' && $attribute) {
      return $attribute[0]['value'];
    }
    return $attribute;
  }

  public function set_value($values) {
    parent::__set('values', $values ? array(array('value' => $values)) : array());
  }

  public function humanized_value() {
    return strip_tags($this->values);
  }

  public function api_friendly_values() {
    return $this->values ? $this->values : null;
  }

}

/**
 * Embed field
 */
class PodioEmbedItemField extends PodioItemField {

  /**
   * Override __set to use field specific method for setting values property
   */
  public function __set($name, $value) {
    if ($name == 'values' && $value !== null) {
      return $this->set_value($value);
    }
    return parent::__set($name, $value);
  }

  /**
   * Override __get to provide values as a PodioCollection of PodioEmbed objects
   */
  public function __get($name) {
    $attribute = parent::__get($name);
    if ($name == 'values' && $attribute) {
      // Create PodioCollection from raw values
      $embeds = new PodioCollection();
      foreach ($attribute as $value) {
        $embed = new PodioEmbed($value['embed']);
        if (!empty($value['file'])) {
          $embed->files = new PodioCollection(array(new PodioFile($value['file'])));
        }
        $embeds[] = $embed;
      }
      return $embeds;
    }
    return $attribute;
  }

  public function humanized_value() {
    if (!$this->values) {
      return '';
    }

    $values = array();
    foreach ($this->values as $value) {
      $values[] = $value->original_url;
    }
    return join(';', $values);
  }

  public function set_value($values) {
    if ($values) {
      // Ensure that we have an array of values
      if (is_a($values, 'PodioCollection')) {
        $values = $values->_get_items();
      }
      if (is_object($values) || (is_array($values) && !empty($values['embed']))) {
        $values = array($values);
      }

      $values = array_map(function($value) {
        if (is_object($value)) {
          $file = $value->files ? $value->files[0] : null;
          unset($value->files);

          return array('embed' => $value->as_json(false), 'file' => $file ? $file->as_json(false) : null);
        }
        return $value;
      }, $values);

      parent::__set('values', $values);
    }
  }

  public function api_friendly_values() {
    if (!$this->values) {
      return array();
    }
    $list = array();
    foreach ($this->values as $value) {
      $list[] = array('embed' => $value->embed_id, 'file' => ($value->files ? $value->files[0]->file_id : null) );
    }
    return $list;
  }

}

/**
 * Location field
 */
class PodioLocationItemField extends PodioItemField {

  /**
   * Override __set to use field specific method for setting values property
   */
  public function __set($name, $value) {
    if ($name == 'values' && $value !== null) {
      return $this->set_value($value);
    }
    return parent::__set($name, $value);
  }

  /**
   * Override __get to provide values as a string
   */
  public function __get($name) {
    $attribute = parent::__get($name);
    if ($name == 'values' && is_array($attribute)) {
      $list = array();
      foreach ($attribute as $value) {
        $list[] = $value['value'];
      }
      return $list;
    }
    return $attribute;
  }

  public function api_friendly_values() {
    return $this->values ? $this->values : null;
  }

  public function set_value($values) {
    if ($values) {
      if (is_array($values)) {
        $formatted_values = array_map(function($value){
          return array('value' => $value);
        }, $values);
        parent::__set('values', $formatted_values);
      }
      else {
        parent::__set('values', array(array('value' => $values)));
      }
    }
  }

  public function add_value($value) {
    if (!$this->values) {
      $this->set_value($value);
    } else {
      $values = $this->values;
      $values[] = $value;
      $this->set_value($values);
    }
  }

  public function humanized_value() {
    if (!$this->values) {
      return '';
    }

    return join(';', $this->values);
  }
}

/**
 * Date field
 */
class PodioDateItemField extends PodioItemField {

  public function __construct($attributes = array()) {
    parent::__construct($attributes, 'date');
  }

  public function set_value($values) {
    $this->values = array();
    if ($values) {
      $this->values = array($values);
    }
  }

  public function humanized_value() {
    $value = $this->values[0];
    // Remove seconds from start and end times since they are always '00' anyway.
    if (!empty($value['start_time'])) {
      $value['start_time'] = substr($value['start_time'], 0, strrpos($value['start_time'], ':'));
    }
    if (!empty($value['end_time'])) {
      $value['end_time'] = substr($value['end_time'], 0, strrpos($value['end_time'], ':'));
    }
    // Variants:

    // Same date
    // 2012-12-12
    // 2012-12-12 14:00
    // 2012-12-12 14:00 - 15:00

    // Different dates
    // 2012-12-12 - 2012-12-14
    // 2012-12-12 14:00 - 2012-12-14
    // 2012-12-12 14:00 - 2012-12-12 15:00

    if (empty($value['end_date']) || $value['start_date'] == $value['end_date']) {
      if (!empty($value['start_time']) && !empty($value['end_time']) && $value['start_time'] != $value['end_time']) {
        return "{$value['start_date']} {$value['start_time']}-{$value['end_time']}";
      }
      elseif (!empty($value['start_time']) && (empty($value['end_time']) || $value['start_time'] == $value['end_time'])) {
        return "{$value['start_date']} {$value['start_time']}";
      }
      else {
        return "{$value['start_date']}";
      }
    }
    else {
      if (!empty($value['start_time']) && !empty($value['end_time']) && $value['end_time'] != '00:00') {
        return "{$value['start_date']} {$value['start_time']} - {$value['end_date']} {$value['end_time']}";
      }
      elseif (!empty($value['end_time']) || $value['end_time'] == '00:00') {
        return "{$value['start_date']} {$value['start_time']} - {$value['end_date']}";
      }
      else {
        return "{$value['start_date']} - {$value['end_date']}";
      }
    }
  }

  // TODO: Set start and end date and times easily
}

/**
 * Contact field
 */
class PodioContactItemField extends PodioItemField {
  /**
   * Override __set to use field specific method for setting values property
   */
  public function __set($name, $value) {
    if ($name == 'values' && $value !== null) {
      return $this->set_value($value);
    }
    return parent::__set($name, $value);
  }

  /**
   * Override __get to provide values as a PodioCollection of PodioEmbed objects
   */
  public function __get($name) {
    $attribute = parent::__get($name);
    if ($name == 'values' && $attribute) {
      // Create PodioCollection from raw values
      $collection = new PodioCollection();
      foreach ($attribute as $value) {
        $collection[] = new PodioContact($value['value']);
      }
      return $collection;
    }
    return $attribute;
  }

  public function humanized_value() {
    if (!$this->values) {
      return '';
    }

    $values = array();
    foreach ($this->values as $value) {
      $values[] = $value->name;
    }
    return join(';', $values);
  }

  public function set_value($values) {
    if ($values) {
      // Ensure that we have an array of values
      if (is_a($values, 'PodioCollection')) {
        $values = $values->_get_items();
      }
      if (is_object($values) || (is_array($values) && !empty($values['profile_id']))) {
        $values = array($values);
      }

      $values = array_map(function($value) {
        if (is_object($value)) {
          return array('value' => $value->as_json(false));
        }
        return array('value' => $value);
      }, $values);

      parent::__set('values', $values);
    }
  }

  public function api_friendly_values() {
    if (!$this->values) {
      return array();
    }
    $list = array();
    foreach ($this->values as $value) {
      $list[] = $value->profile_id;
    }
    return $list;
  }
}

/**
 * App reference field
 */
class PodioAppItemField extends PodioItemField {
  /**
   * Override __set to use field specific method for setting values property
   */
  public function __set($name, $value) {
    if ($name == 'values' && $value !== null) {
      return $this->set_value($value);
    }
    return parent::__set($name, $value);
  }

  /**
   * Override __get to provide values as a PodioCollection of PodioEmbed objects
   */
  public function __get($name) {
    $attribute = parent::__get($name);
    if ($name == 'values' && $attribute) {
      // Create PodioCollection from raw values
      $collection = new PodioCollection();
      foreach ($attribute as $value) {
        $collection[] = new PodioItem($value['value']);
      }
      return $collection;
    }
    return $attribute;
  }

  public function humanized_value() {
    if (!$this->values) {
      return '';
    }

    $values = array();
    foreach ($this->values as $value) {
      $values[] = $value->title;
    }
    return join(';', $values);
  }

  public function set_value($values) {
    if ($values) {
      // Ensure that we have an array of values
      if (is_a($values, 'PodioCollection')) {
        $values = $values->_get_items();
      }
      if (is_object($values) || (is_array($values) && !empty($values['item_id']))) {
        $values = array($values);
      }

      $values = array_map(function($value) {
        if (is_object($value)) {
          return array('value' => $value->as_json(false));
        }
        return array('value' => $value);
      }, $values);

      parent::__set('values', $values);
    }
  }

  public function api_friendly_values() {
    if (!$this->values) {
      return array();
    }
    $list = array();
    foreach ($this->values as $value) {
      $list[] = $value->item_id;
    }
    return $list;
  }
}

/**
 * Category field
 */
class PodioCategoryItemField extends PodioItemField {
  /**
   * Override __set to use field specific method for setting values property
   */
  public function __set($name, $value) {
    if ($name == 'values' && $value !== null) {
      return $this->set_value($value);
    }
    return parent::__set($name, $value);
  }

  /**
   * Override __get to provide values as a string
   */
  public function __get($name) {
    $attribute = parent::__get($name);
    if ($name == 'values' && is_array($attribute)) {
      $list = array();
      foreach ($attribute as $value) {
        $list[] = $value['value'];
      }
      return $list;
    }
    return $attribute;
  }

  public function api_friendly_values() {
    if (!$this->values) {
      return array();
    }
    $list = array();
    foreach ($this->values as $value) {
      $list[] = $value['id'];
    }
    return $list;
  }

  public function set_value($values) {
    if ($values) {
      if (is_array($values)) {
        $formatted_values = array_map(function($value){
          if (is_array($value)) {
            return array('value' => $value);
          }
          else {
            return array('value' => array('id' => $value));
          }
        }, $values);
        parent::__set('values', $formatted_values);
      }
      else {
        parent::__set('values', array(array('value' => array('id' => $values))));
      }
    }
  }

  public function add_value($value) {
    if (!$this->values) {
      $this->set_value($value);
    } else {
      $values = $this->values;
      $values[] = $value;
      $this->set_value($values);
    }
  }

  public function humanized_value() {
    if (!$this->values) {
      return '';
    }
    $list = array();
    foreach ($this->values as $value) {
      $list[] = isset($value['text']) ? $value['text'] : $value['id'];
    }

    return join(';', $list);
  }
}

/**
 * Question field
 */
class PodioQuestionItemField extends PodioCategoryItemField {}

/**
 * Asset field, super class for Image/Video/File fields
 */
class PodioAssetItemField extends PodioItemField {
  /**
   * Override __set to use field specific method for setting values property
   */
  public function __set($name, $value) {
    if ($name == 'values' && $value !== null) {
      return $this->set_value($value);
    }
    return parent::__set($name, $value);
  }

  /**
   * Override __get to provide values as a PodioCollection of PodioEmbed objects
   */
  public function __get($name) {
    $attribute = parent::__get($name);
    if ($name == 'values' && $attribute) {
      // Create PodioCollection from raw values
      $collection = new PodioCollection();
      foreach ($attribute as $value) {
        $collection[] = new PodioFile($value['value']);
      }
      return $collection;
    }
    return $attribute;
  }

  public function humanized_value() {
    if (!$this->values) {
      return '';
    }

    $values = array();
    foreach ($this->values as $value) {
      $values[] = $value->name;
    }
    return join(';', $values);
  }

  public function set_value($values) {
    $this->values = array();
    if ($values) {
      // Ensure that we have an array of values
      if (is_a($values, 'PodioCollection')) {
        $values = $values->_get_items();
      }
      if (is_object($values) || (is_array($values) && !empty($values['file_id']))) {
        $values = array($values);
      }

      $values = array_map(function($value) {
        if (is_object($value)) {
          return array('value' => $value->as_json(false));
        }
        return array('value' => $value);
      }, $values);

      parent::__set('values', $values);
    }
  }

  public function api_friendly_values() {
    if (!$this->values) {
      return array();
    }
    $list = array();
    foreach ($this->values as $value) {
      $list[] = $value->file_id;
    }
    return $list;
  }
}

/**
 * Image field
 */
class PodioImageItemField extends PodioAssetItemField {}

/**
 * Video field
 */
class PodioVideoItemField extends PodioAssetItemField {}

/**
 * File field
 */
class PodioFileItemField extends PodioAssetItemField {}

/**
 * Number field
 */
class PodioNumberItemField extends PodioItemField {

  /**
   * Override __set to use field specific method for setting values property
   */
  public function __set($name, $value) {
    if ($name == 'values' && $value !== null) {
      return $this->set_value($value);
    }
    return parent::__set($name, $value);
  }

  /**
   * Override __get to provide values as a string
   */
  public function __get($name) {
    $attribute = parent::__get($name);
    if ($name == 'values' && $attribute) {
      return $attribute[0]['value'];
    }
    return $attribute;
  }

  public function set_value($values) {
    parent::__set('values', $values ? array(array('value' => $values)) : array());
  }

  public function humanized_value() {
    if ($this->values === null) {
      return '';
    }
    return rtrim(rtrim(number_format($this->values, 4, '.', ''), '0'), '.');
  }

  public function api_friendly_values() {
    return $this->values !== null ? $this->values : null;
  }

}

/**
 * Progress field
 */
class PodioProgressItemField extends PodioItemField {
  /**
   * Override __set to use field specific method for setting values property
   */
  public function __set($name, $value) {
    if ($name == 'values' && $value !== null) {
      return $this->set_value($value);
    }
    return parent::__set($name, $value);
  }

  /**
   * Override __get to provide values as a string
   */
  public function __get($name) {
    $attribute = parent::__get($name);
    if ($name == 'values' && $attribute) {
      return $attribute[0]['value'];
    }
    return $attribute;
  }

  public function set_value($values) {
    parent::__set('values', $values ? array(array('value' => $values)) : array());
  }

  public function humanized_value() {
    if ($this->values === null) {
      return '';
    }
    return $this->values.'%';
  }

  public function api_friendly_values() {
    return $this->values !== null ? $this->values : null;
  }
}

/**
 * State field
 */
class PodioStateItemField extends PodioItemField {

  /**
   * Override __set to use field specific method for setting values property
   */
  public function __set($name, $value) {
    if ($name == 'values' && $value !== null) {
      return $this->set_value($value);
    }
    return parent::__set($name, $value);
  }

  /**
   * Override __get to provide values as a string
   */
  public function __get($name) {
    $attribute = parent::__get($name);
    if ($name == 'values' && $attribute) {
      return $attribute[0]['value'];
    }
    return $attribute;
  }

  public function set_value($values) {
    parent::__set('values', $values ? array(array('value' => $values)) : array());
  }

  public function humanized_value() {
    return $this->values;
  }

  public function api_friendly_values() {
    return $this->values ? $this->values : null;
  }
}

/**
 * Duration field
 */
class PodioDurationItemField extends PodioItemField {

  /**
   * Override __set to use field specific method for setting values property
   */
  public function __set($name, $value) {
    if ($name == 'values' && $value !== null) {
      return $this->set_value($value);
    }
    return parent::__set($name, $value);
  }

  /**
   * Override __get to provide values as an integer
   */
  public function __get($name) {
    $attribute = parent::__get($name);
    if ($name == 'values' && $attribute) {
      return $attribute[0]['value'];
    }
    elseif ($name == 'hours') {
      return floor($this->values/3600);
    }
    elseif ($name == 'minutes') {
      return (($this->values/60)%60);
    }
    elseif ($name == 'seconds') {
      return ($this->values%60);
    }
    return $attribute;
  }

  public function set_value($values) {
    parent::__set('values', $values ? array(array('value' => $values)) : array());
  }

  public function humanized_value() {
    $list = array(str_pad($this->hours, 2, '0', STR_PAD_LEFT), str_pad($this->minutes, 2, '0', STR_PAD_LEFT), str_pad($this->seconds, 2, '0', STR_PAD_LEFT));
    return join(':', $list);
  }

  public function api_friendly_values() {
    return $this->values ? $this->values : null;
  }
}

/**
 * Calculation field
 */
class PodioCalculationItemField extends PodioItemField {

  /**
   * Override __set to use field specific method for setting values property
   */
  public function __set($name, $value) {
    if ($name == 'values') {
      return true;
    }
    return parent::__set($name, $value);
  }

  /**
   * Override __get to provide values as a string
   */
  public function __get($name) {
    $attribute = parent::__get($name);
    if ($name == 'values' && $attribute) {
      return $attribute[0]['value'];
    }
    return $attribute;
  }

  public function set_value($values) {
    return true;
  }

  public function humanized_value() {
    if ($this->values === null) {
      return '';
    }
    return rtrim(rtrim(number_format($this->values, 4, '.', ''), '0'), '.');
  }

  public function api_friendly_values() {
    return $this->values !== null ? $this->values : null;
  }
}

/**
 * Money field
 */
class PodioMoneyItemField extends PodioItemField {
  /**
   * Currency part of the value
   */
  public function currency() {
    if (!empty($this->values)) {
      return $this->values[0]['currency'];
    }
  }
  /**
   * Set the currency value.
   */
  public function set_currency($currency) {
    $value = $this->values[0]['value'] ? $this->values[0]['value'] : 0;
    $this->set_attribute('values', array(array('currency' => $currency, 'value' => $value)));
  }
  /**
   * Amount part of the value
   */
  public function amount() {
    if (!empty($this->values)) {
      return $this->values[0]['value'];
    }
  }
  /**
   * Set the amount.
   */
  public function set_amount($amount) {
    $currency = $this->values[0]['currency'] ? $this->values[0]['currency'] : '';
    $this->set_attribute('values', array(array('currency' => $currency, 'value' => $amount)));
  }

  public function humanized_value() {
    $amount = number_format($this->values[0]['value'], 2, '.', '');
    switch ($this->values[0]['currency']) {
      case 'USD':
        $currency = '$';
      case 'EUR':
        $currency = '€';
        break;
      case 'GBP':
        $currency = '£';
        break;
      default:
        $currency = $this->values[0]['currency'].' ';
        break;
    }
    return $currency.$amount;
  }
}
