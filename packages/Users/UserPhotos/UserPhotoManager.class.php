<?
class UserPhotoManager extends DbAccessor
{
	const TBL_USERS_PHOTOS = 'users_photos';
	
	const STATUS_APPROVED_YES	= 1;
	const STATUS_APPROVED_NO 	= 0;
	
	const STATUS_DEFAULT_YES	= 1;
	const STATUS_DEFAULT_NO 	= 0;
	
	const EXCEPTION_MAX_COUNT_REACHED = 1;
	const EXCEPTION_UNAPROVED_TO_DEFAULT = 2;
	
	private $config;
	
	public function __construct($config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		$this->config = $config;
	}
	
	public function uploadPhoto($file, $userId, $uploadDir = null){
		if(empty($userId)){
			throw new InvalidArgumentException("\$userId is empty!");
		}
		
		if(isset($this->config->maxPhotosCount) and $this->config->maxPhotosCount > 0){
			$filter = new UserPhotosFilter();
			$filter->setUserId($userId);
				
			$userPhotos = $this->getPhotos($filter);
			if(count($userPhotos) >= $this->config->maxPhotosCount){
				throw new UserPhotosException("Maximum photos volume reached for this user.", static::EXCEPTION_MAX_COUNT_REACHED);
			}
		}
		
		if($uploadDir !== null){
			$imageUploaderConfig = new Config();
			$imageUploaderConfig->uploadDir = $uploadDir;
			$image = ImageUploader::upload($file, null, $imageUploaderConfig);
		}
		else{
			$image = ImageUploader::upload($file);
		}
		
		$this->query->exec("INSERT INTO `".Tbl::get('TBL_USERS_PHOTOS')."` 
													(`user_id`, `filename`) 
													VALUES ('$userId', '".$image->fileName."')");
		$photoId = $this->query->getLastInsertId();
		
		return $photoId;
	}
	
	
	/**
	 * Check if user don't have any default photo and 
	 * make default one of it's approved photos
	 * 
	 * @param integer $userId
	 */
	private function correctDefaultPhoto($userId){
		if(empty($userId)){
			throw new InvalidArgumentException("\$userId is empty!");
		}
		
		// If user don't have any default photo
		if(!$this->isUserHasDefaultPhoto($userId)){
			
			$filter = new UserPhotosFilter();
			$filter->setUserId($userId);
			$filter->setApprovedStatus(static::STATUS_APPROVED_YES);
			
			$userPhotos = $this->getPhotos($filter);
			
			if(count($userPhotos)){
				// Set as default first of the user's approved photos
				$this->setAsDefault($userPhotos[0]);
			}
		}
	}
	
	public function isUserHasDefaultPhoto($userId){
		$filter = new UserPhotosFilter();
		$filter->setUserId($userId);
		$filter->setApprovedStatus(static::STATUS_APPROVED_YES);
		$filter->setDefaultStatus(static::STATUS_DEFAULT_YES);
		
		return (count($this->getPhotos($filter)) != 0);
	}
	
	public function isUserHasPhoto($userId){
		$filter = new UserPhotosFilter();
		$filter->setUserId($userId);
		$filter->setApprovedStatus(static::STATUS_APPROVED_YES);
		
		return (count($this->getPhotos($filter)) != 0);
	}
	
	public function isPhotoBelongsToUser($photoId, $userId){
		$filter = new UserPhotosFilter();
		$filter->setPhotoId($photoId);
		
		$photos = $this->getPhotos($filter);
		
		return ($photos[0]->userId == $userId);
	}
	
	public function setAsDefault(UserPhoto $photo){
		if(empty($photo->id)){
			throw new InvalidArgumentException("UserPhoto object has no id!");
		}
		if(empty($photo->userId)){
			$photo = $this->getPhoto($photo->id);
		}
		
		if($photo->approved == static::STATUS_APPROVED_NO){
			throw new UserPhotosException("Unapproved photo can't be set as default.", static::EXCEPTION_UNAPROVED_TO_DEFAULT);
		}
		
		$this->query->exec("UPDATE `".Tbl::get('TBL_USERS_PHOTOS')."` 
								SET `default` = '".static::STATUS_DEFAULT_NO."'
								WHERE `user_id` = '{$photo->userId}' and `default` = '".static::STATUS_DEFAULT_YES."'");
		
		$this->query->exec("UPDATE `".Tbl::get('TBL_USERS_PHOTOS')."` 
								SET `default` = '".static::STATUS_DEFAULT_YES."'
								WHERE `id` = '{$photo->id}' LIMIT 1");
	}
	
	public function deletePhoto(UserPhoto $photo, $uploadDir = null){
		if(empty($photo->id)){
			throw new InvalidArgumentException("UserPhoto object has no id!");
		}
		if(empty($photo->userId)){
			$photo = $this->getPhoto($photo->id);
		}
		
		ImageUploader::deleteImage($photo->fileName, $uploadDir);
		
		
		if(Reg::get('packageMgr')->isPluginLoaded("Image", "ImageCache")){
			Reg::get(ConfigManager::getConfig("Image", "ImageCache")->Objects->ImageCache)->
				clearImageCache($photo->fileName);
		}
		
		if(Reg::get('packageMgr')->isPluginLoaded("Image", "ImageModificator")){
			Reg::get(ConfigManager::getConfig("Image", "ImageModificator")->Objects->ImageModificator)->
				deleteCropSettings($photo->fileName);
		}
		
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_USERS_PHOTOS')."` 
								WHERE `id` = '{$photo->id}' LIMIT 1");
		
		$this->correctDefaultPhoto($photo->userId);
	}
	
	public function approvePhoto(UserPhoto $photo){
		if(empty($photo->id)){
			throw new InvalidArgumentException("UserPhoto object has no id!");
		}
		if(empty($photo->userId)){
			$photo = $this->getPhoto($photo->id);
		}
		
		$this->query->exec("UPDATE `".Tbl::get('TBL_USERS_PHOTOS')."` 
								SET `approved` = '".static::STATUS_APPROVED_YES."'
								WHERE `id` = '{$photo->id}' LIMIT 1");
		
		$this->correctDefaultPhoto($photo->userId);
	}
	
	public function getPhotos(UserPhotosFilter $filter, MysqlPager $pager = null, $cacheMinutes = null){
		
		if($filter == null){
			$filter = new UserPhotosFilter();
		}
		
		$sqlQuery = $filter->getSQL();
		
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery, $cacheMinutes);
		}
		else{
			$this->query->exec($sqlQuery, $cacheMinutes);
		}
		
		$userPhotos = array();
		if($this->query->countRecords()){
			while(($row = $this->query->fetchRecord()) != false){
				array_push($userPhotos, static::getFilledUserPhoto($row));
			}
		}

		return $userPhotos;
	}
	
	public function getPhoto($photoId){
		$filter = new UserPhotosFilter();
		$filter->setPhotoId($photoId);
		
		$photos = $this->getPhotos($filter);
		
		if(count($photos)){
			return $photos[0];
		}
		return false;
	}
	
	public static function getFilledUserPhoto($dbRow){
		$userPhotoObj = new UserPhoto();
		$userPhotoObj->id = $dbRow['id'];
		$userPhotoObj->userId = $dbRow['user_id'];
		$userPhotoObj->fileName = $dbRow['filename'];
		$userPhotoObj->default = $dbRow['default'];
		$userPhotoObj->approved = $dbRow['approved'];
		
		return $userPhotoObj;
	}
}
?>