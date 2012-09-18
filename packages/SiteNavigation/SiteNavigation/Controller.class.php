<?
class Controller
{
	private $config;
	
	public function __construct(Config $config){
		$this->config = $config;
	}
	
	public function exec(){
		$nav = Reg::get(ConfigManager::getConfig("SiteNavigation", "SiteNavigation")->ObjectsIgnored->Nav);
		$levels = ConfigManager::getConfig("RewriteURL", "RewriteURL")->AuxConfig->levels->toArray();
		
		// Starting include path
		$includePath = $this->config->modulesDir . '/';
		for($i = 0; $i < count($levels)-1; $i++){
			$level = $levels[$i];
			if(isset($nav->$level) and !empty($nav->$level)){
				// Assembing $includePath to use later
				$includePath .= $nav->$level . '/';
				
				if(is_dir($includePath . $nav->$levels[$i+1]) && is_file($includePath . $nav->$levels[$i+1] . ".php")){
					throw new RuntimeException("You can't have both folder and file with same name. Path: $includePath, colliding filename: {$nav->$levels[$i+1]}");
				}
				
				// Check if on next level we don't have directory anymore stop here
				if(isset($levels[$i+1]) and !is_dir($includePath . $nav->$levels[$i+1])){
					break;
				}
			}
		}
		
		// Include config.php in includePath if exists
		if(@file_exists($includePath . "config.php")){
			include ($includePath . "config.php");
		}

		// Include common.php in includePath if exists
		if(@file_exists($includePath . "common.php")){
			include ($includePath . "common.php");
		}
		
		// Include action in includePath if exists
		if(isset($nav->{$this->config->actionName}) and !empty($nav->{$this->config->actionName})){
			if(@file_exists($includePath . "actions/{$nav->{$this->config->actionName}}.php")){
				include ($includePath . "actions/{$nav->{$this->config->actionName}}.php");
			}
		}
		
		// Include main controller file in includePath if exists
		if(file_exists($includePath . "{$nav->{$levels[$i+1]}}.php")){
			include ($includePath . "{$nav->{$levels[$i+1]}}.php");
		}
			
	}
}
?>