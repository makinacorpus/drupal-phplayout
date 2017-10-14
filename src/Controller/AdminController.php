<?php

namespace MakinaCorpus\Drupal\Layout\Controller;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Layout\Storage\Layout;
use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Layout\Context\Context;
use MakinaCorpus\Layout\Context\EditToken;
use MakinaCorpus\Layout\Error\GenericError;
use MakinaCorpus\Layout\Storage\LayoutInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Drupal oriented layout actions controller
 */
class AdminController extends Controller
{
    use StringTranslationTrait;

    /**
     * Check CSRF token or die
     */
    private function checkCsrfToken(Request $request)
    {
        $csrfToken = $request->get('csrf');

        if (!$csrfToken || !drupal_valid_token($csrfToken)) {
            throw $this->createAccessDeniedException();
        }
    }

    /**
     * Create redirect response
     */
    private function createRedirect(Request $request, Context $context, EditToken $token = null) : Response
    {
        $route = $request->get('from');

        if (!$route) {
            throw $this->createNotFoundException();
        }

        if ($token) {
            return $this->redirectToRoute($route, [PHP_LAYOUT_TOKEN_PARAMETER => $token->getToken()]);
        }

        return $this->redirectToRoute($route);
    }

    /**
     * Edit a single layout action
     */
    public function editSingleAction(Request $request, Context $context, LayoutInterface $layout) : Response
    {
        $this->checkCsrfToken($request);

        if ($context->hasToken()) {
            $token = $context->getToken();
            $context->addLayout($layout->getId());
        } else {
            $token = $context->createEditToken([$layout->getId()]);
        }

        return $this->createRedirect($request, $context, $token);
    }

    /**
     * Commit single layout in page
     */
    public function commitSingleAction(Request $request, Context $context, EditToken $token, LayoutInterface $layout) : Response
    {
        $this->checkCsrfToken($request);

        throw new GenericError("Not implemented yet");
    }

    /**
     * Commit all in layouts page
     */
    public function commitAllAction(Request $request, Context $context, EditToken $token) : Response
    {
        $this->checkCsrfToken($request);
        $context->commit();

        return $this->createRedirect($request, $context);
    }

    /**
     * Rollback single layout in page
     */
    public function rollbackSingleAction(Request $request, Context $context, EditToken $token, LayoutInterface $layout) : Response
    {
        $this->checkCsrfToken($request);

        throw new GenericError("Not implemented yet");

        return $this->createRedirect($request, $context, $token);
    }

    /**
     * Rollback all layouts in page
     */
    public function rollbackAllAction(Request $request, Context $context, EditToken $token) : Response
    {
        $this->checkCsrfToken($request);
        $context->rollback();

        return $this->createRedirect($request, $context);
    }

    /**
     * Current page layout summary display
     *
     * @todo
     *
     */
    public function layoutSummaryAction(Request $request, Context $context) : Response
    {
        $layouts = [];
        $editableCount = 0;
        $token = null;

        if ($context->hasToken()) {
            $token = $context->getToken();
        }

        /** @var \MakinaCorpus\Layout\Storage\LayoutInterface $layout */
        foreach ($context->getAllLayouts() as $layout) {
            if ($context->isEditable($layout)) {

                $data = [
                    'id' => $layout->getId(),
                    'title' => 'Layout ' . $layout->getId(),
                ];

                if ($token && $token->contains($layout->getId())) {
                    $data['editable'] = true;
                    $editableCount++;
                }

                if ($layout instanceof Layout) {
                    $nodeId = $layout->getNodeId();

                    if ($nodeId) {
                        if ($region = $layout->getRegion()) {
                            $names = system_region_list($GLOBALS['theme']);
                            if (isset($names[$region])) {
                                $region = $names[$region];
                            }
                            $data['title'] = $region;
                        } else {
                            $data['title'] = $this->t("Page content");
                        }
                    }
                }

                $layouts[] = $data;
            }
        }

        $destination = $request->attributes->get('_route');
        if (empty($destination)) {
            $destination = '<front>';
        }

        return $this->render('@phplayout/templates/layout-summary.html.twig', [
            'layouts'       => $layouts,
            'token'         => $token ? $token->getToken() : null,
            'editableCount' => $editableCount,
            'csrfToken'     => drupal_get_token(),
            'destination'   => $destination,
        ]);
    }
}
