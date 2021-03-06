<?php

namespace go\core\jmap;

use go\core\acl\model\Acl;
use go\core\App;
use go\core\db\Query;
use go\core\jmap\exception\InvalidArguments;
use go\core\orm\Entity;
use go\core\orm\TextFilter;

abstract class ReadOnlyEntityController extends Controller {	
	
	
	/**
	 * The class name of the entity this controller is for.
	 * 
	 * @return string
	 */
	abstract protected function entityClass();

	
	/**
	 * Creates a short name based on the class name.
	 * 
	 * This is used to generate response name. 
	 * 
	 * eg. class go\modules\community\notes\model\Note becomes just "note"
	 * 
	 * @return string
	 */
	protected function getShortName() {
		$cls = $this->entityClass();
		return lcfirst(substr($cls, strrpos($cls, '\\') + 1));
	}
	
	/**
	 * Creates a short plural name 
	 * 
	 * @see getShortName()
	 * 
	 * @return string
	 */
	protected function getShortPluralName() {
		
		$shortName = $this->getShortName();
		
		if(substr($shortName, -1) == 'y') {
			return substr($shortName, 0, -1) . 'ies';
		} else
		{
			return $shortName . 's';
		}
	}
	
	/**
	 * 
	 * @param array $params
	 * @return Query
	 */
	protected function getQueryQuery($params) {
		$cls = $this->entityClass();

		$query = $cls::find(['id'])
						->limit($params['limit'])
						->offset($params['position'])
						->orderBy($this->transformSort($params['sort']));

		$filterClass = $this->getFilterClass();
		$filters = $filterClass::fromArray($params['filter'], $cls, $query);		
		
		$cls::applyAclToQuery($query, $this->getQueryPermissionLevel($filters));

		//we don't need entities here. Just a list of id's.
		$query->selectSingleValue($query->getTableAlias() . '.id');
		
		return $query;
	}
	
	
	private function getQueryPermissionLevel($filters) {
		
		foreach($filters as $filter) {
			if($filter instanceof \go\core\orm\Filter) {
				return $filter->getPermissionLevel();
			}
		}
		
		return Acl::LEVEL_READ;
		
	}
	
	/**
	 * Get the class to filter entities
	 * 
	 * @return string
	 */
	private function getFilterClass() {		
		$cls = $this->entityClass();
		$default = $cls . 'Filter';
		if (class_exists($cls . 'Filter')) {
			return $default;
		} else {
			return TextFilter::class;
		}
	}
	
	/**
	 * Takes the request arguments, validates them and fills it with defaults.
	 * 
	 * @param array $params
	 * @return array
	 * @throws InvalidArguments
	 */
	protected function paramsQuery(array $params) {
		if(!isset($params['limit'])) {
			$params['limit'] = Capabilities::get()->maxObjectsInGet;
		}
		
//		if($params['limit'] > self::MAX_LIMIT) {
//			throw new core\jmap\exception\InvalidArguments('Limit must be lower than ' . self::MAX_LIMIT);
//		}
		
		if ($params['limit'] < 1) {
			throw new InvalidArguments("Limit MUST be greater than 0");
		}
		//cap at max of 50
		$params['limit'] = min([$params['limit'], Capabilities::get()->maxObjectsInGet]);
		
		if(!isset($params['position'])) {
			$params['position'] = 0;
		}

		if ($params['position'] < 0) {
			throw new InvalidArguments("Position MUST not be negative");
		}
		
		if(!isset($params['sort'])) {
			$params['sort'] = [];
		}
		
		if(!isset($params['filter'])) {
			$params['filter'] = [];
		}
		
		if(!isset($params['accountId'])) {
			$params['accountId'] = null;
		}
		
		return $params;
	}

	/**
	 * Handles the Foo entity's  "getFooList" command
	 * 
	 * @param array $params
	 */
	public function query($params) {
		
		$p = $this->paramsQuery($params);
		$idsQuery = $this->getQueryQuery($p);
		
		$totalQuery = clone $idsQuery;
		$total = (int) $totalQuery
										->selectSingleValue("count(*)")
										->orderBy(null)
										->limit(1)
										->offset(0)
										->execute()
										->fetch();

		$state = $this->getState();

		Response::get()->addResponse([
				'accountId' => $p['accountId'],
				'state' => $state,
				'ids' => $idsQuery->all(),
				'notfound' => [],
				'total' => $total,
				'canCalculateUpdates' => false
		]);
	}
	
	protected function getState() {
		$cls = $this->entityClass();
		
		//entities that don't support syncing can be listed and fetched with the read only controller
		return method_exists($cls, "getState") ? $cls::getState() : null;
	}

	/**
	 * Transforms ['name ASC'] into: ['name' => 'ASC']
	 * 
	 * @param string[] $sort
	 * @return array[]
	 */
	protected function transformSort($sort) {
		$transformed = [];

		foreach ($sort as $s) {
			list($column, $direction) = explode(' ', $s);
			$transformed[$column] = $direction;
		}

		return $transformed;
	}
	
	

	/**
	 * 
	 * @param int $id
	 * @return boolean|Entity
	 */
	protected function getEntity($id, array $properties = []) {
		$cls = $this->entityClass();

		$entity = $cls::findById($id, $properties);

		if(!$entity){
			return false;
		}
		
		if (isset($entity->deletedAt)) {
			return false;
		}
		
		if(!$entity->hasPermissionLevel(Acl::LEVEL_READ)) {
//			throw new Forbidden();
			
			App::get()->debug("Forbidden: ".$cls.": ".$id);
							
			return false; //not found
		}

		return $entity;
	}

	
	/**
	 * Takes the request arguments, validates them and fills it with defaults.
	 * 
	 * @param array $params
	 * @return array
	 * @throws InvalidArguments
	 */
	protected function paramsGet(array $params) {
		if(isset($params['ids']) && !is_array($params['ids'])) {
			throw new InvalidArguments("ids must be of type array");
		}
		
		if(!isset($params['properties'])) {
			$params['properties'] = [];
		}
		
		if(!isset($params['accountId'])) {
			$params['accountId'] = [];
		}
		
		return $params;
	}
	
	/**
	 * Override to add more query options for the "get" method.
	 * @return Query
	 */
	protected function getGetQuery($params) {
		$cls = $this->entityClass();
		
		return $cls::find($params['properties']);
	}

	
	/**
	 * Handles the Foo entity's getFoo command
	 * 
	 * @param array $params
	 */
	public function get($params) {
		
		$p = $this->paramsGet($params);

		$result = [
				'accountId' => $p['accountId'],
				'state' => $this->getState(),
				'list' => [],
				'notFound' => []
		];
		
		$query = $this->getGetQuery($p);		
		
		if(empty($p['ids'])) {
			$result['list'] = $query->all();
		} else
		{
			$result['list'] = $query->where($query->getTableAlias(). '.id', 'IN', $p['ids'])->all();
			
			$foundIds = [];
			
			foreach($result['list'] as $e) {
				$foundIds[] = $e->id;
			}
			$result['notFound'] = array_values(array_diff($p['ids'], $foundIds));			
		}	

		Response::get()->addResponse($result);
	}
}
