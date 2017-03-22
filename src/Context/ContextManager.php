<?php

namespace MakinaCorpus\Drupal\Layout\Context;

use MakinaCorpus\Layout\Storage\LayoutStorageInterface;

/**
 * Aggregates page contextes and provide the main facade for handling them
 */
class ContextManager
{
    const CONTEXT_PAGE = 1;
    const CONTEXT_SITE = 2;

    private $currentSiteId;
    private $pageContext;
    private $siteContext;
    private $storage;

    /**
     * Constructor
     *
     * @param StorageInterface $storage
     * @param StorageInterface $temporaryStorage
     * @param SiteManager $siteManager
     */
    public function __construct(LayoutStorageInterface $storage, LayoutStorageInterface $temporaryStorage)
    {
        $this->storage = $storage;
        $this->pageContext = new Context($storage, $temporaryStorage);
        $this->siteContext = new Context($storage, $temporaryStorage);
    }

    /**
     * Set current context
     *
     * @param int $nodeId
     * @param int $siteId
     */
    public function setCurrentContext(int $nodeId, int $siteId = null)
    {
        $theme = $GLOBALS['theme'];

        $regions = $this->getThemeRegionConfigFor($theme, self::CONTEXT_PAGE);
        if ($regions) {
            $idList = $this
                ->storage
                ->listWithConditions([
                    'region'  => array_keys($regions),
                    'node_id' => $nodeId,
                    'site_id' => $siteId,
                ])
            ;
            if ($idList) {
                $this->pageContext->setLayoutIdentifiers($idList);
            }
        }

        $regions = $this->getThemeRegionConfigFor($theme, self::CONTEXT_SITE);
        if ($regions) {
            $idList = $this
                ->storage
                ->listWithConditions([
                    'region'  => array_keys($regions),
                    'node_id' => $nodeId,
                    'site_id' => $siteId,
                ])
            ;
            if ($idList) {
                $this->siteContext->setLayoutIdentifiers($idList);
            }
        }
    }

    /**
     * Provides the page layout context, i.e. the context specific
     * to the current node.
     *
     * @return Context
     */
    public function getPageContext()
    {
        return $this->pageContext;
    }

    /**
     * Provides the transversal layout context, i.e. the context common
     * to the whole site.
     *
     * @return Context
     */
    public function getSiteContext()
    {
        return $this->siteContext;
    }

    /**
     * Does the given region belong to the page context?
     *
     * @param string $region Region key
     * @param string $theme Theme key
     *
     * @return boolean
     *
    public function isPageContextRegion($region, $theme)
    {
        $regions = $this->getThemeRegionConfig($theme);
        return (isset($regions[$region]) && ($regions[$region] === self::CONTEXT_PAGE));
    }
     */

    /**
     * Does the given region belong to the transversal context?
     *
     * @param string $region Region key
     * @param string $theme Theme key
     *
     * @return boolean
     *
    public function isSiteContextRegion($region, $theme)
    {
        $regions = $this->getThemeRegionConfig($theme);
        return (isset($regions[$region]) && ($regions[$region] === self::CONTEXT_SITE));
    }
     */

    /**
     * Is the given region in edit mode?
     *
     * @param string $region Region key
     *
     * @return boolean
     *
    public function isRegionInEditMode($region)
    {
        if ($site = $this->siteManager->getContext()) {
            return
                ($this->getPageContext()->isTemporary() && $this->isPageContextRegion($region, $site->theme)) ||
                ($this->getSiteContext()->isTemporary() && $this->issiteContextRegion($region, $site->theme))
            ;
        }
        return false;
    }
     */

    /**
     * Is there any context in edit mode?
     *
     * @return boolean
     */
    public function isInEditMode()
    {
        return ($this->pageContext->isTemporary() || $this->siteContext->isTemporary());
    }

    /**
     * Get the enabled regions of the given theme for the given context type
     *
     * @param string $theme
     * @param int $contextType
     *   One the ::CONTEXT_* constants of this class
     */
    public function getThemeRegionConfigFor($theme, $contextType)
    {
        return array_filter(
            variable_get('layout_regions_' . $theme, []),
            function ($value) use ($contextType) {
                return $value == $contextType;
            }
        );
    }

    /**
     * Get the enabled regions of the given theme.
     *
     * @param string $theme Theme key
     *
     * @return int[]
     *
    public function getThemeRegionConfig($theme)
    {
        $regions = variable_get('ucms_layout_regions_' . $theme, []);

        if (null === $regions) {
            $regions = array_keys(system_region_list($theme));
            $regions = array_fill_keys($regions, self::CONTEXT_PAGE);
        }

        return array_map('intval', $regions);
    }
     */
}
