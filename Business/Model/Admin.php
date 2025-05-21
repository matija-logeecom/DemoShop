<?php

namespace DemoShop\Business\Model;

/*
 * Class for storing Admin Model
 */
class Admin
{
    private string $username;
    private string $password;

    /**
     * Constructs Admin instance
     *
     * @param string $username
     * @param string $password
     */
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Getter for username
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Getter for password
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }


}
