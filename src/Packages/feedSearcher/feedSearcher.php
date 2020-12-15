<?php

namespace App\Packages\feedSearcher;


use App\Core\threadPool;
use App\Packages\feedSearcher\searcher;
use App\App;

class feedSearcher
{

    private $config = [
        'inputFile' => null,
        'searchWords' => [
            //Новости
            'news',
            'жаңалықтар',

            //Лента
            'лента',
            'lenta',
        ],
        'isTest' => false,
        'outputFile' => null
    ];

    public function handle()
    {

        $unificationOnly = App::get('local')['unification-only'];
        $this->config['inputFile'] = App::get('local')['input-file'];

        if(empty($this->config['inputFile'])) {
            App::get('output')->writeLn("--input-file=... is required");
            exit;
        }
        if($unificationOnly) {
            $this->config['outputFile'] =  App::get('local')['output-file'];
            if (empty($this->config['outputFile'])) {
                App::get('output')->writeLn("--output-file=... is required");
                exit;
            }
            $data = file($this->config['outputFile']);
            foreach($data as $key => $string) {
                $data[$key] = trim($string);
            }
            $data = array_unique($data);
            usort($data, function($item1, $item2) {
                return intval($item1) < intval($item2) ? -1 : 1;
            });
            $this->config['uniq'] = App::get('local')['uniq'];
            if(file_exists($this->config['uniq'])) {
                @unlink($this->config['uniq']);
            }
            file_put_contents($this->config['uniq'], implode("\n", $data));
            exit;
        }
        $this->config['isTest'] = App::get('local')['test'];
        if(!$this->config['isTest']) {
            $this->config['outputFile'] = App::get('local')['output-file'];
            if (empty($this->config['outputFile'])) {
                App::get('output')->writeLn("--output-file=... is required");
                exit;
            }
            if (file_exists($this->config['outputFile'])) {
                @unlink($this->config['outputFile']);
            }
        }

        foreach(file($this->config['inputFile']) as $key => $link) {
            $link = trim($link);
            if(!preg_match('/(?:ht|f)tps?\:\/\//ui', $link)) {
                $link = "http://{$link}";
            }
            $link = rtrim($link, '/') . '/';
            if(empty($link)) {
                continue;
            }
            $item = [
                'key' => $key,
                'link' => $link
            ];
            $this->threadPool()->add([$this, 'processLink'], $item);
        }
        $this->threadPool()->waitAll();
    }

    /**
     * @return threadPool
     */
    protected function threadPool()
    {
        static $threadPool;
        if(!isset($threadPool)) {
            $maxThreads = App::get('input')->getInteger('threads');
            if(!$maxThreads) {
                $maxThreads = 10;
            }
            error_log("=== Thread pool threads: {$maxThreads} / sleep 3 seconds ===");
            sleep(3);
            $threadPool = new threadPool();
            $threadPool->setMaxQueue(2000);
            $threadPool->setMaxThreads($maxThreads);
        }

        return $threadPool;
    }

    public function processLink($item)
    {
        $searcher = new searcher($item['link'], $this->config['searchWords']);
        $foundLinks = $searcher->process();
        if($this->config['isTest']) {
            error_log("===============\n[NOHANDLE] RESULT:\n" . json_encode($foundLinks, 384));
        } else {
            if(count($foundLinks)) {
                foreach ($foundLinks as $rssLink) {
                    $this->saveToFile([
                        'key' => $item['key'],
                        'link' => $item['link'],
                        'rss' => $rssLink
                    ]);
                }
            } else {
                $this->saveToFile([
                    'key' => $item['key'],
                    'link' => $item['link'],
                    'rss' => ''
                ]);
            }
        }
    }

    protected function saveToFile($item)
    {
        error_log("Saving item to file...");
        $handle = fopen($this->config['outputFile'], 'a');
        fputcsv($handle, $item);
        fclose($handle);
    }
}