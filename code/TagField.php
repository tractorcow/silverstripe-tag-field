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
	 * Determine lookup case for searching for existing objects
	 *
	 * @var boolean
	 */
	protected $caseSensitive = false;

	/**
	 * @param string      $name
	 * @param null|string $title
	 * @param array       $source
	 * @param string      $value
	 * @param string      $relationTitleField
	 * @param boolean     $caseSensitive
	 */
	public function __construct($name, $title = null, $source = array(), $value = '', $relationTitleField = 'Title', $caseSensitive = false) {
		$this->relationTitleField = $relationTitleField;
		$this->caseSensitive = $caseSensitive;

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

		$properties = array_merge($properties, array(
			'Options' => new ArrayList($options)
		));

		return $this
			->customise($properties)
			->renderWith($this->getTemplates());
	}

	/**
	 * Gets the source
	 *
	 * @return array|DataList
	 */
	public function getSource() {
		return $this->source;
	}

	/**
	 * Loads the related record values into this field. TagField can be uploaded
	 * in one of three ways:
	 *
	 *  - By passing in a list of object IDs in the $value parameter (an array with a single
	 *    key 'Files', with the value being the actual array of IDs).
	 *  - By passing in an explicit list of File objects in the $record parameter, and
	 *    leaving $value blank.
	 *  - By passing in a dataobject in the $record parameter, from which file objects
	 *    will be extracting using the field name as the relation field.
	 *
	 * Each of these methods will update both the items (list of File objects) and the
	 * field value (list of file ID values).
	 *
	 * @param array $value Array of submitted form data, if submitting from a form
	 * @param array|DataObject|SS_List $record Full source record, either as a DataObject,
	 * SS_List of items, or an array of submitted form data
	 * @return UploadField Self reference
	 */
	public function setValue($value, $record = null) {
		// If we're not passed a value directly, we can attempt to infer the field
		// value from the second parameter by inspecting its relations

		// Determine format of presented data
		if(empty($value) && $record) {
			// If a record is given as a second parameter, but no submitted values,
			// then we should inspect this instead for the form values

			if(($record instanceof DataObject)
				&& $record->hasMethod($this->getName())
			) {
				$value = $record
					->{$this->getName()}()
					->getIDList();
			} elseif($record instanceof SS_List) {
				// If directly passing a list then save the items directly
				$value = $record->column('ID');
			}
		}

		// Set value using parent
		return parent::setValue($value, $record);
	}

	public function getAttributes() {
		// Rename HTML fieldname to include []
		return array_merge(
			parent::getAttributes(),
			array('name' => $this->getName().'[]')
		);
	}

	/**
	 * @return array
	 */
	protected function getOptions() {

		// @todo make this function return an array of ArrayData, with the appropriate selected = boolean
		// flag for each. See ListboxField::Field for example

		$value = $this->Value();
		// @todo Like we do in saveInto, should we map titles to dataobjects here?

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
				// @todo - make this work for partially saved data
				// at this point $value could be an ID or title still
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

		$values = $this->Value();

		if(empty($values) || empty($record) || empty($this->relationTitleField)) {
			return;
		}

		if($record->hasMethod($name)) {
			// Inspect relation on current object
			$relation = $record->$name();
			$class = $relation->dataClass();

			// Check datasource and current relation
			$dataSource = $this->source instanceof DataList
				? $this->source
				: DataObject::get($class);

			foreach($values as $i => $value) {
				
				// If passed a string value, assume it is a new tag to be added
				if(!is_numeric($value)) {
					
					// Determine if the datasource contains an existing item
					$caseFilter = $this->caseSensitive ? 'case' : 'nocase';
					$instance = $dataSource->filter(array(
						"{$this->relationTitleField}:{$caseFilter}" => $value
					))->first();

					// Create new instance if not found
					if(!$instance) {
						$instance = new $class();
						$instance->{$this->relationTitleField} = $value;
						$instance->write();
						// Ensure this item exists in the source relation (e.g. parent blog)
						$dataSource->add($instance);
					}

					// Add to datasource and to the current selection
					$values[$i] = $instance->ID;
				}
			}

			$relation->setByIDList($values);
		} else {
			$record->$name = implode(',', $values);
		}
	}
}
