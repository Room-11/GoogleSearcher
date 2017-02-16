<?php declare(strict_types = 1);

namespace Room11\GoogleSearcher;

class SearchResultSet
{
    private $searchTerm;
    private $searchUrl;
    private $results;

    public function __construct(string $searchTerm, string $searchUrl, array $results)
    {
        $this->searchTerm = $searchTerm;
        $this->searchUrl = $searchUrl;
        $this->results = $results;
    }

    public function getSearchTerm(): string
    {
        return $this->searchTerm;
    }

    public function getSearchUrl(): string
    {
        return $this->searchUrl;
    }

    /**
     * @return SearchResult[]
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
