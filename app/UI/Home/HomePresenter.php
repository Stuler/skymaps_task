<?php

declare(strict_types=1);

namespace App\UI\Home;

use App\Model\DataManager\CompanyDataManager;
use App\Model\DataManager\EmployeeDataManager;
use Nette;
use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;

final class HomePresenter extends Nette\Application\UI\Presenter
{
	#[Inject]
	public CompanyDataManager $companyDM;

	#[Inject]
	public EmployeeDataManager $employeeDM;

	#[Persistent]
	public ?int $companyId = null;

	public function renderDefault(): void {
		$this->companyId = null;
		$companies = $this->companyDM->getAllCompanies();
		$this->template->companies = $companies;
	}

	public function renderEditCompany(?int $companyId): void {
		if ($companyId) {
			$this->companyId = $companyId;
			$this['formCompany']->setDefaults($this->companyDM->getDefaults($companyId));
			$hierarchy = $this->employeeDM->getEmployeesHierarchyByCompanyId($this->companyId);
			$this->template->companyId = $companyId;
			$this->template->hierarchy = $hierarchy;
		} else {
			$this->companyId = null;
		}
	}

	public function renderEditEmployee(?int $employeeId): void {
		$company = $this->companyDM->getCompanyById($this->companyId);
		$this->template->companyId = $this->companyId;
		$this->template->companyName = $company->name;
		$this['formEmployee']->setDefaults($this->employeeDM->getDefaults($employeeId, $this->companyId));
	}

	public function createComponentFormCompany(): Form {
		// basic nette form with company name input
		$form = new Form;
		$form->addHidden('id');
		$form->addText('name', 'Company name:')
			->setRequired('Please enter company name');
		$form->addSubmit('send', 'Save');
		$form->onSuccess[] = [$this, 'formCompanySucceeded'];
		return $form;
	}

	public function formCompanySucceeded(\stdClass $values): void {
		$id = $this->companyDM->save($values);
		$this->flashMessage('Company was saved.', 'ok');
		$this->redirect('Home:editCompany', ['companyId' => $id]);

	}

	public function createComponentFormEmployee(): Form {
		// basic nette form with employee name input
		$form = new Form;
		$form->addHidden('id');
		$form->addHidden('company_id');
		$form->addText('name', 'Employee name:')
			->setRequired('Please enter employee name');
		$form->addSelect('manager_id', 'Manager:', $this->employeeDM->getEmployeesForSelect($this->companyId))
			->setPrompt('Select manager');
		$form->addCheckbox('is_ceo', 'CEO');
		$form->addText("phone_number", "Phone number:")
			->setRequired('Please enter phone number');

		$form->addSubmit('send', 'Save');
		$form->onSuccess[] = [$this, 'formEmployeeSucceeded'];
		return $form;
	}

	public function formEmployeeSucceeded(\stdClass $values): void {
		try {
			$id = $this->employeeDM->save($values);
			$this->flashMessage('Employee was saved.', 'ok');
			$this->redirect('editCompany', ['companyId' => $this->companyId]);
		} catch (\Exception $e) {
			$this->flashMessage($e->getMessage(), 'err');
		}
	}

	public function handleDeleteCompany(int $id): void {
		try {
			$this->companyDM->delete($id);
			$this->flashMessage('Company was deleted.', 'ok');
			$this->redirect('Home:');
		} catch (\Exception $e) {
			$this->flashMessage($e->getMessage(), 'err');
		}
	}

	public function handleDeleteEmployee(int $id): void {
		try {
			$this->employeeDM->delete($id);
			$this->flashMessage('Employee was deleted.', 'ok');
			$this->redirect('Home:editCompany', ['companyId' => $this->companyId]);
		} catch (\PDOException $e) { // todo implement
			$this->flashMessage('Employee cannot be deleted because it is a manager of another employee.', 'err');
		}
	}

}
