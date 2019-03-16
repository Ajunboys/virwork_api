<?php
namespace OCA\Virwork_API\Db;

use OCA\Virwork_API\Exceptions\VirworkAuthGroupAccessNotFoundException;
use OCP\AppFramework\Db\Mapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class VirworkAuthGroupAccessMapper extends Mapper {

	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'virwork_auth_group_access');
	}

	/**
	 * @param string $group
	 * @return VirworkAuthGroupAccess
	 * @throws VirworkAuthGroupAccessNotFoundException
	 */
	public function getGroupAccessByIdentifier($group) {
		$qb = $this->db->getQueryBuilder();
		$qb
			->select('*')
			->from($this->tableName)
			->where($qb->expr()->eq('group', $qb->createNamedParameter($group)));
		$result = $qb->execute();
		$row = $result->fetch();
		$result->closeCursor();
		if($row === false) {
			throw new VirworkAuthGroupAccessNotFoundException();
		}
		return VirworkAuthGroupAccess::fromRow($row);
	}

	/**
	 * @return VirworkAuthGroupAccess[]
	 */
	public function getGroupAccessList() {
		$qb = $this->db->getQueryBuilder();
		$qb
			->select('*')
			->from($this->tableName);

		return $this->findEntities($qb->getSQL());
	}
}
