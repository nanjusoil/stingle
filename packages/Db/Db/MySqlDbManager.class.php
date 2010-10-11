<?
class MySqlDbManager
{
	/**
	 * Default database instance key
	 *
	 * @var string
	 */
	private static $default_key = 'defaultKey';

	/**
	 * Multiton instances
	 *
	 * @var MySqlDatabase
	 */
	protected static $instances;
	
    private static $dbClassName = "MySqlDatabase";
    private static $queryClassName = "MySqlQuery";
	
	/**
     * Creates a new database instance and returns key for it
     *
     * @param $server
     * @param $username
     * @param $password
     * @param $db_name
     * @param $persistency
     *
     * @return mixed Automatically generated key for current instance
     */
    public static function createInstance($server, $username, $password, $db_name, $persistency = true)
    {
    	$key = self::generateInstanceKey($server);
		try{
			self::$instances[$key] = new self::$dbClassName($server, $username, $password, $db_name, $persistency);
		}
		catch(MySqlException $e){
			unset(self::$instances[$key]);
			throw $e;
		}

		if( count(self::$instances) == 1 ){
			self::setDefaultInstanceByKey($key);
		}

		return $key;
    }
    
	/**
     * Returns an instance of Db_MySqlDatabase
     * by given key
     *
     * @param $key instance key
     *
     * @return MySqlDatabase
     */
    public static function getDbObject($instanceKey = null){
    	if( $instanceKey === null ){
    		$instanceKey = self::$default_key;
    	}
    	
    	if( !isset(self::$instances[$instanceKey]) or !is_a(self::$instances[$instanceKey], "MySqlDatabase")){
    		throw new MySqlException("Database instance with given key $instanceKey not found.");
    	}
    	
    	return self::$instances[$instanceKey];
    }
    
	/**
     * Returns an instance of Db_MySqlDatabase
     * by given key
     *
     * @param $key instance key
     *
     * @return MySqlDatabase
     */
    public static function getQueryObject($instanceKey = null){
    	$db = self::getDbObject($instanceKey);
    	return new self::$queryClassName($db);
    }
    
	/**
     * Set DB class name
     */
    public static function setDbClassName($name){
    	if( empty($name) ){
    		throw new InvalidArgumentException("Class name is empty");
    	}

    	self::$dbClassName = $name;
    }
    
	/**
     * Set Query class name
     */
    public static function setQueryClassName($name){
    	if( empty($name) ){
    		throw new InvalidArgumentException("Class name is empty");
    	}

    	self::$queryClassName = $name;
    }
    
	/**
     * Set default instance the instance that have given key 
     */
    public static function setDefaultInstanceByKey($key){
    	if( !isset(self::$instances[$key]) ){
    		throw new InvalidArgumentException("Wrong key for database instance.");
    	}

    	self::$default_key = $key;
    }
    
	/**
     * Get default instance key
     * @return string 
     */
    public static function getDefaultInstanceKey(){
    	return self::$default_key;
    }
    
	/**
	 *
	 * @param $server
	 * @return string
	 */
	private static function generateInstanceKey($server){
		return $server . "." . count(self::$instances);
	}
}
?>