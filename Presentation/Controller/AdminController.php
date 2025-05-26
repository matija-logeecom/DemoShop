<?php

namespace DemoShop\Presentation\Controller;

use DemoShop\Infrastructure\Response\JsonResponse;
//use DemoShop\Infrastructure\Session\SessionManager;
use DemoShop\Business\Model\Admin;
use DemoShop\Infrastructure\Response\RedirectionResponse;
use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Infrastructure\Response\Response;
use DemoShop\Business\Service\AdminServiceInterface;
use Exception;

/*
 * Stores logic for handling Admin requests
 */

class AdminController
{
    private AdminServiceInterface $userService;
    private const AUTH_COOKIE_NAME = 'DEMO_SHOP_AUTH';

    /**
     * Constructs Admin Controller instance
     *
     * @param AdminServiceInterface $userService
     */
    public function __construct(AdminServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    public function adminPage(): Response
    {
        $path = VIEWS_PATH . '/admin.phtml';

        return new HtmlResponse($path);
    }

    /**
     * Makes login page response
     *
     * @param Request $request
     *
     * @return Response
     */
    public function loginPage(Request $request): Response
    {
        $path = VIEWS_PATH . '/login.phtml';

        return new HtmlResponse($path, variables: [
            'username' => $request->getRouteParam('username'),
            'usernameError' => $request->getRouteParam('usernameError'),
            'passwordError' => $request->getRouteParam('passwordError'),
        ]);
    }

    /**
     * Sends Login info to service
     *
     * @param Request $request
     *
     * @return Response
     */
    public function sendLoginInfo(Request $request): Response
    {
        $errors['username'] = '';
        $errors['password'] = '';

        $username = trim($request->getBody()['username']) ?? '';
        $password = trim($request->getBody()['password']) ?? '';
        $validUsername = $this->userService->isValidUsername($username, $errors);
        $validPassword = $this->userService->isValidPassword($password, $errors);

        if (!$validUsername || !$validPassword) {
            $request->setRouteParams([
                'username' => $username,
                'usernameError' => $errors['username'],
                'passwordError' => $errors['password'],
            ]);

            return $this->loginPage($request);
        }

        $admin = new Admin($username, $password);
        $adminId = $this->userService->authenticate($admin);
        if ($adminId > 0) {
            $tokenData = $this->userService->handleLoginAndCreateAuthToken($adminId);

            if (!empty($tokenData) && isset($tokenData['selector']) && isset($tokenData['validator'])) {
                $cookieValue = "{$tokenData['selector']}:{$tokenData['validator']}";
                $expiryTime = time() + (86400 * 30);
                setcookie(self::AUTH_COOKIE_NAME, $cookieValue, [
                    'expires' => $expiryTime,
                    'path' => '/',
                    'domain' => '', // Current domain
                    'secure' => $request->getServer()['HTTPS'] ?? false, // Set to true if served over HTTPS
                    'httponly' => true,
                    'samesite' => 'Lax' // Or 'Strict'
                ]);
                return new RedirectionResponse('/admin');
            } else {
                $request->setRouteParams([
                    'username' => $username,
                    'passwordError' => 'Username and password do not match.',
                ]);

                return $this->loginPage($request);
            }
        } else {
            $request->setRouteParams([
                'username' => $username,
                'passwordError' => 'Username and password do not match.',
            ]);
            return $this->loginPage($request);
        }
    }

    public function logout(Request $request): Response
    {
        if (isset($_COOKIE[self::AUTH_COOKIE_NAME])) {
            $cookieValue = $_COOKIE[self::AUTH_COOKIE_NAME];
            $parts = explode(':', $cookieValue);
            if (count($parts) === 2) {
                $selector = $parts[0];
                $this->userService->handleLogout($selector);
            }
        }

        setcookie(self::AUTH_COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => $request->getServer()['HTTPS'] ?? false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        return new RedirectionResponse('/login');
    }

    /**
     * Handles request for dashboard data
     *
     * @param Request $request
     *
     * @return Response
     */
    public function dashboardData(Request $request): Response
    {
        $data = $this->userService->getDashboardData();

        return new JsonResponse($data, 200, [], true);
    }

    public function createCategory(Request $request): Response
    {
        $requestBody = $request->getBody();
        $success = $this->userService->createCategory($requestBody);

        if (!$success) {
            return HtmlResponse::createInternalServerError();
        }

        return new JsonResponse(['success' => true], 201);
    }

    public function getCategories(Request $request): Response
    {
        try {
            $categoriesData = $this->userService->getCategories();
            return new JsonResponse($categoriesData, 200);
        } catch (Exception $e) {
            return JsonResponse::createInternalServerError();
        }
    }

    public function updateCategory(Request $request): Response
    {
        $requestBody = $request->getBody();
        $requestBody['id'] = $request->getRouteParam('id');
        $success = $this->userService->updateCategory($requestBody);

        if (!$success) {
            return HtmlResponse::createInternalServerError();
        }

        return new JsonResponse(['id' => $requestBody['id']], 200);
    }

    public function deleteCategory(Request $request): Response
    {
        $idToDelete = $request->getRouteParam('id');
        $success = $this->userService->deleteCategory($idToDelete);

        if (!$success) {
            return HtmlResponse::createInternalServerError();
        }

        return new JsonResponse(['id' => $idToDelete], 200);

    }
}
