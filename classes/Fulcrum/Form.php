<?php

namespace Fulcrum;

/**
 * Form class for the Fulcrum REST API (https://api.fulcrumapp.com/api/v2)
 *
 * @author Felix Langfeldt (Neverwoods)
 * @version 0.9.1
 */
class Form
{
    private $objStdForm = null;
    private $keyMap = array();

    /**
     * Create a new Form object.
     *
     * @param \stdClass $objStdForm The JSON decoded standard class from the API.
     */
    public function __construct(\stdClass $objStdForm = null)
    {
        if (!is_null($objStdForm)) {
            $this->init($objStdForm);
        }
    }

    /**
     * Initialize the class with the standard class from the API.
     *
     * @param \stdClass $objStdForm The JSON decoded standard class from the API.
     */
    public function init(\stdClass $objStdForm)
    {
        $this->objStdForm = $objStdForm;
    }

    /**
     * Get the standard JSON decoded class from the API.
     *
     * @return \stdClass
     */
    public function getRawClass()
    {
        return $this->objStdForm;
    }

    /**
     * Retrieve an array of all records for this form.
     *
     * @return \Fulcrum\Record[]
     */
    public function getRecords()
    {
        $objReturn = new Collection();

        if (!is_null($this->objStdForm)) {
            $objReturn = API::getInstance()->getRecords($this->objStdForm->id);
        }

        return $objReturn;
    }

    public function getField($strFieldName)
    {
        $objReturn = null;

        $arrKeyMap = $this->getKeyMap();
        if (isset($arrKeyMap[$strFieldName])) {
            $objReturn = new Field($arrKeyMap[$strFieldName]["meta"]);
        }

        return $objReturn;
    }

    /**
     * Get a key => value map of all fields where the key is the data_name and the value is an array with 2 keys.
     * "key" is the key of the field in the form and "meta" is the standard Fulcrum class of the field.
     *
     * @return array
     */
    public function getKeyMap()
    {
        if (count($this->keyMap) == 0) {
            $this->keyMap = self::mapKeysToDataNames($this->objStdForm);
        }

        return $this->keyMap;
    }

    /**
     * Create a new record for this form.
     *
     * @param  array           $arrFields An array with values for fields in the new record.
     * @return \Fulcrum\Record
     */
    public function createRecord($arrFields, $strApiKey = null)
    {
        $objReturn = null;

        if (!is_null($this->objStdForm)) {
            $arrFormFields = $this->mapFieldsToKeys($arrFields);
            $arrSettings = array("record" => array(
                "longitude" => (isset($arrFields["location"]["lng"])) ? $arrFields["location"]["lng"] : -68.899040,
                "latitude" => (isset($arrFields["location"]["lat"])) ? $arrFields["location"]["lat"] : 12.115844,
                "form_id" => $this->objStdForm->id,
                "form_values" => $arrFormFields
            ));
            
			if (isset($arrFields["assigned_to_id"])) {
            	$arrSettings["assigned_to_id"] = $arrFields["assigned_to_id"];
			}

            $objReturn = API::getInstance()->createRecord($arrSettings, $strApiKey);
        }

        return $objReturn;
    }

    /**
     * Get an array of the immidiate sections in this form.
     *
     * @return \Fulcrum\Section[]
     */
    public function getSections()
    {
        $arrReturn = array();

        if (!is_null($this->objStdForm)) {
            foreach ($this->objStdForm->elements as $objStdClass) {
                if ($objStdClass->type == "Section") {
                    $arrReturn[] = new Section($objStdClass);
                }
            }
        }

        return $arrReturn;
    }

    /**
     * Find a section recursively by data name.
     *
     * @param string $strSectionName
     * @param \Fulcrum\Section $objSection
     * @return \Fulcrum\Section
     */
    public function getSection($strSectionName, Section $objSection = null)
    {
        $objReturn = null;

        $objSubject = (is_object($objSection)) ? $objSection : $this;
        $objSections = $objSubject->getSections();
        foreach ($objSections as $objSection) {
            if ($objSection->getDataName() == $strSectionName) {
                return $objSection;
            }
        }

        //*** Recursive.
        foreach ($objSections as $objSection) {
            $objReturn = $this->getSection($strSectionName, $objSection);
            if (is_object($objReturn)) {
                return $objReturn;
            }
        }

        return $objReturn;
    }

    /**
     * Magic call function to retrieve the Fulcrum properties of the form through "get" calls.
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
            if (isset($this->objStdForm->$strProperty)) {
                return $this->objStdForm->$strProperty;
            }
        }
    }

    /**
     * Convert a key name => value array to a field id => value array.
     *
     * @param  array $arrFields
     * @return array
     */
    public function mapFieldsToKeys($arrFields)
    {
        $arrReturn = array();

        $arrMap = $this->getKeyMap();
        foreach ($arrFields as $key => $value) {
            if (isset($arrMap[$key])) {
                switch ($arrMap[$key]["meta"]->type) {
                    case "ChoiceField":
                        $arrReturn[$arrMap[$key]["key"]] = ["choice_values" => [$value]];

                        break;
                    default:
                        $arrReturn[$arrMap[$key]["key"]] = $value;
                }
            }
        }

        return $arrReturn;
    }

    /**
     * Map Fulcrum keys to data names.
     *
     * @param  \stdClass $objSection
     * @return Ambigous  <multitype:, multitype:multitype:NULL unknown  >
     */
    protected static function mapKeysToDataNames(\stdClass $objSection)
    {
        $arrReturn = array();

        if (is_object($objSection) && isset($objSection->elements)) {
            foreach ($objSection->elements as $objStdClass) {
                if (isset($objStdClass->data_name)) {
                    $arrReturn[$objStdClass->data_name] = array("key" => $objStdClass->key, "meta" => $objStdClass);
                }

                if (isset($objStdClass->elements)) {
                    $arrReturn = array_merge($arrReturn, self::mapKeysToDataNames($objStdClass));
                }
            }
        }

        return $arrReturn;
    }
}
