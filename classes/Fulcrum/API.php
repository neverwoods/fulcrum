<?php

namespace Fulcrum;

/**
 * API class for the Fulcrum REST API (https://api.fulcrumapp.com/api/v2)
 *
 * @author Felix Langfeldt (Neverwoods)
 * @version 0.9.1
 */
class API
{
    private $apiKey;
    private $apiUrl = "http://api.fulcrumapp.com/api/v2";
    private $photoUrl = "http://web.fulcrumapp.com/api/v2";
    private $objRest;
    private $response;
    private $responseStatus;
    private static $instance;

    /**
     * Private constructor. The API supports factory and singleton patterns.
     *
     * @param string $apiKey API token provided by Fulcrum
     * @param string $apiUrl API url where the calls are made
     */
    private function __construct($apiKey, $apiUrl = null)
    {
        $this->apiKey = $apiKey;

        if (!is_null($apiUrl)) {
            $this->apiUrl = $apiUrl;
        }
    }

    /**
     * Initial method to call for setup of the required properties.
     *
     * @param  string       $apiKey API token provided by Fulcrum
     * @param  string       $apiUrl API url where the calls are made
     * @return \Fulcrum\API
     */
    public static function factory($apiKey, $apiUrl)
    {
        self::$instance = new API($apiKey, $apiUrl);

        return self::$instance;
    }

    /**
     * Singelton method to retrieve a configured instance of the API.
     *
     * @return \Fulcrum\API
     */
    public static function getInstance()
    {
        $objReturn = null;

        if (is_object(self::$instance)) {
            $objReturn = self::$instance;
        }

        return $objReturn;
    }

    /**
     * Retrieve an array with available users.
     *
     * @return \Fulcrum\User[]
     */
    public function getUsers()
    {
        $objReturn = new Collection();

        $strGet = "/users";

        $this->objRest = new RestRequest(
            $this->apiUrl . $strGet,
            "GET",
            array(),
            $this->getHeaders()
        );
        $this->objRest->execute();
        $this->parseResponse();

        if (is_object($this->response)) {
            $objReturn->setTotalCount($this->response->total_count);

            foreach ($this->response->users as $objStdClass) {
                $objReturn->addObject(new User($objStdClass));
            }
        }

        return $objReturn;
    }

    /**
     * Retrieve an array with available forms.
     *
     * @param  boolean         $blnSimple Indicate if all form properties schould be retrieved or only basic ones.
     * @return \Fulcrum\Form[]
     */
    public function getForms($blnSimple = false)
    {
        $objReturn = new Collection();

        $strGet = "/forms";
        $arrParameters = ($blnSimple) ? array("schema" => "false") : array();

        $this->objRest = new RestRequest(
            $this->apiUrl . $strGet,
            "GET",
            $arrParameters,
            $this->getHeaders()
        );
        $this->objRest->execute();
        $this->parseResponse();

        if (is_object($this->response)) {
            $objReturn->setTotalCount($this->response->total_count);

            foreach ($this->response->forms as $objStdClass) {
                $objReturn->addObject(new Form($objStdClass));
            }
        }

        return $objReturn;
    }

    /**
     * Retrieve a single form by it's id.
     *
     * @param  string        $strFormId The unique Fulcrum id for the form
     * @return \Fulcrum\Form
     */
    public function getForm($strFormId)
    {
        $objReturn = null;

        $strGet = "/forms/{$strFormId}";

        $this->objRest = new RestRequest(
            $this->apiUrl . $strGet,
            "GET",
            array(),
            $this->getHeaders()
        );
        $this->objRest->execute();
        $this->parseResponse();

        if (is_object($this->response)) {
            $objReturn = new Form($this->response->form);
        }

        return $objReturn;
    }

    /**
     * Retrieve an array with available records for a specific form.
     *
     * @param  string           $strFormId The Fulcrum form id
     * @return \Fulcrum\Records
     */
    public function getRecords($strFormId, $arrParameters = array())
    {
        $objReturn = new Collection();

        $strGet = "/records";
        $arrParameters["form_id"] = $strFormId;

        $this->objRest = new RestRequest(
            $this->apiUrl . $strGet,
            "GET",
            $arrParameters,
            $this->getHeaders()
        );
        $this->objRest->execute();
        $this->parseResponse();

        if (is_object($this->response)) {
            $objReturn->setTotalCount($this->response->total_count);

            $objStdRecords = $this->response->records;
            $objForm = $this->getForm($strFormId);

            foreach ($objStdRecords as $objStdClass) {
                $objReturn->addObject(new Record($objStdClass, $objForm));
            }
        }

        return $objReturn;
    }

    /**
     * Retrieve a single record by it's id.
     *
     * @param  string          $strRecordId The unique Fulcrum id for the record
     * @return \Fulcrum\Record
     */
    public function getRecord($strRecordId)
    {
        $objReturn = null;

        $strGet = "/records/{$strRecordId}";

        $this->objRest = new RestRequest(
            $this->apiUrl . $strGet,
            "GET",
            array(),
            $this->getHeaders()
        );
        $this->objRest->execute();
        $this->parseResponse();

        if (is_object($this->response)) {
            $objStdRecord = $this->response->record;
            $objForm = $this->getForm($objStdRecord->form_id);

            $objReturn = new Record($objStdRecord, $objForm);
        }

        return $objReturn;
    }

    /**
     * Test if a record has been updated remotely since a specific Datetime.
     *
     * @param string $strRecordId The unique Fulcrum id for the record
     * @param DateTime $dtUpdated The DateTime to check against
     * @return boolean
     */
    public function recordHasRemoteUpdate($strRecordId, $dtUpdated)
    {
        $blnReturn = false;

        $strGet = "/records/{$strRecordId}";

        $this->objRest = new RestRequest(
            $this->apiUrl . $strGet,
            "GET",
            array(),
            $this->getHeaders()
        );
        $this->objRest->execute();
        $this->parseResponse();

        if (is_object($this->response)) {
            $objStdRecord = $this->response->record;

            $dtRemoteUpdated = new \DateTime($objStdRecord->updated_at);
            $blnReturn = ($dtRemoteUpdated > $dtUpdated);
        }

        return $blnReturn;
    }

    /**
     * Create a single record.
     *
     * @param  array           $arrSettings The settings used for the new record
     * @return \Fulcrum\Record
     */
    public function createRecord($arrSettings, $strApiKey = null)
    {
        $objReturn = null;

        $strPost = "/records";

        $this->objRest = new RestRequest(
            $this->apiUrl . $strPost,
            "POST",
            $arrSettings,
            $this->getHeaders($strApiKey)
        );
        $this->objRest->execute();
        $this->parseResponse();

        if (is_object($this->response)) {
            $objStdRecord = $this->response->record;
            $objForm = $this->getForm($objStdRecord->form_id);

            $objReturn = new Record($objStdRecord, $objForm);
        }

        return $objReturn;
    }

    /**
     * Update a single record.
     *
     * @param string    $strRecordId The Fulcrum id of the record
     * @param stdClass  $objStdRecord The updated fields of the record
     * @return \Fulcrum\Record
     */
    public function updateRecord($strRecordId, $objStdRecord)
    {
        $objReturn = null;

        $strUpdate = "/records/{$strRecordId}";
        $arrRecord = ["record" => $objStdRecord];

        $this->objRest = new RestRequest(
            $this->apiUrl . $strUpdate,
            "PUT",
            $arrRecord,
            $this->getHeaders()
        );
        $this->objRest->execute();
        $this->parseResponse();

        if (is_object($this->response)) {
            $objStdRecord = $this->response->record;
            $objForm = $this->getForm($objStdRecord->form_id);

            $objReturn = new Record($objStdRecord, $objForm);
        }

        return $objReturn;
    }

    /**
     * Insert a single record.
     *
     * @param stdClass  $objStdRecord The updated fields of the record
     * @return \Fulcrum\Record
     */
    public function insertRecord($objStdRecord)
    {
        $objReturn = null;

        $strUpdate = "/records/";
        $arrRecord = ["record" => $objStdRecord];

        $this->objRest = new RestRequest(
            $this->apiUrl . $strUpdate,
            "POST",
            $arrRecord,
            $this->getHeaders()
        );
        $this->objRest->execute();
        $this->parseResponse();

        if (is_object($this->response)
                && ($this->responseStatus >= 200 && $this->responseStatus < 300)) {
            $objStdRecord = $this->response->record;
            $objForm = $this->getForm($objStdRecord->form_id);

            $objReturn = new Record($objStdRecord, $objForm);
        }

        return $objReturn;
    }

    /**
     * Delete a single record.
     *
     * @param string $strRecordId The Fulcrum id of the record
     * @return boolean
     */
    public function deleteRecord($strRecordId)
    {
        $blnReturn = false;

        $strDelete = "/records/{$strRecordId}";

        $this->objRest = new RestRequest(
            $this->apiUrl . $strDelete,
            "DELETE",
            array(),
            $this->getHeaders()
        );
        $this->objRest->execute();
        $this->parseResponse();

        if (is_object($this->response)) {
            $blnReturn = true;
        }

        return $blnReturn;
    }

    /**
     * Retrieve a single photo by it's id.
     *
     * @param  string         $strPhotoId The unique Fulcrum id for the photo
     * @return \Fulcrum\Photo
     */
    public function getPhoto($strPhotoId)
    {
        $objReturn = null;

        $strGet = "/photos/{$strPhotoId}";

        $this->objRest = new RestRequest(
            $this->apiUrl . $strGet,
            "GET",
            array(),
            $this->getHeaders()
        );
        $this->objRest->execute();
        $this->parseResponse();

        if (is_object($this->response)) {
            $objStdRecord = $this->response->photo;

            $objReturn = new Photo($objStdRecord);
        }

        return $objReturn;
    }

    /**
     * Insert a single photo.
     *
     * @param $arrFields The fields of the photo
     * @return \Fulcrum\Photo
     */
    public function insertPhoto($arrFields)
    {
        $objReturn = null;

        $strUpdate = "/photos.json";
        $arrHeaders = $this->getHeaders();
        $arrHeaders["Content-Type"] = "multipart/form-data";

        $this->objRest = new RestRequest(
            $this->apiUrl . $strUpdate,
            "POST",
            $arrFields,
            $arrHeaders,
            true
        );
        $this->objRest->execute();
        $this->parseResponse();

        if (is_object($this->response)
                && ($this->responseStatus >= 200 && $this->responseStatus < 300)) {
            $objStdRecord = $this->response->record;
            $objForm = $this->getForm($objStdRecord->form_id);

            $objReturn = new Record($objStdRecord, $objForm);
        }

        return $objReturn;
    }

    /**
     * Retrieve a single signature by it's id.
     *
     * @param  string         $strSignatureId The unique Fulcrum id for the signature
     * @return \Fulcrum\Photo
     */
    public function getSignature($strPhotoId)
    {
        $objReturn = null;

        $strGet = "/signatures/{$strPhotoId}";

        $this->objRest = new RestRequest(
            $this->apiUrl . $strGet,
            "GET",
            array(),
            $this->getHeaders()
        );
        $this->objRest->execute();
        $this->parseResponse();

        if (is_object($this->response)) {
            $objStdRecord = $this->response->signature;

            $objReturn = new Photo($objStdRecord);
        }

        return $objReturn;
    }

    /**
     * Download a photo from Fulcrum using API credential headers.
     *
     * @param  string $strPath
     * @return binary The binary data for the photo
     */
    public function downloadPhoto($strPath)
    {
        $varReturn = null;

        //*** Normalize the path.
        $strPath = str_replace($this->photoUrl, "", $strPath);
        $strPath = str_replace($this->apiUrl, "", $strPath);

        $this->objRest = new RestRequest(
            $this->apiUrl . $strPath,
            "GET",
            array(),
            $this->getHeaders()
        );
        $this->objRest->execute();

        $strResponse = $this->objRest->getResponseBody();
        if (!empty($strResponse)) {
            $varReturn = $strResponse;
        }

        return $varReturn;
    }

    public function getStatus()
    {
        $intReturn = 0;

        if ($this->responseStatus > 0) {
            $intStatus = $this->responseStatus;
        }

        return $intReturn;
    }

    private function parseResponse ()
    {
        $strResponse = $this->objRest->getResponseBody();
        $objResponse = json_decode($strResponse);

        $this->response = $objResponse;
        $this->responseStatus = $this->objRest->getResponseInfo()["http_code"];
    }

    private function getHeaders($strApiKey = null)
    {
        if (is_null($strApiKey)) {
            $strApiKey = $this->apiKey;
        }

        return array("X-ApiToken" => $strApiKey);
    }
}
