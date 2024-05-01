<?php

namespace App\Model\DataManager;

use App\Model\Repository\EmployeeRepository;
use App\Model\TDO\TDbEmployee;
use Nette\Database\Table\Selection;
use Nette\DI\Attributes\Inject;

class EmployeeDataManager {

	public function __construct(
		private EmployeeRepository $employeeRepository
	) {
	}

	/**
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

	public function getEmployeesForSelect(): array {
		return $this->employeeRepository->findAll()->fetchPairs('id', 'name');
	}

	public function getEmployeesHierarchyByCompanyId(int $companyId): array {
		$employees = $this->employeeRepository->findAll()->select('*')->where('company_id', $companyId)->fetchAssoc('[]');
		// create employees hierarchy: there is only with 1 ceo with no manager
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


}