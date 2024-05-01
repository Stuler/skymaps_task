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
	public int $companyId;

	public function renderDefault(): void {
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
		}
	}

	public function renderEditEmployee(?int $employeeId): void {
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
		 try {
			$id = $this->companyDM->save($values);
			$this->flashMessage('Company was saved.', 'ok');
			$this->redirect('Home:editCompany', ['companyId' => $id]);
		} catch (\Exception $e) {
			$this->flashMessage($e->getMessage(), 'err');
		}
	}

	public function createComponentFormEmployee(): Form {
		// basic nette form with employee name input
		$form = new Form;
		$form->addHidden('id');
		$form->addHidden('company_id');
		$form->addText('name', 'Employee name:')
			->setRequired('Please enter employee name');
		$form->addSelect('manager_id', 'Manager:', $this->employeeDM->getEmployeesForSelect())
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

}
