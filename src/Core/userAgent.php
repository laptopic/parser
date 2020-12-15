<?php

namespace App\Core;



class userAgent
{
    const TYPE_WINDOWS = 'windows_ie';
    const TYPE_MOBILE_ANDROID = 'mobile_android';


    /**
     * Generate user agent
     *
     * @param string $type
     *
     * @return string
     */
    public static function generateUserAgent($type = self::TYPE_WINDOWS)
    {
        self::prepareVars();
        $base = self::$templateVars[$type];
        $result = $base['template'];
        foreach($base['vars'] as $varName => $varValues) {
            $varValuesMaxKey = count($varValues) - 1;
            $value = $varValues[rand(0, rand(0, $varValuesMaxKey))];
            $result = str_replace("{{$varName}}", $value, $result);
        }
        return $result;
    }

    /**
     * Generate unique user agents
     *
     * @param int   $count   Count of user agents
     * @param float $timeout Timeout in seconds.microseconds to generate next unique UA since last generated
     *
     * @return array
     */
    public static function generateUserAgents($count, $timeout = 3.0)
    {
        $result = [];
        $generated = 0;
        $timeoutAt = microtime(true) + $timeout;

        do {
            $userAgent = self::generateUserAgent();
            if(!isset($result[$userAgent])) {
                $result[$userAgent] = true;
                $generated++;
                $timeoutAt = microtime(true) + $timeout;
            }
        } while($generated < $count && microtime(true) < $timeoutAt);

        return array_keys($result);
    }

    protected static function prepareVars()
    {
        self::$templateVars = [
            self::TYPE_WINDOWS => [
                'template' => '{type}/{typeVersion} ({system1}{system2}{platform}{subPlatform}{locale}){kit1}{kit2}{kit3}',
                'vars' => [
                    'type' => [
                        'Mozilla',
                        'Opera',
                    ],
                    'typeVersion' => [
                        '5.0',
                        rand(51, 59) / 10
                    ],
                    'system1' => [
                        'compatible; ',
                        'Windows; ',
                        'Linux (Wine); ',
                        'Linux (VirtualBox); ',

                    ],
                    'system2' => [
                        '',
                        'U; ',
                        'I; ',
                        'N; ',
                    ],
                    'platform' => [
                        'Windows NT ' . (rand(61, 72) / 10) . '; ',
                        'Linux; ',
                        'Linux i686; ',
                        'Linux i686(x86_64); ',
                    ],
                    'subPlatform' => [
                        '',
                        'x64; ',
                        'WOW64; ',
                        'Trident/ ' . (rand(56, 85) / 10) . ' ; ',
                        'ARM; ',
                        'AMD64; ',
                    ],
                    'locale' => [
                        'en-US',
                        'en-us',
                        'en-us',
                        'ru-ru',
                        'ru',
                    ],
                    'kit1' => [
                        ' like Gecko',
                        '',
                        ' Gecko/' . date("Ymd", rand(strtotime('2004-01-01'), strtotime('now'))),
                        ' KHTML/4.3.5 (like Gecko)',
                    ],
                    'kit2' => [
                        ' Chrome/' . (rand(300, 419) / 10) . '.' . (rand(50015, 400700) / 1000),
                        '',
                    ],
                    'kit3' => [
                        ' Safari/' . (rand(50010, 65099) / 100),
                        '',
                        ' Firefox/' . (rand(60, 75) / 10),
                    ],
                ]
            ],
            self::TYPE_MOBILE_ANDROID => [
                'template' => 'Mozilla/5.0 (Linux; U; Android {androidVersion}; {locale};{subPlatform}){kit1} Version/4.0 Mobile Safari/{safariVersion}',
                'vars' => [
                    'androidVersion' => [
                        '5.0.3',
                        '5.0.3',
                        '5.0.3',
                        '5.0.3',
                        '5.0.1',
                        '4.4.1',
                        '4.3.4',
                        '4.3.3',
                        '4.2.5',
                        '4.1.1',
                        '4.0.3',
                        '2.3',
                    ],
                    'subPlatform' => [
                        '',
                        ' SAMSUNG SM-N9005 Build/JSS15J',
                        ' SAMSUNG GT-I9505 Build/JDQ39',
                        ' SAMSUNG GT-I9300 Build/IMM76D',
                        ' LG-L160L Build/IML74K',
                        ' HTC Sensation Build/IML74K',
                        ' HTC_IncredibleS_S710e Build/GRJ90',
                        ' HTC Vision Build/GRI40',
                        ' HTC Desire Build/GRJ22',
                        ' T-Mobile myTouch 3G Slide Build/GRI40',
                    ],
                    'locale' => [
                        'en-US',
                        'en-us',
                        'en-us',
                        'ru-ru',
                        'ru',
                    ],
                    'kit1' => [
                        '',
                        ' like Gecko',
                        ' Gecko/' . date("Ymd", rand(strtotime('2004-01-01'), strtotime('now'))),
                        ' KHTML/4.3.5 (like Gecko)',
                    ],
                    'safariVersion' => [
                        '999.9',
                        '534.30',
                        '533.1',
                    ]
                ]
            ]
        ];
    }

    private static $templateVars = [];
}