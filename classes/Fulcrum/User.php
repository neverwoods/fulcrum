<?php

namespace Fulcrum;

/**
 * User class for the Fulcrum REST API (https://api.fulcrumapp.com/api/v2)
 *
 * @author Felix Langfeldt (Neverwoods)
 * @version 0.9.1
 */
class User
{
    private $objStdUser = null;

    /**
     * Create a new User object.
     *
     * @param \stdClass $objStdUser The JSON decoded standard class from the API.
     */
    public function __construct(\stdClass $objStdUser = null)
    {
        if (!is_null($objStdUser)) {
            $this->init($objStdUser);
        }
    }

    /**
     * Initialize the class with the standard class from the API.
     *
     * @param \stdClass $objStdUser The JSON decoded standard class from the API.
     */
    public function init(\stdClass $objStdUser)
    {
        $this->objStdUser = $objStdUser;
    }

    /**
     * Get the standard JSON decoded class from the API.
     *
     * @return \stdClass
     */
    public function getRawClass()
    {
        return $this->objStdUser;
    }

    /**
     * Get the full name of the user.
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->getFname() . " " . $this->getLname();
    }

    /**
     * Magic call function to retrieve the Fulcrum properties of the user through "get" calls.
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
            if (isset($this->objStdUser->$strProperty)) {
                return $this->objStdUser->$strProperty;
            }
        }
    }
}
