<?php declare(strict_types = 1);

namespace Room11\GoogleSearcher;

final class SearchResult
{
    private $url;
    private $title;
    private $description;
    private $date;

    public function __construct(string $url, string $title, string $description, ?\DateTimeImmutable $date)
    {
        $this->url = $url;
        $this->title = $title;
        $this->description = $description;
        $this->date = $date;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }
}
