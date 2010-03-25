<?php
if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

Class fieldDatetime extends Field {

    const SIMPLE = 0;
    const REGEXP = 1;
    const RANGE = 3;
    const ERROR = 4;
    private $key;

    /**
     * Initialize Datetime as unrequired field.
     */
    function __construct(&$parent) {
        parent::__construct($parent);
        $this->_name = __('Date/Time');
        $this->_required = false;
    }

    /**
	 * Allow data source filtering.
	 *
	 * @return boolean
	 *	true
     */
    function canFilter() {
        return true;
    }

    /**
	 * Allow data source sorting.
	 *
	 * @return boolean
	 *	true.
     */
    function isSortable() {
        return true;
    }

    /**
	 * Allow prepopulation of other fields.
	 *
	 * @return boolean
	 *	true.
     */
    function canPrePopulate() {
        return false;
    }

    /**
	 * Allow data source output grouping.
	 *
	 * @return boolean
	 *	true.
     */
    function allowDatasourceOutputGrouping() {
        return true;
    }

    /**
	 * Allow data source parameter output.
	 *
	 * @return boolean
	 *	true.
     */
    function allowDatasourceParamOutput() {
        return true;
    }

    /**
     * Displays setting panel in section editor.
     *
	 * @param XMLElement $wrapper -
	 *	parent element wrapping the field
	 * @param array $errors
	 *	array with field errors, $errors['name-of-field-element']
     */
    function displaySettingsPanel(&$wrapper, $errors=NULL) {

        // initialize field settings based on class defaults (name, placement)
        parent::displaySettingsPanel($wrapper, $errors);
        $this->appendShowColumnCheckbox($wrapper);

        // format
        $label = new XMLElement('label', __('Date format') . '<i>' . __('Use comma to separate date and time') . '</i>');
        $label->appendChild(
            Widget::Input('fields['.$this->get('sortorder').'][format]', $this->get('format') ? $this->get('format') : 'd MMMM yyyy, HH:mm')
        );
        $wrapper->appendChild($label);

        // prepopulate
        $label = Widget::Label();
        $input = Widget::Input('fields['.$this->get('sortorder').'][prepopulate]', 'yes', 'checkbox');
        if($this->get('prepopulate') != 'no') {
            $input->setAttribute('checked', 'checked');
        }
        $label->setValue(__('%s Pre-populate this field with today\'s date', array($input->generate())));
        $wrapper->appendChild($label);

        // allow multiple
        $label = Widget::Label();
        $input = Widget::Input('fields['.$this->get('sortorder').'][allow_multiple_dates]', 'yes', 'checkbox');
        if($this->get('allow_multiple_dates') != 'no') {
            $input->setAttribute('checked', 'checked');
        }
        $label->setValue(__('%s Allow multiple dates', array($input->generate())));
        $wrapper->appendChild($label);

    }

    /**
	 * Save fields settings in section editor. Rather than execute an update
	 * this uses delete and insert.
	 *
	 * @return boolean
	 *	true if the commit was successful, false otherwise.
     */
    function commit() {
        // prepare commit
        if(!parent::commit()) return false;
        $id = $this->get('id');
        if($id === false) return false;

        // set up fields
        $fields = array();
        $fields['field_id'] = $id;
        $fields['format'] = $this->get('format');
        if(empty($fields['format'])) $fields['format'] = 'd MMMM yyyy, HH:mm';
        $fields['prepopulate'] = ($this->get('prepopulate') ? $this->get('prepopulate') : 'no');
        $fields['allow_multiple_dates'] = ($this->get('allow_multiple_dates') ? $this->get('allow_multiple_dates') : 'no');

        // delete old field settings
		Symphony::Database()->query(
            "DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1"
        );

        // save new field setting
        return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());
	}

	/**
	 * Add the required header information to the page.
	 */
	protected function addHeader() {
        Administration::instance()->Page->addScriptToHead(URL . '/extensions/datetime/assets/jquery-ui.js', 100, true);
        Administration::instance()->Page->addScriptToHead(URL . '/extensions/datetime/assets/datetime.js', 201, false);
        Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/datetime/assets/datetime.css', 'screen', 202, false);
	}

	/**
	 * Add the title and the help to the panel.
	 *
	 * @param XMLElement &$wrapper
	 *	the parent element of the xml document to add this to.
	 */
	protected function addTitle(&$wrapper) {
        $wrapper->setValue($this->get('label') . '<i>' . __('Press <code>alt</code> to add a range') . '</i>');
	}

	/**
	 * Conditionally add the create new link to the display panel depending on
	 * whether this supports the addition of multiple dates.
	 *
	 * @param XMLElement &$wrapper
	 *	the display panel widget to conditionally append the new link to.
	 * @return XMLElement
	 *	the new link xml element added or null if none was added.
	 */
	protected function addPublishNewLink(&$wrapper) {
		if($this->get('allow_multiple_dates') == 'yes') {
			$output = new XMLElement('a', __('Add new date'), array('class' => 'new'));
			$wrapper->appendChild($output);
			return $output;
		}
		return null;
	}

	/**
	 * Accessor to the field name of this.
	 *
	 * @return string
	 *	the field name of this.
	 */
	protected function getFieldName() {
		return 'fields['  .$this->get('element_name') . ']';
	}

	/**
	 * Add the settings to the input parent xml element.
	 *
	 * @param XMLElement &$wrapper
	 *	the xml element to add this to.
	 * @return XMLElement
	 *	the xml element that was appended to the parent.
	 */
	protected function addPublishSettings(&$wrapper) {
        $setting = array(
            'DATE' => __('date'),
            'FROM' => __('from'),
            'START' => __('start'),
            'END' => __('end'),
            'FORMAT' => $this->get('format'),
            'multiple' => $this->get('allow_multiple_dates'),
            'prepopulate' => $this->get('prepopulate')
		);
		$output = Widget::Input($this->getFieldName() . '[settings]', str_replace('"', "'", json_encode($setting)), 'hidden');
		$wrapper->appendChild($output);
		return $output;
	}

	/**
	 * Add the label to the output by appending it to the input wrapper
	 * xml element. The construction of the label is dependent on the input form
	 * data.
	 *
	 * @param XMLElement &$wrapper
	 *	the xml element to append this to.
	 * @param array[string]string data
	 *	the submitted form data.
	 * @param number $index
	 *	the number of labels added thus far
	 * @return XMLElement
	 *	the label xml element added.
	 */
	protected function addPublishLabel(&$wrapper, $data, $index) {
		$label = null;
		if($data == null) {
			$label = Widget::Label(NULL, NULL, 'first last');
		} else {
			$label = Widget::Label();
			$class = "";
			if ($index == 1) {
				$class .= 'first';
			}
			if ($index == count($data['start'])) {
				$class .= ' last';
			}
			$label->setAttribute('class', $class);
		}
		$wrapper->appendChild($label);
		return $label;
	}

	/**
	 * Make sure that the start and end elements of the data array are themselves
	 * arrays provided the input data itself isn't null. The input data array is
	 * changed in place.
	 *
	 * @param array $data
	 *	the data to clean
	 */
	protected function ensureArrayValues($data) {
		if($data == null) {
			return;
		}
		if(!is_array($data['start'])) $data['start'] = array($data['start']);
		if(!is_array($data['end'])) $data['end'] = array($data['end']);
	}

	/**
	 * Add the start entry to the input xml element. Construct the start entry
	 * given the input form data and the current index into that data.
	 *
	 * @param XMLElement &$wrapper
	 *	the xml element to append the start entry to.
	 * @param array $data
	 *	the form data with which to construct the sart entry.
	 * @param number $index
	 *	the current start index.
	 * @return
	 *	the constructed start xml widget.
	 */
	protected function addPublishStart(&$wrapper, $data, $index) {
		$start = new XMLElement('span', null, array('class' => 'start'));
		$start->appendChild(new XMLElement('em', __('from'), array()));
		$start->appendChild(Widget::Input($this->getFieldName() . '[start][]', is_array($data['start']) ? $data['start'][$index - 1] : $data['start'], 'text'));
		$this->addPublishDelete($start);
		$wrapper->appendChild($start);
		return $start;
	}

	/**
	 * Add the start entry to the input xml element. Construct the start entry
	 * given the input form data and the current index into that data.
	 *
	 * @param XMLElement &$wrapper
	 *	the xml element to append the start entry to.
	 * @param array $data
	 *	the form data with which to construct the sart entry.
	 * @param number $index
	 *	the current start index.
	 * @return
	 *	the constructed start xml widget.
	 */
	protected function addPublishEnd(&$wrapper, $data, $index) {
		$end = new XMLElement('span', null, array('class' => 'end'));
		$end->appendChild(new XMLElement('em', __('to'), array()));
		if($data != null and isset($data['end']) and is_array($data['start'])) {
			// handle multiply set date-times in input data
			$end->appendChild(Widget::Input($this->getFieldName() . '[end][]', ($data['end'][$index - 1] == '0000-00-00 00:00:00') ? '' : $data['end'][$index - 1], 'text'));
		} elseif($data != null and isset($data['end'])) {
			// handle a single data-time in input data
			$end->appendChild(Widget::Input($this->getFieldName() . '[end][]', ($data['end'] == '0000-00-00 00:00:00') ? '' : $data['end'], 'text'));
		} else {
			// handle no date-times in input data
			$end->appendChild(Widget::Input($this->getFieldName() . '[end][]', '', 'text'));
		}
		$wrapper->appendChild($end);
		return $end;
	}

	/**
	 * Add the delete link to the input xml element.
	 *
	 * @param XMLElement &$wrapper
	 *	the xml element to append the start entry to.
	 * @return XMLELement
	 *	the constructed XMLElement.
	 */
	protected function addPublishDelete(&$wrapper) {
		$delete = new XMLElement('a', 'delete', array('class' => 'delete'));
		$wrapper->appendChild($delete);
		return $delete;
	}

    /**
	 * Displays publish panel in content area by appending the html elements
	 * to the input xml element and adding any css and js dependencies to the
	 * header of the page.
     *
	 * @param XMLElement $wrapper
	 *	the parent XMLElement to add the content of this to.
	 * @param array[string]string $data (optional)
	 *	the post data if any. this defaults to null.
	 * @param mixed $flagWithError (optional)
	 *	??
	 * @param string $fieldnamePrefix (optional)
	 *	the prefix to prepend to the name of this field for display. defaults
	 *	to null.
	 * @param string $fieldnameSuffix (optional)
	 *	the suffix to append to the name of this field for display. defaults
	 *	to null.
     */
	function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnameSuffix=NULL) {
		$this->addHeader();
		$this->addTitle($wrapper);
		$this->ensureArrayValues($data);

		$count = 1;
		do {
			$label = $this->addPublishLabel($wrapper, $data, $count);
			$this->addPublishStart($label, $data, $count);
			$this->addPublishEnd($label, $data, $count);
			$this->addPublishSettings($label, $count);
		} while($data != null && $count++ < count($data['start']));
		$this->addPublishNewLink($wrapper);
    }

    /**
	 * Prepares field values for database. This create an multidimensional
	 * array structure. The keys in the top-level array are the column
	 * names. The values are each an array of values for that column.
	 *
	 * @param array $data
	 *	the form input data to process.
	 * @param mixed $status
	 *	the status to return from this function.
	 * @param boolean $simulate (optional)
	 *	true if the processing should be simulated, false otherwise. this
	 *	defaults to false and is ignored in this particular implementation.
	 * @param mixed $entry_id (optional)
	 *	the id of the entry in the database to make.
	 * @return array[string]
	 *	the processed data as an array structured appropriately for insertion
	 *	into the database.
     */
    function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL) {

        $status = self::__OK__;
        if(!is_array($data) or empty($data)) return NULL;

        // Replace relative and locale date and time strings
        $english = array(
            'yesterday', 'today', 'tomorrow', 'now',
            'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday',
            'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat',
            'Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa',
            'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December',
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        );
        foreach($english as $string) {
            $locale[] = __($string);
        }

        $result = array('entry_id' => array(), 'start' => array(), 'end' => array());
        $count = count($data['start']);
        for($i = 0; $i < $count; $i++) {
            if(!empty($data['start'][$i])) {
                $result['entry_id'][] = $entry_id;
                $result['start'][] = date('c', strtotime(str_replace($locale, $english, $data['start'][$i])));
                $result['end'][] = empty($data['end'][$i]) ? '0000-00-00 00:00:00' : date('c', strtotime(str_replace($locale, $english, $data['end'][$i])));
            }
        }
        return $result;

    }

    /**
	 * Creates database field table.
	 *
	 * @return boolean
	 *	true if the creation was successful, false otherwise.
     */
    function createTable() {
        return Symphony::Database()->query(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				`id` int(11) unsigned NOT NULL auto_increment,
				`entry_id` int(11) unsigned NOT NULL,
				`start` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				`end` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (`id`),
            KEY `entry_id` (`entry_id`)
            );"
        );
    }

    /**
     * Prepare value for the content overview table.
     *
	 * @param array $data
	 *	the input form fata.
	 * @param XMLElement $link (optional)
	 *	the link to instantiate if this is in the first column. this defaults
	 *	to null.
	 * @return string|XMLElement
	 *	either this represented as a string, or an XMLElement.
     */
    function prepareTableValue($data, XMLElement $link=NULL) {
        $value = '';
        if(!is_array($data['start'])) $data['start'] = array($data['start']);
        if(!is_array($data['end'])) $data['end'] = array($data['end']);

        foreach($data['start'] as $id => $date) {
            if(empty($date)) continue;
            if($data['end'][$id] != "0000-00-00 00:00:00") {
                if($value != '') $value .= ', ';

				/* 	If it's not the same day
				**	from {date}{time} to {date}{time} else
				**	{date}{time} - {time}
				*/
				if(DateTimeObj::get("D M Y", strtotime($data['start'][$id])) != DateTimeObj::get("D M Y", strtotime($data['end'][$id]))) {
					$value .= '<span style="color: rgb(136, 136, 119);">' . __('from') . '</span> ' . DateTimeObj::get(__SYM_DATETIME_FORMAT__, strtotime($data['start'][$id]));
	                $value .= ' <span style="color: rgb(136, 136, 119);">' .__('to') . '</span> ' . DateTimeObj::get(__SYM_DATETIME_FORMAT__, strtotime($data['end'][$id]));
				} else {
					$value .= '<span style="color: rgb(136, 136, 119);">' . __('from') . '</span> ' . DateTimeObj::get(__SYM_DATETIME_FORMAT__, strtotime($data['start'][$id]));
					$value .= ' <span style="color: rgb(136, 136, 119);">-</span> ' . DateTimeObj::get(__SYM_TIME_FORMAT__, strtotime($data['end'][$id]));
				}

            } else {
                if($value != '') $value .= ', ';
                $value .= DateTimeObj::get(__SYM_DATETIME_FORMAT__, strtotime($data['start'][$id]));
            }
        }
        return parent::prepareTableValue(array('value' => $value), $link);
    }

    /**
     * Build data source sorting sql.
     *
     * @param string $joins
     * @param string $where
     * @param string $sort
     * @param string $order
     */
    function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC') {
        $joins .= "LEFT OUTER JOIN `tbl_entries_data_".$this->get('id')."` AS `dt` ON (`e`.`id` = `dt`.`entry_id`) ";
        $sort = 'ORDER BY ' . (in_array(strtolower($order), array('random', 'rand')) ? 'RAND()' : "`dt`.`start` $order");
    }

    /**
     * Build data source retrival sql.
     *
     * @param array $data
     * @param string $joins
     * @param string $where
     * @param boolean $andOperation
     */
    function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {

        if(self::isFilterRegex($data[0])) return parent::buildDSRetrivalSQL($data, $joins, $where, $andOperation);

        $parsed = array();

        foreach($data as $string) {
            $type = self::__parseFilter($string);
            if($type == self::ERROR) return false;
            if(!is_array($parsed[$type])) $parsed[$type] = array();
            $parsed[$type][] = $string;
        }

        foreach($parsed as $type => $value) {
            switch($type) {
                case self::RANGE:
                    if(!empty($value)) $this->__buildRangeFilterSQL($value, $joins, $where, $andOperation);
                    break;

                case self::SIMPLE:
                    if(!empty($value)) $this->__buildSimpleFilterSQL($value, $joins, $where, $andOperation);
                    break;
            }
        }

        return true;

    }

    /**
     * Build sql for single dates.
     *
     * @param array $data
     * @param string $joins
     * @param string $where
     * @param boolean $andOperation
     */
    protected function __buildSimpleFilterSQL($data, &$joins, &$where, $andOperation = false) {

        $field_id = $this->get('id');

        $connector = ' OR '; // filter separated with commas
        if($andOperation == 1) $connector = ' AND '; // filter conntected with plus signs

        foreach($data as $date) {
            $tmp[] = "'" . DateTimeObj::get('Y-m-d', strtotime($date)) . "' BETWEEN
                DATE_FORMAT(`t$field_id".$this->key."`.start, '%Y-%m-%d') AND
                DATE_FORMAT(`t$field_id".$this->key."`.end, '%Y-%m-%d')";
        }
        $joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id".$this->key."` ON `e`.`id` = `t$field_id".$this->key."`.entry_id ";
        $where .= " AND (".@implode($connector, $tmp).") ";
        $this->key++;

    }

    /**
     * Build sql for dates ranges.
     *
     * @param array $data
     * @param string $joins
     * @param string $where
     * @param boolean $andOperation
     */
    protected function __buildRangeFilterSQL($data, &$joins, &$where, $andOperation=false) {

        $field_id = $this->get('id');

        $connector = ' OR '; // filter separated with commas
        if($andOperation == 1) $connector = ' AND '; // filter conntected with plus signs

        foreach($data as $date) {
            $tmp[] = "(DATE_FORMAT(`t$field_id".$this->key."`.start, '%Y-%m-%d') BETWEEN
                '" . DateTimeObj::get('Y-m-d', strtotime($date['start'])) . "' AND
                '" . DateTimeObj::get('Y-m-d', strtotime($date['end'])) . "' OR
                DATE_FORMAT(`t$field_id".$this->key."`.end, '%Y-%m-%d') BETWEEN
                '" . DateTimeObj::get('Y-m-d', strtotime($date['start'])) . "' AND
                '" . DateTimeObj::get('Y-m-d', strtotime($date['end'])) . "')";
        }
        $joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id".$this->key."` ON `e`.`id` = `t$field_id".$this->key."`.entry_id ";
        $where .= " AND (".@implode($connector, $tmp).") ";
        $this->key++;

    }

    /**
     * Clean up date string.
     * This function is a copy from the core date field.
     *
     * @param string $string
     */
    protected static function __cleanFilterString($string) {
        $string = trim($string);
        $string = trim($string, '-/');
        return $string;
    }

    /**
     * Parse filter string for shorthand dates and ranges.
     * This function is a copy from the core date field.
     *
     * @param string $string
     */
    protected static function __parseFilter(&$string) {

        $string = self::__cleanFilterString($string);

        // Check its not a regexp
        if(preg_match('/^regexp:/i', $string)) {
            $string = str_replace('regexp:', '', $string);
            return self::REGEXP;
        }

        // Look to see if its a shorthand date (year only), and convert to full date
        elseif(preg_match('/^(1|2)\d{3}$/i', $string)) {
            $string = "$string-01-01 to $string-12-31";
        }

        elseif(preg_match('/^(earlier|later) than (.*)$/i', $string, $match)) {

            $string = $match[2];

            if(!self::__isValidDateString($string)) return self::ERROR;

            $time = strtotime($string);

            switch($match[1]){
                case 'later': $string = DateTimeObj::get('Y-m-d H:i:s', $time+1) . ' to 2038-01-01'; break;
                case 'earlier': $string = '1970-01-03 to ' . DateTimeObj::get('Y-m-d H:i:s', $time-1); break;
            }

        }

        // Look to see if its a shorthand date (year and month), and convert to full date
        elseif(preg_match('/^(1|2)\d{3}[-\/]\d{1,2}$/i', $string)) {

            $start = "$string-01";

            if(!self::__isValidDateString($start)) return self::ERROR;

            $string = "$start to $string-" . date('t', strtotime($start));
        }

        // Match for a simple date (Y-m-d), check its ok using checkdate() and go no further
        elseif(!preg_match('/to/i', $string)) {

            if(!self::__isValidDateString($string)) return self::ERROR;

            $string = DateTimeObj::get('Y-m-d H:i:s', strtotime($string));
            return self::SIMPLE;

        }

        // Parse the full date range and return an array

        if(!$parts = preg_split('/to/', $string, 2, PREG_SPLIT_NO_EMPTY)) return self::ERROR;

        $parts = array_map(array('self', '__cleanFilterString'), $parts);

        list($start, $end) = $parts;

        if(!self::__isValidDateString($start) || !self::__isValidDateString($end)) return self::ERROR;

        $string = array('start' => $start, 'end' => $end);

        return self::RANGE;
    }

    /**
     * Validate date.
     * This function is a copy from the core date field.
     *
     * @param string $string
     */

    protected static function __isValidDateString($string) {

        $string = trim($string);

        if(empty($string)) return false;

        // Its not a valid date, so just return it as is
        if(!$info = getdate(strtotime($string))) return false;
        elseif(!checkdate($info['mon'], $info['mday'], $info['year'])) return false;

        return true;
    }

    /**
     * Group records by year and month (calendar view).
     *
     * @param $wrapper
     */

    function groupRecords($records) {

        if(!is_array($records) || empty($records)) return;

        $groups = array('year' => array());

        // walk through dates
        foreach($records as $entry) {
            $data = $entry->getData($this->get('id'));
            if(!is_array($data['start'])) $data['start'] = array($data['start']);
            if(!is_array($data['end'])) $data['end'] = array($data['end']);
            // create calendar
            foreach($data['start'] as $id => $start) {
                $start = date('Y-m-01', strtotime($start));
                if($data['end'][$id] == "0000-00-00 00:00:00") $data['end'][$id] = $start;
                $end = date('Y-m-01', strtotime($data['end'][$id]));
                $starttime = strtotime($start);
                $endtime = strtotime($end);
                // find matching months
                while($starttime <= $endtime) {
                    $year = date('Y', $starttime);
                    $month[1] = date('n', $starttime);
                    $month[2] = date('m', $starttime);
                    // add entry
                    $groups['year'][$year]['attr']['value'] = $year;
                    $groups['year'][$year]['groups']['month'][$month[1]]['attr']['value'] = $month[2];
                    $groups['year'][$year]['groups']['month'][$month[1]]['records'][] = $entry;
                    // jump to next month
                    $starttime = strtotime(date('Y-m-01', $starttime) . ' +1 month');
                }
            }
        }

        // sort years and months
        ksort($groups['year']);
        foreach($groups['year'] as $year) {
            $current = $year['attr']['value'];
            ksort($groups['year'][$current]['groups']['month']);
        }

        // return calendar groups
        return $groups;

	}

	/**
	 * Add a formatted time element to the input xml element.
	 *
	 * @param XMLElement $wrapper
	 *	the xml element to append the formatted time element to.
	 * @param string $name
	 *	the name of the xml element.
	 * @param int $time
	 *	the timestamp formatted data for the current entry.
	 * @return XMLElement
	 *	the constructed time element.
	 */
	protected function addFormattedTime(&$wrapper, $name, $time) {
		$element = new XMLElement($name, DateTimeObj::get('Y-m-d', $time), array(
							'iso' => DateTimeObj::get('c', $time),
							'time' => DateTimeObj::get('H:i', $time),
							'weekday' => DateTimeObj::get('w', $time),
							'offset' => DateTimeObj::get('O', $time)
						)
					);
		$wrapper->appendChild($element);
		return $element;
	}

	/**
	 * Add a formatted date-time element to the input xml element.
	 *
	 * @param XMLElement $wrapper
	 *	the xml element to append the formatted date element to.
	 *	data from the input data
	 * @param number $index
	 *	the index of the current entry
	 * @param array $entry
	 *	the start and end entry array.
	 * @return XMLElement
	 *	the constructed date element.
	 */
	protected function addFormattedDateTime(&$wrapper, $index, $entry) {
        $date = new XMLElement('date');
		$date->setAttribute('timeline', $index);
		// set the default atrtribute type to exact
		$date->setAttribute('type', 'exact');
		$this->addFormattedTime($date, 'start', strtotime($entry['start']));
        if($entry['end'] != "0000-00-00 00:00:00") {
			$this->addTime($date, 'end', strtotime($entry['end']));
			// over write the default type as this is a range.
			$date->setAttribute('type', 'range');
		}
		$wrapper->appendChild($date);
		return $date;
	}

	/**
	 * Transform the input data array structure. The default output of symphony is an
	 * array of column names with a row number indexed array of values for that
	 * column. We transform this structure into an array of entries, each entry
	 * being an array of that associates the column name to its data. For example,
	 * if the structure in the database is:
	 * +----+----------+---------------------+---------------------+
	 * | id | entry_id | start               | end                 |
	 * +----+----------+---------------------+---------------------+
	 * | 30 |        8 | 2010-08-03 10:52:00 | 0000-00-00 00:00:00 |
	 * | 29 |        8 | 2010-08-27 10:50:00 | 0000-00-00 00:00:00 |
	 * | 28 |        8 | 2010-05-03 14:37:00 | 2010-05-21 14:38:00 |
	 * | 27 |        8 | 2010-03-05 14:33:00 | 0000-00-00 00:00:00 |
	 * +----+----------+---------------------+---------------------+
	 * symphony would return:
	 * array
	 *  'start' => 
	 *    array
	 *      0 => string '2010-03-05 14:33:00' (length=19)
	 *      1 => string '2010-05-03 14:37:00' (length=19)
	 *      2 => string '2010-08-27 10:50:00' (length=19)
	 *      3 => string '2010-08-03 10:52:00' (length=19)
	 *  'end' => 
	 *    array
	 *      0 => string '0000-00-00 00:00:00' (length=19)
	 *      1 => string '2010-05-21 14:38:00' (length=19)
	 *      2 => string '0000-00-00 00:00:00' (length=19)
	 *      3 => string '0000-00-00 00:00:00' (length=19)
	 * however, because we wish to output the above in order of start time
	 * and there is no separate concept of array position aside from index
	 * in php we cannot do so. therefore we transform the above into:
	 * array
	 *	0 => 
	 *    array
	 *		'start' => string '2010-03-05 14:33:00' (length=19)
	 *		'end' => string '0000-00-00 00:00:00' (length=19)
	 *	1 =>
	 *	  array
	 *		'start' => string '2010-05-03 14:37:00' (length=19)
	 *		'end' => string '2010-05-21 14:38:00' (length=19)
	 *	2
	 *	  array
	 *		'start' => string '2010-08-03 10:52:00' (length=19)
	 *		'end'  => string '0000-00-00 00:00:00' (length=19)
	 *	3
	 *    array
	 *		'start' => string '2010-08-27 10:50:00' (length=19)
	 *		'end' => string '0000-00-00 00:00:00' (length=19)
	 *	which is in start time order.
	 *
	 * @param array $data
	 *	the data to transform.
	 * @return array
	 *	the transformed array.
	 */
	protected function toEntryArray($data) {
		$result = array();
		// iterate over the entries
		foreach($data['start'] as $id => $date) {
			// iterate over the key types
			$entry = array();
			foreach($data as $key => $value) {
				$entry[$key] = $data[$key][$id];
			}
			$result[$id] = $entry;
		}
		// create an anonymous sort function that compares the arrays based on their
		// start value. in php 5.3 create_function wouldn't be necessary. use usort and not
		// uasort as we want the indeces to change. use strtotime to ensure that the
		// comparison is not based on teh string representation.
		usort($result, create_function('$a, $b', 'return strtotime($a[\'start\']) - strtotime($b[\'start\']);'));
		return $result;
	}

    /**
	 * Generate data source output. Given a simple array structure that maps columns
	 * to arrays of their values, construct the xml output of this field.
     *
	 * @param XMLElement $wrapper
	 *	the xml element to append the formatted output to.
	 * @param array $data
	 *	the array structure returned from the database for this instance.
	 * @param boolean $encode (optional)
	 *	true if this should be html encoded, false otherwise.
     */
	public function appendFormattedElement(&$wrapper, $data, $encode=false) {
		$this->ensureArrayValues($data);
		$datetime = new XMLElement($this->get('element_name'));
		$transformed = $this->toEntryArray($data);

		// generate XML
		foreach ($transformed as $index => $entry) {
			$this->addFormattedDateTime($datetime, $index, $entry);
		}
		// append date and time to data source
		$wrapper->appendChild($datetime);
    }

    /**
     * Generate parameter pool values.
     *
     * @param array $data
     */

    public function getParameterPoolValue($data) {

        $start = array();
        foreach($data['start'] as $date) {
            $start[] = DateTimeObj::get('Y-m-d H:i:s', strtotime($date));
        }

        return implode(',', $start);

    }

    /**
     * Sample markup for the event editor.
     */

    public function getExampleFormMarkup() {

        $label = Widget::Label($this->get('label'));
        $label->appendChild(Widget::Input('fields['.$this->get('element_name').'][start][]'));
        return $label;

    }
}
