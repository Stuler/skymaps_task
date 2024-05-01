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

}