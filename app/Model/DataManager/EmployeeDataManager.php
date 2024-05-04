<?php

namespace App\Model\DataManager;

use App\Model\Repository\EmployeeRepository;
use App\Model\TDO\TDbEmployee;

class EmployeeDataManager {

	public function __construct(
		private readonly EmployeeRepository $employeeRepository
	) {
	}

	/**
	 * Saves the employee to the database.
	 * @throws \Exception
	 */
	public function save(\stdClass $values): int {
		$saveValues = new TDbEmployee();
		$saveValues->id = $values->id;
		$saveValues->name = $values->name;
		$saveValues->company_id = $values->company_id;
		$saveValues->is_ceo = $this->validateCeo($values);
		$saveValues->manager_id = $values->is_ceo ? null : $values->manager_id;
		$saveValues->phone_number = $values->phone_number;
		return $this->employeeRepository->save($saveValues);
	}

	/**
	 * Returns the default values for the employee form.
	 */
	public function getDefaults(?int $employeeId, ?int $companyId): array {
		/** @var TDbEmployee $employee */
		$employee = $this->employeeRepository->fetchById($employeeId);
		if ($employee) {
			return [
				'id' => $employee->id,
				'name' => $employee->name,
				'company_id' => $employee->company_id,
				'manager_id' => $employee->manager_id,
				'is_ceo' => $employee->is_ceo,
				'phone_number' => $employee->phone_number,
			];
		}
		return [
			'company_id' => $companyId,
		];
	}

	/**
	 * Returns all employees from the database in format: id => name.
	 */
	public function getEmployeesForSelect(int $companyId): array {
		return $this->employeeRepository->findAll()
				->where("company_id", $companyId)
				->fetchPairs('id', 'name');
	}

	/**
	 * Returns the employees hierarchy for the company.
	 * @param int $companyId
	 * @return array
	 */
	public function getEmployeesHierarchyByCompanyId(int $companyId): array {
		$employees = $this->employeeRepository->findAll()
				->select('*')
				->where('company_id', $companyId)
				->fetchAssoc('[]');
		return $this->createHierarchy($employees);
	}

	/**
	 * Checks if there is a CEO in the company. Returns true if there is no CEO in the company.
	 * @throws \Exception
	 */
	private function validateCeo($values) {
		if ($values->is_ceo && $values->manager_id) {
			throw new \Exception('CEO cannot have a manager.');
		}
		if (!$values->is_ceo && !$values->manager_id) {
			throw new \Exception('Employee must have a manager or must be marked as "CEO".');
		}
		if ($values->is_ceo) {
			$ceo = $this->employeeRepository->findAll()
				->where('company_id', $values->company_id)
				->where('is_ceo', true)
				->fetch();
			if ($ceo) {
				throw new \Exception('There is already a CEO in the company.');
			}
		}
		return $values->is_ceo;
	}

	/**
	 * Creates and returns a hierarchy tree of employees.
	 * output format: [
	 * 	{
	 * 		"id": 1,
	 * 		"name": "John Doe",
	 * 		"company_id": 1,
	 * 		"manager_id": null,
	 * 		"is_ceo": true,
	 * 		"phone_number": "123456789",
	 * 		"subordinates": [
	 * 			{
	 * 				"id": 2,
	 * 				"name": "Jane Doe",
	 * 				"company_id": 1,
	 * 				"manager_id": 1,
	 * 				"is_ceo": false,
	 * 				"phone_number": "987654321",
	 * 				"subordinates": []
	 * 			}
	 * 		]
	 * 	}
	 * CEO is employee with manager_id == null
	 */
	private function createHierarchy($employees, $managerId = null): array {
		$branch = array();

		foreach ($employees as $employee) {
			if ($employee['manager_id'] == $managerId) {
				$children = $this->createHierarchy($employees, $employee['id']);
				if ($children) {
					$employee['subordinates'] = $children;
				}
				$branch[] = $employee;
			}
		}
		return $branch;
	}

	public function delete(int $id): void {
		$this->employeeRepository->delete($id);
	}
}