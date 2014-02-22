<?php
class PodioTextItemFieldTest extends PHPUnit_Framework_TestCase {

  public function setup() {
    $this->object = new PodioTextItemField(array(
      'field_id' => 123,
      'values' => array(
        array('value' => 'FooBar')
      )
    ));
  }

  public function test_can_provide_value() {
    // Empty values
    $empty_values = new PodioTextItemField(array('field_id' => 1));
    $this->assertNull($empty_values->values);

    // Populated values
    $this->assertEquals('FooBar', $this->object->values);
  }

  public function test_can_set_value() {
    $this->object->values = 'Baz';
    $this->assertEquals('Baz', $this->object->values);
  }

  public function test_can_humanize_value() {
    // Empty values
    $empty_values = new PodioTextItemField(array('field_id' => 1));
    $this->assertEquals('', $empty_values->humanized_value());

    // HTML content
    $html_values = new PodioTextItemField(array('field_id' => 1));
    $html_values->values = '<p>FooBar</p>';
    $this->assertEquals('FooBar', $html_values->humanized_value());

    // Populated values
    $this->assertEquals('FooBar', $this->object->humanized_value());
  }

  public function test_can_convert_to_api_friendly_json() {
    // Empty values
    $empty_values = new PodioTextItemField(array('field_id' => 1));
    $this->assertEquals('null', $empty_values->as_json());

    // Populated values
    $this->assertEquals('"FooBar"', $this->object->as_json());
  }

}
