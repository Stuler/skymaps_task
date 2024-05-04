<?php

namespace App\Model\Repository;

use Nette\Utils\ArrayHash;

class EmployeeRepository extends AbstractRepository {

	protected $table = "employee";

	/**
	 * Save the values to the database.
	 */
	public function save(ArrayHash|array $values): int {
		return parent::save($values);
	}

	/**
	 * Delete all employees by company id.
	 */
	public function deleteByCompanyId(int $id): void {
		$this->db->table($this->table)
				->where('company_id', $id)
				->delete();
	}

}