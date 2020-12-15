<?php

namespace App\Packages\feedSearcher;

use App\App;
use Goutte\Client;
use App\Core\url;
use App\Core\userAgent;
use Symfony\Component\DomCrawler\Crawler;

class searcher
{
    private $url;
    private $words = [
        'новости',
        'лента'
    ];

    public function __construct($url, $words = null)
    {
        $this->url = $url;
        if(!empty($words)) {
            $this->words = $words;
        }
    }

    public function process()
    {
         $attempts= 5;
        $crawler = null;
        do {
            try {
                $crawler = $this->getCrawler($this->url);
            } catch (\Exception $e) {
                error_log("Fail on loading crawler...");
                sleep(1);
            }
        } while(--$attempts > 0 && !$crawler);

        if(!isset($crawler)) {
            error_log("Skipping main, because unable to load page...");
            return [];
        }

        try {
            $found = [
                'rss' => $this->getFiltered($crawler, 'link[type="application/rss+xml"],link[type="application/rss"],link[type="application/xml"]'),
                'possible' => []
            ];
            error_log("That's we have found true rss in head:\n" . json_encode($found, 384));
            $checkCallable = function ($item) use (&$found){
                /** @var Crawler $item */
                $texthref = mb_strtolower("{$item->text()}:{$item->attr('href')}");
                if (mb_strpos($texthref, 'rss') !== false) {
                    return true;
                }
                foreach ($this->words as $searchWord) {
                    if (mb_strpos($texthref, $searchWord) !== false) {
                        return true;
                    }
                }
                $absoluteUrl = url::getAbsoluteUrl($this->url, $item->attr('href'));
                switch (mb_substr($texthref, -4)) {
                    case '.rdf':
                    case '.xml':
                    case '.rss':
                    case 'atom':
                        $found['rss'][] = $absoluteUrl;
                        break;
                }

                return false;
            };
            $linksToRss = $this->getFiltered($crawler, 'a', $checkCallable);
        } catch(\Exception $e) {
            error_log("Something failed:\n{$e->getTraceAsString()}");

            return [
                $e->getMessage()
            ];
        }

        if(!count($linksToRss)) {
            error_log("No any more links to rss found :(");
        } else {
            error_log("And that's is possible rss feeds or page with links to feed:\n" . json_encode($linksToRss, 384));
            foreach($linksToRss as $linkToRss) {
                try {
                    error_log("Processing page {$linkToRss}");
                    $possibleCrawler = $this->getCrawler($linkToRss);
                    if (!isset($possibleCrawler)) {
                        error_log("Skipping, because unable to load page...");
                        continue;
                    }
                    $found['possible'] = array_merge($found['possible'], $this->getFiltered($possibleCrawler, 'a', $checkCallable));
                } catch(\Exception $e) {
                    error_log("Some error on {$linkToRss}: {$e->getMessage()}");
                    continue;
                }
            }
        }
        $result = $found['rss'];
        foreach($found['possible'] as $key => $link) {
            if(!empty($link) && !in_array($link, $linksToRss) && substr($link, -4) !== 'html') {
                if(in_array(strtolower(substr($link, -4)), ['.rdf', '.xml', '.rss'])) {
                    $result[] = $link;
                } else {
                    try {
                        $crawlerLink = $this->getCrawler($link);
                        if (!isset($crawlerLink)) {
                            error_log("Skipping, because unable to load page...");
                            continue;
                        }
                        if ($crawlerLink->filter('rdf,rss,channel,item,description')->count()) {
                            error_log("Adding to result: {$link}");
                            $result[] = $link;
                        } else {
                            error_log("Skipping: " . mb_substr(preg_replace('/[\r\n]+/', ' ', $crawlerLink->html()), 0, 128));
                        }
                    } catch(\Exception $e) {
                        error_log("Some error on {$link}: {$e->getMessage()}");
                        continue;
                    }
                }
            }
        }

        array_unique($result);
        error_log("Total from {$this->url}:\n" . json_encode($result, 384));

        return $result;
    }

    protected function getCrawler($url, $attempts = 3)
    {
        error_log("Loading page {$url}...");
        do {
            try {
                $crawler = $this->getGoutte()->request('GET', $url);
            } catch (\Exception $e) {
                error_log("[ERROR] On loading page: {$e->getMessage()}, attempts elapsed: {$attempts}");
                $crawler = null;
            }
        } while(!isset($crawler) && --$attempts > 0);

        return $crawler;
    }

    /**
     * @param Crawler $crawler
     * @param string  $selector
     * @param callable|null    $check
     *
     * @return array
     */
    protected function getFiltered($crawler, $selector, $check = null)
    {
        $isHtml = (bool)$crawler->filter('html,body,div,input,script')->count();
        $found = [];
        if($isHtml) {
            $crawler->filter($selector)->each(function ($item) use (&$found, $check){
                try {
                    if (!isset($check) || $check($item)) {
                        /** @var Crawler $item */
                        $link = url::getAbsoluteUrl($this->url, $item->attr('href'));
                        $found[] = $link;
                    }
                } catch(\Exception $e) {
                    error_log("Some error on getFiltered: {$e->getMessage()}");
                }
            });
        }

        return $found;
    }

    protected function getGoutte()
    {
        static $goutte;
        if(!isset($goutte)) {
            $goutte = new Client();
            $goutte->getClient()->setDefaultOption('config/curl/' . CURLOPT_USERAGENT, userAgent::generateUserAgent());
        }

        return $goutte;
    }
}