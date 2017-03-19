<?php

namespace MakinaCorpus\Drupal\Layout\Tests;

use MakinaCorpus\Drupal\Layout\Storage\Layout;
use MakinaCorpus\Drupal\Layout\Storage\TemporaryLayoutStorage;
use MakinaCorpus\Layout\Storage\LayoutStorageInterface;

/**
 * Test temporary storage
 *
 * WARNING: this test will break your database into pieces.
 */
class TemporaryStorageTest extends StorageTest
{
    /**
     * Creates the tested storage instance
     *
     * @return LayoutStorageInterface
     */
    protected function createStorage() : LayoutStorageInterface
    {
        return new TemporaryLayoutStorage($this->getDrupalContainer()->get('cache.layout'));
    }
}
