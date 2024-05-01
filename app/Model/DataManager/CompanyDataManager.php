<?php

namespace App\Model\DataManager;

use App\Model\Repository\CompanyRepository;
use App\Model\TDO\TDbCompany;
use Nette\Database\Table\Selection;
use Nette\DI\Attributes\Inject;

class CompanyDataManager {


	public function __construct(
		private CompanyRepository $companyRepository
	) {
	}

	public function save(\stdClass $values): int {
		$saveValues = new TDbCompany();
		$saveValues->id = $values->id;
		$saveValues->name = $values->name;
		return $this->companyRepository->save($saveValues);
	}

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

	public function getAllCompanies(): Selection {
		return $this->companyRepository->findAll();
	}
}