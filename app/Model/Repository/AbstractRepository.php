<?php

namespace App\Model\Repository;

use Nette\Database\Context;
use Nette\Database\Table\Selection;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;

class AbstractRepository {
	use SmartObject;

	/** @var Context */
	protected $db;

	/** @var string */
	protected $table = "";

	/**
	 * @param Context $db
	 */
	function __construct(Context $db) {
		$this->db = $db;
	}

	/**
	 * @param $id
	 * @return \Nette\Database\Table\Selection
	 */
	public function findById($id) {
		return $this->db->table($this->table)->wherePrimary($id);
	}

	/**
	 * @param $id
	 * @return \Nette\Database\Table\ActiveRow|null
	 */
	public function fetchById($id) {
		return $this->findById($id)->fetch();
	}

	/**
	 * @return Selection
	 */
	public function findAll(): Selection {
		return $this->db->table($this->table);
	}

	public function findAllActive(): Selection {
		return $this->findAll()->where("$this->table.date_deleted IS NULL");
	}

	/**
	 * @param array|ArrayHash $values
	 */
	public function save(ArrayHash|array $values): int {
		if ($values instanceof ArrayHash) {
			$values = (array)$values;
		}

		if ($this->isSetId($values)) {
			$id = $values['id'];
			unset($values['id']);
			$this->db->query("UPDATE `$this->table` SET ? WHERE id = ?", $values, $id);
			return intval($id);
		} else {
			if (array_key_exists('id', $values)) {
				unset($values['id']);
			}
			$this->db->query("INSERT INTO `$this->table`", $values);

			return intval($this->db->getInsertId());
		}
	}

	/**
	 * detekce, zda jde o editaci nebo vytvoření nového záznamu
	 * @param array|ArrayHash $values
	 * @return bool
	 */
	public function isSetId($values) {
		return array_key_exists('id', $values) && intval($values['id']) > 0;
	}

	public function getColumnNames($tableName): array {
		return $this->db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME ='$tableName' AND TABLE_SCHEMA='skss'")
				->fetchPairs(null, "COLUMN_NAME");
	}

	/**
	 * @param int|string $id
	 * @return int 1|0
	 */
	public function delete($id): int {
		return $this->db->table($this->table)->wherePrimary($id)->delete();
	}

	/**
	 * Nastaví položku jako smazanou
	 * @param int|string  $id
	 * @param null|string $column
	 */
	public function softDelete($id, $column = 'is_deleted') {
		$this->db->query("UPDATE `$this->table` SET $column = 1 WHERE id = ?", $id);
	}

	/**
	 * Nastaví položku na smazanou v čase a uživatelem
	 * @param int $id
	 * @param int $loginId
	 */
	public function softDeleteDate(int $id, int $loginId) {
		$args = ["date_deleted" => new \DateTime, "deleted_by" => $loginId];
		$this->db->query("UPDATE `$this->table` SET ? WHERE id = ?", $args, $id);
	}
}