<?php

namespace MakinaCorpus\Drupal\Layout\Context;

trait TokenAwareTrait
{
    /**
     * @var string
     */
    private $token;

    /**
     * Set current page session token
     *
     * This identifiers is unique for each page being edited, the same way
     * form tokens are, and allow us to change on the fly the unique cache
     * identifier ensuring no conflicts.
     *
     * @param string $token
     */
    public function setToken(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get current page session token
     *
     * @return string
     */
    public function getToken() : string
    {
        if (!$this->token) {
            throw new \LogicException("Token is not set");
        }

        return $this->token;
    }

    /**
     * Does this instance has a token
     *
     * @return bool
     */
    public function hasToken() : bool
    {
        return !empty($this->token);
    }
}
