<?php

namespace Fulcrum;

/**
 * Section class for the Fulcrum REST API (https://api.fulcrumapp.com/api/v2)
 *
 * @author Felix Langfeldt (Neverwoods)
 * @version 0.9.1
 */
class Section
{
    private $objStdSection = null;

    /**
     * Create a new Section object.
     *
     * @param \stdClass $objStdSection The JSON decoded standard class from the API.
     */
    public function __construct(\stdClass $objStdSection = null)
    {
        if (!is_null($objStdSection)) {
            $this->init($objStdSection);
        }
    }

    /**
     * Initialize the class with the standard class from the API.
     *
     * @param \stdClass $objStdSection The JSON decoded standard class from the API.
     */
    public function init(\stdClass $objStdSection)
    {
        $this->objStdSection = $objStdSection;
    }

    /**
     * Get an array of the immidiate sections in this section.
     *
     * @return \Fulcrum\Section[]
     */
    public function getSections()
    {
        $arrReturn = array();

        if (is_object($this->objStdSection)) {
            foreach ($this->objStdSection->elements as $objStdClass) {
                if ($objStdClass->type == "Section") {
                    $arrReturn[] = new Section($objStdClass);
                }
            }
        }

        return $arrReturn;
    }

    /**
     * Get an array of the immidiate sections in this section.
     *
     * @return \Fulcrum\Section[]
     */
    public function getSection($strDataName)
    {
    	$objReturn = null;
    	
        if (is_object($this->objStdSection)) {
            foreach ($this->objStdSection->elements as $objStdClass) {
                if ($objStdClass->data_name == $strDataName) {
                    $objReturn = new Section($objStdClass);
                    break;
                }
            }
        }
    	
    	return $objReturn;
    }

    /**
     * Get an array of the immidiate fields in this section.
     *
     * @param \Fulcrum\Record $objRecord
     * @return \Fulcrum\Field[]
     */
    public function getFields(Record $objRecord = null)
    {
        $arrReturn = array();

        if (is_object($this->objStdSection)) {
            foreach ($this->objStdSection->elements as $objStdClass) {
                if ($objStdClass->type != "Section") {
                    if (!is_null($objRecord)) {
                        $arrReturn[] = $objRecord->getField($objStdClass->data_name, true);
                    } else {
                        $arrReturn[] = new Field($objStdClass);
                    }
                }
            }
        }

        return $arrReturn;
    }

    /**
     * Magic call function to retrieve the Fulcrum properties of the section through "get" calls.
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
            if (isset($this->objStdSection->$strProperty)) {
                return $this->objStdSection->$strProperty;
            }
        }
    }
}
