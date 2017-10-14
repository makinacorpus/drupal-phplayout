<?php

namespace MakinaCorpus\Drupal\Layout;

use MakinaCorpus\Drupal\Layout\Storage\Layout;
use MakinaCorpus\Layout\Context\Context;
use MakinaCorpus\Layout\Render\EditRendererDecorator;
use MakinaCorpus\Layout\Render\Renderer;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default object responsible for injecting layouts into pages during
 * hook_page_build() time, usable as-is but which probably should be
 * extended or replaced for a lot of sites.
 */
class DefaultPageInjector
{
    private $context;
    private $editGridRenderer;
    private $renderer;

    /**
     * Default constructor
     *
     * @param Context $context
     * @param Renderer $renderer
     * @param EditRendererDecorator $editGridRenderer
     */
    public function __construct(Context $context, Renderer $renderer, EditRendererDecorator $editGridRenderer)
    {
        $this->context  = $context;
        $this->renderer = $renderer;
        $this->editGridRenderer = $editGridRenderer;
    }

    /**
     * Injects layouts into sites
     *
     * @param Request $request
     *   Incoming request
     * @param array $page
     *   Page from Drupal hook_page_build()
     */
    public function inject(Request $request, array &$page)
    {
        $token = null;
        if ($this->context->hasToken()) {
            $token = $this->context->getToken();
        }

        // Working multiple pass version
        foreach ($this->context->getPageLayouts() as $layout) {
            if (!$layout instanceof Layout || !($region = $layout->getRegion())) {
                $region = 'content';
            }

            if ($token && $token->contains($layout->getId())) {
                $this->editGridRenderer->setCurrentToken($token);
            } else {
                $this->editGridRenderer->dropToken();
            }

            $page[$region]['layout'][$layout->getId()] = ['#markup' => $this->renderer->render($layout->getTopLevelContainer(), $this->context)];
        }

        if (true) { // @todo access at least one layout

            if ($token) {
                drupal_add_library('phplayout', 'edit_basic');
                drupal_add_js([
                    'layout' => [
                        'token'   => $token->getToken(),
                        'baseurl' => base_path(),
                        'destination' => drupal_get_destination()['destination'],
                    ]
                ], 'setting');
            }
        }
    }
}
