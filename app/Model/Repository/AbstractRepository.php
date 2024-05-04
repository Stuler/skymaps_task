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
	 * New record or existing record update detection
	 * @param array|ArrayHash $values
	 * @return bool
	 */
	public function isSetId($values) {
		return array_key_exists('id', $values) && intval($values['id']) > 0;
	}

	/**
	 * @param int|string $id
	 * @return int 1|0
	 */
	public function delete($id): int {
		return $this->db->table($this->table)->wherePrimary($id)->delete();
	}
}