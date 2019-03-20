<?php
class LoaderMemcache extends Loader{
	protected function includes(){
		stingleInclude ('Exceptions/MemcacheException.class.php');
		stingleInclude ('Managers/MemcacheWrapper.class.php');
		stingleInclude ('Managers/MySqlQueryMemcache.class.php');
	}
	
	protected function loadMemcache(){
		$this->register(new MemcacheWrapper($this->config->AuxConfig));
	}
	
	protected function loadQuery(){
		MySqlDbManager::setQueryClassName("MySqlQueryMemcache");
		$query = new MySqlQueryMemcache(Reg::get(ConfigManager::getConfig("Db","Db")->Objects->Db));
		$this->register($query);
	}
	
	public function hookAddMemcacheTimeConfig(Array $params){
		extract($params);
		if(isset($pluginConfig->Memcache)){
			foreach($pluginConfig->Memcache->toArray() as $className => $cacheTime){
				ConfigManager::addConfig(array('Db','Memcache','AuxConfig','Time'), $className, $cacheTime);
			}
		}
	}
}
