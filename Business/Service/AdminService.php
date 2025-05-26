<?php

namespace DemoShop\Business\Service;

use Carbon\Carbon;
use DemoShop\Business\Model\Admin;
use DemoShop\Data\Repository\AdminAuthTokenRepository;
use DemoShop\Data\Repository\AdminRepository;
use DemoShop\Data\Repository\CategoryRepository;
use Exception;

/*
 * Stores business logic for admins
 */

class AdminService implements AdminServiceInterface
{
    private AdminRepository $repository;
    private CategoryRepository $categoryRepository;
    private AdminAuthTokenRepository $adminAuthTokenRepository;

    /**
     * Constructs Admin Service instance
     *
     * @param AdminRepository $repository
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(AdminRepository $repository,
                                CategoryRepository $categoryRepository,
                                AdminAuthTokenRepository $adminAuthTokenRepository)
    {
        $this->repository = $repository;
        $this->categoryRepository = $categoryRepository;
        $this->adminAuthTokenRepository = $adminAuthTokenRepository;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(Admin $admin): int
    {
        return $this->repository->authenticate($admin);
    }

    public function handleLoginAndCreateAuthToken(int $adminId): array
    {
        try {
            $selector = bin2hex(random_bytes(16));
            $validator = bin2hex(random_bytes(32));
            $hashedValidator = hash('sha256', $validator);
            $expiresAt = Carbon::now()->addDays(30)->toDateTimeString();

            $tokenStored = $this->adminAuthTokenRepository->storeToken(
                $adminId,
                $selector,
                $hashedValidator,
                $expiresAt
            );

            if ($tokenStored) {
                return ['selector' => $selector, 'validator' => $validator];
            }
        } catch (Exception $e) {
            echo $e->getMessage();

            return [];
        }
        return [];
    }

    public function validateAuthToken(string $selector, string $validatorFromCookie): ?int
    {
        $tokenInstance = $this->adminAuthTokenRepository->findTokenBySelector($selector);

        if ($tokenInstance) {
            if (hash_equals($tokenInstance->hashed_validator, hash('sha256', $validatorFromCookie))) {
                return $tokenInstance->admin_id;
            }
        } else {
            $this->adminAuthTokenRepository->deleteTokenBySelector($selector);
        }

        return null;
    }

    public function handleLogout(string $selector): bool
    {
        return $this->adminAuthTokenRepository->deleteTokenBySelector($selector);
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

    public function updateCategory(array $data): bool
    {
        return $this->categoryRepository->updateCategory($data);
    }

    public function deleteCategory(int $id): bool
    {
        return $this->categoryRepository->deleteCategory($id);
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
