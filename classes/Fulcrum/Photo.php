<?php

namespace Fulcrum;

use Bili\FileIO;
/**
 * Photo class for the Fulcrum REST API (https://api.fulcrumapp.com/api/v2)
 *
 * @author Felix Langfeldt (Neverwoods)
 * @version 0.9.1
 */
class Photo
{
    private $objStdPhoto = null;

    /**
     * Create a new Photo object.
     *
     * @param \stdClass $objStdPhoto The JSON decoded standard class from the API.
     */
    public function __construct(\stdClass $objStdPhoto = null)
    {
        if (!is_null($objStdPhoto)) {
            $this->init($objStdPhoto);
        }
    }

    /**
     * Initialize the class with the standard class from the API.
     *
     * @param \stdClass $objStdPhoto The JSON decoded standard class from the API.
     */
    public function init(\stdClass $objStdPhoto)
    {
        $this->objStdPhoto = $objStdPhoto;
    }

    /**
     * Download the original image using the credentials from the API.
     *
     * @return resource Binary data representing the image
     */
    public function downloadImage()
    {
        $binReturn = null;

        if (FileIO::webFileExists($this->getOriginal())) {
            $binReturn = FileIO::getWebFile($this->getOriginal());
        }

        return $binReturn;
    }

    /**
     * Magic call function to retrieve the Fulcrum properties of the photo through "get" calls.
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
            if (isset($this->objStdPhoto->$strProperty)) {
                return $this->objStdPhoto->$strProperty;
            }
        }
    }
}
