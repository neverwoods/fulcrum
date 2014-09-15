<?php

namespace Fulcrum;

/**
 * Field class for the Fulcrum REST API (https://api.fulcrumapp.com/api/v2)
 *
 * @author Felix Langfeldt (Neverwoods)
 * @version 0.9.1
 */
class Field
{
    private $objStdField = null;
    private $objStdFieldValue = null;

    /**
     * Create a new Field object.
     *
     * @param \stdClass              $objStdField      The JSON decoded standard class from the API.
     * @param \stdClass|array|string $objStdFieldValue The optional value for this field. Can be a \stdClass if
     *                                                 SelectField, an array if PhotoField or a string if TextField.
     */
    public function __construct(\stdClass $objStdField = null, $objStdFieldValue = null)
    {
        if (!is_null($objStdField)) {
            $this->init($objStdField, $objStdFieldValue);
        }
    }

    /**
     * Initialize the class with the standard class from the API.
     *
     * @param \stdClass              $objStdField      The JSON decoded standard class from the API.
     * @param \stdClass|array|string $objStdFieldValue The optional value for this field. Can be a \stdClass if
     *                                                 SelectField, an array if PhotoField or a string if TextField.
     */
    public function init(\stdClass $objStdField, $objStdFieldValue = null)
    {
        $this->objStdField = $objStdField;
        $this->objStdFieldValue = $objStdFieldValue;
    }

    /**
     * Get the value for this field.
     *
     * @param  boolean $blnFormatArray Indicate if an array value should be formated as a string.
     * @return null|array|\Fulcrum\Photo|string Each field type returns a different value.
     */
    public function getValue($blnFormatArray = true)
    {
        $varReturn = null;

        if (is_object($this->objStdFieldValue)) {
            if ($this->objStdField->type == "SignatureField") {
                $varReturn = new Photo($this->objStdFieldValue);
            } else {
                $varReturn = $this->prepareObjectValue($this->objStdFieldValue);

                if ($blnFormatArray) {
                    $varReturn = (is_array($varReturn)) ? implode(", ", $varReturn) : $varReturn;
                }
            }
        } elseif (is_array($this->objStdFieldValue)) {
            $varReturn = array();
            foreach ($this->objStdFieldValue as $objStdPhoto) {
                $varReturn[] = new Photo($objStdPhoto);
            }
        } else {
            $varReturn = $this->objStdFieldValue;
        }

        return $varReturn;
    }

    /**
     * Magic call function to retrieve the Fulcrum properties of the field through "get" calls.
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
            if (isset($this->objStdField->$strProperty)) {
                return $this->objStdField->$strProperty;
            }
        }
    }

    private function prepareObjectValue(\stdClass $objFieldValue)
    {
        $varReturn = null;

        if (isset($objFieldValue->choice_values) && count($objFieldValue->choice_values) > 0) {
            if (count($objFieldValue->choice_values) == 1) {
                $varReturn = $objFieldValue->choice_values[0];
            } else {
                $varReturn = $objFieldValue->choice_values;
            }
        }

        if (isset($objFieldValue->other_values) && count($objFieldValue->other_values) > 0) {
            if (count($objFieldValue->other_values) == 1) {
                if (isset($varReturn)) {
                    if (is_array($varReturn)) {
                        array_push($varReturn, $objFieldValue->other_values[0]);
                    } else {
                        $varReturn = array($varReturn, $objFieldValue->other_values[0]);
                    }
                } else {
                    $varReturn = $objFieldValue->other_values[0];
                }
            } else {
                if (isset($varReturn)) {
                    if (is_array($varReturn)) {
                        $varReturn = array_merge($varReturn, $objFieldValue->other_values);
                    } else {
                        array_unshift($objFieldValue->other_values, $varReturn);
                    }
                } else {
                    $varReturn = $objFieldValue->other_values;
                }
            }
        }

        return $varReturn;
    }
}
