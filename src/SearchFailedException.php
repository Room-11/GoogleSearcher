<?php declare(strict_types = 1);

namespace Room11\GoogleSearcher;

class SearchFailedException extends \RuntimeException
{
    private $searchTerm;
    private $searchUri;

    public function __construct(string $message, string $searchTerm, string $searchUri, \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);

        $this->searchTerm = $searchTerm;
        $this->searchUri = $searchUri;
    }

    public function getSearchTerm(): string
    {
        return $this->searchTerm;
    }

    public function getSearchUri(): string
    {
        return $this->searchUri;
    }
}
