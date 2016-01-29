# Foreword

## Introduction

* The following MediaWikiApi has been developed in order to help interacting with a MediaWiki. Feel free to use this code for your own purposes, suggestions, ideas, pull requests and finding bugs are always welcomed :-). For any of those do not hesitate to open an issue.

### Why did I build this?

* I was facing a situation where I needed to make a batch update over a series of images that were not well imported in my Mediawiki deployment.
  * It would take too long to do it by hand (over 50 images)
  * Setting the extension [Nuke Extenstion](https://www.mediawiki.org/wiki/Extension:Nuke), [DeleteBatch Extension](https://www.mediawiki.org/wiki/Extension:DeleteBatch) was not an option because of a limited access to the hosting servers.

* I decided to interact with the available Api for that purpose

# Documentation

## Requirements

* The wiki with which the class will interact will need to have the Api set and activated (see https://www.mediawiki.org/wiki/Manual:Configuration_settings#API).

* This code was tested on a mediawiki 26 wmf 18, so any behavior on other versions is not guaranteed and may require some modifications.

## Environment

* This code uses CURL to proceed the queries, so this module will have to set on the php environment.

## Usage

* The usage is pretty StraightForward:
  * The class has to be imported in the files that will use it with:
```php
<?php
  import('<path to the file>/mw-api.php');
?>
```
  * Then initialize an object with the url of the targeted wiki. The constructor takes a String that represent an url.
```php
  <?php
      $url = "http://<targeted-wiki>/api.php";
      $mwApi = new MediaWikiApi($url);
  ?>
```

### Methods

* The following methods can be used:
  * login it requires the login and the password (and the domain if a domain authentication was set, see https://www.mediawiki.org/wiki/Extension:LDAP_Authentication)
  * delete (user with rights mandatory): it requires the name of the page that will be delete (or the page id) and a comment to set the reason for the deletion
  * upload (user with rights mandatory): it requires the localfile name, the title of the page on which the file will be uploaded, the type of upload and if the distant page should be overwritten.
  * logout

# Afterword

## Additional resources

* Many things can be done with the MediaWiki Api, this class only provides an implementation of a few features.
* https://www.mediawiki.org/wiki/User:Patrick_Nagel/Login_with_snoopy_post-1.15.3: Small implementation of the login feature.
* https://www.mediawiki.org/wiki/User:Bcoughlan/Login_with_curl: Another implementation of the login.
* https://github.com/ppKrauss/MediawikiNavigator: Login and navigation in the wiki.
* https://www.mediawiki.org/wiki/API_talk:Upload#Real_world_example_with_PHP: Implementation of the upload.
* https://www.mediawiki.org/wiki/API:Main_page: Complete description of the different APIs.


## TODO

* An error handler should be useful, in order to provide to the user of this API information about the failure or the success of an API call. For example when you try to modify a page and do not have the rights or upload an existing file without setting the overwrite flag.

* Implement the other method such as get the page, or edit the page

## legal stuff

d(- _ -)b

```
The MIT License (MIT)
Copyright (c) 2016 gtonye

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
```
