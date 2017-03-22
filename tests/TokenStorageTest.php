<?php

namespace MakinaCorpus\Drupal\Layout\Tests;

use MakinaCorpus\Drupal\Layout\Storage\Layout;
use MakinaCorpus\Layout\Controller\EditToken;
use MakinaCorpus\Layout\Error\InvalidTokenError;
use MakinaCorpus\Layout\Grid\HorizontalContainer;
use MakinaCorpus\Layout\Storage\LayoutInterface;
use MakinaCorpus\Layout\Tests\Unit\Render\XmlGridRenderer;

/**
 * Test token storage basics
 *
 * WARNING: this test will break your database into pieces.
 */
class TokenStorageTest extends AbstractLayoutTest
{
    /**
     * Test storage creation
     */
    public function testCreateAndLoad()
    {
        $context = $this->createPageContext();
        $storage = $this->createStorage();
        $tokenStorage = $this->createTokenStorage();

        /** @var \MakinaCorpus\Drupal\Layout\Storage\Layout $layout */
        $layout1 = $storage->create();
        $layout2 = $storage->create();

        $context->add([$layout1], true);
        $context->add([$layout2], false);
        $token = $context->createEditToken(['user_id' => 17]);

        // Should not exist before save
        try {
            $tokenStorage->loadToken($token->getToken());
            $this->fail();
        } catch (InvalidTokenError $e) {
            $this->assertTrue(true);
        }

        $tokenStorage->saveToken($token);

        // Later on...
        $newToken = $tokenStorage->loadToken($token->getToken());
        $this->assertInstanceOf(EditToken::class, $newToken);
        $this->assertSame($token->getToken(), $newToken->getToken());
        $this->assertTrue($newToken->contains($layout1));
        $this->assertFalse($newToken->contains($layout2));
    }

    /**
     * Load multiple load everything with no side effects
     */
    public function testLoadLayoutEmpty()
    {
        $context = $this->createPageContext();
        $tokenStorage = $this->createTokenStorage();
        $token = $context->createEditToken(['user_id' => 17]);
        $tokenString = $token->getToken();

        // Seems stupid, but actually we can load nothing
        $tokenStorage->saveToken($token);
        $ret = $tokenStorage->loadMultiple($tokenString, []);
        $this->assertEmpty($ret);

        // But still should failed when token does not exists
        try {
            $tokenStorage->loadMultiple('some_non_existing_arbitrary_token', []);
            $this->fail();
        } catch (InvalidTokenError $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Load multiple load everything with no side effects
     */
    public function testLoadLayoutAll()
    {
        $context = $this->createPageContext();
        $storage = $this->createStorage();
        $tokenStorage = $this->createTokenStorage();

        $layout1 = $storage->create();
        $layout2 = $storage->create();
        $layout3 = $storage->create();

        $context->add([$layout1, $layout3], true);
        $context->add([$layout2], false);
        $token = $context->createEditToken(['user_id' => 17]);
        $tokenString = $token->getToken();
        $tokenStorage->saveToken($token);

        // Save our editable instances
        $tokenStorage->update($tokenString, $layout1);
        $tokenStorage->update($tokenString, $layout3);

        // Test load single works
        $newLayout1 = $tokenStorage->load($tokenString, $layout1->getId());
        $this->assertSame($layout1->getId(), $newLayout1->getId());

        // Load single will raise exceptions
        try {
            $tokenStorage->load($tokenString, $layout2->getId());
            $this->fail();
        } catch (InvalidTokenError $e) {
            $this->assertTrue(true);
        }

        // Test load multiple works
        $others = $tokenStorage->loadMultiple($token->getToken(), [$layout1->getId(), $layout3->getId()]);
        $this->assertCount(2, $others);

        // Load multiple will raise exceptions
        try {
            $tokenStorage->loadMultiple($tokenString, [$layout2->getId()]);
            $this->fail();
        } catch (InvalidTokenError $e) {
            $this->assertTrue(true);
        }
        try {
            $tokenStorage->loadMultiple($tokenString, [$layout3->getId(), $layout2->getId()]);
            $this->fail();
        } catch (InvalidTokenError $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Delete method works (and SQL data is wiped-out)
     */
    public function testDelete()
    {
        $context = $this->createPageContext();
        $storage = $this->createStorage();
        $tokenStorage = $this->createTokenStorage();

        $layout1 = $storage->create();
        $layout2 = $storage->create();
        $layout3 = $storage->create();

        $context->add([$layout1, $layout3], true);
        $context->add([$layout2], false);
        $token = $context->createEditToken(['user_id' => 17]);
        $tokenString = $token->getToken();
        $tokenStorage->saveToken($token);

        // Save our editable instances
        $tokenStorage->update($tokenString, $layout1);
        $tokenStorage->update($tokenString, $layout3);

        // Validate that load still work
        $tokenStorage->loadToken($tokenString);
        $tokenStorage->loadMultiple($tokenString, [$layout1->getId(), $layout3->getId()]);

        // And now delete
        $tokenStorage->deleteAll($tokenString);

        try {
            $tokenStorage->loadToken($tokenString);
            $this->fail();
        } catch (InvalidTokenError $e) {
            $this->assertTrue(true);
        }
        try {
            $tokenStorage->loadMultiple($tokenString, [$layout1->getId()]);
            $this->fail();
        } catch (InvalidTokenError $e) {
            $this->assertTrue(true);
        }
        try {
            $tokenStorage->load($tokenString, $layout3->getId());
            $this->fail();
        } catch (InvalidTokenError $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * We are just reusing MakinaCorpus\Layout\Tests\Unit\RenderTest code
     *
     * @param LayoutInterface $layout
     */
    private function createAwesomelyComplexLayout(LayoutInterface $layout)
    {
        $typeRegistry = $this->createTypeRegistry();
        $aType = $typeRegistry->getType('a');
        $bType = $typeRegistry->getType('b');

        // Place a top level container and build layout (no items)
        $topLevel = $layout->getTopLevelContainer();
        $c1 = new HorizontalContainer('C1');
        $topLevel->append($c1);
        $c11 = $c1->appendColumn('C11');
        $c12 = $c1->appendColumn('C12');
        $c2 = new HorizontalContainer('C2');
        $c12->append($c2);
        $c21 = $c2->appendColumn('C21');
        $c22 = $c2->appendColumn('C22');
        $c3 = new HorizontalContainer('C3');
        $topLevel->append($c3);
        $c31 = $c3->appendColumn('C31');
        $c32 = $c3->appendColumn('C32');
        $c33 = $c3->appendColumn('C33');

        // Now place all items
        $a1  = $aType->create(1);
        $a2  = $aType->create(2);
        $b3  = $bType->create(3);
        $b4  = $bType->create(4);
        $a5  = $aType->create(5);
        $a6  = $aType->create(6);
        $b7  = $bType->create(7);
        $b8  = $bType->create(8);
        $a9  = $aType->create(9);
        $b10 = $bType->create(10);
        $b11 = $bType->create(11);
        $a12 = $aType->create(12);

        $c11->append($a1);
        $c11->append($b4);

        $c21->append($a2);
        $c21->append($a5);

        $c22->append($b3);

        $c31->append($a6);
        $c31->append($a9);

        $c32->append($b7);
        $c32->append($b10);

        $c33->append($b8);
        $c33->append($b11);
        $c33->append(clone $a1);

        $topLevel->append($a12);
        $topLevel->append(clone $b7);
    }

    /**
     * We need to be able to store our layout for further testing
     */
    public function testCreateAndUpdate()
    {
        $storage = $this->createStorage();
        $typeRegistry = $this->createTypeRegistry();
        $renderer = $this->createRenderer($typeRegistry, new XmlGridRenderer());

        /** @var \MakinaCorpus\Drupal\Layout\Storage\Layout $layout */
        $layout = $storage->create();

        // For the sake of simplicity, just create something similar to what
        // the php-layout library does, just see their documentation for more
        // information.
        $this->createAwesomelyComplexLayout($layout);

        $topLevelId = 'layout-' . $layout->getId();
        $representation = <<<EOT
<vertical id="container:vbox/{$topLevelId}">
    <horizontal id="container:hbox/C1">
        <column id="container:vbox/C11">
            <item id="leaf:a/1"/>
            <item id="leaf:b/4"/>
        </column>
        <column id="container:vbox/C12">
            <horizontal id="container:hbox/C2">
                <column id="container:vbox/C21">
                    <item id="leaf:a/2" />
                    <item id="leaf:a/5" />
                </column>
                <column id="container:vbox/C22">
                    <item id="leaf:b/3" />
                </column>
            </horizontal>
        </column>
    </horizontal>
    <horizontal id="container:hbox/C3">
        <column id="container:vbox/C31">
            <item id="leaf:a/6" />
            <item id="leaf:a/9" />
        </column>
        <column id="container:vbox/C32">
            <item id="leaf:b/7" />
            <item id="leaf:b/10" />
        </column>
        <column id="container:vbox/C33">
            <item id="leaf:b/8" />
            <item id="leaf:b/11" />
            <item id="leaf:a/1" />
        </column>
    </horizontal>
    <item id="leaf:a/12" />
    <item id="leaf:b/7" />
</vertical>
EOT;

        // This just tests the testing helpers, and validate that our layout
        // is correct before we do save it.
        $string = $renderer->render($layout->getTopLevelContainer());
        $this->assertSameRenderedGrid($representation, $string);

        // Now, save it, load it, and ensure rendering is the same.
        $storage->update($layout);

        $otherLayout = $storage->load($layout->getId());
        $string = $renderer->render($otherLayout->getTopLevelContainer());
        $this->assertSameRenderedGrid($representation, $string);

        // Remove a few elements, compare to a new representation
        $representation = <<<EOT
<vertical id="container:vbox/{$topLevelId}">
    <horizontal id="container:hbox/C1">
        <column id="container:vbox/C11">
            <item id="leaf:b/4"/>
        </column>
        <column id="container:vbox/C12">
            <horizontal id="container:hbox/C2">
                <column id="container:vbox/C21">
                    <item id="leaf:a/2" />
                    <item id="leaf:a/5" />
                </column>
            </horizontal>
        </column>
    </horizontal>
    <item id="leaf:b/7" />
</vertical>
EOT;
        $otherLayout->getTopLevelContainer()->removeAt(1);
        $otherLayout->getTopLevelContainer()->getAt(0)->getColumnAt(0)->removeAt(0);
        $otherLayout->getTopLevelContainer()->getAt(0)->getColumnAt(1)->getAt(0)->removeColumnAt(1);
        $otherLayout->getTopLevelContainer()->removeAt(1);
        $storage->update($otherLayout);

        $thirdLayout = $storage->load($layout->getId());
        $string = $renderer->render($thirdLayout->getTopLevelContainer());
        $this->assertSameRenderedGrid($representation, $string);

        // Adds new elements, compare to a new representation

        // Changes a few item styles, and ensure update
    }
}
