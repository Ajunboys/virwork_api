<?php
namespace OCA\Virwork_API\Db;

use OCA\Virwork_API\Exceptions\VirworkAuthNotFoundException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Db\Mapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class VirworkAuthMapper extends QBMapper {

	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'virwork_auth');
	}
    
     /**
      * @param string $username
      * @throws VirworkAuthNotFoundException
      */
	public function getAuthByUsername($username)  {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName())
			->where($qb->expr()->eq('username', $qb->createNamedParameter($username)));
	    /*
		$cursor = $qb->execute();
        $row = $cursor->fetch();
        $cursor->closeCursor();

        return $row;
        */
        return $this->findEntity($qb);
        
	}

     /**
      * @throws VirworkAuthNotFoundException
      */
	 public function findAll() {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from($this->getTableName());
		
        /*
        $cursor = $qb->execute();
        $row = $cursor->fetch();
        $cursor->closeCursor();

        return $row;
        */
        return $this->findEntities($qb);
    }
}
