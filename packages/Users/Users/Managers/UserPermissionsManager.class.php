<?php
class UserPermissionsManager extends DbAccessor{
	
	
	public function getPermissions(UserPermissionsFilter $filter = null){
		if($filter === null){
			$filter = new UserPermissionsFilter();
		}
		
		$this->query->exec($filter->getSQL());
		
		$perms = array();
		if($this->query->countRecords()){
			foreach($this->query->fetchRecords() as $row){
				array_push($perms, static::getPermissionsObjectFromData($row));
			}
		}
		
		return $perms;
	}
	
	public function addPermissionToUser(Permission $perm, User $user, $args = null){
		$qb = new QueryBuilder();
		
		$values = array(
				'user_id' => $user->id,
				'permission_id' => $perm->id
		);
		
		if($args !== null){
			$values['args'] = serialize($args);
		}
		
		$qb->insert(Tbl::get('TBL_USERS_PERMISSIONS', 'UserManager'))
			->values($values);
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function addPermissionToGroup(Permission $perm, Group $group, $args = null){
		$qb = new QueryBuilder();
		
		$values = array(
				'group_id' => $group->id,
				'permission_id' => $perm->id
		);
		
		if($args !== null){
			$values['args'] = serialize($args);
		}
		
		$qb->insert(Tbl::get('TBL_GROUPS_PERMISSIONS', 'UserManager'))
			->values($values);
		
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function removePermissionFromUser(Permission $perm, User $user){
		$qb = new QueryBuilder();
	
		$qb->delete(Tbl::get('TBL_USERS_PERMISSIONS', 'UserManager'))
		->where($qb->expr()->equal(new Field('permission_id'), $perm->id))
		->andWhere($qb->expr()->equal(new Field('user_id'), $user->id));
	
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public function removePermissionFromGroup(Permission $perm, Group $group){
		$qb = new QueryBuilder();
	
		$qb->delete(Tbl::get('TBL_GROUPS_PERMISSIONS', 'UserManager'))
			->where($qb->expr()->equal(new Field('permission_id'), $perm->id))
			->andWhere($qb->expr()->equal(new Field('group_id'), $group->id));
	
		return $this->query->exec($qb->getSQL())->affected();
	}
	
	public static function getPermissionsObjectFromData($data){
		$perm = new Permission();
		
		$perm->id = $data['id'];
		$perm->name = $data['name'];
		$perm->description = $data['description'];
		
		if(isset($data['args'])){
			try{
				$perm->args = unserialize($data['args']);
			}
			catch(ErrorException $e){
				$perm->args = null;
			}
		}
		
		return $perm;
	}
}
