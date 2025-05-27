<?php

namespace DemoShop\Presentation\Controller;

use DemoShop\Business\Model\Admin;
use DemoShop\Business\Service\AuthServiceInterface;
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
    
    public function __construct(AuthServiceInterface $authService)
    {
        $this->authService = $authService;
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
        $validUsername = $this->authService->isValidUsername($username, $errors);
        $validPassword = $this->authService->isValidPassword($password, $errors);

        if (!$validUsername || !$validPassword) {
            $request->setRouteParams([
                'username' => $username,
                'usernameError' => $errors['username'],
                'passwordError' => $errors['password'],
            ]);

            return $this->loginPage($request);
        }

        $admin = new Admin($username, $password);
        $adminId = $this->authService->authenticate($admin);
        if ($adminId > 0) {
            $keepLoggedIn = !empty($request->getBody()['keep_logged_in']);

            $cookieValue = null;
            $cookieExpiry = 0;

            if ($keepLoggedIn) {
                $tokenData = $this->authService->handleLoginAndCreateAuthToken($adminId);
                if (!empty($tokenData) && isset($tokenData['selector']) && isset($tokenData['validator'])) {
                    $cookieValue = self::DB_TOKEN_PREFIX . "{$tokenData['selector']}:{$tokenData['validator']}";
                    $cookieExpiry = time() + (86400 * 30);
                }
            }

            if (!$keepLoggedIn) {
                $encryptedPayload = $this->authService->createEncryptedSessionPayload($adminId);
                if ($encryptedPayload !== null) {
                    $cookieValue = self::SESSION_PAYLOAD_PREFIX . $encryptedPayload;
                }
            }

            if ($cookieValue !== null) {
                setcookie(self::AUTH_COOKIE_NAME, $cookieValue, [
                    'expires' => $cookieExpiry,
                    'path' => '/',
                    'domain' => '', // Current domain
                    'secure' => $request->getServer()['HTTPS'] ?? false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);

                return new RedirectionResponse('/admin');
            }
        }

        $request->setRouteParams([
            'username' => $username,
            'passwordError' => 'Username and password do not match.',
        ]);

        return $this->loginPage($request);
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

            // When stored in DB
            if (str_starts_with($cookieValue, self::DB_TOKEN_PREFIX)) {
                $tokenString = substr($cookieValue, strlen(self::DB_TOKEN_PREFIX));
                $tokenParts = explode(':', $tokenString);
                if (count($tokenParts) === 2) {
                    $selector = $tokenParts[0];
                    $this->authService->handleLogoutAndInvalidateToken($selector);
                }
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
}