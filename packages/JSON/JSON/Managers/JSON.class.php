<?php
class JSON
{
	/**
	 * Make Json output and disable Smarty output
	 * @param array $array
	 */
	public static function jsonOutput($array){
		if(Reg::get('packageMgr')->isPluginLoaded("Output", "Smarty")){
			$smartyConfig = ConfigManager::getConfig("Output", "Smarty");
			Reg::get($smartyConfig->Objects->Smarty)->disableOutput();
		}
		
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');
		$log = new \Monolog\Logger('name');
		$log->pushHandler(new \Monolog\Handler\StreamHandler('/var/www/html/your.log', \Monolog\Logger::WARNING));
		    $log->warning(self::jsonEncode($array));


		echo self::jsonEncode($array);
	}
	
	public static function jsonEncode($array){
		return json_encode($array);
	}
	
	public static function jsonDecode($string){
		return json_decode($string);
	}
}
