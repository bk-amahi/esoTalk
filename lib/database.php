<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Database class: Handles database actions such as connecting, and running queries.
// Also contains useful functions for constructing queries.

if (!defined("IN_ESOTALK")) exit;

class Database {

var $esoTalk;
var $link;

// Connect to a database.
function connect($host, $user, $password, $db)
{
	global $language, $config;
	if (!($this->link = @mysql_connect($host, $user, $password)) or !@mysql_select_db($db, $this->link)) return false;
	return true;
}

// Run a query. If $fatal is true, then a fatal error will be displayed and page execution will be halted if the query fails.
function query($query, $fatal = true)
{
	global $language, $config;
	if (!$query) return false;
	
	$this->esoTalk->callHook("beforeDatabaseQuery", array(&$query));

	// Execute the query. If there is a problem, return a formatted fatal error.
	$result = mysql_query($query, $this->link);
	if (!$result and $fatal) {
		$error = $this->error();
		$this->esoTalk->fatalError($config["verboseFatalErrors"] ? $error . "<p style='font:100% monospace; overflow:auto'>" . $this->highlightQueryErrors($query, $error) . "</p>" : "");
	}
	
	$this->esoTalk->callHook("afterDatabaseQuery", array($query, &$result));
	
	return $result;
}

// Find anything in single quotes in the error and make it red in the query (just to make debugging a bit easier.)
function highlightQueryErrors($query, $error)
{
	preg_match("/'(.+?)'/", $error, $matches);
	if (!empty($matches[1])) $query = str_replace($matches[1], "<span style='color:#f00'>{$matches[1]}</span>", $query);
	return $query;
}

// Return the number of rows affected by the last query.
function affectedRows()
{
	return mysql_affected_rows($this->link);
}

// Fetch associative array.
function fetchAssoc($input)
{
	if (is_resource($input)) return mysql_fetch_assoc($input);
	$result = $this->query($input);
	if (!$this->numRows($result)) return false;
	return $this->fetchAssoc($result);
}

// Fetch sequential array.
function fetchRow($input)
{
	if (is_resource($input)) return mysql_fetch_row($input);
	$result = $this->query($input);
	if (!$this->numRows($result)) return false;
	return $this->fetchRow($result);
}

// Database result.
function result($input, $field = 0)
{
	if (is_resource($input)) return mysql_result($input, $field);
	$result = $this->query($input);
	if (!$this->numRows($result)) return false;
	return $this->result($result);
}

// Get the last db insert id.
function lastInsertId()
{
	return $this->result($this->query("SELECT LAST_INSERT_ID()"), 0);
}

// Return the number of rows in the result.
function numRows($input)
{
	if (is_resource($input)) return mysql_num_rows($input);
	$result = $this->query($input);
	return $this->numRows($result);
}

// Return the most recent mysql error.
function error()
{
	return mysql_error();
}

// Construct a select query. $components is an array. ex. array("select" => array("foo", "bar"), "from" => "members")
function constructSelectQuery($components)
{
	// Implode the query components.
	$select = isset($components["select"]) ? (is_array($components["select"]) ? implode(", ", $components["select"]) : $components["select"]) : false;
	$from = isset($components["from"]) ? (is_array($components["from"]) ? implode("\n\t", $components["from"]) : $components["from"]) : false;
	$groupBy = isset($components["groupBy"]) ? (is_array($components["groupBy"]) ? implode(", ", $components["groupBy"]) : $components["groupBy"]) : false;
	$where = isset($components["where"]) ? (is_array($components["where"]) ? "(" . implode(")\n\tAND (", $components["where"]) . ")" : $components["where"]) : false;
	$having = isset($components["having"]) ? (is_array($components["having"]) ? "(" . implode(") AND (", $components["having"]) . ")" : $components["having"]) : false;
	$orderBy = isset($components["orderBy"]) ? (is_array($components["orderBy"]) ? implode(", ", $components["orderBy"]) : $components["orderBy"]) : false;
	$limit = isset($components["limit"]) ? $components["limit"] : false;
	
	// Return the constructed query.
	return ($select ? "SELECT $select\n" : "") . ($from ? "FROM $from\n" : "") . ($where ? "WHERE $where\n" : "") . ($having ? "HAVING $having\n" : "") . ($groupBy ? "GROUP BY $groupBy\n" : "") . ($orderBy ? "ORDER BY $orderBy\n" : "") . ($limit ? "LIMIT $limit" : "");
}

// Construct an insert query with an associative array of data.
function constructInsertQuery($table, $data)
{
	global $config;
	return "INSERT INTO {$config["tablePrefix"]}$table (" . implode(", ", array_keys($data)) . ") VALUES (" . implode(", ", $data) . ")";
}

// Construct an update query with associative arrays of data/conditions.
function constructUpdateQuery($table, $data, $conditions)
{
	$update = "";
	foreach ($data as $k => $v) $update .= "$k=$v, ";
	$update = rtrim($update, ", ");
	
	$where = "";
	foreach ($conditions as $k => $v) $where .= "$k=$v AND ";
	$where = rtrim($where, " AND ");
	
	global $config;
	return "UPDATE {$config["tablePrefix"]}$table SET $update WHERE $where";
}

}

?>