<?php

/**
 * Tag field, using jQuery/Select2.
 *
 * @package    forms
 * @subpackage fields-formattedinput
 */
class TagField extends DropdownField {
	/**
	 * @var null|string
	 */
	protected $relationTitleField;

	/**
	 * @var mixed
	 */
	protected $selectedValues;

	/**
	 * @param string      $name
	 * @param null|string $title
	 * @param array       $source
	 * @param string      $value
	 * @param string      $relationTitleField
	 */
	public function __construct($name, $title = null, $source = array(), $value = '', $relationTitleField = 'Title') {
		$this->relationTitleField = $relationTitleField;
		$this->selectedValues = $value;

		parent::__construct($name, $title, $source, $value);
	}

	/**
	 * @param array $properties
	 *
	 * @return string
	 */
	public function Field($properties = array()) {
		Requirements::css(TAG_FIELD_DIR . '/css/select2.min.css');

		Requirements::javascript(TAG_FIELD_DIR . '/js/TagField.js');
		Requirements::javascript(TAG_FIELD_DIR . '/js/select2.js');

		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');

		$this->addExtraClass('silverstripe-tag-field');

		$this->setAttribute('multiple', 'multiple');

		$options = $this->getOptions();

		$this->setAttribute(
			'data-selected-values',
			join(',', $options)
		);

		$this->name = trim($this->name, '[]') . '[]';

		return parent::Field($properties);
	}

	/**
	 * @return array
	 */
	protected function getOptions() {
		$value = $this->selectedValues;

		if(!is_string($value) && !is_array($value) && !($value instanceof Traversable)) {
			throw new InvalidArgumentException('Value must be string, array or Traversable');
		}

		$source = $this->getSource();

		$selected = array();

		if(is_string($value)) {
			$selected = explode(',', $value);
		}

		if(is_array($value)) {
			$selected = $value;
		}

		if($value instanceof Traversable) {
			foreach($value as $key => $noop) {
				$selected[] = $key;
			}
		}

		$options = array();

		if($source) {
			foreach($source as $value => $title) {
				if(in_array($value, $selected)) {
					$options[] = $value;
				}
			}
		}

		return $options;
	}

	/**
	 * Save the current value of this TagField into a DataObject.
	 * If the field it is saving to is a has_many or many_many relationship,
	 * it is saved by setByIDList(), otherwise it creates a comma separated
	 * list for a standard DB text/varchar field.
	 *
	 * @param DataObjectInterface $record
	 */
	public function saveInto(DataObjectInterface $record) {
		parent::saveInto($record);

		$name = trim($this->name, '[]');

		$values = $this->Value();

		if(empty($values) || empty($record) || empty($this->relationTitleField)) {
			return;
		}

		if($record->hasMethod($name)) {
			$relation = $record->$name();

			$class = $relation->dataClass();

			foreach($values as $i => $value) {
				if(!is_numeric($value)) {
					$instance = new $class();
					$instance->{$this->relationTitleField} = $value;
					$instance->write();

					$values[$i] = $instance->ID;
				}
			}

			$relation->setByIDList($values);
		} else {
			$record->$name = implode(',', $values);
		}
	}
}
