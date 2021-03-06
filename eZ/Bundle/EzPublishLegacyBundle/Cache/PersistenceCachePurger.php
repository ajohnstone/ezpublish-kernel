<?php
/**
 * File containing the PersistenceCachePurger class.
 *
 * @copyright Copyright (C) 2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Bundle\EzPublishLegacyBundle\Cache;

use Tedivm\StashBundle\Service\CacheService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentType;

class PersistenceCachePurger
{
    /**
     * @var \Tedivm\StashBundle\Service\CacheService
     */
    protected $cache;

    /**
     * @var \eZ\Publish\API\Repository\LocationService
     */
    protected $locationService;

    /**
     * Avoid clearing sub elements if all cache is already cleared, avoids redundant calls to Stash.
     *
     * @var bool
     */
    protected $allCleared = false;

    /**
     * Activation flag.
     *
     * @var bool
     */
    protected $isEnabled = true;

    /**
     * Setups current handler with everything needed
     *
     * @param \Tedivm\StashBundle\Service\CacheService $cache
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     */
    public function __construct( CacheService $cache, LocationService $locationService )
    {
        $this->cache = $cache;
        $this->locationService = $locationService;
    }

    /**
     * Clear all persistence cache
     *
     * Sets a internal flag 'allCleared' to avoid clearing cache several times
     */
    public function all()
    {
        if ( $this->isEnabled === false )
            return;

        $this->cache->clear();
        $this->allCleared = true;
    }

    /**
     * Returns true if all cache has been cleared already
     *
     * Returns the internal flag 'allCleared' that avoids clearing cache several times
     *
     * @return bool
     */
    public function isAllCleared()
    {
        return $this->allCleared;
    }

    /**
     * Reset 'allCleared' flag
     *
     * Resets the internal flag 'allCleared' that avoids clearing cache several times
     */
    public function resetAllCleared()
    {
        $this->allCleared = false;
    }

    /**
     * Enables or disables cache purger.
     * Disabling the cache purger might be useful in certain situations
     * (like setup wizard where legacy cache is cleared but everything is not set yet to correctly clear SPI cache).
     *
     * @param bool $isEnabled
     */
    public function setIsEnabled( $isEnabled )
    {
        $this->isEnabled = (bool)$isEnabled;
    }

    /**
     * Checks if cache purger is enabled or not.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * Clear all content persistence cache, or by locationIds (legacy content/cache mechanism is location based).
     *
     * Either way all location and urlAlias cache is cleared as well.
     *
     * @param int|int[]|null $locationIds Ids of location we need to purge content cache for. Purges all content cache if null
     *
     * @return array|int|\int[]|null
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType On invalid $id type
     */
    public function content( $locationIds = null )
    {
        if ( $this->allCleared === true || $this->isEnabled === false )
            return;

        if ( $locationIds === null )
        {
            $this->cache->clear( 'content' );
            goto relatedCache;
        }
        else if ( !is_array( $locationIds ) )
        {
            $locationIds = array( $locationIds );
        }

        foreach ( $locationIds as $id )
        {
            if ( !is_scalar( $id ) )
                throw new InvalidArgumentType( "\$id", "int[]|null", $id );

            $location = $this->locationService->loadLocation( $id );
            $this->cache->clear( 'content', $location->contentId );
            $this->cache->clear( 'content', 'info', $location->contentId );
        }

        // clear content related cache as well
        relatedCache:
        $this->cache->clear( 'urlAlias' );
        $this->cache->clear( 'location' );

        return $locationIds;
    }

    /**
     * Clear all contentType persistence cache, or by id
     *
     * @param int|null $id Purges all contentType cache if null
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType On invalid $id type
     */
    public function contentType( $id = null )
    {
        if ( $this->allCleared === true || $this->isEnabled === false )
            return;

        if ( $id === null )
        {
            $this->cache->clear( 'contentType' );
        }
        else if ( is_scalar( $id ) )
        {
            $this->cache->clear( 'contentType', $id );
        }
        else
        {
            throw new InvalidArgumentType( "\$id", "int|null", $id );
        }
    }

    /**
     * Clear all contentTypeGroup persistence cache, or by id
     *
     * Either way, contentType cache is also cleared as it contains the relation to contentTypeGroups
     *
     * @param int|null $id Purges all contentTypeGroup cache if null
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType On invalid $id type
     */
    public function contentTypeGroup( $id = null )
    {
        if ( $this->allCleared === true || $this->isEnabled === false )
            return;

        if ( $id === null )
        {
            $this->cache->clear( 'contentTypeGroup' );
        }
        else if ( is_scalar( $id ) )
        {
            $this->cache->clear( 'contentTypeGroup', $id );
        }
        else
        {
            throw new InvalidArgumentType( "\$id", "int|null", $id );
        }

        // clear content type in case of changes as it contains the relation to groups
        $this->cache->clear( 'contentType' );
    }

    /**
     * Clear all section persistence cache, or by id
     *
     * @param int|null $id Purges all section cache if null
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType On invalid $id type
     */
    public function section( $id = null )
    {
        if ( $this->allCleared === true || $this->isEnabled === false )
            return;

        if ( $id === null )
        {
            $this->cache->clear( 'section' );
        }
        else if ( is_scalar( $id ) )
        {
            $this->cache->clear( 'section', $id );
        }
        else
        {
            throw new InvalidArgumentType( "\$id", "int|null", $id );
        }
    }

    /**
     * Clear all language persistence cache, or by id
     *
     * @param array $ids
     */
    public function languages( array $ids )
    {
        if ( $this->allCleared === true || $this->isEnabled === false )
            return;

        foreach ( $ids as $id )
            $this->cache->clear( 'language', $id );
    }

    /**
     * Clear all user persistence cache
     *
     * @param int|null $id Purges all users cache if null
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType On invalid $id type
     */
    public function user( $id = null )
    {
        if ( $this->allCleared === true || $this->isEnabled === false )
            return;

        if ( $id === null )
        {
            $this->cache->clear( 'user' );
        }
        else if ( is_scalar( $id ) )
        {
            $this->cache->clear( 'user', $id );
        }
        else
        {
            throw new InvalidArgumentType( "\$id", "int|null", $id );
        }
    }
}
