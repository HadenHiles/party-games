<?php
/*
 * Author: Justin Searle
 * Date: 7/3/2016
 * Description: Require this file anywhere you need connect to the database and call "global $db;" to assign to this connection to the $db variable
 */
require_once("config.php");

$db = new PDO("mysql:host=".THE_HOST.";dbname=".THE_DB, THE_USER, THE_PASSWORD);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// example of database connection below

// global $db;
// $sql = 'SELECT * FROM my_table WHERE id = :id';
// $result = $db->prepare($sql);
// $result->bindParam(":id", $id);
// if ($result->execute() && $result->errorCode() == 0 && $result->rowCount() > 0) {
//      return $result->fetchAll(PDO::FETCH_ASSOC);
// }

?>
