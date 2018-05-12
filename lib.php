<?php
	/*
	*	Title: PHP Function Library
	*	Author: Daniel Harris @TheWebAuthor
	*/
	
	$success[] = true;
	
	//Show alert window
	function alert($txt){
		js('alert("'.$txt.'");');
	}
	
	//Run JavaScript
	function js($js) {
		echo '<script>'.$js.'</script>';
	}
	
	//This function forwards the page to another one.
	function go($url, $delay=0){
		$delay *= 1000;
		js("setTimeout(function(){ window.location = '$url'; }, $delay);");
	}
	
	//This function goes back to the previous page.
	function back($delay = 0) {
		go('javascript:history.back()', $delay);
	}
	
	//This function returns the current date and time in MySQL format.
	function now() {
		return date('Y-m-d H:i:s');
	}
	
	function dump($arr) {
		echo '<pre>';
		var_dump($arr);
		echo '</pre>';
	}
	
	//This function capitalizes all words in a string and removes all "_"
	function capitalize($str) {
		return ucwords(str_replace('-', ' ', str_replace('_', ' ', $str)));
	}
	
	//This function converts a word to singular form.
	function single($str) {
		return ends_with('s', $str) ? substr($str, 0, strlen($str) - 1) : $str;
	}
	
	//This function converts a word to plural form.
	function plural($str) {
		return ends_with('s', $str) ? $str : $str.'s';
	}
	
	//This function converts any datetime into MySQL format
	function mysqli_date($date = '') {
		return date('Y-m-d H:i:s', strtotime($date));
	}
	
	function format_date($date = '', $format) {
		return date($format, strtotime($date));
	}
	
	//This function adds 'http://' if it doesn't exist in the url.
	function abs_url($url){
		return starts_with($url, 'http') || starts_with($url, 'https') ? $url : 'http://'.$url;
	}
	
	//This function tests if the string is an IP address.
	function is_ip($ip) {
		return filter_var($ip, FILTER_VALIDATE_IP);
	}
	
	//This function will add new variables (string or array) at the end of a query string in the current URL else returns the current URL only.
	function url($add){
		if ($add){
			if (is_array($add)){
				$out2 = $add;
			}
			else {
				parse_str($add, $out2);
			}
				parse_str($_SERVER['QUERY_STRING'], $out1);
				$out = array_merge($out1, $out2);
				$out = array_map('urlencode', $out);
				return $_SERVER['PHP_SELF'].'?'.http_build_query($out);			
		}
		else {
			return $_SERVER['REQUEST_URI'];
		}
	}
	
	//This function lets you connect to a database.
	function connect($host, $user, $pw, $dbname){
		global $mysqli;
		$mysqli = mysqli_connect($host, $user, $pw, $dbname);
		
		if (mysqli_connect_errno()) {
			notify("Failed to connect to the database.", 5, "Error");
		}
	}
	
	function mysqli_result($res, $row, $field=0) { 
		$res->data_seek($row); 
		$datarow = $res->fetch_array(); 
		return $datarow[$field]; 
	}
	
	//This function returns the values from a SELECT query in an array. i.e. select(COLUMNS, TABLE, CONDITIONS, SORT)
	//Returns $rows[Row Number][Column] = Value
	function select($cols, $table, $conditions='', $sort=''){	
		if ($cols && $table) {
			global $mysqli;
			
			if (is_array($cols)) {
				$cols = implode(", ", $cols);
			}
			
			$sql = "SELECT ".$cols." FROM ".$table.($conditions ? " WHERE ".$conditions : "")." ".$sort;
			
			$result = mysqli_query($mysqli, $sql);
			
			if (sql_error($result, $sql)){
				return false;
			}
			else {
				while($rows[]=mysqli_fetch_assoc($result));
				
				$rows = array_filter($rows);
				
				return $rows;
			}
		}
		else {
			return false;
		}
	}
	
	//This functions returns the value for a single variable in a single row. If more than one column selected, it returns an array $row[COLUMN] = VALUE
	function select_row($cols, $table, $conditions='', $sort=''){
		if ($cols && $table) {
			global $mysqli;
			
			if (is_array($cols)) {
				$cols = implode(", ", $cols);
			}
			
			$sql = "SELECT ".$cols." FROM ".$table.($conditions ? " WHERE ".$conditions : "")." ".$sort." LIMIT 1";
			
			$result = mysqli_query($mysqli, $sql);
			
			if (sql_error($result, $sql)){
				return false;
			}
			else {
				$row = mysqli_num_rows($result) ? (mysqli_num_fields($result) > 1 ? mysqli_fetch_assoc($result) : mysqli_result($result, 0)) : false;
				
				return $row;		
			}
		}
		else {
			return false;
		}
	}
	
	//This function returns columns from a table.
	function select_cols($table, $hide_id = false, $hide_reserved = true, $limit = '50') {
		global $db_name;
		return select("`COLUMN_NAME` as name", "`INFORMATION_SCHEMA`.`COLUMNS`", "`TABLE_SCHEMA`='$db_name' AND `TABLE_NAME`='$table' ".($hide_id ? "AND `COLUMN_NAME` <> 'id' " : '').($hide_reserved ? "AND `COLUMN_NAME` <> 'reserved'" : ''), "LIMIT $limit");
	}
	
	//This function allows any type of queries to be executed. Returns one or more rows if select is used. Returns the value if only one column and one row is selected.
	function q($sql, $notify_me=true){
		global $mysqli;
		
		$result = mysqli_query($mysqli, $sql);
		
		global $success;

		if (has('SELECT', $sql)){
			$notify_me = false;
		}
		if (sql_error($result, $sql)){
			if ($notify_me) {
				$success[] = false;
				notify("Failed to update/delete item.", 3, "Error");
			}
			return false;
		}
		else {	
			if ($result === true){
				if ($notify_me) {
					$success[] = true;
					notify("Changes Successful!", 3, "Success");
				}
				return true;
			}
			else {
				switch(mysqli_num_rows($result)){
					case 0:
						return false;
					break;			
					default:
						if ($single){
							$data = mysqli_num_fields($result) > 1 ? mysqli_fetch_assoc($result) : mysqli_result($result, 0);
						}
						else {
							while($data[]=mysqli_fetch_assoc($result));
							$data = array_filter($data);
						}
					break;	
				}
				return $data;
			}
		}
		
		success();
	}	

	//This function tests an array $success and returns true if all values are true or false if one value is false.
	function success() {
		global $success;

		if ($success){
			$suc = true;		
			foreach ($success as $s){
				if (!$s){
					$suc = false;
					break;
				}
			}	
			if ($suc){
				notify("Update was successful!", 5, "Success");
			}	
			else {
				notify("Failed to update with one or more of your requests!", 10, "Error");
			}
			return $suc;
		}
	}
	
	//Thus function will return the number of rows of any SELECT or SHOW query.
	function nrows($cols, $table, $conditions='', $sort=''){
		return count(select($cols, $table, $conditions, $sort));	
	}

	//This function checks the username and password for logging in.
	function check_login($login_attempts=5){
		if ($_SESSION['login_attempts'] > $login_attempts) {
			notify("You have exceeded the max number of $login_attempts login attempts.", 15, "Notice");
		}
		else {
			$username = $_POST['username'];
			$salt = "2015";
			$password = md5(escape($_POST['password']).$salt);

			$_SESSION['login_attempts'] += 1;
			
			$match = select_row("id", "s_users", "username='$username' AND password='$password'", "");
			
			if ($match) {
				$_SESSION['login_attempts'] = 0;
				$_SESSION['user']['id'] = $match;
				return $match;
			}
			else {
				notify("Your login credentials are invalid. <br>(Login attempt ".$_SESSION['login_attempts']." out of $login_attempts)", 15, "Error");
			}
		
		}
	}
	
	//This function checks if a column exists in a table. Returns the row if there is a match. Returns empty if no match.
	function col_exists($col, $table) {
		return q("SHOW COLUMNS FROM `$table` LIKE '$col'");
	}
	
	//This function returns true if the file is an image. The filetype parameter must be the type in the $_FILES array.
	function is_image($filetype){
		return has('image', $filetype);
	}
	
	//This function returns the extension of any file
	function get_ext($file){
		return pathinfo($file, PATHINFO_EXTENSION);
	}
	
	//This function returns a substring that is limited by the length from the original string.
	function cut($chars, $txt){
		$txt = strip_tags($txt);
		if (strlen($txt) > $chars){
			$s = substr($txt, 0, $chars);
			$result = substr($s, 0, strrpos($s, ' '));
			return $result.'...';
		}
		else {
			return $txt;
		}
		
	}
	
	//This function will capitalize the first letters of words even if they include_once hyphens.
	function ucwordsh($str){
		return str_replace('- ','-',ucwords(str_replace('-','- ',$str)));
	}
	
	//Strip URLs from text
	function strip_urls($string){
		return preg_replace("/[a-zA-Z]*[:\/\/]*[A-Za-z0-9\-_]+\.+[A-Za-z0-9\.\/%&=\?\-_]+/i", '', $string);
	}
	
	//Strip E-mails from text
	function strip_emails($string){
		return preg_replace("/[^@\s]*@[^@\s]*\.[^@\s]*/", '', $string);
	}
	
	//Strips E-mails, URLs, and slashes from text
	function strip_all($string){
		return str_replace('\r\n', '', strip_urls(strip_emails($string)));
	}
	
	//This function returns only the filename of the current page.
	function get_filename(){
		return basename($_SERVER['SCRIPT_NAME']);
	}
	
	//This function creates a title from the filename.
	function get_title(){
		return ucwordsh(str_replace('_', ' ', basename($_SERVER['SCRIPT_NAME'], '.php')));
	}
	
	//This function splits an array into smaller arrays. Returns $array[0] for array 1, $array[1] for array 2, and so on.
	function split_array($array, $parts) {
		$t = 0;
		$result = array();
		$max = ceil(count($array) / $parts);
		foreach(array_chunk($array, $max) as $v) {
			if ($t < $parts) {
				$result[] = $v;
			} else {
				foreach($v as $d) {
					$result[] = array($d);
				}
			}
			$t += count($v);
		}
		return $result;
	}
	
	//This function simply adds 't_' to the types
	function format_type($string) {
		return starts_with('t_', $string) ? $string : 't_'.$string;
	}

	//This function allows the text to be escaped so that it can be used in an insert SQL query function.
	function escape($text){
		global $mysqli;
		return mysqli_real_escape_string($mysqli, $text);
	}
	
	//This function creates an all lowercase slug from a name
	function toslug($txt) {
		return str_replace(" ", "-", strtolower($txt));
	}
	
	//This function converts an input type to a MySQL column type
	function to_mysql_col_type($str, $length) {
		switch($str) {
			case "text":
			case "textarea":
			case "select":
			case "select_format":
				return "VARCHAR($length)";
			break;
			case "decimal":
				return "DECIMAL($length)";
			break;
			case "bool":
				return "VARCHAR(3)";
			break;
			case "number":
				return "INT($length)";
			break;
			case "date":
				return "DATE";
			break;
			case "datetime":
				return "DATETIME";
			break;
			case "time":
				return "TIME";
			break;
		}
	}
	
	//This function generates a select field populated from a single column of rows in a database
	function generate_select($col_id, $name, $selected_val = '', $class = '', $disabled = 'No') {
		$select = select_row("`text`, from_table, args", "s_selects", "col_id='$col_id'", "");
		if ($select) {
			$from_table = select_row("slug", "s_types", "id='{$select['from_table']}'", "");
			$no_answer = select_row("`no-answer`", "s_columns", "id='$col_id'", "");
			
			$output = "<select ".($disabled == 'Yes' ? 'readonly' : '')." name='$name' class='form-control $class'>";
			$output .= $no_answer === 'Yes' ? '<option value="" '.($selected_val == '' ? 'selected' : '').'>N/A</option>' : '';
			
			$options = select('id, `'.$select['text'].'`', '`'.$from_table.'`', $select['args'], "ORDER BY `{$select['text']}` ASC");
			
			foreach ($options as $option) {
				$output .= '<option value="'.$option['id'].'" '.($option['id'] == $selected_val ? 'selected' : '').'>'.capitalize($option[$select['text']]).'</option>';
			}
			$output .= '</select>';
			echo $output;
		}
		else {
			echo '<p>Select column is not set up. <a href="'.$domain.$root_dir.'?action=add&type=s_selects&col_id='.$col_id.'">Set one up here.</a></p>';
			return false;
		}
	}
	
	function generate_select_format($name, $val = '', $class = '', $disabled = 'No') {
		echo '
			<select '.($disabled == 'Yes' ? 'readonly' : '').' name="'.$name.'" class="form-control '.$class.'">
				<option value="text" '.($val == 'text' ? 'selected' : '').'>Text</option>
				<option value="textarea" '.($val == 'textarea' ? 'selected' : '').'>TextArea</option>
				<option value="select" '.($val == 'select' ? 'selected' : '').'>Select</option>
				<option value="select_format" '.($val == 'select_format' ? 'selected' : '').'>Select Format</option>
				<option value="decimal" '.($val == 'decimal' ? 'selected' : '').'>Decimal</option>
				<option value="bool" '.($val == 'bool' ? 'selected' : '').'>Boolean</option>
				<option value="number" '.($val == 'number' ? 'selected' : '').'>Number</option>
				<option value="date" '.($val == 'date' ? 'selected' : '').'>Date</option>
				<option value="datetime" '.($val == 'datetime' ? 'selected' : '').'>Date/Time</option>
				<option value="time" '.($val == 'time' ? 'selected' : '').'>Time</option>
			</select>
		';
	}
	
	//This function returns true if the haystack string starts with the searched string.
	function starts_with($needle, $haystack) {
		return strpos($haystack, $needle) === 0;
	}

	//This function returns true if the haystack string ends with the searched string.
	function ends_with($needle, $haystack) {
		$length = strlen($needle);
		return (substr($haystack, -$length) === $needle);
	}
	
	//This function returns true if the string contains another sub string.
	function has($needle, $haystack) {
		return is_numeric(stripos($haystack, $needle));
	}

	//Encryption/Decryption
	/*$size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
	$iv = mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);

	function encrypt($data, $key='252525')
	{
		$data = mcrypt_cbc(MCRYPT_RIJNDAEL_256, $key, $data, MCRYPT_ENCRYPT, $iv);
		return base64_encode($data);
	}

	function decrypt($data, $key='252525')
	{
		$data = base64_decode($data);
		$data = mcrypt_cbc(MCRYPT_RIJNDAEL_256, $key, $data, MCRYPT_DECRYPT, $iv);
		return trim($data);
	}*/
	
	//This function lets you delete a file.
	function delete_file($filename){
		if (file_exists($filename)){
			if (unlink($filename)){
				return true;
			}
			else {
				notify('Unable to delete '.$filename);
				return false;
			}
		}
		else {
			notify($filename.' does not exist.');
			return false;
		}
	}
	
	//This function lets you create a new file with custom content.
	function new_file($filename, $content){
		$filehandle = fopen($filename, 'w');
		fwrite($filehandle, $content);
		fclose($filehandle);
		return true;
	}
	
	//Add or Create a text file.
	function append_to_file($file, $data) {
		$data = $data. PHP_EOL;
		if (file_exists($file))
		{
			return file_put_contents($file, $data, FILE_APPEND);
		}
		else
		{
			return file_put_contents($file, $data);
		}
	}
	
	//This function shuffles an array while preserving the keys.
	function shuffle_assoc($list) { 
		if (!is_array($list)) return $list; 

		$keys = array_keys($list); 
		shuffle($keys); 
		$random = array(); 
		foreach ($keys as $key) { 
			$random[] = $list[$key]; 
		}
		return $random; 
	} 

		
	function logout(){
		unset($_SESSION['loggedin']);
		unset($_SESSION['user']);
		session_unset();
		header("Refresh:0");
	}

	function notify($msg, $delay = 5, $error_type='Notice'){
		global $notify;
		
		$notify[] = array(escape($msg), $delay, $error_type);
	}	
	
	function notify_write() {
		global $notify;

		if (count($notify)) {	
			foreach ($notify as $id => $v) {
				$script .= "notify('".$v[0]."', '".$v[1]."', '".$v[2]."'); ";
			}
			js($script);
		}
	}

	function sql_error($result, $sql='') {
		global $mysqli;
		
		switch (true) {
			case mysqli_connect_error():
				$info = debug_backtrace();		
			
				error_log(date("n-d-Y g:i a")."\t".$_SERVER['REQUEST_URI']."\nLine: ".$info[1]['line']."\nError #:".mysqli_connect_errno()."\nError Message:".mysqli_connect_error()."\nQuery: ".$sql."\n\n", 3, 'errors.txt');

				mail_error($info[1]['file'], $info[1]['line'], mysqli_error($mysqli), $sql);
				
				notify("Something went wrong here. A report of this error was sent to the administrator's email.", 5, 'Error');
				
				return true;			
			break;
			case mysqli_error($mysqli):
				$info = debug_backtrace();		
			
				error_log(date("n-d-Y g:i a")."\t".$_SERVER['REQUEST_URI']."\nLine: ".$info[1]['line']."\nError #:".mysqli_errno($mysqli)."\nError Message:".mysqli_error($mysqli)."\nQuery: ".$sql."\n\n", 3, 'errors.txt');

				mail_error($info[1]['file'], $info[1]['line'], mysqli_error($mysqli), $sql);
				
				notify("Something went wrong here. A report of this error was sent to the administrator's email.", 5, 'Error');
				
				return true;			
			break;
			default:
				return false;
			break;
		}
	}
	
	function error($errno, $errstr, $errfile, $errline) {
		if ($errno == E_USER_ERROR || $errno == E_USER_WARNING || $errno == E_ERROR) {

			error_log(date("n-d-Y g:i a")."\t".$_SERVER['REQUEST_URI']."\nLine: ".$errline."\nError #: ".$errno."\nError Message: ".$errstr."\n", 3, 'errors.txt');

			mail_error($errfile, $errline, $errstr);
			
			notify("Something went wrong here. A report of this error was sent to the administrator's email.", 5, 'Error');
		}	
		return true;
	}
	
	function mail_error($file, $line, $message, $sql='') {
		global $user;
		global $admin_email;
		$server = $_SERVER;
		ksort($server);
		
		$headers = "From: bugs@goportcanaveral.com\r\n";
		$headers .= "Content-type:text/html;charset=iso-8859-1";

		$body = "<p><strong>Page:</strong>\t\t".basename($file)."</p>";
		$body .= "<p><strong>Line:</strong>\t\t$line</p>";
		$body .= "<p><strong>When:</strong>\t\t".date("n-d-Y g:i a")."</p>";
		$body .= "<p><strong>Error Message:</strong></p><p>$message</p>";
		$body .= "<p><strong>Query:</strong></p><p>$sql</p>";
		$body .= "<p><strong>Server Information:</strong></p><pre>".print_r($server, true)."</pre>";
		
		return mail($admin_email, 'Auto Bug Report - GoPortCanaveral Admin', $body, $headers);
	}
	
	function mail_reset_pw($token, $email) {
		$headers = "From: noreply@goportcanaveral.com\r\n";
		$headers .= "Content-type:text/html;charset=iso-8859-1";

		$body = "<p>Hello, you requested to change your password for GoPortCanaveral.com Admin. Please <a href='http://goportwork.tk/?action=reset_form&token=$token&email=$email'>click here</a> to reset your password.</p>";
		
		return mail($email, 'Reset Password - GoPortCanaveral Admin', $body, $headers);
	}
?>