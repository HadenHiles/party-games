<?php
/*
 * Author: Justin Searle
 * Date: 7/3/2016
 * Description: Require this on all pages where the session is needed
 *              Also define any constants for the web page here.
 */

/*
 * start the session
 */
session_start();

/*
 * common constats for the website
 */
define("SESSION_ID", session_id());
define("DEVICE_IP", $_SERVER['REMOTE_ADDR']);
define("ROOT", $_SERVER['DOCUMENT_ROOT']);

/*
 * examples of authorization for the website
 */
//define("IS_LOGGED_IN", isset($_SESSION['username']));
//define("IS_USER", (IS_LOGGED_IN && (int) $_SESSION['level'] >= 2));
//define("IS_ADMIN", (IS_LOGGED_IN && (int) $_SESSION['level'] >= 3));
//define("IS_SUPER_ADMIN", (IS_ADMIN && (int) $_SESSION['level'] === 4));

/*
 * toggle the hiding of errors
 */
//error_reporting(E_ERROR | E_WARNING | E_NOTICE | E_PARSE);
error_reporting(E_ERROR | E_WARNING | E_PARSE);
//error_reporting(E_ERROR | E_PARSE);

/*
 * common var dumps
 */
//var_dump($_SESSION);
//var_dump($_POST);
?>
