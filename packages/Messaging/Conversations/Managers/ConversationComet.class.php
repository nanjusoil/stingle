<?
class ConversationComet extends CometChunk{
	
	protected $lastId;
	protected $newLastId;
	protected $uuid;
	
	protected $newMessages;
	
	public function __construct($params){
		$this->setName('conv');
		
		$this->lastId = $params['lastId'];
		$this->uuid = $params['uuid'];
	}
	
	
	public function run(){
		$filter = new ConversationMessagesFilter();
		
		$filter->setUUID($this->uuid);
		$filter->setIdGreater($this->lastId);
		
		$messages = Reg::get('convMgr')->getConversationMessages($filter);
		
		if(count($messages) > 0){
			$this->newMessages = $messages;
			$this->newLastId = $messages[count($messages)-1]->id;
			$this->setIsAnyData();
		}
	}
	
	public function getDataArray(){
		$responseArray = array();
		
		if(!empty($this->newLastId)){
			$responseArray['lastId'] = $this->newLastId;
		}
		else{
			$responseArray['lastId'] = Reg::get('convMgr')->getMessagesLastId();
		}
		
		if(is_array($this->newMessages) and count($this->newMessages)>0){
			$responseArray['messages'] = $this->newMessages;
		}
		
		return $responseArray;
	}
}
?>