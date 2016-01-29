<?php
  /**
  * @author gtonye
  * this file is an example file, make sur to replace by actual values (url of a wiki, login and password) to make it work...
  **/

  include("library-mediawiki-api/mw-api.php");

  // initialize the Api Class with wiki url
  $url = "http://mediawiki.wonderland.com/api.php";
  $mwApi = new MediaWikiApi($url);

  // will display the result of the calls in the console
  $mwApi->debug = true;

  // login
  $login = "baruch";
  $password = "spinoza";
  $mwApi->login($login, $password, "enlighting.com");

  // delet a file name minion.jpg on the wiki
  $dir = ".";
  $filename = "minion.jpg";
  $mwApi->delete("File:" . $filename, "", "the reason to delete is that this image will be uploaded after");

  // upload the image minion.jpg on a minion.jpg page
  $mwApi->upload($dir . "/" . $filename, $filename);

  // logout
  $mwApi->logout();

?>
