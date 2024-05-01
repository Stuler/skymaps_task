<?php

namespace App\Model\Repository;

use Nette\Utils\ArrayHash;

class CompanyRepository extends AbstractRepository {

	protected $table = "company";

	/**
	 * Save the values to the database.
	 */
	public function save(ArrayHash|array $values): int {
		return parent::save($values);
	}

}