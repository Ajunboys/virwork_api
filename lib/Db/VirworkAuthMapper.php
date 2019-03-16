<?php
namespace OCA\Virwork_API\Db;

use OCA\Virwork_API\Exceptions\VirworkAuthNotFoundException;
use OCP\AppFramework\Db\Mapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class VirworkAuthMapper extends Mapper {

	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'virwork_auth');
	}
   /**
	 * @param string $username
	 * @return VirworkAuth
	 * @throws VirworkAuthNotFoundException
	 */
	public function getAuthByUsername($username) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName())
			->where($qb->expr()->eq('username', $qb->createNamedParameter($username)));
		
		return $this->findEntity($qb) instanceof VirworkAuth;
	}

	 public function findAll() {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from($this->getTableName());

        return $this->findEntities($qb);
    }
}
