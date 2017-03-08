<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore;

use Pimcore\Cache\Core\CoreHandlerInterface;
use Pimcore\Cache\Core\ZendCacheHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This acts as facade for the actual cache implementation and exists primarily for BC reasons.
 */
class Cache
{
    /**
     * @var CoreHandlerInterface
     */
    protected static $handler;

    /**
     * @var ZendCacheHandler
     */
    protected static $zendHandler;

    /**
     * @deprecated
     * @return \Zend_Cache_Core|null
     */
    public static function getInstance()
    {
        throw new \RuntimeException('getInstance() is not supported anymore');
    }

    /**
     * @param CoreHandlerInterface $handler
     */
    public static function setHandler(CoreHandlerInterface $handler)
    {
        self::$handler = $handler;
    }

    /**
     * @param ZendCacheHandler $zendHandler
     */
    public static function setZendHandler(ZendCacheHandler $zendHandler)
    {
        static::$zendHandler = $zendHandler;
        static::$zendHandler->setZendFrameworkCaches();
    }

    /**
     * Get the cache handler implementation
     *
     * @return CoreHandlerInterface
     */
    public static function getHandler()
    {
        return static::$handler;
    }

    /**
     * Returns the content of the requested cache entry
     *
     * @param string $key
     * @param bool $doNotTestCacheValidity Not used anymore
     * @return mixed
     */
    public static function load($key, $doNotTestCacheValidity = false)
    {
        return static::$handler->load($key);
    }

    /**
     * Save an item to the cache (deferred to shutdown if force is false and forceImmediateWrite is not set)
     *
     * @param mixed $data
     * @param string $key
     * @param array $tags
     * @param int|\DateInterval|null $lifetime
     * @param int $priority
     * @param bool $force
     * @return bool
     */
    public static function save($data, $key, $tags = [], $lifetime = null, $priority = 0, $force = false)
    {
        return static::$handler->save($key, $data, $tags, $lifetime, $priority, $force);
    }

    /**
     * Remove an item from the cache
     *
     * @param $key
     * @return bool
     */
    public static function remove($key)
    {
        return static::$handler->remove($key);
    }

    /**
     * Empty the cache
     *
     * @return bool
     */
    public static function clearAll()
    {
        return static::$handler->clearAll();
    }

    /**
     * Removes entries from the cache matching the given tag
     *
     * @param string $tag
     * @return bool
     */
    public static function clearTag($tag)
    {
        return static::$handler->clearTag($tag);
    }

    /**
     * Removes entries from the cache matching the given tags
     *
     * @param array $tags
     * @return bool
     */
    public static function clearTags($tags = [])
    {
        if (!empty($tags) && !is_array($tags)) {
            $tags = [$tags];
        }

        return static::$handler->clearTags($tags);
    }

    /**
     * Adds a tag to the shutdown queue
     *
     * @param string $tag
     */
    public static function addClearTagOnShutdown($tag)
    {
        static::$handler->addTagClearedOnShutdown($tag);
    }

    /**
     * Add tag to the list ignored on save. Items with this tag won't be saved to cache.
     *
     * @param string $tag
     */
    public static function addIgnoredTagOnSave($tag)
    {
        static::$handler->addTagIgnoredOnSave($tag);
    }

    /**
     * Remove tag from the list ignored on save
     *
     * @param string $tag
     */
    public static function removeIgnoredTagOnSave($tag)
    {
        static::$handler->removeTagIgnoredOnSave($tag);
    }

    /**
     * Add tag to the list ignored on clear. Tags in this list won't be cleared via clearTags()
     *
     * @param string $tag
     */
    public static function addIgnoredTagOnClear($tag)
    {
        static::$handler->addTagIgnoredOnClear($tag);
    }

    /**
     * Remove tag from the list ignored on clear
     *
     * @param string $tag
     */
    public static function removeIgnoredTagOnClear($tag)
    {
        static::$handler->removeTagIgnoredOnClear($tag);
    }

    /**
     * @deprecated Use addIgnoredTagOnSave() instead
     * @param string $tag
     */
    public static function addClearedTag($tag)
    {
        static::$handler->getLogger()->warning('addClearedTag is deprecated, please use addIngoredTagOnSave instead', [
            'tag' => $tag
        ]);

        // instead of messing with the internal cleared tags property, we expose a
        // dedicated property for tags which should be ignored on save
        static::addIgnoredTagOnSave($tag);
    }

    /**
     * Write and clean up cache
     *
     * @param bool $forceWrite
     */
    public static function shutdown($forceWrite = false)
    {
        static::$handler->shutdown($forceWrite);
    }

    /**
     * Disables the complete pimcore cache
     */
    public static function disable()
    {
        static::$zendHandler->disable();
        static::$handler->disable();
    }

    /**
     * Enables the pimcore cache
     */
    public static function enable()
    {
        static::$zendHandler->enable();
        static::$handler->enable();
    }

    /**
     * @return bool
     */
    public static function isEnabled()
    {
        return static::$handler->isEnabled();
    }

    /**
     * @param bool $forceImmediateWrite
     */
    public static function setForceImmediateWrite($forceImmediateWrite)
    {
        static::$handler->setForceImmediateWrite($forceImmediateWrite);
    }

    /**
     * @return bool
     */
    public static function getForceImmediateWrite()
    {
        return static::$handler->getForceImmediateWrite();
    }

    /**
     * @return bool
     */
    public static function maintenance()
    {
        $result = static::$handler->purge();

        if (null !== static::$zendHandler) {
            // TODO if the ZF backend and the handler itemPool are the same, purge will be called twice. However, not
            // calling clean would result in ZF cache never being cleaned up if the backend differs from the core item pool.
            $result = $result && static::$zendHandler->getCache()->clean(\Zend_Cache::CLEANING_MODE_OLD);
        }

        return $result;
    }
}
