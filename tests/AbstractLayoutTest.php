<?php

namespace MakinaCorpus\Drupal\Layout\Tests;

use Drupal\node\NodeInterface;
use MakinaCorpus\Drupal\Layout\Storage\LayoutStorage;
use MakinaCorpus\Drupal\Layout\Storage\TokenLayoutStorage;
use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Layout\Context\Context;
use MakinaCorpus\Layout\Context\DefaultTokenGenerator;
use MakinaCorpus\Layout\Storage\LayoutStorageInterface;
use MakinaCorpus\Layout\Storage\TokenLayoutStorageInterface;
use MakinaCorpus\Layout\Tests\Unit\ComparisonTestTrait;

/**
 * Basis for tests.
 */
abstract class AbstractLayoutTest extends AbstractDrupalTest
{
    use ComparisonTestTrait;

    /**
     * @var NodeInterface[]
     */
    protected $nodes = [];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->markAsRisky();

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        foreach ($this->nodes as $node) {
            try {
                node_delete($node->id());
            } catch (\Exception $e) {
                // Pass and delete everything you can
            }
        }

        parent::tearDown();
    }

    /**
     * Create layout page context
     *
     * @return Context
     */
    protected function createPageContext() : Context
    {
        return new Context($this->createStorage(), $this->createTokenStorage(), null, null, new DefaultTokenGenerator());
    }

    /**
     * Creates the tested storage instance
     *
     * @return LayoutStorageInterface
     */
    protected function createStorage() : LayoutStorageInterface
    {
        return new LayoutStorage($this->getDatabaseConnection(), $this->createTypeRegistry());
    }

    /**
     * Creates the tested storage instance
     *
     * @return TokenLayoutStorageInterface
     */
    protected function createTokenStorage() : TokenLayoutStorageInterface
    {
        return new TokenLayoutStorage($this->getDatabaseConnection());
    }
}
