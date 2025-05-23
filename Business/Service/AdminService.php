<?php

namespace DemoShop\Business\Service;

use DemoShop\Business\Model\Admin;
use DemoShop\Data\Repository\AdminRepository;
use DemoShop\Data\Repository\CategoryRepository;

/*
 * Stores business logic for admins
 */

class AdminService implements AdminServiceInterface
{
    private AdminRepository $repository;
    private CategoryRepository $categoryRepository;

    /**
     * Constructs Admin Service instance
     *
     * @param AdminRepository $repository
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(AdminRepository $repository, CategoryRepository $categoryRepository)
    {
        $this->repository = $repository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(Admin $admin): bool
    {
        return $this->repository->authenticate($admin);
    }

    /**
     * @inheritDoc
     */
    public function getDashboardData(): array
    {
        return [
            'productsCount' => 120,
            'categoriesCount' => 15,
            'homePageOpeningCount' => 50,
            'mostOftenViewedProduct' => 'prod 1',
            'numberOfProd1Views' => 32
        ];
    }

    public function createCategory(array $data): bool
    {
        return $this->categoryRepository->addCategory($data);
    }

    public function getCategories(): array
    {
        return $this->categoryRepository->getCategories();
    }

    /**
     * @inheritDoc
     */
    public function isValidUsername(string $username, array &$errors): bool
    {
        if (empty($username)) {
            $errors['username'] = 'Username cannot be empty';

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function isValidPassword(string $password, array &$errors): bool
    {
        if (empty($password)) {
            $errors['password'] = 'Password cannot be empty';

            return false;
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';

            return false;
        }

        $lowerFlag = 0;
        $upperFlag = 0;
        $specialFlag = 0;
        $numberFlag = 0;

        foreach (str_split($password) as $char) {
            if (ctype_upper($char)) {
                $upperFlag = 1;
            }

            if (ctype_lower($char)) {
                $lowerFlag = 1;
            }

            if (ctype_digit($char)) {
                $numberFlag = 1;
            }

            if ($char === '!' || $char === '_' || $char === '#' || $char === '$' || $char === '-') {
                $specialFlag = 1;
            }
        }

        if (!($lowerFlag && $upperFlag && $numberFlag && $specialFlag)) {
            $errors['password'] = 'Password must contain at least one upper, one lower,
             one number and one special character';

            return false;
        }

        return true;
    }
}
