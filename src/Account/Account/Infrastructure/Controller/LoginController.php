<?php

namespace CTIC\Account\Account\Infrastructure\Controller;

use CTIC\Account\Account\Domain\Account;
use CTIC\App\Base\Infrastructure\View\Rest\View;
use CTIC\App\Company\Application\CreateCompany;
use CTIC\App\Company\Domain\Command\CompanyCommand;
use CTIC\App\Company\Domain\Company;
use CTIC\App\Company\Infrastructure\Controller\CompanyController;
use Nette\Security\Identity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use CTIC\App\User\Infrastructure\Controller\UserController;
use Symfony\Component\Yaml\Yaml;

class LoginController extends UserController
{
    /**
     * @param $companyFromDb
     * @return Company
     * @throws \Exception
     */
    protected function getCompanyFromDb($companyFromDb): Company
    {
        $companyCommand = new CompanyCommand();
        $companyCommand->id = $companyFromDb['id'];
        $companyCommand->taxName = $companyFromDb['taxName'];
        $companyCommand->administratorName = $companyFromDb['administratorName'];
        $companyCommand->businessName = $companyFromDb['businessName'];
        $companyCommand->taxIdentification = $companyFromDb['taxIdentification'];
        $companyCommand->administratorIdentification = $companyFromDb['administratorIdentification'];
        $companyCommand->ccc = $companyFromDb['ccc'];
        $companyCommand->address = $companyFromDb['address'];
        $companyCommand->postalCode = $companyFromDb['postalCode'];
        $companyCommand->town = $companyFromDb['town'];
        $companyCommand->country = $companyFromDb['country'];
        $companyCommand->smtpEmail = $companyFromDb['smtpEmail'];
        $companyCommand->smtpHost = $companyFromDb['smtpHost'];
        $companyCommand->smtpPassword = $companyFromDb['smtpPassword'];
        $companyCommand->smtpAliasName = $companyFromDb['smtpAliasName'];
        $companyCommand->includedIVA = $companyFromDb['includedIVA'];
        $companyCommand->defect = $companyFromDb['defect'];
        $companyCommand->enabled = $companyFromDb['enabled'];

        $company = CreateCompany::create($companyCommand);
        if (empty($company)) {
            throw new \Exception('Default company malformed');
        }

        return $company;
    }
    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws
     */
    public function loginAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'], true)) {
            $em = $this->repository->getEm();
            $usernameAndAccount = explode('@', $request->get('name'));
            $username = (empty($usernameAndAccount[0]))? null : $usernameAndAccount[0];
            $accountname = (empty($usernameAndAccount[1]))? null : $usernameAndAccount[1];
            $password = $request->get('password');

            if ($username === null || $accountname === null || $password === null) {
                return $this->redirectToLogin($configuration);
            }

            /** @var Account $account */
            $account = $this->repository->findOneBy(array('name' => $accountname));
            if (empty($account)) {
                return $this->redirectToLogin($configuration);
            }
            $dbHost = (empty($account->getDbHost()))? null : $account->getDbHost();
            $dbUser = (empty($account->getDbUser()))? null : $account->getDbUser();
            $dbPassword = (empty($account->getDbPassword()))? null : $account->getDbPassword();
            $dbName = (empty($account->getDbName()))? null : $account->getDbName();

            if ($dbHost === null || $dbUser === null || $dbPassword === null || $dbName === null) {
                return $this->redirectToLogin($configuration);
            }

            try {
                $mysqli = new \mysqli($dbHost, $dbUser, $dbPassword, $dbName);

                if ($mysqli->connect_errno) {
                    return $this->redirectToLogin($configuration);
                }

                $sql = "SELECT * FROM User WHERE name = '$username'";
                if (!$result = $mysqli->query($sql)) {
                    return $this->redirectToLogin($configuration);
                }
                if ($result->num_rows === 0) {
                    return $this->redirectToLogin($configuration);
                }
                $user = $result->fetch_assoc();

                $passwordFromDb = $user['password'];
                $permissionFromDb = $user['permission'];
                $idFromDb = $user['id'];
            } catch (\Exception $e) {
                return $this->redirectToLogin($configuration);
            }


            if (md5($password) != $passwordFromDb) {
                return $this->redirectToLogin($configuration);
            }

            $identity = new Identity($idFromDb, $permissionFromDb, ['username' => $username, 'id' => $idFromDb]);
            $session = new Session();
            $session->set('identity', array(
                'id' => $identity->getId(),
                'roles' => $identity->getRoles(),
                'data'  => $identity->getData()
            ));
            $session->set('dbHost',$dbHost);
            $session->set('dbUser',$dbUser);
            $session->set('dbPassword',$dbPassword);
            $session->set('dbName',$dbName);

            try {
                $idDefaultCompanyFromDb = $user['default_company_id'];

                $sql = "SELECT * FROM Company WHERE id = $idDefaultCompanyFromDb";
                if (!$result = $mysqli->query($sql)) {
                    return $this->redirectToLogin($configuration);
                }
                if ($result->num_rows === 0) {
                    return $this->redirectToLogin($configuration);
                }
                $companyFromDb = $result->fetch_assoc();

                $company = $this->getCompanyFromDb($companyFromDb);

                $sql = "SELECT * FROM Company";
                if (!$result = $mysqli->query($sql)) {
                    return $this->redirectToLogin($configuration);
                }
                if ($result->num_rows === 0) {
                    return $this->redirectToLogin($configuration);
                }

                $companies = array();
                while ($companyFromDb = $result->fetch_assoc()) {
                    $companies[] = $this->getCompanyFromDb($companyFromDb);
                }
                CompanyController::setCompanyData($session, $company, $request, $companies);
            } catch (\Exception $e) {
                return $this->redirectToLogin($configuration);
            }


            $request->setSession($session);

            $configYaml = Yaml::parse(file_get_contents(__DIR__.'/../../../../../config.yml'));
            $hostToLocation = $configYaml['modlue_main']['host'];

            $roles = $identity->getRoles();
            if (!empty($roles[0]) && $roles[0] == 3) {
                header('Location: http://' . $hostToLocation . '/fichar');
                exit();
            }

            header('Location: http://' . $hostToLocation);
            exit();
        }

        $view = View::create();

        if ($configuration->isHtmlRequest()) {
            $view
                ->setTemplate($configuration->getTemplate('login.html'))
                ->setTemplateVar($this->metadata->getName())
                ->setData([
                    'configuration' => $configuration,
                    'metadata' => $this->metadata
                ])
            ;
        }

        return $this->viewHandler->handle($view, $configuration->getRequest());
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws
     */
    public function logoutAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $session = new Session();
        $session->remove('identity');
        $session->remove('dbHost');
        $session->remove('dbUser');
        $session->remove('dbPassword');
        $session->remove('dbName');
        $session->remove('company');
        $session->remove('companies');
        $request->setSession($session);

        return $this->redirectHandler->redirectToRoute($configuration, 'login');
    }
}