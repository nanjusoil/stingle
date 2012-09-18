<?
class RewriteAliasURL extends RewriteURL{
	
	private $_aliasMap;
	
	protected $customAliasesDir = 'incs/customUrlAliases/';
	
	public function __construct(Config $config, $aliasMap = false){
		parent::__construct($config);
		
		$this->_aliasMap = $aliasMap;
	}
	
	public function parseAliases(){
		if($this->_aliasMap !== false and isset($_SERVER['REQUEST_URI'])){
			$uri = rawurldecode($_SERVER['REQUEST_URI']);
			static::ensureLastSlash($uri);
			foreach($this->_aliasMap as $urlAlias){
				$uri = str_replace($urlAlias["alias"], $urlAlias["map"], $uri);
			}
			
			$_SERVER['REQUEST_ORIGINAL_URI'] = $_SERVER['REQUEST_URI']; 
			$_SERVER['REQUEST_URI'] = $uri;
		}
	}
	
	public function callParseCustomAliases(){
		if(isset($_SERVER['REQUEST_URI'])){
			$uri = rawurldecode($_SERVER['REQUEST_URI']);
			static::ensureLastSlash($uri);
			if(method_exists($this, 'parseCustomAliases')){
				$uri = $this->parseCustomAliases($uri);
				$_SERVER['REQUEST_URI'] = $uri;
			}
		}
	}
	
	public function addAliasToLink($stringlink){
		$linkWithAlias = $stringlink;
		foreach($this->_aliasMap as $urlAlias){
			if(strpos($stringlink, $urlAlias["map"]) !== false){
				$linkWithAlias = str_replace($urlAlias["map"], $urlAlias["alias"], $stringlink);
				continue;
			}
		}
		return $linkWithAlias;
	}
	
	public function glink($strUrl){
		$strUrl = parent::glink($strUrl);
		return $this->addAliasToLink($strUrl);
	}
}
?>