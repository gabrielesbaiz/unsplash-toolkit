<?php

namespace Gabrielesbaiz\UnsplashToolkit\Queries;

trait QueryBuilder
{
    /**
     * Associative array to hold query parameters.
     *
     * @var array<string, mixed>
     */
    public array $query = [];

    /**
     * Page number to retrieve.
     *
     * @param  int|string|null $param
     * @return $this
     */
    public function page($param = null): self
    {
        $this->query['page'] = $param ?? null;

        return $this;
    }

    /**
     * Number of items per page.
     *
     * @param  int|string|null $param
     * @return $this
     */
    public function perPage($param = null): self
    {
        $this->query['per_page'] = $param ?? null;

        return $this;
    }

    /**
     * How to sort the photos.
     *
     * Valid values: latest, oldest, popular
     *
     * @param  string|null $param
     * @return $this
     */
    public function orderBy(?string $param = null): self
    {
        $this->query['order_by'] = $param ?? null;

        return $this;
    }

    /**
     * Public collection ID(s) to filter selection.
     * If multiple, comma-separated.
     *
     * @param  string|null $param
     * @return $this
     */
    public function collections(?string $param = null): self
    {
        $this->query['collections'] = $param ?? null;

        return $this;
    }

    /**
     * Limit selection to featured photos.
     *
     * @param  string|null $param
     * @return $this
     */
    public function featured(?string $param = null): self
    {
        $this->query['featured'] = $param ?? null;

        return $this;
    }

    /**
     * Limit selection to a single user.
     *
     * @param  string|null $param
     * @return $this
     */
    public function username(?string $param = null): self
    {
        $this->query['username'] = $param ?? null;

        return $this;
    }

    /**
     * Filter search results by photo orientation.
     *
     * Valid values: landscape, portrait, and squarish
     *
     * @param  string|null $param
     * @return $this
     */
    public function orientation(?string $param = null): self
    {
        $this->query['orientation'] = $param ?? null;

        return $this;
    }

    /**
     * Limit selection to photos matching a search term.
     *
     * @param  string|null $param
     * @return $this
     */
    public function term(?string $param = null): self
    {
        $this->query['query'] = $param ?? null;

        return $this;
    }

    /**
     * The number of photos to return.
     *
     * @param  int|string|null $param
     * @return $this
     */
    public function count($param = null): self
    {
        $this->query['count'] = $param ?? null;

        return $this;
    }

    /**
     * The public id of the photo.
     *
     * @param  int|string|null $param
     * @return $this
     */
    public function id($param = null): self
    {
        $this->query['id'] = $param ?? null;

        return $this;
    }

    /**
     * The frequency of the stats.
     *
     * @param  string|null $param
     * @return $this
     */
    public function resolution(?string $param = null): self
    {
        $this->query['resolution'] = $param ?? null;

        return $this;
    }

    /**
     * The amount for each stat.
     *
     * @param  int|string|null $param
     * @return $this
     */
    public function quantity($param = null): self
    {
        $this->query['quantity'] = $param ?? null;

        return $this;
    }

    /**
     * Filter results by color.
     *
     * Valid values: black_and_white, black, white, yellow, orange, red, purple, magenta, green, teal, and blue
     *
     * @param  string|null $param
     * @return $this
     */
    public function color(?string $param = null): self
    {
        $this->query['color'] = $param ?? null;

        return $this;
    }

    /**
     * Query for search terms.
     *
     * @param  string|null $param
     * @return $this
     */
    public function query(?string $param = null): self
    {
        $this->query['query'] = $param ?? null;

        return $this;
    }

    /**
     * Build the query string.
     *
     * @return string
     */
    public function getQuery(): string
    {
        return http_build_query($this->query, '', '&');
    }
}
