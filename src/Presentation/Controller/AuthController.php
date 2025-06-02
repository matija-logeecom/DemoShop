<?php

namespace DemoShop\Presentation\Controller;

use DemoShop\Business\Interfaces\Service\AuthServiceInterface;
use DemoShop\Business\Model\Admin;
use DemoShop\Infrastructure\Cookie\CookieManager;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Infrastructure\Response\RedirectionResponse;
use DemoShop\Infrastructure\Response\Response;

class AuthController
{
    private const AUTH_COOKIE_NAME = 'DEMO_SHOP_AUTH';
    private const DB_TOKEN_PREFIX = 'db_token:';
    private const SESSION_PAYLOAD_PREFIX = 'session_payload:';
    private AuthServiceInterface $authService;

    public function __construct()
    {
        try {
            $this->authService = ServiceRegistry::get(AuthServiceInterface::class);
        } catch (\Exception $e) {
            error_log("CRITICAL: AuthController could not be initialized.
             Failed to get AuthService service. Original error: " . $e->getMessage());
            throw new \RuntimeException(
                "AuthController failed to initialize due to a
                 missing critical dependency.",
                0, $e
            );
        }
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
        $username = trim($request->getBody()['username']) ?? '';
        $password = trim($request->getBody()['password']) ?? '';

        $admin = new Admin($username, $password);

        return $this->handleLogin($admin, $request);
    }

    /**
     * Logs the user out
     *
     * @param Request $request
     *
     * @return Response
     */
    public function logout(Request $request): Response
    {
        if (isset($_COOKIE[self::AUTH_COOKIE_NAME])) {
            $cookieValue = $_COOKIE[self::AUTH_COOKIE_NAME];

            if (str_starts_with($cookieValue, self::DB_TOKEN_PREFIX)) {
                $tokenString = substr($cookieValue, strlen(self::DB_TOKEN_PREFIX));
                $tokenParts = explode(':', $tokenString);
                if (count($tokenParts) === 2) {
                    $selector = $tokenParts[0];
                    $this->authService->handleLogoutAndInvalidateToken($selector);
                }
            }
        }

        CookieManager::getInstance()->setEmptyCookie(self::AUTH_COOKIE_NAME, $request->getServer()['HTTPS']);

        return new RedirectionResponse('/login');
    }

    /**
     * Handles logging in the user
     *
     * @param Admin $admin
     * @param Request $request
     *
     * @return Response
     */
    private function handleLogin(Admin $admin, Request $request): Response
    {
        $adminId = $this->authService->authenticate($admin);
        if ($adminId > 0) {
            $keepLoggedIn = !empty($request->getBody()['keep_logged_in']);

            if ($keepLoggedIn) {
                $cookie = $this->handleKeepLoggedIn($adminId);
            }

            if (!$keepLoggedIn) {
                $cookie = $this->handleDontKeepLoggedIn($adminId);
            }

            if ($cookie !== []) {
                CookieManager::getInstance()->set(self::AUTH_COOKIE_NAME, $cookie['value'], $cookie['expiry'],
                    $request->getServer()['HTTPS']);

                return new RedirectionResponse('/admin');
            }
        }

        $request->setRouteParams([
            'username' => $admin->getUsername(),
            'passwordError' => 'Username and password do not match.',
        ]);

        return $this->loginPage($request);
    }

    /**
     * Handles logging in when keep logged in is checked
     *
     * @param $adminId
     *
     * @return array
     */
    private function handleKeepLoggedIn($adminId): array
    {
        $cookie = [];
        $tokenData = $this->authService->handleLoginAndCreateAuthToken($adminId);
        if (!empty($tokenData) && isset($tokenData['selector']) && isset($tokenData['validator'])) {
            $cookie['value'] = self::DB_TOKEN_PREFIX . "{$tokenData['selector']}:{$tokenData['validator']}";
            $cookie['expiry'] = time() + (86400 * 30);
        }

        return $cookie;
    }

    /**
     * Handles logging in when keep me logged in is unchecked
     *
     * @param $adminId
     *
     * @return array
     */
    private function handleDontKeepLoggedIn($adminId): array
    {
        $cookie = [];
        $encryptedPayload = $this->authService->createEncryptedSessionPayload($adminId);
        if ($encryptedPayload !== null) {
            $cookie['value'] = self::SESSION_PAYLOAD_PREFIX . $encryptedPayload;
            $cookie['expiry'] = 0;
        }

        return $cookie;
    }
}