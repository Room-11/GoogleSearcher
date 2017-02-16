<?php declare(strict_types = 1);

namespace Room11\GoogleSearcher;

use Amp\Artax\HttpClient;
use Amp\Artax\Request as HttpRequest;
use Amp\Artax\Response as HttpResponse;
use Amp\Promise;
use Room11\DOMUtils\LibXMLFatalErrorException;
use function Amp\resolve;
use function Room11\DOMUtils\domdocument_load_html;
use function Room11\DOMUtils\xpath_html_class;

class Searcher
{
    private const BASE_URL = 'https://www.google.com/search';
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko';
    private const ENCODING = 'UTF-8';
    private const PARSE_DESCRIPTION_REGEX = '#
        ^\s*
        ([0-9]{1,2})
        \s+
        (jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)
        \s+
        ([0-9]{4})
        \s+-\s+
        (.+)
    #iux';

    private $httpClient;

    private function getSearchUrl(string $term): string
    {
        return self::BASE_URL . '?' . http_build_query([
            'q' => $term,
            'lr' => 'lang_en',
        ]);
    }

    private function parseDescription(string $description): array
    {
        if (!preg_match(self::PARSE_DESCRIPTION_REGEX, $description, $match)) {
            return [$description, null];
        }

        return [
            $match[4],
            \DateTimeImmutable::createFromFormat(
                'j M Y',
                sprintf('%s %s %s', ltrim($match[1], '0'), $match[2], $match[3])
            )
        ];
    }

    /**
     * @param \DOMNodeList $resultNodes
     * @param \DOMXPath $xpath
     * @return SearchResult[]
     */
    private function getSearchResults(\DOMNodeList $resultNodes, \DOMXPath $xpath): array
    {
        $results = [];

        foreach ($resultNodes as $resultNode) {
            $linkNodes = $xpath->query(".//h3/a", $resultNode);

            if (!$linkNodes->length) {
                continue;
            }

            /** @var \DOMElement $linkNode */
            $linkNode = $linkNodes->item(0);

            $descriptionNodes = $xpath->query('.//span[@class="st"]', $resultNode);

            $description = 'No description available';
            $date = null;

            if ($descriptionNodes->length !== 0) {
                list($description, $date) = $this->parseDescription($descriptionNodes->item(0)->textContent);
            }

            $results[] = new SearchResult($linkNode->getAttribute("href"), $linkNode->textContent, $description, $date);
        }

        return $results;
    }

    private function doSearch(string $term)
    {
        $uri = $this->getSearchURL($term);

        $request = (new HttpRequest)
            ->setMethod('GET')
            ->setUri($uri)
            ->setHeader('User-Agent', self::USER_AGENT);

        /** @var HttpResponse $response */
        $response = yield $this->httpClient->request($request);

        if ($response->getStatus() !== 200) {
            throw new SearchFailedException(
                "Google responded with an HTTP status code of {$response->getStatus()}",
                $term, $uri
            );
        }

        if (preg_match('#charset\s*=\s*([^;]+)#i', trim(implode(', ', $response->getHeader('Content-Type'))), $match)
            && !preg_match('/' . preg_quote(self::ENCODING, '/') . '/i', $match[1])) {
            $body = iconv($match[1], self::ENCODING, $response->getBody());
        }

        if (empty($body)) {
            $body = $response->getBody();
        }

        try {
            $dom = domdocument_load_html($body);
        } catch (LibXMLFatalErrorException $e) {
            throw new SearchFailedException("Failed parsing response HTML", $term, $uri, $e);
        }

        $xpath = new \DOMXPath($dom);
        $resultNodes = $xpath->query('//*[' . xpath_html_class('g') . ']');

        return $resultNodes->length > 0
            ? $this->getSearchResults($resultNodes, $xpath)
            : [];
    }

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param string $term
     * @return Promise<SearchResult[]>
     */
    public function search(string $term): Promise
    {
        return resolve($this->doSearch($term));
    }
}
