<?php
declare(strict_types=1);
namespace ExpressRequest\Types;

use ExpressRequest\Controller\Component\ExpressRequestComponent;

class ExpressConfig
{
    private bool $pagination;
    private int $maxSize;
    private int $size;
    private int $currentPage = 1;
    private bool $ssl;
    private bool $fullUrl;
    private bool $cache;
    private string $cacheConfig;
    private bool $isToLimitQuery = false;

    private array $keywords = [];

    public function __construct(
        bool $pagination,
        int $maxSize,
        int $size,
        bool $ssl,
        bool $fullUrl,
        bool $cache,
        string $cacheConfig = 'default'
    )
    {
        $this->pagination = $pagination;
        $this->maxSize = $maxSize;
        $this->size = $size;
        $this->ssl = $ssl;
        $this->fullUrl = $fullUrl;
        $this->cache = $cache;
        $this->cacheConfig = $cacheConfig;
    }

    public static function factory(ExpressRequestComponent $component): ExpressConfig
    {
        $keywords = $component->getConfig('reserved');
        $self = new static(
            (bool)$component->getConfig('pagination'),
            $component->getConfig('maxSize'),
            $component->getConfig($keywords['size']),
            (bool)$component->getConfig('ssl'),
            (bool)$component->getConfig('fullUrl'),
            (bool)$component->getConfig('cache'),
            $component->getConfig('cacheConfig')
        );

        $self->keywords = $keywords;

        return $self;
    }

    public function reconfigure(array $options): ExpressConfig
    {
        if (key_exists($this->getReserved('size'), $options)) {
            $this->isToLimitQuery = true;
        }

        $this
            ->setSize($options[$this->getReserved('size')] ?? $this->getSize())
            ->setCurrentPage($options[$this->getReserved('page')] ?? 1);

        isset($options['noPage']) && $this->setPagination(false);

        return $this;
    }

    /**
     * Get configured reserved keyword for prevent collision.
     * @param string|null $key
     * @param null|string|int $default
     * @return mixed
     */
    public function getReserved(string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->keywords;
        }
        return $this->keywords[$key] ?? $default;
    }

    /**
     * @return bool
     */
    public function getPagination(): bool
    {
        return $this->pagination;
    }

    /**
     * @param bool $pagination
     * @return $this
     */
    public function setPagination($pagination): ExpressConfig
    {
        $this->pagination = isEnabled($pagination);
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    /**
     * @param int $maxSize
     */
    public function setMaxSize(int $maxSize): void
    {
        $this->maxSize = $maxSize;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @param int $size
     * @return $this
     */
    public function setSize($size): ExpressConfig
    {
        if (!is_numeric($size)) {
            return $this;
        }
        $this->size = (int)$size > $this->maxSize ? $this->maxSize : (int)$size;
        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * @param int $currentPage
     * @return $this
     */
    public function setCurrentPage($currentPage): ExpressConfig
    {
        if (!is_numeric($currentPage)) {
            return $this;
        }
        $this->currentPage = (int)$currentPage;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSsl(): bool
    {
        return $this->ssl;
    }

    /**
     * Used to limit query if size param is founded on parameters by request
     * @return bool
     */
    public function isToLimit(): bool
    {
        return $this->isToLimitQuery;
    }

    /**
     * @param bool $ssl
     * @return $this
     */
    public function setSsl(bool $ssl): ExpressConfig
    {
        $this->ssl = $ssl;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFullUrl(): bool
    {
        return $this->fullUrl;
    }

    /**
     * @param bool $fullUrl
     */
    public function setFullUrl(bool $fullUrl): void
    {
        $this->fullUrl = $fullUrl;
    }

    /**
     * @return bool
     */
    public function canCache(): bool
    {
        return $this->cache;
    }

    /**
     * @param bool $cache
     */
    public function setCache(bool $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * @return string
     */
    public function getCacheConfig(): string
    {
        return $this->cacheConfig;
    }

    /**
     * @param string $cacheConfig
     */
    public function setCacheConfig(string $cacheConfig): void
    {
        $this->cacheConfig = $cacheConfig;
    }
}