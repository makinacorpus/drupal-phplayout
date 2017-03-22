<?php

namespace MakinaCorpus\Drupal\Layout\Storage;

use MakinaCorpus\Layout\Controller\EditToken;
use MakinaCorpus\Layout\Error\InvalidTokenError;
use MakinaCorpus\Layout\Storage\LayoutInterface;
use MakinaCorpus\Layout\Storage\TokenLayoutStorageInterface;

/**
 * Layout database storage
 */
class TokenLayoutStorage implements TokenLayoutStorageInterface
{
    /**
     * @var \DatabaseConnection
     */
    private $database;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $database
     */
    public function __construct(\DatabaseConnection $database)
    {
        $this->database = $database;
    }

    /**
     * {@inheritdoc}
     */
    public function loadToken(string $token) : EditToken
    {
        $data = $this->database->query("select data from {layout_token} where token = ?", [$token])->fetchField();

        if (!$data) {
            throw new InvalidTokenError(sprintf("%s: token does not exists", $token));
        }

        $instance = @unserialize($data);

        // @codeCoverageIgnoreStart
        // This mean data is broken in the database side
        if (!$instance || !$instance instanceof EditToken) {
            $this->database->query("delete from {layout_token} where token = ?", [$token]);

            throw new InvalidTokenError(sprintf("%s: token does not exists", $token));
        }
        // @codeCoverageIgnoreEnd

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function saveToken(EditToken $token)
    {
        $this
            ->database
            ->merge('layout_token')
            ->key(['token' => $token->getToken()])
            ->fields(['data' => serialize($token)])
            ->execute()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $token, int $id) : LayoutInterface
    {
        return $this->loadMultiple($token, [$id])[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function loadMultiple(string $token, array $idList) : array
    {
        $ret = [];

        if (!$idList) {
            // Do the security check for the token itself
            $this->loadToken($token);

            return $ret;
        }

        $data = $this
            ->database
            ->query(
                "select layout_id, data from {layout_token_layout} where token = :str and layout_id in (:ids)",
                [':str' => $token, ':ids' => $idList]
            )
            ->fetchAllKeyed()
        ;

        foreach ($idList as $id) {

            if (!isset($data[$id])) {
                throw new InvalidTokenError(sprintf("%s, %s: layout does not exists", $token, $id));
            }

            $instance = @unserialize($data[$id]);

            // @codeCoverageIgnoreStart
            // This mean data is broken in the database side
            if (!$instance || !$instance instanceof Layout) {
                $this->database->query("delete from {layout_token_layout} where token = ? and layout_id = ?", [$token, $id]);

                throw new InvalidTokenError(sprintf("%s, %s: token does not exists", $token, $id));
            }
            // @codeCoverageIgnoreEnd

            $ret[$id] = $instance;
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll(string $token)
    {
        // Let the ON DELETE CASCADE do its job naturaly
        $this->database->query("delete from {layout_token} where token = ?", [$token]);
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $token, LayoutInterface $layout)
    {
        $this
            ->database
            ->merge('layout_token_layout')
            ->key(['token' => $token, 'layout_id' => $layout->getId()])
            ->fields(['data' => serialize($layout)])
            ->execute()
        ;
    }
}
