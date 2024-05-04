<?php

namespace App\Model\DataManager;

use App\Model\Repository\CompanyRepository;
use App\Model\Repository\EmployeeRepository;
use App\Model\TDO\TDbCompany;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\DI\Attributes\Inject;

class CompanyDataManager {


	public function __construct(
		private readonly CompanyRepository $companyRepository,
		private readonly EmployeeRepository $employeeRepository
	) {
	}

	/**
	 * Saves the company to the database.
	 */
	public function save(\stdClass $values): int {
		$saveValues = new TDbCompany();
		$saveValues->id = $values->id;
		$saveValues->name = $values->name;
		return $this->companyRepository->save($saveValues);
	}

	/**
	 * Returns the default values for the company form.
	 */
	public function getDefaults(?int $companyId): array {
		/** @var TDbCompany $company */
		$company = $this->companyRepository->fetchById($companyId);
		if ($company) {
			return [
				'id' => $company->id,
				'name' => $company->name,
			];
		}
		return [];
	}

	/**
	 * Returns all companies from the database.

	 */
	public function getAllCompanies(): Selection {
		return $this->companyRepository->findAll();
	}

	/**
	 * Deletes a company and all its employees.
	 */
	public function delete(int $id): void {
		$this->employeeRepository->deleteByCompanyId($id);
		$this->companyRepository->delete($id);
	}

	/**
	 * Returns a company by its ID.
	 */
	public function getCompanyById(?int $companyId): ?ActiveRow {
		return $this->companyRepository->fetchById($companyId);
	}
}