<?
class LoaderSiteNavigation extends Loader{
	protected function includes(){
		require_once ('Nav.class.php');
		require_once ('RequestParser.class.php');
		require_once ('Controller.class.php');
	}
	
	protected function customInitBeforeObjects(){
		$this->controller = new Controller($this->config);
	}
	
	protected function loadRequestParser(){
		$this->requestParser = new RequestParser($this->config->AuxConfig);
		$this->register($this->requestParser);
	}
	
	public function hookParse(){
		Reg::register($this->config->ObjectsIgnored->Nav, $this->requestParser->parse());
	}
	
	public function hookExecController(){
		$this->controller->exec();
	}
}
?>