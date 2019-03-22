<?php
namespace OCA\Virwork_API\Db;

use OCA\Virwork_API\Exceptions\VirworkAuthNotFoundException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Db\Mapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class VirworkGroupAuthMapper extends QBMapper {

	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'virwork_auth_group');
	}
    
     /**
      * @param string $group
      * @throws VirworkAuthNotFoundException
      */
	public function getAuthByName($group)  {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName())
			->where($qb->expr()->eq('group', $qb->createNamedParameter($group)));
	 
        return $this->findEntity($qb);
        
	}

     /**
      * @throws VirworkAuthNotFoundException
      */
	 public function findAll() {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from($this->getTableName());
        return $this->findEntities($qb);
    }
}
