<?php

namespace Fulcrum;

/**
 * REST Client class
 */
class RestRequest
{
    protected $url;
    protected $verb;
    protected $requestBody;
    protected $requestHeaders;
    protected $requestLength;
    protected $username;
    protected $password;
    protected $acceptType;
    protected $responseBody;
    protected $responseInfo;
    protected $multipart;

    public function __construct ($url = null, $verb = "GET", $requestBody = null, $headers = null, $blnMultipart = false)
    {
        $this->url				= $url;
        $this->verb				= $verb;
        $this->requestBody		= $requestBody;
        $this->requestHeaders	= $headers;
        $this->requestLength	= 0;
        $this->username			= null;
        $this->password			= null;
        $this->acceptType		= "application/json";
        $this->responseBody		= null;
        $this->responseInfo		= null;
        $this->multipart        = $blnMultipart;

        if ($this->requestBody !== null) {
            $this->buildPostBody();
        }
    }

    public function flush ()
    {
        $this->requestBody		= null;
        $this->requestLength	= 0;
        $this->verb				= "GET";
        $this->responseBody		= null;
        $this->responseInfo		= null;
    }

    public function execute ()
    {
        $ch = curl_init();

        //*** Ignore the SSL certificate.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $this->setAuth($ch);

        try {
            switch (strtoupper($this->verb)) {
                case "GET":
                    $this->executeGet($ch);
                    break;
                case "POST":
                    $this->executePost($ch);
                    break;
                case "PUT":
                    $this->executePut($ch);
                    break;
                case "DELETE":
                    $this->executeDelete($ch);
                    break;
                default:
                    throw new \InvalidArgumentException("Current verb '" . $this->verb . "' is an invalid REST verb.");
            }
        } catch (\InvalidArgumentException $e) {
            curl_close($ch);
            throw $e;
        } catch (\Exception $e) {
            curl_close($ch);
            throw $e;
        }
    }

    public function buildPostBody ($data = null)
    {
        $data = ($data !== null) ? $data : $this->requestBody;

        if (!is_array($data) && !is_object($data)) {
            throw new \InvalidArgumentException("Invalid data input for postBody. Array expected.");
        }

        if (!$this->multipart) {
            $data = http_build_query($data, "", "&");

            /**
             * Fulcrum doesn't like indexed arrays ([0], [1], [2], etc.). This line replaces all [0-9 max. 2 positions long]
             * instances with [].
             */
            $data = preg_replace('/%5B[0-9]{1,2}%5D/simU', '%5B%5D', $data);
        }

        $this->requestBody = $data;
    }

    public function buildHeaders()
    {
        $arrReturn = array();

        $this->requestHeaders = (!is_array($this->requestHeaders)) ? array() : $this->requestHeaders;
        if (is_array($this->requestHeaders)) {
            foreach ($this->requestHeaders as $key => $value) {
                $arrReturn[] = "{$key}: {$value}";
            }
        }
        $arrReturn[] = "Accept: " . $this->acceptType;

        return $arrReturn;
    }

    protected function executeGet ($ch)
    {
        if (is_string($this->requestBody)) {
            $this->url .= "?" . $this->requestBody;
        }

        $this->doExecute($ch);
    }

    protected function executePost ($ch)
    {
        if (!is_string($this->requestBody) && !$this->multipart) {
            $this->buildPostBody();
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);

        $this->doExecute($ch);
    }

    protected function executePut ($ch)
    {
        if (!is_string($this->requestBody)) {
            $this->buildPostBody();
        }

        $this->requestLength = strlen($this->requestBody);

        $fh = fopen("php://memory", "rw");
        fwrite($fh, $this->requestBody);
        rewind($fh);

        curl_setopt($ch, CURLOPT_INFILE, $fh);
        curl_setopt($ch, CURLOPT_INFILESIZE, $this->requestLength);
        curl_setopt($ch, CURLOPT_PUT, true);

        $this->doExecute($ch);

        fclose($fh);
    }

    protected function executeDelete ($ch)
    {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

        $this->doExecute($ch);
    }

    protected function doExecute (&$curlHandle)
    {
        $this->setCurlOpts($curlHandle);
        $this->responseBody 	= curl_exec($curlHandle);
        $this->responseInfo  	= curl_getinfo($curlHandle);

        curl_close($curlHandle);
    }

    protected function setCurlOpts (&$curlHandle)
    {
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 30);
        curl_setopt($curlHandle, CURLOPT_URL, $this->url);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $this->buildHeaders());
    }

    protected function setAuth (&$curlHandle)
    {
        if ($this->username !== null && $this->password !== null) {
            curl_setopt($curlHandle, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($curlHandle, CURLOPT_USERPWD, $this->username . ":" . $this->password);
        }
    }

    public function getAcceptType ()
    {
        return $this->acceptType;
    }

    public function setAcceptType ($acceptType)
    {
        $this->acceptType = $acceptType;
    }

    public function getPassword ()
    {
        return $this->password;
    }

    public function setPassword ($password)
    {
        $this->password = $password;
    }

    public function getResponseBody ()
    {
        return $this->responseBody;
    }

    public function getResponseInfo ()
    {
        return $this->responseInfo;
    }

    public function getUrl ()
    {
        return $this->url;
    }

    public function setUrl ($url)
    {
        $this->url = $url;
    }

    public function getUsername ()
    {
        return $this->username;
    }

    public function setUsername ($username)
    {
        $this->username = $username;
    }

    public function getVerb ()
    {
        return $this->verb;
    }

    public function setVerb ($verb)
    {
        $this->verb = $verb;
    }
}
