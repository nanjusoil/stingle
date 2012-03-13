<?
function format_exception(Exception $e, $insert_pre = false){
	$message = get_class($e) . "\n" .
	"Message:\n" . $e->getMessage() . "\n\n" .
	"File: " . $e->getFile() . " on line " . $e->getLine() . "\n\n" .
	"Trace:\n" . $e->getTraceAsString() . "\n\n" .
	"Get:\n". print_r($_GET,true) . "\n\n" .
	"Post:\n". print_r($_POST,true) . "\n\n" .
	"Cookie:\n". print_r($_COOKIE,true) . "\n\n" .
	"Server:\n". print_r($_SERVER,true) . "\n\n" .
	"Code: " . $e->getCode();
	if($insert_pre){
		$message = "<pre>$message</pre>";
	}

	return $message;
}

function ensurePathLastSlash(&$path){
	if(substr($path, strlen($path)-1) != '/'){
		$path .= '/';
	}
}

/**
 * Function return size with it's units.<br>
 * e.g. 2 M, 35 Kb, 346 b
 *
 * @param double $size
 * @return string
 */
function determine_size_units($size = 0){
	if($size < 1024){
		$size .= ' b';
	}
	if($size >= 1024 and $size < 1048576){
		$size = round($size /= 1024);
		$size .= ' Kb';
	}
	if($size >= 1048576){
		$size = round($size /= 1048576);
		$size .= ' M';
	}
	return $size;
}

function get_age ($birthday){
	$explodedArray = explode("-",$birthday);
	if(count($explodedArray) != 3){
		return false;
	}
	
	list($Year, $Month, $Day) = $explodedArray;

	$YearDiff = date("Y") - $Year;
	if(date("m") < $Month || (date("m") == $Month && date("d") < $Day)){
		$YearDiff--;
	}
	return $YearDiff;
}

/**
 * Create random value on give criteria
 *
 * @param int $length
 * @param string $type (mixed, chars, digits)
 * @return string
 */
function generateRandomString($length, $type = null){
	if($length === null){
		$length = 12;
	}
	if($type === null){
		$type = 'mixed';
	}
	
	if(($type != 'mixed') && ($type != 'chars') && ($type != 'digits')) return false;

	$rand_value = '';
	while(strlen($rand_value) < $length){
		if($type == 'digits'){
			$char = myRand(0, 9);
		}
		else{
			$char = chr(myRand(0, 255));
		}
		if($type == 'mixed'){
			if(preg_match('/^[a-z0-9]$/i', $char)) $rand_value .= $char;
		}
		elseif($type == 'chars'){
			if(preg_match('/^[a-z]$/i', $char)) $rand_value .= $char;
		}
		elseif($type == 'digits'){
			if(preg_match('^[0-9]$', $char)) $rand_value .= $char;
		}
	}

	return $rand_value;
}

function myRand($min = null, $max = null){
	static $seeded;

	if(!isset($seeded)){
		mt_srand((double)microtime() * 1000000);
		$seeded = true;
	}

	if(isset($min) && isset($max)){
		if($min >= $max){
			return $min;
		}
		else{
			return mt_rand($min, $max);
		}
	}
	else{
		return mt_rand();
	}
}

/**
 * Analog of empty() but taking function return value
 *
 * @param mixed $var
 * @return boolean
 */
function fempty($var){
	if($var === '' || $var === 0 || $var == null || $var === false || $var === "0" || $var === array()){
		return true;
	}
	return false;
}

/**
 * Redirect to other page
 *
 * @param string $url
 */
function redirect($url){
	header('Location: ' . $url);
	exit();
}

/**
 * Checks validity of given email addess
 * Also check's domain name for validity
 *
 * @param string $address
 * @return boolean
 */
function valid_email($address){
	$ret_val = false;
	if(filter_var($address, FILTER_VALIDATE_EMAIL)){
		$host = substr($address, strpos($address, '@') + 1);

		//$host=$host . '.';
		if(function_exists('getmxrr')){
			if(getmxrr($host, $mxhosts) == false && gethostbyname($host) == $host) $ret_val = false;
			else
			$ret_val = true;
		}
		else{
			if(gethostbyname($host) == $host) $ret_val = false;
			else
			$ret_val = true;
		}
	}
	return $ret_val;
}

function getCurrentUrl($exclude = array()){
	$siteNavConfig = ConfigManager::getConfig("SiteNavigation")->AuxConfig;
	$currentUrl = RewriteURL::generateCleanBaseLink(Reg::get("nav")->module, Reg::get("nav")->page, $siteNavConfig->firstLevelDefaultValue);
	$currentUrl = RewriteURL::ensureOutputLastDelimiter($currentUrl);
	$currentUrl .= get_all_get_params($exclude);

	return Reg::get('rewriteURL')->glink($currentUrl);
}

/**
 * Build the string of GET parameters
 *
 * @param array $exclude_array
 * @return string
 */
function get_all_get_params(array $exclude_array = array()){
	$config = ConfigManager::getConfig("RewriteURL")->AuxConfig;
	$return_string = '';

	foreach($exclude_array as &$exclude){
		$exclude = trim($exclude);
	}

	switch($config->source_link_style){
		case "nice" :
			$delimiter = '/';
			if(is_array($_GET) && (sizeof($_GET) > 0)){
				reset($_GET);
				while((list($key, $value) = each($_GET)) != false){
					if($key == RewriteURL::getSystemModuleName() or $key == RewriteURL::getSystemPageName()){
						continue;
					}
					if(!in_array($key, $exclude_array)){
						$return_string .= $key . ':' . rawurlencode($value) . $delimiter;
					}
				}
			}
			break;
		case "default" :
		default :
			$delimiter = '&';
			if(is_array($_GET) && (sizeof($_GET) > 0)){
				reset($_GET);
				while((list($key, $value) = each($_GET)) != false){
					if($key == RewriteURL::getSystemModuleName() or $key == RewriteURL::getSystemPageName()){
						continue;
					}
					if(!in_array($key, $exclude_array)){
						$return_string .= $key . '=' . rawurlencode($value) . $delimiter;
					}
				}
			}
			break;
	}

	return $return_string;
}

/**
 * Adds leading zero to number
 *
 * @param double $digit
 * @return string
 */

function add_leading_zero($digit){
	if(abs($digit) < 10){
		if($digit < 0){
			return '-0' . abs($digit);
		}
		else{
			return '0' . $digit;
		}
	}
	else{
		return $digit;
	}
}

/**
 * Cuts given string with given number of characters preserving words
 *
 * @param string $string
 * @param int $char_limit
 * @param string $trailing_chars
 * @return string
 */
function smart_cut($string, $char_limit, $trailing_chars = '...'){
	if(empty($string) or $char_limit < 1){
		return '';
	}
	$arr = explode(' ', $string);
	$ret_str = '';
	$lend = 0;
	foreach($arr as $word){
		if(mb_strlen($ret_str, "UTF-8") + mb_strlen($word, "UTF-8") <= $char_limit){
			$ret_str .= $word . ' ';
		}
		else{
			$ret_str = mb_substr($ret_str, 0, mb_strlen($ret_str) - 1, "UTF-8") . $trailing_chars;
			$lend = 1;
			break;
		}
	}
	if($lend == 0){
		$ret_str = mb_substr($ret_str, 0, mb_strlen($ret_str) - 1, "UTF-8");
	}
	return $ret_str;
}

/**
 * Returns number of days for given month and year
 *
 * @param int $month
 * @param int $year
 * @return int Number of days
 */
function getMonthDays($month, $year){
	//If claendar extantion is installed.
	if(is_callable("cal_days_in_month")){
		return cal_days_in_month(CAL_GREGORIAN, $month, $year);
	}
	else{ //Get it directly
		return date("d", mktime(0, 0, 0, $month + 1, 0, $year));
	}
}

function urlFriendlyText($string){
	return preg_replace('/\s+/i', "-", $string);
}

function removeAccents($msg){
	$a = array(
	'/[ÂÀÁÄÃ]/u'=>'A',
	'/[âãàáä]/u'=>'a',
	'/[ÊÈÉË]/u'=>'E',
	'/[êèéë]/u'=>'e',
	'/[ÎÍÌÏ]/u'=>'I',
	'/[îíìï]/u'=>'i',
	'/[ÔÕÒÓÖ]/u'=>'O',
	'/[ôõòóö]/u'=>'o',
	'/[ÛÙÚÜ]/u'=>'U',
	'/[ûúùü]/u'=>'u',
	'/ç/u'=>'c',
	'/Ç/u'=> 'C');
	return preg_replace(array_keys($a), array_values($a), $msg);
}

/**
 * Get value of array by key
 *
 * @param array $array
 * @param string $key
 * @return string|array
 */
function getValue($array, $key){
	if(isset($array[$key])){
    	return $array[$key];
	}
	return false;
}

/**
 * Is site now in production mode
 * 
 * @return boolean
 */
function isInProductionMode(){
	if (SiteMode::get() == SiteMode::MODE_PRODUCTION){
		return true;
	}
	return false;
}

/**
 * Is site now in development mode
 * 
 * @return boolean
 */
function isInDevelopmentMode(){
	if (SiteMode::get() == SiteMode::MODE_DEVELOPMENT){
		return true;
	}
	return false;
}

/**
 * Function does parsing constants and replaces all regex with given values
 * 
 * @param string $text
 * @param array $params
 * @return string|bollean|false
 */
function parse($text, $params = null){
	if(!isset($text)){
		return false;
	}
	else {
		if(empty($params)){
			return $text;
		}
		else {
			$patterns = array();
			$replacements = array();
			foreach($params as $pattern=>$replacement){
				$patterns[] = '/\['.$pattern.'\]/';
				$replacements[] = $replacement;
			}
			return preg_replace($patterns, $replacements, $text);
		}
	}
	return false;
}
?>