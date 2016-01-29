<?php

/**
* @author gtonye
* The following class provides an API to interact with the Mediawiki API
**/

class MediaWikiApi {

  /// ## Attributes

  public $token;
  public $url;
  public $userId = NULL;
  public $userName = NULL;

  public $editToken = NULL;

  private $cookiePrefix = NULL;
  private $sessionId = NULL;

  public $STATUS_ERROR = "ERROR";
  public $STATUS_SUCCESS = "SUCCESS";

  public $debug = false;

  /// ## Class C-TOR

  /**
  * @param the url of the api (eg http://mediawiki/api.php)
  **/
  public function __construct($apiUrl) {
    if ($apiUrl == "") {
      trigger_error("The Api URL can not be empty.", E_USER_ERROR);
      die("Could not create MediaWikiApi object.");
    }
    $this->url = $apiUrl;
  }


  /// ## Class Methods

  /// ### Utils

  /**
    * See https://www.mediawiki.org/wiki/API_talk:Upload#Real_world_example_with_PHP
    * @param an array of fields
    * @param a boundary pattern
    * @param local File Path to upload
    * @param remote file name which will be given to the file on the server
    * @return the query's body
    **/
  function multipartBuildQueryBody($fields, $boundary, $localFilePath = "", $remoteFileName = "") {
    $data = '';
    $eol = "\r\n";
    foreach($fields as $key => $value){
      $data .= '--' . $boundary . $eol;
			$data .= 'Content-Disposition: form-data; name="' . $key . '"' . $eol;
			$data .= 'Content-Type: text/plain; charset=UTF-8' .  $eol;
			$data .= 'Content-Transfer-Encoding: 8bit' .  $eol . $eol;
			$data .= $value . $eol;
    }
    if ($localFilePath != "") {
      $handle = fopen($localFilePath, "rb");
    	$file_body = fread($handle, filesize($localFilePath));
    	fclose($handle);
      $data .= '--' . $boundary . $eol;
      $data .= 'Content-Disposition: form-data; name="file"; filename="'. $remoteFileName .'"' . $eol; //Filename here
      $data .= 'Content-Type: application/octet-stream; charset=UTF-8' . $eol;
      $data .= 'Content-Transfer-Encoding: binary' . $eol . $eol;
      $data .= $file_body . $eol;
      $data .= "--" . $boundary . "--" . $eol . $eol; // finish with two eol's
    }
    return $data;
  }

  /**
  * @param the url to post to
  * @param the parameters for the post request with key value
  * @param (optional) header for the request if necessary
  * @param (optional) body if the query body should not be built from param
  * @return the resul
  **/
  private function httpPost($url, $params, $headers = "", $queryBody = "") {
    $postData = '';

    if ($queryBody != "") { $postData = $queryBody; }
    else {
      //create name value pairs seperated by &
      foreach($params as $k => $v) { $postData .= $k . '='.$v.'&'; }
      $postData = rtrim($postData, '&');
    }

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    if ( ($this->cookiePrefix != NULL) && ($this->sessionId != NULL) ) {
      $cookie = $this->cookiePrefix . "_session=" . $this->sessionId . "";
      curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    if ($headers !== "") {
        //curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    } else { curl_setopt($ch, CURLOPT_HEADER, false); }
    curl_setopt($ch, CURLOPT_POST, count($postData));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $output=curl_exec($ch);

    // TODO: Remove the debug
    if($errno = curl_errno($ch)) {
        $error_message = curl_strerror($errno);
        echo "cURL error ({$errno}):\n {$error_message}";
    }

    curl_close($ch);
    return $output;
  }

  /**
    * @param a string that will be printed
    * Print the parameter if the debug has been set in the class
    **/
  private function mPrint($string) {
    if ( $this->debug === true ) { echo $string; }
  }

  /// ### Api's Methods

  /**
  * see https://www.mediawiki.org/wiki/API:Login
  * @param login
  * @param password
  * @param domain if LDAP was added to the Mediawiki installation
  * @return json with status of the login
  **/
  public function login($login, $password, $domain = "") {
    $params = array(
      "action" => "login",
      "lgname" => $login,
      "lgpassword" => $password,
      "format" => "json"
    );
    $errorJson = '{ "status": "' . $this->STATUS_ERROR . '", "description": "Login could not be done" }';

    if ($domain != "") { $params["lgdomain"] = $domain; }

    $phase1ResultJsonString = $this->httpPost($this->url, $params); // login phase 1
    $phase1ResultJson = json_decode($phase1ResultJsonString, true);
    if ( !array_key_exists('result', $phase1ResultJson['login']) ||
    !in_array($phase1ResultJson['login']['result'], array("NeedToken", "Success")) ) {
      trigger_error("Could not log in. Request result: " . $phase1ResultJsonString, E_USER_WARNING);
      return($errorJson);
    }

    if ($phase1ResultJson['login']['result'] === "NeedToken") {

      $this->cookiePrefix = $phase1ResultJson['login']['cookieprefix'];
      $this->sessionId = $phase1ResultJson['login']['sessionid'];
      $params["lgtoken"] = $phase1ResultJson["login"]["token"];

      $phase2ResultJsonString = $this->httpPost($this->url, $params); // login phase 2
      $phase2ResultJson = json_decode($phase2ResultJsonString, true);

      if ($phase2ResultJson['login']['result'] !== "Success") { return($errorJson); }

      $this->token = $phase2ResultJson["login"]["lgtoken"];
      $this->userId = $phase2ResultJson["login"]["lguserid"];
      $this->userName = $phase2ResultJson["login"]["lgusername"];
    }
    $this->mPrint(json_encode($phase2ResultJson, JSON_PRETTY_PRINT)); // TODO: remove for debug
    return ('{ "status": "' . $this->STATUS_SUCCESS . '", "description": "Login proceed" }');
  }

  public function logout() {
    $params = array(
      "action" => "logout",
      "format" => "json"
    );
    $resultJsonString = $this->httpPost($this->url, $params);
    echo ( json_encode($resultJsonString, JSON_PRETTY_PRINT) );
  }


  /**
    * See https://www.mediawiki.org/wiki/API:Tokens
    * @return the json status of the query (ERROR or SUCCESS)
    **/
  public function getEditToken() {
    $params = array(
      "action" => "query",
      "meta" => "tokens",
      "type" => "csrf",
      "format" => "json"
    );

    if ( empty($resultJsonString = $this->httpPost($this->url, $params)) ) {
      trigger_error("Could not get an edit token. Request result: " . $resultJsonString, E_USER_WARNING);
      return ('{ "status": "' . $this->STATUS_ERROR . '", "description": "Could not get an edit token." }');
    }

    $resultJson = json_decode($resultJsonString, true);
    $this->editToken = $resultJson["query"]["tokens"]["csrftoken"];

    $this->mPrint( json_encode(json_decode($resultJsonString), JSON_PRETTY_PRINT) ); // TODO: remove for debug
    return ('{ "status": "' . $this->STATUS_SUCCESS . '", "description": "Edit token set" }');
  }

  private function titleToUrlTitle($title) {
    return (urlencode(str_replace(" ", "_", $title)));
  }

  /**
    * https://www.mediawiki.org/wiki/API:Delete
    * @param the title of the page to delete
    * @param the id of the page (only necessary if the title was not provided)
    * @param a short text describing the reason
    * @return a json with the status
    **/
  public function delete($pageTitle, $pageId = "", $reason) {
    if ( ($pageTitle == "") && ($pageId == "") ) {
      return ('{ "status": "' . $this->STATUS_ERROR . '", "description": "Need at least either the page title or the page id." }');
    }

    if ($this->editToken === NULL) {
      $tokenQueryStatusJsonString = $this->getEditToken();
      $tokenQueryStatusJson = json_decode($tokenQueryStatusJsonString, true);
      if ($tokenQueryStatusJson["status"] === $this->STATUS_ERROR) {
        trigger_error("Could not get an edit token. Request result: " . $resultJsonString, E_USER_WARNING);
        return ('{ "status": "' . $this->STATUS_ERROR . '", "description": "Could not get an edit token." }');
      }
    }

    $params = array(
      "action" => "delete",
      "format" => "json",
      "token" => urlencode($this->editToken)
    );

    if ($pageTitle === "") { $params["pageid"] = $pageId; }
    else { $params["title"] = $this->titleToUrlTitle($pageTitle); }

    if ($reason !== "") { $params["reason"] = $reason; }

    if ( empty($resultJsonString = $this->httpPost($this->url, $params)) ) {
      trigger_error("Could not get an edit token. Request result: " . $resultJsonString, E_USER_WARNING);
      return ('{ "status": "' . $this->STATUS_ERROR . '", "description": "Could not delete the page." }');
    }
    $this->mPrint( json_encode(json_decode($resultJsonString), JSON_PRETTY_PRINT) . "\n"); // TODO: remove for debug
    return ('{ "status": "' . $this->STATUS_SUCCESS . '", "description": "Page was successfully deleted." }');
  }

  /**
    * See https://www.mediawiki.org/wiki/API:Upload
    * @param the path of local file to upload
    * @param the name of the remote file
    * @param the type of upload (DIRECTLY | CHUNKED | URL)
    **/
  private function uploadDirectly($localFilePath, $remoteFileName, $overwriteExisting) {
    $params = array(
      "action" => "upload",
      "filename" => $remoteFileName,
      "token" => $this->editToken,
      "ignorewarnings" => $overwriteExisting,
      "format"=>"json"
    );
    $boundary = "---------------------" . md5(mt_rand() . microtime());
    $queryBody = $this->multipartBuildQueryBody($params, $boundary, $localFilePath, $remoteFileName);

    $resultJsonString = $this->httpPost($this->url, $params, array("Content-Type: multipart/form-data; boundary=$boundary"), $queryBody);
    $this->mPrint( json_encode(json_decode($resultJsonString), JSON_PRETTY_PRINT) . "\n"); // TODO: remove for debug
  }

  /**
    * See https://www.mediawiki.org/wiki/API:Upload
    * @param the path of local file to upload
    * @param the name of the remote file
    * @param the type of upload (DIRECTLY | CHUNKED | URL)
    **/
  public function upload($localFilePath, $RemoteFileName, $type = "DIRECTLY", $overwriteExisting = "yes") {
    if ($this->editToken === NULL) {
      $tokenQueryStatusJsonString = $this->getEditToken();
      $tokenQueryStatusJson = json_decode($tokenQueryStatusJsonString, true);
      if ($tokenQueryStatusJson["status"] === $this->STATUS_ERROR) {
        trigger_error("Could not get an edit token. Request result: " . $resultJsonString, E_USER_WARNING);
        return ('{ "status": "' . $this->STATUS_ERROR . '", "description": "Could not get an edit token." }');
      }
    }

    if ( ($localFilePath == "") || ($RemoteFileName == "") ) {
      return ('{ "status": "' . $this->STATUS_ERROR . '", "description": "Could upload the file, the local path or hte remote name is empty." }');
    }

    // TODO: create private functions for each type of upload
    if ($type == "DIRECTLY") { $this->uploadDirectly($localFilePath, $RemoteFileName, $overwriteExisting); }

  }

}
?>
