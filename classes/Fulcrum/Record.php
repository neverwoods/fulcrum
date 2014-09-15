<?php

namespace Fulcrum;

/**
 * Record class for the Fulcrum REST API (https://api.fulcrumapp.com/api/v2)
 *
 * @author Felix Langfeldt (Neverwoods)
 * @version 0.9.1
 */
class Record
{
    private $objStdRecord = null;
    private $objForm = null;
    private $keyMap = array();

    /**
     * Create a new Record object.
     *
     * @param \stdClass     $objStdRecord The JSON decoded standard class from the API.
     * @param \Fulcrum\Form $objForm      The Form object this record belongs to.
     */
    public function __construct(\stdClass $objStdRecord = null, Form $objForm = null)
    {
        if (!is_null($objStdRecord)) {
            $this->init($objStdRecord, $objForm);
        }
    }

    /**
     * Initialize the class with the standard class from the API.
     *
     * @param \stdClass     $objStdRecord The JSON decoded standard class from the API.
     * @param \Fulcrum\Form $objForm      The Form object this record belongs to.
     */
    public function init(\stdClass $objStdRecord, Form $objForm = null)
    {
        $this->objStdRecord = $objStdRecord;
        $this->objForm = $objForm;
        $this->keyMap = $objForm->getKeyMap();
    }

    /**
     * Get the standard JSON decoded class from the API.
     *
     * @return \stdClass
     */
    public function getRawClass()
    {
        return $this->objStdRecord;
    }

    /**
     * Get a Field object from this record by Fulcrum data name.
     *
     * @param  string         $strDataName    The unique data name for a specific field.
     * @param  boolean        $blnReturnEmpty Indicate if a field with an empty value should be returned.
     * @return \Fulcrum\Field
     */
    public function getField($strDataName, $blnReturnEmpty = false)
    {
        $objReturn = null;

        if (isset($this->keyMap[$strDataName])) {
            $objStdFieldValue = $this->lookupFieldValue($this->keyMap[$strDataName]["key"]);
            if (!is_null($objStdFieldValue) || $blnReturnEmpty) {
                $objReturn = new Field($this->keyMap[$strDataName]["meta"], $objStdFieldValue);
            }
        }

        return $objReturn;
    }

    /**
     * Get the Form object from this Record.
     *
     * @return \Fulcrum\Form
     */
    public function getForm()
    {
        return $this->objForm;
    }

    /**
     * Delete the record from Fulcrum.
     */
    public function delete()
    {
        API::getInstance()->deleteRecord($this->getId());
    }

    /**
     * Update the fields of a record.
     *
     * @param array $arrFields
     */
    public function update($arrFields)
    {
        $arrFormFields = $this->objForm->mapFieldsToKeys($arrFields);
        if (!is_array($arrFormFields)) {
            $arrFormFields = [];
        }

        $arrSettings = array("record" => array(
            "longitude" => $this->getLongitude(),
            "latitude" => $this->getLatitude(),
            "form_id" => $this->objForm->getId(),
            "form_values" => $arrFormFields
        ));

        if (isset($arrFields["assigned_to_id"])) {
            $arrSettings["record"]["assigned_to_id"] = $arrFields["assigned_to_id"];
        }

        $objReturn = API::getInstance()->updateRecord($this->getId(), $arrSettings);
    }

    /**
     * Save the record to Fulcrum.
     */
    public function save()
    {
        if (!is_null($this->objStdRecord)) {
        	if ($this->getId() != "") {
            	$objReturn = API::getInstance()->updateRecord($this->getId(), $this->objStdRecord);
        	} else {
        		$objReturn = API::getInstance()->insertRecord($this->objStdRecord);
        	}
        }

        return $objReturn;
    }

    /**
     * Magic call function to retrieve the Fulcrum properties of the record through "get" calls.
     * Underscores ("_") get translated to uppercase. So data_name becomes getDataName().
     *
     * @param string $name
     * @param array  $arguments
     */
    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) == "get") {
            //*** Remove "get" and lower case first character.
            $strProperty = lcfirst(substr($name, 3));

            //*** Convert from camelcase to underscore case.
            $strProperty = preg_replace_callback(
                '/([A-Z])/',
                function ($c) {
                    return ("_" . strtolower($c[1]));
                },
                $strProperty
            );

            //*** Check if the property exists.
            if (isset($this->objStdRecord->$strProperty)) {
                return $this->objStdRecord->$strProperty;
            }
        }

        if (substr($name, 0, 3) == "set") {
            //*** Remove "set" and lower case first character.
            $strProperty = lcfirst(substr($name, 3));

            //*** Convert from camelcase to underscore case.
            $strProperty = preg_replace_callback(
                '/([A-Z])/',
                function ($c) {
                    return ("_" . strtolower($c[1]));
                },
                $strProperty
            );

            //*** Check if the property exists.
            if (isset($this->objStdRecord->$strProperty)) {
                $this->objStdRecord->$strProperty = (is_array($arguments) && count($arguments) > 0) ? $arguments[0] : null;
            }
        }
    }

    public function lookupFieldValue($strFieldId)
    {
        $strReturn = null;

        if (is_object($this->objStdRecord) && isset($this->objStdRecord->form_values->$strFieldId)) {
            $strReturn = $this->objStdRecord->form_values->$strFieldId;
        }

        return $strReturn;
    }
}
