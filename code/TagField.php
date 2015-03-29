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
	 * @param string      $name
	 * @param null|string $title
	 * @param array       $source
	 * @param string      $value
	 * @param null|string $relationTitleField
	 */
	public function __construct($name, $title = null, $source = array(), $value = '', $relationTitleField = null) {
		$this->relationTitleField = $relationTitleField;

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

		$this->addExtraClass('silverstripe-tag-field');

		$this->setAttribute('multiple', 'multiple');

		$this->setName(trim($this->name, '[]') . '[]');

		return parent::Field($properties);
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
		if(!$record) {
			goto saveInto;
		}

		$values = $this->Value();

		if(empty($values)) {
			goto saveInto;
		}

		if(empty($this->relationTitleField)) {
			goto saveInto;
		}

		$name = trim($this->name, '[]');

		if($record->hasMethod($name)) {
			$relation = $record->$name();

			$class = $relation->dataClass();

			foreach($values as $i => $value) {
				if(!is_numeric($value)) {
					$instance = new $class();
					$instance->{$this->relationTitleField} = $value;

					$values[$i] = $instance->write();
				}
			}

			$relation->setByIDList($values);
		} else {
			$record->$name = implode(',', $values);
		}

		goto saveInto;

		saveInto:
		parent::saveInto($record);
	}
}
