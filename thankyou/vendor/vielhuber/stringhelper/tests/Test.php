<?php
use vielhuber\stringhelper\__;

class Test extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
        }
    }

    function test__x()
    {
        $this->assertFalse(__x(null));
        $this->assertFalse(__x(false));
        $this->assertTrue(__x(true));
        $this->assertFalse(__x([]));
        $this->assertFalse(__x(['']));
        $this->assertTrue(__x(0));
        $this->assertTrue(__x(1));
        $this->assertTrue(__x(-1));
        $this->assertTrue(__x('0'));
        $this->assertTrue(__x('1'));
        $this->assertTrue(__x('-1'));
        $this->assertFalse(__x(''));
        $this->assertFalse(__x(' '));
        $this->assertTrue(__x('null'));
        $this->assertTrue(__x('false'));
        $this->assertTrue(__x('true'));
        $this->assertTrue(__x('str'));
        $this->assertTrue(__x([0, 1]));
        $this->assertTrue(__x([0]));
        $this->assertFalse(__x('a:0:{}'));
        $this->assertTrue(__x('b:1;'));
        $this->assertFalse(__x('b:0;'));
        $this->assertFalse(__x(new stdClass()));
        $this->assertFalse(__x(@$_GET['undefined']));
        $this->assertTrue(
            __x(function ($foo, $bar) {
                return false;
            })
        );
    }

    function test__nx()
    {
        $this->assertTrue(__nx(null));
        $this->assertTrue(__nx(false));
        $this->assertFalse(__nx(true));
        $this->assertTrue(__nx([]));
        $this->assertTrue(__nx(['']));
        $this->assertFalse(__nx(0));
        $this->assertFalse(__nx(1));
        $this->assertFalse(__nx(-1));
        $this->assertFalse(__nx('0'));
        $this->assertFalse(__nx('1'));
        $this->assertFalse(__nx('-1'));
        $this->assertTrue(__nx(''));
        $this->assertTrue(__nx(' '));
        $this->assertFalse(__nx('null'));
        $this->assertFalse(__nx('false'));
        $this->assertFalse(__nx('true'));
        $this->assertFalse(__nx('str'));
        $this->assertFalse(__nx([0, 1]));
        $this->assertFalse(__nx([0]));
        $this->assertTrue(__nx('a:0:{}'));
        $this->assertFalse(__nx('b:1;'));
        $this->assertTrue(__nx('b:0;'));
        $this->assertTrue(__nx(new stdClass()));
        $this->assertTrue(__nx(@$_GET['undefined']));
        $this->assertFalse(
            __nx(function ($foo, $bar) {
                return false;
            })
        );
    }

    function test__fx()
    {
        $this->assertFalse(
            __fx(function () use (&$var) {
                return $var;
            })
        );
        $this->assertFalse(
            __fx(function () use (&$var) {
                return $var['undefined'];
            })
        );
        $this->assertFalse(
            __fx(function () use (&$var) {
                return $var['undefined']['foo']['bar'];
            })
        );
        $this->assertFalse(
            __fx(function () use (&$var) {
                return $var();
            })
        );
        $this->assertTrue(
            __fx(function () {
                return 'foo';
            })
        );
        $this->assertFalse(
            __fx(function () {
                return false;
            })
        );
    }

    function test__true()
    {
        $this->assertFalse(__true(null));
        $this->assertFalse(__true(false));
        $this->assertTrue(__true(true));
        $this->assertFalse(__true([]));
        $this->assertFalse(__true(['']));
        $this->assertFalse(__true(0));
        $this->assertTrue(__true(1));
        $this->assertTrue(__true(-1));
        $this->assertFalse(__true('0'));
        $this->assertTrue(__true('1'));
        $this->assertTrue(__true('-1'));
        $this->assertFalse(__true(''));
        $this->assertFalse(__true(' '));
        $this->assertFalse(__true('null'));
        $this->assertFalse(__true('false'));
        $this->assertTrue(__true('true'));
        $this->assertTrue(__true('str'));
        $this->assertTrue(__true([0, 1]));
        $this->assertTrue(__true([0]));
        $this->assertFalse(__true('a:0:{}'));
        $this->assertTrue(__true('b:1;'));
        $this->assertFalse(__true('b:0;'));
        $this->assertFalse(__true(new stdClass()));
        $this->assertFalse(__true(@$_GET['undefined']));
    }

    function test__false()
    {
        $this->assertFalse(__false(null));
        $this->assertTrue(__false(false));
        $this->assertFalse(__false(true));
        $this->assertFalse(__false([]));
        $this->assertFalse(__false(['']));
        $this->assertTrue(__false(0));
        $this->assertFalse(__false(1));
        $this->assertFalse(__false(-1));
        $this->assertTrue(__false('0'));
        $this->assertFalse(__false('1'));
        $this->assertFalse(__false('-1'));
        $this->assertFalse(__false(''));
        $this->assertFalse(__false(' '));
        $this->assertFalse(__false('null'));
        $this->assertTrue(__false('false'));
        $this->assertFalse(__false('true'));
        $this->assertFalse(__false('str'));
        $this->assertFalse(__false([0, 1]));
        $this->assertFalse(__false([0]));
        $this->assertFalse(__false('a:0:{}'));
        $this->assertFalse(__false('b:1;'));
        $this->assertTrue(__false('b:0;'));
        $this->assertFalse(__false(new stdClass()));
        $this->assertFalse(__false(@$_GET['undefined']));
    }

    function test__v()
    {
        $this->assertSame(__v('foo'), 'foo');
        $this->assertSame(__v(0), 0);
        $this->assertSame(__v(''), null);
        $this->assertSame(__v(' ', 'default'), 'default');
        $this->assertSame(__v('', [], 'baz'), 'baz');
        $this->assertSame(__v('', [], null), null);
        $this->assertSame(__v(), null);
        $this->assertSame(__v(__e()), null);
    }

    function test__e()
    {
        $this->assertSame(__e('foo'), 'foo');
        $this->assertSame(__e(0), 0);
        $this->assertEquals(__e(''), __empty());
        $this->assertSame(__e(' ', 'default'), 'default');
        $this->assertSame(__e('', [], 'baz'), 'baz');
        $this->assertEquals(__e('', [], null), __empty());
        $this->assertEquals(__e(), __empty());
        $this->assertEquals(get_class(__empty()), '__empty_helper');
    }

    function test__loop_e()
    {
        $array = ['foo', 'bar', 'baz'];
        foreach (__e($array) as $array__key => $array__value) {
            if ($array__key === 0) {
                $this->assertSame($array__value, 'foo');
            }
            if ($array__key === 1) {
                $this->assertSame($array__value, 'bar');
            }
            if ($array__key === 2) {
                $this->assertSame($array__value, 'baz');
            }
        }
        $array = [];
        foreach (__e($array) as $array__key => $array__value) {
            $this->assertTrue(false);
        }
        foreach (__e(@$array2) as $array2__key => $array2__value) {
            $this->assertTrue(false);
        }
    }

    function test__loop_i()
    {
        $array = ['foo', 'bar', 'baz'];
        foreach (__i($array) as $array__key => $array__value) {
            if ($array__key === 0) {
                $this->assertSame($array__value, 'foo');
            }
            if ($array__key === 1) {
                $this->assertSame($array__value, 'bar');
            }
            if ($array__key === 2) {
                $this->assertSame($array__value, 'baz');
            }
        }
        $array = [];
        foreach (__i($array) as $array__key => $array__value) {
            $this->assertTrue(false);
        }
        foreach (__i(@$array2) as $array2__key => $array2__value) {
            $this->assertTrue(false);
        }
    }

    function test__stfu()
    {
        if (__x(@$var)) {
            $this->assertTrue(false);
        }
        if (__nx(@$var)) {
            $this->assertTrue(true);
        }
        if (__true(@$var)) {
            $this->assertTrue(false);
        }
        if (__false(@$var)) {
            $this->assertTrue(true);
        }
        if (@$var === 'foo') {
            $this->assertTrue(false);
        }
        if (@$_GET['number'] == 1337) {
            $this->assertTrue(false);
        }
        foreach (__e(@$array) as $array__key => $array__value) {
            $this->assertTrue(false);
        }
        $this->assertSame(__v(@$var), null);
    }

    function test__class()
    {
        $this->assertSame(
            Person::find(1)
                ->getAddress()
                ->getCountry()
                ->getName(),
            'Germany'
        );
        $this->assertEquals(
            Person::find(2)
                ->getAddress()
                ->getCountry()
                ->getName(),
            ''
        );
        $this->assertEquals(
            Person::find(3)
                ->getAddress()
                ->getCountry()
                ->getName(),
            ''
        );
        $this->assertTrue(
            __x(
                Person::find(1)
                    ->getAddress()
                    ->getCountry()
                    ->getName()
            )
        );
        $this->assertFalse(
            __x(
                Person::find(2)
                    ->getAddress()
                    ->getCountry()
                    ->getName()
            )
        );
        $this->assertSame(
            __v(
                Person::find(1)
                    ->getAddress()
                    ->getCountry()
                    ->getName(),
                'default'
            ),
            'Germany'
        );
        $this->assertSame(
            __v(
                Person::find(2)
                    ->getAddress()
                    ->getCountry()
                    ->getName(),
                'default'
            ),
            'default'
        );
    }

    function test__cookies()
    {
        // the @ is needed because we cannot set cookies in phpunit (only $_COOKIE gets filled)
        @__cookie_set('cookie_name', 'cookie_value', 7);
        $this->assertSame(__cookie_exists('cookie_name'), true);
        $this->assertSame(__cookie_get('cookie_name'), 'cookie_value');
        @__cookie_delete('cookie_name');
        $this->assertSame(__cookie_exists('cookie_name'), false);
        $this->assertSame(__cookie_get('cookie_name'), null);
        @__cookie_set('cookie_name', [1, '42'], 7);
        $this->assertSame(__cookie_exists('cookie_name'), true);
        $this->assertSame(__cookie_get('cookie_name'), [1, '42']);
        @__cookie_delete('cookie_name');
        @__cookie_set('cookie_name', 'cookie_value');
        $this->assertSame(__cookie_exists('cookie_name'), true);
        $this->assertSame(__cookie_get('cookie_name'), 'cookie_value');
        @__cookie_set('special_cookie_name', 'cookie_value', 7, [
            'path' => '/',
            'domain' => '',
            'samesite' => 'None',
            'secure' => true,
            'httponly' => false
        ]);
        $this->assertSame(__cookie_get('special_cookie_name'), 'cookie_value');
    }

    function test__truncate_string()
    {
        $this->assertSame(__truncate_string('foo', 50), 'foo');
        $this->assertSame(__truncate_string('', 50), '');
        $this->assertSame(__truncate_string([], 50), []);
        $this->assertSame(__truncate_string(null, 50), null);
        $this->assertSame(__truncate_string(false, 50), false);
        $this->assertSame(__truncate_string(true, 50), true);
        $this->assertSame(__truncate_string(str_repeat('aaaaaaaaa ', 5), 50), str_repeat('aaaaaaaaa ', 5));
        $this->assertSame(
            __truncate_string(str_repeat('aaaaaaaaaa', 5) . 'a', 50),
            str_repeat('aaaaaaaaaa', 5) . ' ...'
        );
        $this->assertSame(
            __truncate_string(str_repeat('aaaaaaaaa ', 5) . 'a', 50),
            str_repeat('aaaaaaaaa ', 4) . '...'
        );
        $this->assertSame(
            __truncate_string(str_repeat('aaaaaaaaa ', 5) . 'a', 50, '…'),
            str_repeat('aaaaaaaaa ', 4) . '…'
        );
        $this->assertSame(__truncate_string('Lorem ipsum dolor sit amet, consectetuer.', 20), 'Lorem ipsum dolor ...');
    }

    function test__trim_every_line()
    {
        $this->assertSame(__trim_every_line("foo\n bar"), "foo\nbar");
        $this->assertSame(__trim_every_line("foo\nbar"), "foo\nbar");
        $this->assertSame(__trim_every_line("foo\n bar\nbaz "), "foo\nbar\nbaz");
        $this->assertSame(__trim_every_line(''), '');
        $this->assertSame(__trim_every_line(null), null);
        $this->assertSame(__trim_every_line(false), false);
        $this->assertSame(__trim_every_line(true), true);
        $this->assertSame(__trim_every_line([]), []);
    }

    function test__br2nl()
    {
        $this->assertSame(__br2nl('foo<br/>bar'), "foo\nbar");
        $this->assertSame(__br2nl('foo<br>bar'), "foo\nbar");
        $this->assertSame(__br2nl("foo\nbar"), "foo\nbar");
        $this->assertSame(__br2nl('foo bar'), 'foo bar');
        $this->assertSame(__br2nl('foobar'), 'foobar');
        $this->assertSame(__br2nl(''), '');
        $this->assertSame(__br2nl(null), null);
        $this->assertSame(__br2nl(false), false);
        $this->assertSame(__br2nl(true), true);
        $this->assertSame(__br2nl([]), []);
    }

    function test__str_to_dom()
    {
        foreach (
            [
                '
                <!DOCTYPE html><html><body>
                <picture>
                    <source media="(max-width: 300px)" srcset="#" data-size="360x">
                    <source media="(max-width: 600px)" srcset="#" data-size="768x">
                </picture>
                </body></html>
                ',
                '<command data-foo="bar" />',
                '<embed data-foo="bar" />',
                '<keygen data-foo="bar" />',
                '<source data-foo="bar" />',
                '<track data-foo="bar" />',
                '<wbr data-foo="bar" />',
                '<ul><li></li><li></li></ul>',
                '<custom-component @click.prevent="foo()"></custom-component>',
                '<custom-component
 @click.prevent="foo()"></custom-component>',
                '<custom-component
@click.prevent="foo()"
></custom-component>',
                'Dies ist ein Test',
                '<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, minimum-scale=1" />
<title>.</title>
</head>
<body>

</body>
</html>',
                '<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, minimum-scale=1" />
<title>.</title>
</head>
<body>
    <p>lsdäwekü0päeikpokpokóäölällöä</p>
</body>
</html>',
                '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xsi:schemalocation="http://www.sitemaps.org/schemas/sitemap/0.9">
	<url>
		<loc>http://gtbabel.local.vielhuber.de/blog/quick-tip-split-testing-mit-apache/</loc>
		<lastmod>2020-09-16T00:14:59+00:00</lastmod>
	</url>
	<url>
		<loc>http://gtbabel.local.vielhuber.de/blog/seite-per-css-hinter-overlay-weichzeichnen/</loc>
		<lastmod>2020-09-20T22:21:54+00:00</lastmod>
	</url>
	<url>
		<loc>http://gtbabel.local.vielhuber.de/blog/tricks-fuer-laravel-eloquent-relationships/</loc>
		<lastmod>2020-10-09T15:50:30+00:00</lastmod>
	</url>
</urlset>
<!-- XML Sitemap generated by Yoast SEO --><!-- permalink_structure ends with slash (/) but REQUEST_URI does not end with slash (/) -->'
            ]
            as $html__value
        ) {
            $this->assertSame(__minify_html(__dom_to_str(__str_to_dom($html__value))), __minify_html($html__value));
        }

        $in = <<<'EOD'
<!DOCTYPE html>
<html>
<head>
    <script>
    var baz;
    </script>
    <title>test</title>
    <script>
    console.log("<h1>hello</h1>");
    var foo = '<a href="#"></a>';
    var bar = "<a href=\"#\"></a>";
    </script>
</head>
<body>
</body>
</html>
EOD;
        $out = <<<'EOD'
<!DOCTYPE html>
<html>
<head>
    <script>
    var baz;
    </script>
    <title>test</title>
    <script>
    console.log("<h1>hello<\/h1>");
    var foo = '<a href="#"><\/a>';
    var bar = "<a href=\"#\"><\/a>";
    </script>
</head>
<body>
</body>
</html>
EOD;
        $this->assertSame(__minify_html(__dom_to_str(__str_to_dom($in))), __minify_html($out));

        $domdocument = __str_to_dom('Test');
        $domxpath = new \DOMXPath($domdocument);
        $domxpath->query('/html/body')[0]->setAttribute('data-foo', 'bar');
        $this->assertSame(__dom_to_str($domdocument), 'Test');
    }

    function test__translate_google()
    {
        foreach (['free', $_SERVER['GOOGLE_TRANSLATION_API_KEY']] as $api_keys__value) {
            foreach (
                [
                    [
                        'Das ist das <span>Haus</span> vom Nikolaus',
                        ['This is the <span>house</span> of Santa Claus', 'This is Santa\'s <span>house</span>'],
                        'de',
                        'en'
                    ],
                    [
                        'Das ist das <span class="notranslate">Haus</span> vom Nikolaus',
                        [
                            'This is the <span class="notranslate">Haus</span> of Santa Claus',
                            'This is Santa\'s <span class="notranslate">Haus</span>'
                        ],
                        'de',
                        'en'
                    ],
                    [
                        'Land: <a href="#">Dies ist ein Test</a>',
                        [
                            'Pays: <a href="#">Ceci est un test</a>',
                            'Pays : <a href="#">Ceci est un test</a>',
                            'Pays : <a href="#">Ceci est un test</a>'
                        ],
                        'de',
                        'fr'
                    ],
                    [
                        'Haus: <a>+49 (0) 89 21 540 01 42</a><br/> Telefax: +49 (0) 89 21 544 59 1<br/> E-Mail: <a>david@vielhuber.local.vielhuber.de</a>',
                        'House: <a>+49 (0) 89 21 540 01 42</a> <br/> Fax: +49 (0) 89 21 544 59 1<br/> Email: <a>david@vielhuber.local.vielhuber.de</a>',
                        'de',
                        'en'
                    ],
                    [
                        'Sein oder Nichtsein; das ist hier die Frage.',
                        'To be or not to be; that is the question.',
                        'de',
                        'en'
                    ],
                    [
                        '<a>VanillaJS</a> ist seit <a>ES6</a> quasi in allen Bereichen dem Urgestein <a>jQuery</a> ebenbürtig und inzwischen weit überlegen.',
                        [
                            '<a>VanillaJS</a> has been on <a>par with</a> the veteran <a>jQuery</a> in almost all areas since <a>ES6</a> and is now far superior.',
                            'VanillaJS has been on <a>par with</a><a>the veteran jQuery</a> in almost all areas <a>since ES6</a> and is now far superior.',
                            'Since <a>ES6</a> , <a>VanillaJS</a> has been on a par with the veteran <a>jQuery</a> in almost all areas and is now far superior.',
                            '<a>Since ES6,</a> VanillaJS has been on a <a>par with</a> <a>the veteran jQuery</a> in almost all areas and is now far superior.'
                        ],
                        'de',
                        'en'
                    ],
                    [
                        '<a p="1">VanillaJS</a> ist seit <a p="2">ES6</a> quasi in allen Bereichen dem Urgestein <a p="3">jQuery</a> ebenbürtig und inzwischen weit überlegen.',
                        [
                            '<a p="1">VanillaJS</a> has been on <a p="1">par with</a> the veteran <a p="3">jQuery</a> in almost all areas since <a p="2">ES6</a> and is now far superior.',
                            'VanillaJS has been on <a p="1">par with</a> <a p="3">the veteran jQuery</a> in almost all areas <a p="2">since ES6</a> and is now far superior.',
                            'Since <a p="2">ES6</a> , <a p="1">VanillaJS</a> has been on a par with the veteran <a p="3">jQuery</a> in almost all areas and is now far superior.',
                            '<a p="2">Since ES6,</a> VanillaJS has been on a <a p="1">par with</a> <a p="3">the veteran jQuery</a> in almost all areas and is now far superior.'
                        ],
                        'de',
                        'en'
                    ],
                    ['<b>Haus</b><span>Hund</span>', '<b>House</b><span>Dog</span>', 'de', 'en'],
                    [
                        '<i>Hund</i><b>Haus</b><i>Hallo</i><b>Stadt</b>',
                        '<i>Dog</i><b>House</b><i>Hello</i><b>City</b>',
                        'de',
                        'en'
                    ],
                    ['<b>Hund</b><span>Haus</span>', '<b>Dog</b><span>House</span>', 'de', 'en'],
                    ['Haus. Welt.', 'House. World.', 'de', 'en'],
                    ['<i>Haus.</i><b>Hund.</b>', '<i>House.</i><b>Dog.</b>', 'de', 'en'],
                    ['<b>Haus.</b><span>Hund.</span>', '<b>House.</b><span>Dog.</span>', 'de', 'en'],
                    ['<b>Haus.</b><i>Hund.</i>', '<b>House.</b><i>Dog.</i>', 'de', 'en'],
                    ['<b>Haus.</b><b>Hund.</b>', '<b>House.</b><b>Dog.</b>', 'de', 'en'],
                    ['<i>Haus.</i><i>Hund.</i>', '<i>House.</i><i>Dog.</i>', 'de', 'en'],
                    [
                        '<b p="1">Haus</b><span p="2">Hund</span>',
                        '<b p="1">House</b><span p="2">Dog</span>',
                        'de',
                        'en'
                    ],
                    [
                        '<span>Haus</span><span>Das ist ein Haus. Und noch ein Haus.</span>',
                        '<span>House</span> <span>this is a house. And another house.</span>',
                        'de',
                        'en'
                    ]
                ]
                as $examples__key => $examples__value
            ) {
                $trans = __translate_google(
                    $examples__value[0],
                    $examples__value[2],
                    $examples__value[3],
                    $api_keys__value
                );
                if (!is_array($examples__value[1])) {
                    $examples__value[1] = [$examples__value[1]];
                }
                $match = false;
                foreach ($examples__value[1] as $examples__value__value) {
                    if (__minify_html(mb_strtolower($trans)) == __minify_html(mb_strtolower($examples__value__value))) {
                        $match = true;
                        break;
                    }
                }
                if ($match === false) {
                    $this->assertSame($examples__key, $trans);
                }
            }
        }

        foreach (['free', $_SERVER['GOOGLE_TRANSLATION_API_KEY']] as $api_keys__value) {
            $this->assertSame(__translate_google(null, 'de', 'en', $api_keys__value), null);
            $this->assertSame(__translate_google('', 'de', 'en', $api_keys__value), '');
        }

        $this->assertSame(
            __translate_google(
                'Hund
Haus',
                'de',
                'en',
                $_SERVER['GOOGLE_TRANSLATION_API_KEY']
            ),
            'Dog
House'
        );

        try {
            __translate_google('Das ist ein Test', 'dejhfjhfhh', 'enjhgffjhfj', 'free');
        } catch (\Throwable $t) {
            $this->assertSame(strpos($t->getMessage(), 'HTTP server (unknown)') !== false, true);
        }

        try {
            __translate_google('Sein oder Nichtsein; das ist hier die Frage.', 'de', 'en', 'WRONG_KEY!');
        } catch (\Throwable $t) {
            $this->assertSame(strpos($t->getMessage(), 'API key not valid') !== false, true);
        }

        if (isset($_SERVER['PROXY'])) {
            foreach ([$_SERVER['GOOGLE_TRANSLATION_API_KEY'], 'free'] as $api_keys__value) {
                $this->assertSame(__translate_google('Haus', 'de', 'en', $api_keys__value, $_SERVER['PROXY']), 'House');
            }
        }
    }

    function test__translate_microsoft()
    {
        $this->assertTrue(true);
        return;
        foreach (['free', $_SERVER['MICROSOFT_TRANSLATION_API_KEY']] as $api_keys__value) {
            if ($api_keys__value === 'free') {
                continue;
            }
            if ($api_keys__value === 'free' && @$_SERVER['CI'] === true) {
                continue;
            }
            $this->assertContains(
                __translate_microsoft('Sein oder Nichtsein; das ist hier die Frage.', 'de', 'en', $api_keys__value),
                ['To be or not to be; that is the question here.', 'Being or not being; that is the question here.']
            );

            $this->assertContains(
                __translate_microsoft(
                    '<a>VanillaJS</a> ist seit <a>ES6</a> quasi in allen Bereichen dem Urgestein <a>jQuery</a> ebenbürtig und inzwischen weit überlegen.',
                    'de',
                    'en',
                    $api_keys__value
                ),
                [
                    'Since <a>ES6,</a> <a>VanillaJS</a> has been on an equal footing with the original <a>rock jQuery</a> in virtually all areas and is now far superior.',
                    '<a>VanillaJS</a> has been on an equal footing with the original rock <a>jQuery</a> in almost all areas since <a>ES6</a> and is now far superior.',
                    '<a>VanillaJS</a> is since <a>ES6</a> virtually equal to the veteran <a>jQuery</a> in all areas and is now far superior.'
                ]
            );

            $this->assertContains(
                __translate_microsoft(
                    '<a p="1">VanillaJS</a> ist seit <a p="2">ES6</a> quasi in allen Bereichen dem Urgestein <a p="3">jQuery</a> ebenbürtig und inzwischen weit überlegen.',
                    'de',
                    'en',
                    $api_keys__value
                ),
                [
                    'Since <a p="2">ES6,</a> <a p="1">VanillaJS</a> has been on an equal footing with the original <a p="3">rock jQuery</a> in virtually all areas and is now far superior.',
                    '<a p="1">VanillaJS</a> has been equal to the original rock <a p="3">jQuery</a> since <a p="2">ES6</a> in virtually all areas.',
                    '<a p="1">VanillaJS</a> is since <a p="2">ES6</a> virtually equal to the veteran <a p="3">jQuery</a> in all areas and is now far superior.'
                ]
            );
        }

        try {
            __translate_microsoft('Sein oder Nichtsein; das ist hier die Frage.', 'de', 'en', 'WRONG_KEY!');
        } catch (\Throwable $t) {
            $this->assertSame(strpos($t->getMessage(), 'credentials are missing') !== false, true);
        }

        /* this is currently disabled because microsoft is very good at detecting proxies (showCaptcha is responded) */
        if (1 == 0) {
            if (isset($_SERVER['PROXY'])) {
                foreach ([$_SERVER['MICROSOFT_TRANSLATION_API_KEY'], 'free'] as $api_keys__value) {
                    $this->assertSame(
                        __translate_microsoft('Haus', 'de', 'en', $api_keys__value, $_SERVER['PROXY']),
                        'House'
                    );
                }
            }
        }
    }

    function test__translate_deepl()
    {
        $this->assertTrue(true);
        return;
        foreach (['free', @$_SERVER['DEEPL_TRANSLATION_API_KEY']] as $api_keys__value) {
            if ($api_keys__value == '') {
                continue;
            }
            $this->assertSame(
                __translate_deepl('Sein oder Nichtsein; das ist hier die Frage.', 'de', 'en', $api_keys__value),
                'To be or not to be; that is the question here.'
            );

            $this->assertContains(
                __translate_deepl(
                    '<a p="1">VanillaJS</a> ist seit <a p="2">ES6</a> quasi in allen Bereichen dem Urgestein <a p="3">jQuery</a> ebenbürtig und inzwischen weit überlegen.',
                    'de',
                    'en',
                    $api_keys__value
                ),
                [
                    'Since <a p="2">ES6</a>,<a p="1">VanillaJS</a> has been on par with <a p="3">jQuery</a> in all areas and is now far superior.',
                    'Since <a p="2">ES6</a>,<a p="1">VanillaJS</a> has been on par with <a p="3">jQuery</a> in virtually all areas and is now far superior.',
                    '<a p="1">VanillaJS</a> is since <a p="2">ES6</a> virtually in all areas the equal of the Urgestein <a p="3">jQuery</a> and now far superior.'
                ]
            );

            $this->assertContains(
                __translate_deepl('Das ist <br> ein<br> Haus <br> und Hund.<hr>Cool!', 'de', 'en', $api_keys__value),
                [
                    'This is <br/> a<br/> house <br/> and dog.<hr/>Cool!',
                    'That\'s<br/> a<br/> house<br/> and dog.<hr/>Cool!'
                ]
            );
        }

        try {
            __translate_deepl('Sein oder Nichtsein; das ist hier die Frage.', 'de', 'en', 'WRONG_KEY!');
        } catch (\Throwable $t) {
            $this->assertSame(strpos($t->getMessage(), 'WRONG_KEY') !== false, true);
        }

        if (isset($_SERVER['PROXY'])) {
            foreach ([$_SERVER['DEEPL_TRANSLATION_API_KEY'], 'free'] as $api_keys__value) {
                $this->assertSame(__translate_deepl('Haus', 'de', 'en', $api_keys__value, $_SERVER['PROXY']), 'House');
            }
        }
    }

    function test__atrim()
    {
        $this->assertSame(__atrim(null), null);
        $this->assertSame(__atrim(false), false);
        $this->assertSame(__atrim(true), true);
        $this->assertSame(__atrim([]), []);
        $this->assertSame(__atrim(['foo', 'bar', 'baz']), ['foo', 'bar', 'baz']);
        $this->assertSame(
            __atrim([
                'foo
',
                'bar',
                '
baz'
            ]),
            ['foo', 'bar', 'baz']
        );
        $this->assertSame(__atrim(['�']), ['�']);
    }

    function test__has_spamwords()
    {
        $this->assertSame(__has_spamwords('loli*ta gi*rl fu*ck c*p pt*hc'), true);
        $this->assertSame(__has_spamwords('This is cool stuff.'), false);
        $this->assertSame(__has_spamwords('I do spy software your website.'), true);
        $this->assertSame(
            __has_spamwords(
                'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus.'
            ),
            false
        );
        $this->assertSame(
            __has_spamwords(
                'Es gibt im Moment in diese Mannschaft, oh, einige Spieler vergessen ihnen Profi was sie sind. Ich lese nicht sehr viele Zeitungen, aber ich habe gehört viele Situationen. Erstens: wir haben nicht offensiv gespielt. Es gibt keine deutsche Mannschaft spielt offensiv und die Name offensiv wie Bayern. Letzte Spiel hatten wir in Platz drei Spitzen: Elber, Jancka und dann Zickler. Wir müssen nicht vergessen Zickler. Zickler ist eine Spitzen mehr, Mehmet eh mehr Basler. Ist klar diese Wörter, ist möglich verstehen, was ich hab gesagt? Danke.'
            ),
            false
        );
        $this->assertSame(
            __has_spamwords(
                'Lorem ipsum dolor sit amet 100% Plagiaris, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus.'
            ),
            true
        );
        $this->assertSame(
            __has_spamwords(
                'Es gibt im Moment in diese Mannschaft 100% Plagiaris, oh, einige Spieler vergessen ihnen Profi was sie sind. Ich lese nicht sehr viele Zeitungen, aber ich habe gehört viele Situationen. Erstens: wir haben nicht offensiv gespielt. Es gibt keine deutsche Mannschaft spielt offensiv und die Name offensiv wie Bayern. Letzte Spiel hatten wir in Platz drei Spitzen: Elber, Jancka und dann Zickler. Wir müssen nicht vergessen Zickler. Zickler ist eine Spitzen mehr, Mehmet eh mehr Basler. Ist klar diese Wörter, ist möglich verstehen, was ich hab gesagt? Danke.'
            ),
            true
        );
        $this->assertSame(__has_spamwords('Hongsheng Ltd'), false);
        $this->assertSame(__has_spamwords('Hongsheng Ltd', ['hongsheng']), true);
        $this->assertSame(__has_spamwords('I do spy software your website.', null, ['spy software']), false);
        $this->assertSame(__has_spamwords('foobar@icloud.com'), false);
        $this->assertSame(__has_spamwords('foobar@yahoo.com'), false);
        $this->assertSame(__has_spamwords('foobar@1und1.de'), false);
        $this->assertSame(__has_spamwords('foobar@ionos.de'), false);
        $this->assertSame(__has_spamwords('foobar@gmail.com'), false);
        $this->assertSame(__has_spamwords('foobar@freenet.de'), false);
        $this->assertSame(__has_spamwords('foobar@aol.com'), false);
        $this->assertSame(__has_spamwords('foobar@outlook.de'), false);
        $this->assertSame(__has_spamwords('foobar@t-online.de'), false);
        $this->assertSame(__has_spamwords('foobar@web.de'), false);
        $this->assertSame(__has_spamwords('foobar@gmx.de'), false);
        $this->assertSame(__has_spamwords('foobar@gmx.net'), false);
        $this->assertSame(__has_spamwords(' '), false);
        $this->assertSame(__has_spamwords(''), false);
        $this->assertSame(__has_spamwords(null), false);
        $this->assertSame(__has_spamwords(true), false);
        $this->assertSame(__has_spamwords(false), false);
        $this->assertSame(__has_spamwords([]), false);
    }

    function test__ip_is_on_spamlist()
    {
        $this->assertSame(__ip_is_on_spamlist('127.0.0.1'), false);
        $this->assertSame(__ip_is_on_spamlist('foo'), false);
        $this->assertSame(__ip_is_on_spamlist(''), false);
        $this->assertSame(__ip_is_on_spamlist(null), false);
        $this->assertSame(__ip_is_on_spamlist([]), false);
        $this->assertSame(__ip_is_on_spamlist(true), false);
        $this->assertSame(__ip_is_on_spamlist(false), false);
    }

    function test__is_repetitive_action()
    {
        $default = $_SERVER['REMOTE_ADDR'];

        $_SERVER['REMOTE_ADDR'] =
            mt_rand(100, 999) . '.' . mt_rand(100, 999) . '.' . mt_rand(100, 999) . '.' . mt_rand(100, 999);
        $this->assertSame(__is_repetitive_action(), false);
        $this->assertSame(__is_repetitive_action(), true);

        $_SERVER['REMOTE_ADDR'] =
            mt_rand(100, 999) . '.' . mt_rand(100, 999) . '.' . mt_rand(100, 999) . '.' . mt_rand(100, 999);
        $this->assertSame(__is_repetitive_action('foo'), false);
        $this->assertSame(__is_repetitive_action('bar'), false);
        $this->assertSame(__is_repetitive_action('foo'), true);
        $this->assertSame(__is_repetitive_action('bar'), true);

        $_SERVER['REMOTE_ADDR'] =
            mt_rand(100, 999) . '.' . mt_rand(100, 999) . '.' . mt_rand(100, 999) . '.' . mt_rand(100, 999);
        $this->assertSame(__is_repetitive_action('foo'), false);
        sleep(2);
        $this->assertSame(__is_repetitive_action('foo', 1 / 60), false);
        $this->assertSame(__is_repetitive_action('foo'), true);

        $_SERVER['REMOTE_ADDR'] =
            mt_rand(100, 999) . '.' . mt_rand(100, 999) . '.' . mt_rand(100, 999) . '.' . mt_rand(100, 999);
        $this->assertSame(__is_repetitive_action('foo'), false);
        $this->assertSame(__is_repetitive_action('foo', null, $_SERVER['REMOTE_ADDR']), false);
        $this->assertSame(__is_repetitive_action('foo', null, [$_SERVER['REMOTE_ADDR']]), false);
        $this->assertSame(__is_repetitive_action('foo', null, [$_SERVER['REMOTE_ADDR'] . '.111']), true);

        $_SERVER['REMOTE_ADDR'] = $default;
    }

    function test__age_from_date()
    {
        foreach (
            [
                [date(strtotime('now - 20 years - 30 days')), 20, 1047, 7335],
                [date(strtotime('now - 20 years + 1 day')), 19, 1043, 7304],
                [date(strtotime('now - 20 years')), 20, 1043, 7305],
                [date(strtotime('now - 40 years - 30 days')), 40, 2091, 14640],
                [date(strtotime('now - 40 years + 30 days')), 39, 2082, 14580],
                [null, null, null, null],
                [false, null, null, null],
                ['foo', null, null, null],
                [[date(strtotime('now - 20 years - 30 days')), date(strtotime('now - 10 years'))], 10, 526, 3682],
                [[date(strtotime('now - 20 years')), date(strtotime('now - 10 years'))], 10, 521, 3652],
                [[date(strtotime('now - 20 years - 30 days')), null], 20, 1047, 7335],
                [[date(strtotime('now - 20 years - 30 days')), ''], 20, 1047, 7335],
                [[date(strtotime('now - 20 years - 30 days')), 'foo'], null, null, null],
                [date(strtotime('5232-01-01')), null, null, null],
                [[date('Y-m-d', strtotime('27.12.2007')), date('Y-m-d', strtotime('27.12.2007'))], 0, 0, 0],
                [[date('Y-m-d', strtotime('27.12.2007')), date('Y-m-d', strtotime('26.12.2008'))], 0, 52, 365],
                [[date('Y-m-d', strtotime('27.12.2007')), date('Y-m-d', strtotime('27.12.2008'))], 1, 52, 366],
                [[date('Y-m-d', strtotime('27.12.2007')), date('Y-m-d', strtotime('28.12.2008'))], 1, 52, 367]
            ]
            as $data__value
        ) {
            if (is_array($data__value[0])) {
                $date_birth = $data__value[0][0];
                $date_relative = $data__value[0][1];
            } else {
                $date_birth = $data__value[0];
                $date_relative = null;
            }
            $this->assertSame(__age_from_date($date_birth, $date_relative), $data__value[1]);
            $this->assertSame(__age_from_date_weeks($date_birth, $date_relative), $data__value[2]);
            $this->assertSame(__age_from_date_days($date_birth, $date_relative), $data__value[3]);
        }
    }

    function test__curl()
    {
        $response = __curl('https://httpbin.org/anything');
        $this->assertSame($response->result->method, 'GET');
        $this->assertSame($response->status, 200);
        $this->assertSame(!empty($response->headers), true);
        $response = __curl('https://httpbin.org/anything', ['foo' => 'bar'], 'POST');
        $this->assertSame($response->result->method, 'POST');
        $this->assertSame($response->result->data, json_encode(['foo' => 'bar']));
        $response = __curl('https://httpbin.org/anything', ['foo' => 'bar'], 'PUT');
        $this->assertSame($response->result->method, 'PUT');
        $this->assertSame($response->result->data, json_encode(['foo' => 'bar']));
        $response = __curl('https://httpbin.org/anything', null, 'DELETE');
        $this->assertSame($response->result->method, 'DELETE');
        $this->assertSame($response->result->data, '');
        $response = __curl('https://httpbin.org/anything', ['foo' => 'bar'], 'POST', [
            'Bar' => 'baz'
        ]);
        $this->assertSame($response->result->headers->Bar, 'baz');
        $response = __curl('https://vielhuber.de');
        $this->assertTrue(strpos($response->result, '<html') !== false);

        $response = __curl('https://httpbin.org/basic-auth/foo/bar');
        $this->assertSame($response->status, 401);
        $response = __curl('https://httpbin.org/basic-auth/foo/bar', null, null, null, false, true, 60, [
            'foo' => 'bar'
        ]);
        $this->assertSame($response->status, 200);
        $response = __curl('https://foo:bar@httpbin.org/basic-auth/foo/bar', null, null, null, false, true, 60, null);
        $this->assertSame($response->status, 200);

        $response = __curl('https://httpbin.org/cookies', null, null, null, false, false, 60, null, null);
        $this->assertSame(empty((array) $response->result->cookies), true);
        $response = __curl('https://httpbin.org/cookies', null, null, null, false, false, 60, null, [
            'foo' => 'bar',
            'bar' => 'baz'
        ]);
        $this->assertEquals($response->result->cookies, (object) ['foo' => 'bar', 'bar' => 'baz']);

        // changed host due to https://github.com/postmanlabs/httpbin/issues/617
        $response = __curl(
            'https://httpbingo.org/absolute-redirect/1',
            null,
            null,
            null,
            false,
            false,
            60,
            null,
            null,
            true
        );
        $this->assertSame($response->status, 200);
        $response = __curl(
            'https://httpbingo.org/absolute-redirect/1',
            null,
            null,
            null,
            false,
            false,
            60,
            null,
            null,
            false
        );
        $this->assertSame($response->status, 302);
        $response = __curl(
            'https://httpbingo.org/absolute-redirect/1',
            null,
            null,
            null,
            false,
            false,
            60,
            null,
            null,
            true
        );
        $this->assertSame($response->url, 'https://httpbingo.org/get');

        if (isset($_SERVER['PROXY'])) {
            $response = __curl(
                'https://httpbingo.org/ip',
                null,
                'GET',
                null,
                false,
                true,
                3,
                null,
                null,
                true,
                $_SERVER['PROXY']
            );
            $this->assertSame($response->status, 200);
            $this->assertSame(
                strpos($response->result->origin, explode(':', explode('@', $_SERVER['PROXY'])[1])[0]) !== false,
                true
            );
        }

        // fill in your wp credentials to test this
        if (1 == 0) {
            $wp_url = 'https://vielhuber.de';
            $wp_username = 'username';
            $wp_password = 'password';
            __curl(
                $wp_url . '/wp-login.php',
                ['log' => $wp_username, 'pwd' => $wp_password],
                'POST',
                null,
                true,
                false
            );
            $response = __curl($wp_url . '/wp-admin/options.php', null, 'GET', null, true); // gets the html code of wp backend
            $this->assertTrue(strpos($response->result, 'show_avatars') !== false);
        }
    }

    function test_timestamp_excel_unix()
    {
        $this->assertSame(__timestamp_excel_to_str(36526), '2000-01-01 00:00:00');
        $this->assertSame(__timestamp_excel_to_str(36526), '2000-01-01 00:00:00');
        $this->assertSame(__timestamp_excel_to_str(36526.3440972222), '2000-01-01 08:15:30');
        $this->assertSame(__timestamp_str_to_excel('2000-01-01'), 36526);
        $this->assertSame(__timestamp_str_to_excel('2000-01-01 00:00:00'), 36526);
        $this->assertSame(__timestamp_str_to_excel('2000-01-01 08:15:30'), 36526.3440972222);
    }

    function test_has_basic_auth()
    {
        $this->assertSame(__has_basic_auth('https://httpbin.org/basic-auth/foo/bar'), true);
        $this->assertSame(__has_basic_auth('https://vielhuber.de'), false);
        $this->assertSame(__has_basic_auth('http://dewuiztgchdnhbvwsvdhzu.com'), false);
        $this->assertSame(__has_basic_auth(null), false);
        $this->assertSame(__has_basic_auth(false), false);
        $this->assertSame(__has_basic_auth(true), false);
        $this->assertSame(__has_basic_auth('foo'), false);
        $this->assertSame(__has_basic_auth(''), false);
    }

    function test_check_basic_auth()
    {
        $this->assertSame(__check_basic_auth('https://httpbin.org/basic-auth/foo/bar', 'foo', 'bar'), true);
        $this->assertSame(__check_basic_auth('https://httpbin.org/basic-auth/foo/bar', 'foo', 'baz'), false);
        $this->assertSame(__check_basic_auth('https://vielhuber.de', 'foo', 'baz'), true);
        $this->assertSame(__check_basic_auth('https://vielhuber.de', 'foo', ''), true);
        $this->assertSame(__check_basic_auth(null), true);
        $this->assertSame(__check_basic_auth(false), true);
        $this->assertSame(__check_basic_auth(true), true);
        $this->assertSame(__check_basic_auth('foo'), true);
        $this->assertSame(__check_basic_auth(''), true);
    }

    function test_array_map_keys()
    {
        $this->assertSame(__array_map_keys(null, null), null);
        $this->assertSame(__array_map_keys(function () {}, false), false);
        $this->assertSame(
            __array_map_keys(function ($k) {
                return $k . '!';
            }, true),
            true
        );
        $this->assertSame(
            __array_map_keys(function ($k) {
                return $k . '!';
            }, 1337),
            1337
        );
        $this->assertSame(
            __array_map_keys(function ($k) {
                return $k . '!';
            }, 'foo'),
            'foo'
        );
        $this->assertSame(
            __array_map_keys(function ($k) {
                return $k . '!';
            }, []),
            []
        );
        $this->assertSame(
            __array_map_keys(
                function ($k) {
                    return $k;
                },
                ['foo' => 'bar']
            ),
            ['foo' => 'bar']
        );
        $this->assertSame(
            __array_map_keys(
                function () {
                    return '';
                },
                ['foo' => 'bar', 'foo' => 'bar', 'bar' => 'baz']
            ),
            ['' => 'baz']
        );
        $this->assertSame(
            __array_map_keys(
                function ($k) {
                    return $k . '!';
                },
                ['foo' => 'bar', 'bar' => 'baz']
            ),
            ['foo!' => 'bar', 'bar!' => 'baz']
        );
    }

    function test_progress()
    {
        foreach (
            [
                [
                    [0, 100, 'Loading...', 75, '#'],
                    'Loading... [>                                                                           ]   0%'
                ],
                [
                    [50, 100, 'Loading...', 75, '#'],
                    'Loading... [######################################>                                     ]  50%'
                ],
                [
                    [100, 100, 'Loading...', 75, '#'],
                    'Loading... [############################################################################] 100%'
                ]
            ]
            as $tests
        ) {
            ob_start();
            __progress($tests[0][0], $tests[0][1], $tests[0][2], $tests[0][3], $tests[0][4]);
            echo PHP_EOL;
            $output = trim(ob_get_contents());
            ob_end_clean();
            $this->assertSame($tests[1], $output);
        }
    }

    function test_mb_sprintf()
    {
        $this->assertSame(sprintf('%7.7s', 'foo'), '    foo');
        $this->assertSame(__mb_sprintf('%7.7s', 'foo'), '    foo');
        $this->assertSame(sprintf('%7.7s', 'mäh'), '   mäh');
        $this->assertSame(__mb_sprintf('%7.7s', 'mäh'), '    mäh');
    }

    function test_encode_decode_string()
    {
        $data = ['foo' => 'bar', 'bar' => 'baz'];
        $this->assertEquals(__encode_data($data), 'YToyOntzOjM6ImZvbyI7czozOiJiYXIiO3M6MzoiYmFyIjtzOjM6ImJheiI7fQ==');
        $this->assertEquals(__decode_data(__encode_data($data)), $data);
        $this->assertEquals(__decode_data(__encode_data([])), []);
        $this->assertEquals(__decode_data(__encode_data((object) $data)), (object) $data);
        $this->assertEquals(__decode_data(__encode_data(null)), null);
        $this->assertEquals(__decode_data(__encode_data(true)), true);
        $this->assertEquals(__decode_data(__encode_data(false)), false);
        $this->assertEquals(__decode_data(__encode_data(0)), 0);
        $this->assertEquals(__decode_data(__encode_data('foo')), 'foo');
        $this->assertEquals(__decode_data(__encode_data(['foo'])), ['foo']);
        $this->assertEquals(__decode_data(__encode_data(['foo', 'bar'])), ['foo', 'bar']);
        $this->assertEquals(__decode_data(__encode_data('foo', 'bar')), ['foo', 'bar']);
        $this->assertEquals(__decode_data(__encode_data([[[[['❤️´´èd""\'\'']]]]])), [[[[['❤️´´èd""\'\'']]]]]);
    }

    function test__extract_urls_from_sitemap()
    {
        $urls = __extract_urls_from_sitemap('https://vielhuber.de/sitemap_index.xml');
        $this->assertSame(count($urls) > 100, true);
        $this->assertSame(in_array('https://vielhuber.de/impressum/', $urls), true);
        $this->assertSame(in_array('https://vielhuber.de/blog/google-translation-api-hacking/', $urls), true);
        $this->assertSame(in_array('https://vielhuber.de/blog/', $urls), true);

        $this->assertSame(__extract_urls_from_sitemap('https://vielhuber.de/map.xml'), []);
        $this->assertSame(__extract_urls_from_sitemap('https://vielhuber.de/'), []);
        $this->assertSame(__extract_urls_from_sitemap(null), []);
        $this->assertSame(__extract_urls_from_sitemap(true), []);
        $this->assertSame(__extract_urls_from_sitemap(false), []);
        $this->assertSame(__extract_urls_from_sitemap('foo'), []);

        $urls = __extract_urls_from_sitemap('https://vielhuber.de/sitemap_index.xml', null, true);
        $this->assertSame(count($urls) > 100, true);
        $this->assertSame(count($urls[0]) === 2, true);
        $this->assertSame(strpos($urls[0]['url'], 'https://') !== false, true);
        $this->assertSame(__validate_date($urls[0]['lastmod']), true);
    }

    function test__remove_accents()
    {
        $this->assertSame(__remove_accents('Çººĺ'), 'Cool');
        $this->assertSame(__remove_accents('Äťśçĥ'), 'Atsch');
        $this->assertSame(__remove_accents('Äťśçĥ', true), 'Aetsch');
        $this->assertSame(
            __remove_emoji(
                'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus.',
                true
            ),
            'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus.'
        );
        $this->assertSame(__remove_accents(''), '');
        $this->assertSame(__remove_accents(null), null);
        $this->assertSame(__remove_accents(true), true);
        $this->assertSame(__remove_accents(false), false);
        $this->assertSame(__remove_accents(42), 42);
    }

    function test__remove_non_printable_chars()
    {
        $this->assertSame(__remove_non_printable_chars('foobar'), 'foobar');
        $this->assertSame(__remove_non_printable_chars(''), '');
        $this->assertSame(__remove_non_printable_chars([]), []);
        $this->assertSame(__remove_non_printable_chars(null), null);
        $this->assertSame(__remove_non_printable_chars(true), true);
        $this->assertSame(__remove_non_printable_chars(false), false);
        $this->assertSame(__remove_non_printable_chars(42), 42);
    }

    function test__trim_whitespace()
    {
        $this->assertSame(
            __trim_whitespace('      string including nasty whitespace chars  '),
            'string including nasty whitespace chars'
        );
        $this->assertSame(
            __trim_whitespace(
                '

   

   string including nasty whitespace chars  

    ' .
                    html_entity_decode('&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;') .
                    '

'
            ),
            'string including nasty whitespace chars'
        );
        $this->assertSame(__trim_whitespace('𩸽 '), '𩸽');
        $this->assertSame(__trim_whitespace('Català'), 'Català');
        $this->assertSame(
            __trim_whitespace('multi
line
string



'),
            'multi
line
string'
        );
    }

    function test__extract_title_from_url()
    {
        $this->assertSame(
            __extract_title_from_url('https://vielhuber.de'),
            'David Vielhuber > Full-Stack Developer aus München'
        );
        $this->assertSame(
            __extract_title_from_url('https://vielhuber.de/'),
            'David Vielhuber > Full-Stack Developer aus München'
        );
        $this->assertSame(__extract_title_from_url(null), '');
        $this->assertSame(__extract_title_from_url(true), '');
        $this->assertSame(__extract_title_from_url(false), '');
        $this->assertSame(__extract_title_from_url('foo'), '');
    }

    function test__extract_meta_desc_from_url()
    {
        $this->assertSame(
            __extract_meta_desc_from_url('https://vielhuber.de'),
            '🌀 Vielhuber David ist ein Web-Geek mit einem Faible für schönes Design, einer Prise Perfektionismus und Augen für klare Konturen. 🌀'
        );
        $this->assertSame(
            __extract_meta_desc_from_url('https://vielhuber.de/'),
            '🌀 Vielhuber David ist ein Web-Geek mit einem Faible für schönes Design, einer Prise Perfektionismus und Augen für klare Konturen. 🌀'
        );
        $this->assertSame(__extract_meta_desc_from_url(null), '');
        $this->assertSame(__extract_meta_desc_from_url(true), '');
        $this->assertSame(__extract_meta_desc_from_url(false), '');
        $this->assertSame(__extract_meta_desc_from_url('foo'), '');
    }

    function test__array_map_deep()
    {
        $this->assertSame(
            __array_map_deep([__e(), 'bar' => [__e(), __e()]], function ($a) {
                return __v($a);
            }),
            [null, 'bar' => [null, null]]
        );
        $this->assertSame(
            __array_map_deep(['foo', 'bar' => ['baz', 'gnarr']], function ($a) {
                return $a . '!';
            }),
            ['foo!', 'bar' => ['baz!', 'gnarr!']]
        );
        $this->assertSame(
            __array_map_deep(['foo', 'bar' => ['baz', ['1', '2']]], function ($a) {
                return $a . '!';
            }),
            ['foo!', 'bar' => ['baz!', ['1!', '2!']]]
        );
        $this->assertSame(
            __array_map_deep(null, function ($a) {
                return $a . '!';
            }),
            '!'
        );
        $this->assertSame(
            __array_map_deep(true, function ($a) {
                return !$a;
            }),
            false
        );
        $this->assertSame(
            __array_map_deep([[[[[[[[[[[[[[[[[[[[true]]]]]]]]]]]]]]]]]]]], function ($a) {
                return !$a;
            }),
            [[[[[[[[[[[[[[[[[[[[false]]]]]]]]]]]]]]]]]]]]
        );
        $this->assertSame(
            __array_map_deep([[[[[[[[[[[[[[[json_encode([[[[[true]]]]])]]]]]]]]]]]]]]], function ($a) {
                return !$a;
            }),
            [[[[[[[[[[[[[[[json_encode([[[[[false]]]]])]]]]]]]]]]]]]]]
        );
        $this->assertSame(
            __array_map_deep([[[[[[[[[[[[[[[[[[[[42 => 'no', 7 => 'ok']]]]]]]]]]]]]]]]]]]], function ($value, $key) {
                return $key === 42 ? $value : $value . '!';
            }),
            [[[[[[[[[[[[[[[[[[[[42 => 'no', 7 => 'ok!']]]]]]]]]]]]]]]]]]]]
        );
        $this->assertSame(
            __array_map_deep(
                ['foo' => ['bar' => 'baz'], 'bar' => ['baz' => 'gnarr'], 'gnarr' => ['foo' => 'gnaz']],
                function ($value, $key, $key_chain) {
                    return in_array('bar', $key_chain) ? $value . '!' : $value;
                }
            ),
            ['foo' => ['bar' => 'baz!'], 'bar' => ['baz' => 'gnarr!'], 'gnarr' => ['foo' => 'gnaz']]
        );
        $output = [];
        __array_map_deep([1 => [2 => [3 => [4 => [5 => 'ok1'], 6 => [7 => 'ok2']]]], 8 => 'ok3'], function (
            $value,
            $key,
            $key_chain
        ) use (&$output) {
            $output[] = $value . ': ' . implode('.', $key_chain);
        });
        $this->assertSame(implode(' - ', $output), 'ok1: 1.2.3.4.5 - ok2: 1.2.3.6.7 - ok3: 8');

        $this->assertEquals(
            __array_map_deep(['foo', 'bar' => (object) ['baz', 'gnarr']], function ($a) {
                return $a . '!';
            }),
            ['foo!', 'bar' => (object) ['baz!', 'gnarr!']]
        );
        $output = [];
        __array_map_deep(
            (object) [
                1 => (object) [2 => (object) [3 => (object) [4 => (object) [5 => 'ok1'], 6 => (object) [7 => 'ok2']]]],
                8 => 'ok3'
            ],
            function ($value, $key, $key_chain) use (&$output) {
                $output[] = $value . ': ' . implode('.', $key_chain);
            }
        );
        $this->assertSame(implode(' - ', $output), 'ok1: 1.2.3.4.5 - ok2: 1.2.3.6.7 - ok3: 8');

        $this->assertEquals(
            __array_map_deep(['foo', 'bar' => json_encode(['baz', 'gnarr'])], function ($a) {
                return $a . '!';
            }),
            ['foo!', 'bar' => json_encode(['baz!', 'gnarr!'])]
        );
    }

    function test__anonymize_ip()
    {
        $this->assertSame(__anonymize_ip('207.142.131.005'), '207.142.131.XXX');
        $this->assertSame(
            __anonymize_ip('2001:0db8:0000:08d3:0000:8a2e:0070:7344'),
            '2001:0db8:0000:08d3:0000:8a2e:XXXX:XXXX'
        );
        $this->assertSame(
            __anonymize_ip('2001:0db8:0000:08d3:0000:8a2e:0070:734a'),
            '2001:0db8:0000:08d3:0000:8a2e:XXXX:XXXX'
        );
        $this->assertSame(__anonymize_ip('207.142.131.5'), '207.142.131.XXX');
        $this->assertSame(__anonymize_ip('2001:0db8::8d3::8a2e:7:7344'), '2001:0db8::8d3::8a2e:XXXX:XXXX');
        $this->assertSame(__anonymize_ip('::1'), ':XXXX:XXXX');
        $this->assertSame(__anonymize_ip('127.0.0.1'), '127.0.0.XXX');
        $this->assertSame(__anonymize_ip(''), '');
        $this->assertSame(__anonymize_ip(true), true);
        $this->assertSame(__anonymize_ip(false), false);
        $this->assertSame(__anonymize_ip(), '192.168.178.XXX');
        $this->assertSame(__anonymize_ip(null), '192.168.178.XXX');
    }

    function test__password_strength()
    {
        $this->assertSame(__password_strength('3iu'), 1);
        $this->assertSame(__password_strength('3iurehkHEDJ'), 2);
        $this->assertSame(__password_strength('3iurehkHEDJK§$R$A'), 3);
        $this->assertSame(__password_strength(null), 1);
        $this->assertSame(__password_strength(''), 1);
        $this->assertSame(__password_strength([]), 1);
    }

    function test__foreach_nested()
    {
        $a = [1, 2];
        $b = [3, 4];
        $c = [5, 6];
        $output = [];
        $fn = function ($x, $y, $z) use (&$output) {
            $output[] = $x . '' . $y . '' . $z;
        };
        __foreach_nested($a, $b, $c, $fn);
        $this->assertSame($output, ['135', '136', '145', '146', '235', '236', '245', '246']);
    }

    function test__distance_haversine()
    {
        $this->assertSame(__distance_haversine([48.576809, 13.403207], [48.127686, 11.575371]), 143999);
        $this->assertSame(__distance_haversine(['48.576809', '13.403207'], ['48.127686', '11.575371']), 143999);
        $this->assertSame(__distance_haversine(null, [null, 0]), null);
    }

    function test__referer()
    {
        $this->assertSame(__referer(), 'https://google.de/');
    }

    function test__hook()
    {
        $GLOBALS['hook_test'] = 0;
        __hook_fire('hook_name');
        $this->assertSame($GLOBALS['hook_test'], 0);
        __hook_add('hook_name', function () {
            $GLOBALS['hook_test']++;
        });
        $this->assertSame($GLOBALS['hook_test'], 0);
        __hook_fire('hook_name');
        $this->assertSame($GLOBALS['hook_test'], 1);
        __hook_fire('hook_name');
        $this->assertSame($GLOBALS['hook_test'], 2);
        __hook_add('hook_name', function () {
            $GLOBALS['hook_test'] *= 2;
        });
        __hook_fire('hook_name');
        $this->assertSame($GLOBALS['hook_test'], 6);
        __hook_fire('hook_name');
        $this->assertSame($GLOBALS['hook_test'], 14);

        $foo = 1;
        __hook_add(
            'filter_name',
            function ($a) {
                return $a + 1;
            },
            20
        );
        __hook_add(
            'filter_name',
            function ($a) {
                return $a * 2;
            },
            10
        );
        __hook_add(
            'filter_name',
            function ($a) {
                return $a - 3;
            },
            PHP_INT_MAX
        );
        $foo = __hook_fire('filter_name', $foo);
        $this->assertSame($foo, 0);
        $foo = __hook_fire('filter_name', $foo);
        $this->assertSame($foo, -2);

        $foo = true;
        $foo = __hook_fire('unknown_hook', $foo);
        $this->assertSame($foo, true);
    }

    function test__arr_depth()
    {
        $this->assertSame(__arr_depth(['foo' => 'bar', 'bar' => ['baz' => ['gnarr' => 'gnaz']]]), 3);
        $this->assertSame(__arr_depth(['foo' => 'bar']), 1);
        $this->assertSame(__arr_depth([]), 1);
        $this->assertSame(__arr_depth('foo'), 0);
    }

    function test__strip_tags()
    {
        foreach (
            [
                [
                    '<p>foo</p><iframe src="#"></iframe><script>alert();</script><p>bar</p>',
                    '<p>foo</p><p>bar</p>',
                    ['iframe', 'script'],
                    true
                ],
                [
                    '<p>foo</p><iframe src="#"></iframe><script>alert();</script><p>bar</p>',
                    '<p>foo</p>alert();<p>bar</p>',
                    ['iframe', 'script'],
                    false
                ],
                [
                    '<p>foo</p><iframe src="#"></iframe><script>alert();</script><p>bar</p>',
                    '<p>foo</p><script>alert();</script><p>bar</p>',
                    'iframe',
                    true
                ],
                [
                    '<iframe id="google_ads_iframe.html" 
data-attr="foo">
</iframe>',
                    '',
                    'iframe',
                    true
                ],
                [
                    '<p>foo</p><iframe src="#">
</iframe><script>alert();</script><p>bar</p>',
                    '<p>foo</p><script>alert();</script><p>bar</p>',
                    'iframe',
                    true
                ],

                [
                    '<p>foo</p><iframe src="#"></iframe><script>alert();</script><p>bar</p>',
                    '<p>foo</p><iframe src="#"></iframe>alert();<p>bar</p>',
                    'script',
                    false
                ]
            ]
            as $tests__value
        ) {
            $this->assertSame(__strip_tags($tests__value[0], $tests__value[2], $tests__value[3]), $tests__value[1]);
        }
    }

    function test__array_filter_recursive_all()
    {
        $this->assertSame(
            __array_filter_recursive_all(['foo' => ['foo' => ['foo' => ['foo' => ['foo' => []]]]]], function (
                $value,
                $key,
                $key_chain
            ) {
                return $key == 'foo' && empty($value);
            }),
            []
        );

        $this->assertSame(
            __array_filter_recursive_all(
                ['bar' => ['foo' => ['foo' => ['foo' => ['foo' => ['foo' => []]]]]]],
                function ($value, $key, $key_chain) {
                    return $key == 'foo' && empty($value);
                }
            ),
            ['bar' => []]
        );

        $arr = [
            [
                'children' => [
                    [
                        'children' => [
                            [
                                'permission' => true
                            ],
                            [
                                'permission' => false
                            ]
                        ]
                    ]
                ]
            ],
            ['permission' => true],
            [
                'children' => [
                    [
                        'children' => [
                            [
                                'permission' => false
                            ],
                            [
                                'permission' => false
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $arr = __array_filter_recursive_all($arr, function ($value, $key, $key_chain) {
            return is_array($value) && array_key_exists('permission', $value) && $value['permission'] === false;
        });
        $arr = __array_filter_recursive_all($arr, function ($value, $key, $key_chain) {
            return is_array($value) && array_key_exists('children', $value) && empty($value['children']);
        });
        $this->assertSame($arr, [
            [
                'children' => [
                    [
                        'children' => [
                            [
                                'permission' => true
                            ]
                        ]
                    ]
                ]
            ],
            ['permission' => true]
        ]);
    }

    function test__array_walk_recursive_all()
    {
        $arr = ['foo' => 'bar', 'bar' => ['baz' => 'gnarr', 'gnarr' => 'baz']];
        __array_walk_recursive_all($arr, function (&$value, $key, $key_chain) {
            if (is_array($value) && array_key_exists('baz', $value) && $value['baz'] === 'gnarr') {
                $value['gnarr'] = 'baz2';
            }
        });
        $this->assertSame($arr, ['foo' => 'bar', 'bar' => ['baz' => 'gnarr', 'gnarr' => 'baz2']]);
        $counter = 0;
        $arr = ['foo' => 'bar', 'bar' => ['baz' => 'gnarr', 'gnarr' => 'baz']];
        __array_walk_recursive_all($arr, function (&$value, $key, $key_chain) use (&$counter) {
            $counter++;
        });
        $this->assertSame($counter, 5);
    }

    function test__array_map_deep_all()
    {
        $this->assertSame(
            __array_map_deep_all(['foo' => 'bar', 'bar' => ['baz' => 'gnarr', 'gnarr' => 'baz']], function (
                $value,
                $key,
                $key_chain
            ) {
                if (is_array($value) && array_key_exists('baz', $value) && $value['baz'] === 'gnarr') {
                    $value['gnarr'] = 'baz2';
                }
                return $value;
            }),
            ['foo' => 'bar', 'bar' => ['baz' => 'gnarr', 'gnarr' => 'baz2']]
        );
    }

    function test__video_info()
    {
        $base64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents('tests/assets/thumbnail_youtube.jpg'));
        $this->assertSame(__video_info('https://www.youtube.com/watch?v=WAZlcK7FUic'), [
            'id' => 'WAZlcK7FUic',
            'provider' => 'youtube',
            'thumbnail' => $base64
        ]);
        $this->assertSame(__video_info('https://www.youtube.com/embed/WAZlcK7FUic?feature=oembed'), [
            'id' => 'WAZlcK7FUic',
            'provider' => 'youtube',
            'thumbnail' => $base64
        ]);
        $this->assertSame(__video_info('WAZlcK7FUic'), [
            'id' => 'WAZlcK7FUic',
            'provider' => 'youtube',
            'thumbnail' => $base64
        ]);

        $base64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents('tests/assets/thumbnail_vimeo.jpg'));
        $this->assertSame(__video_info('https://vimeo.com/527316428'), [
            'id' => '527316428',
            'provider' => 'vimeo',
            'thumbnail' => $base64
        ]);
        $this->assertSame(__video_info('https://player.vimeo.com/video/527316428?dnt=1&app_id=122963'), [
            'id' => '527316428',
            'provider' => 'vimeo',
            'thumbnail' => $base64
        ]);
        $this->assertSame(__video_info('https://vimeo.com/channels/foo/527316428'), [
            'id' => '527316428',
            'provider' => 'vimeo',
            'thumbnail' => $base64
        ]);
        $this->assertSame(__video_info('https://vimeo.com/groups/foo/videos/527316428'), [
            'id' => '527316428',
            'provider' => 'vimeo',
            'thumbnail' => $base64
        ]);
        $this->assertSame(__video_info('527316428'), [
            'id' => '527316428',
            'provider' => 'vimeo',
            'thumbnail' => $base64
        ]);

        $this->assertSame(__video_info('https://www.youtube.com/watch?v=WAZlcK7FUiZ'), null);
        $this->assertSame(__video_info('https://www.youtube.com/watch?v=abc'), null);
        $this->assertSame(__video_info('https://vimeo.com/abc'), null);
        $this->assertSame(__video_info('https://foo.com/27366'), null);
        $this->assertSame(__video_info(''), null);
        $this->assertSame(__video_info([]), null);
        $this->assertSame(__video_info(null), null);
        $this->assertSame(__video_info(false), null);
        $this->assertSame(__video_info(true), null);
    }

    function test__exception()
    {
        try {
            __exception('foo');
        } catch (\Throwable $t) {
            $this->assertSame(__exception_message($t), 'foo');
        }
        try {
            __exception(['foo' => 'bar']);
        } catch (\Throwable $t) {
            $this->assertSame(__exception_message($t), ['foo' => 'bar']);
        }
        try {
            throw new \Exception('bar');
        } catch (\Throwable $t) {
            $this->assertSame(__exception_message($t), 'bar');
        }
        try {
            __exception('foo');
        } catch (\ExtendedException $t) {
            $this->assertSame(__exception_message($t), 'foo');
        }
        try {
            throw new \Exception('bar');
        } catch (\ExceptionExtended $t) {
            $this->assertSame(true, false);
        } catch (\Throwable $t) {
            $this->assertSame(true, true);
        }
    }

    function test__minify_html()
    {
        $this->assertSame(__minify_html('VanillaJS is <strong>cool</strong>'), 'VanillaJS is <strong>cool</strong>');
        $this->assertSame(__minify_html('<p>foo</p>'), '<p>foo</p>');
        $this->assertSame(__minify_html(null), null);
        $this->assertSame(__minify_html(true), true);
        $this->assertSame(__minify_html(false), false);
        $this->assertSame(__minify_html('Dies ist ein Test'), 'Dies ist ein Test');
        $this->assertSame(__minify_html('  Dies ist ein Test '), 'Dies ist ein Test');
        $this->assertSame(__minify_html(''), '');
        $this->assertSame(__minify_html('<br/>'), '<br>');
        $this->assertSame(__minify_html('<br />'), '<br>');
        $this->assertSame(
            __minify_html('<!DOCTYPE html>
<title>shortest valid html5 document</title>
<p>cool stuff</p>'),
            '<!DOCTYPE html><title>shortest valid html5 document</title><p>cool stuff</p>'
        );
        $this->assertSame(
            __minify_html('<!doctype html>
<html lang="en">
<head>

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1.0" />

<link href="http://example.com/style.css" rel="stylesheet" />
<link rel="icon" href="http://example.com/favicon.png" />

<title>Tiny Html Minifier</title>

</head>
<body class="body">

<div class="main-wrap">
    <main>
        <textarea>
            Some text
            with newlines
            and some spaces
        </textarea>

        <div class="test">
            <p>This text</p>
            <p>should not</p>
            <p>wrap on multiple lines</p>
        </div>
    </main>
</div>
<script>
    console.log("Script tags are not minified");
    console.log("This is inside a script tag");
</script></body>
</html>'),
            '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><link href="http://example.com/style.css" rel="stylesheet"><link rel="icon" href="http://example.com/favicon.png"><title>Tiny Html Minifier</title></head><body class="body"><div class="main-wrap"><main><textarea>
            Some text
            with newlines
            and some spaces
        </textarea><div class="test"><p>This text</p><p>should not</p><p>wrap on multiple lines</p></div></main></div><script>
    console.log("Script tags are not minified");
    console.log("This is inside a script tag");
</script></body></html>'
        );
    }

    function test__is_serialized()
    {
        $this->assertSame(__is_serialized('a:1:{s:3:"foo";s:3:"bar";}'), true);
        $this->assertSame(__is_serialized('a:1:{s:3:\"foo\";s:3:\"bar\";}'), false);
        $this->assertSame(__is_serialized('i:1;'), true);
        $this->assertSame(__is_serialized('a:1:{42}'), false);
        $this->assertSame(__is_serialized('s:9:"foo " bar";'), true);
        $this->assertSame(__is_serialized('s:9:\"foo \" bar\";'), false);
        $this->assertSame(__is_serialized('s:10:"foo \" bar";'), true);
        $this->assertSame(__is_serialized('s:10:\"foo \\\" bar\";'), false);
        $this->assertSame(__is_serialized('s:12:"foo \\\" bar";'), false);
        $this->assertSame(__is_serialized(''), false);
        $this->assertSame(__is_serialized(null), false);
        $this->assertSame(__is_serialized(false), false);
        $this->assertSame(__is_serialized(true), false);
        $this->assertSame(__is_serialized([]), false);
        $this->assertSame(__is_serialized((object) []), false);
        $this->assertSame(__is_serialized('idkfa'), false);
        $this->assertSame(__is_serialized('b:0;'), true);
    }

    function test__strcmp()
    {
        $arr = ['äther', 'Äther2', 'Ü12.pdf', 'Ü2.pdf'];
        usort($arr, function ($a, $b) {
            return __mb_strcmp($a, $b);
        });
        $this->assertSame($arr, ['Äther2', 'Ü12.pdf', 'Ü2.pdf', 'äther']);
        usort($arr, function ($a, $b) {
            return __mb_strcasecmp($a, $b);
        });
        $this->assertSame($arr, ['äther', 'Äther2', 'Ü12.pdf', 'Ü2.pdf']);
        usort($arr, function ($a, $b) {
            return __mb_strnatcmp($a, $b);
        });
        $this->assertSame($arr, ['Äther2', 'Ü2.pdf', 'Ü12.pdf', 'äther']);
        usort($arr, function ($a, $b) {
            return __mb_strnatcasecmp($a, $b);
        });
        $this->assertSame($arr, ['äther', 'Äther2', 'Ü2.pdf', 'Ü12.pdf']);
    }

    function test__array_multisort()
    {
        $arr = [['a' => 17, 'b' => 42], ['a' => 13, 'b' => 19]];
        usort($arr, __array_multisort([['a', 'asc'], ['b', 'asc']]));
        $this->assertSame($arr, [['a' => 13, 'b' => 19], ['a' => 17, 'b' => 42]]);
        usort(
            $arr,
            __array_multisort(function ($v) {
                return [[$v['a'], 'asc'], [$v['b'], 'asc']];
            })
        );
        $this->assertSame($arr, [['a' => 13, 'b' => 19], ['a' => 17, 'b' => 42]]);
        $arr = [['a' => true, 'b' => true, 'c' => 'Test!'], ['a' => true, 'b' => false, 'c' => 'yo']];
        usort($arr, __array_multisort([['a', 'asc'], ['b', 'asc'], ['c', 'asc']]));
        $this->assertSame($arr, [['a' => true, 'b' => false, 'c' => 'yo'], ['a' => true, 'b' => true, 'c' => 'Test!']]);
        usort($arr, __array_multisort([['a', 'desc'], ['b', 'desc'], ['c', 'asc']]));
        $this->assertSame($arr, [['a' => true, 'b' => true, 'c' => 'Test!'], ['a' => true, 'b' => false, 'c' => 'yo']]);
        usort($arr, __array_multisort([['a', 'desc'], ['b', 'asc'], ['c', 'desc']]));
        $this->assertSame($arr, [['a' => true, 'b' => false, 'c' => 'yo'], ['a' => true, 'b' => true, 'c' => 'Test!']]);

        $arr = [
            ['id' => 1, 'pos' => null, 'date' => new dateTime('2020-01-01'), 'created_at' => '2020-01-16 13:03:32'],
            ['id' => 2, 'pos' => null, 'date' => new dateTime('2020-01-01'), 'created_at' => '2020-01-16 13:03:32'],
            ['id' => 3, 'pos' => null, 'date' => new dateTime('2020-01-01'), 'created_at' => '2020-01-16 13:03:32'],
            ['id' => 4, 'pos' => null, 'date' => new dateTime('2020-01-01'), 'created_at' => '2020-01-16 13:03:32'],
            ['id' => 5, 'pos' => null, 'date' => new dateTime('2020-01-01'), 'created_at' => '2020-01-16 13:03:32'],
            ['id' => 6, 'pos' => null, 'date' => new dateTime('2020-01-01'), 'created_at' => '2020-01-16 13:03:32']
        ];
        usort($arr, __array_multisort([['pos', 'asc'], ['date', 'asc'], ['created_at', 'asc'], ['id', 'asc']]));
        $this->assertEquals($arr, [
            ['id' => 1, 'pos' => null, 'date' => new dateTime('2020-01-01'), 'created_at' => '2020-01-16 13:03:32'],
            ['id' => 2, 'pos' => null, 'date' => new dateTime('2020-01-01'), 'created_at' => '2020-01-16 13:03:32'],
            ['id' => 3, 'pos' => null, 'date' => new dateTime('2020-01-01'), 'created_at' => '2020-01-16 13:03:32'],
            ['id' => 4, 'pos' => null, 'date' => new dateTime('2020-01-01'), 'created_at' => '2020-01-16 13:03:32'],
            ['id' => 5, 'pos' => null, 'date' => new dateTime('2020-01-01'), 'created_at' => '2020-01-16 13:03:32'],
            ['id' => 6, 'pos' => null, 'date' => new dateTime('2020-01-01'), 'created_at' => '2020-01-16 13:03:32']
        ]);

        $arr = [
            ['id' => 1, 'date1' => '2000-01-01', 'date2' => '2000-01-01'],
            ['id' => 2, 'date1' => '2002-01-01', 'date2' => '2009-01-01'],
            ['id' => 3, 'date1' => '2002-01-01', 'date2' => '2008-01-01'],
            ['id' => 4, 'date1' => '2001-01-01', 'date2' => '2000-01-01']
        ];
        usort($arr, __array_multisort([['date1', 'asc'], ['date2', 'asc']]));
        $this->assertEquals($arr, [
            ['id' => 1, 'date1' => '2000-01-01', 'date2' => '2000-01-01'],
            ['id' => 4, 'date1' => '2001-01-01', 'date2' => '2000-01-01'],
            ['id' => 3, 'date1' => '2002-01-01', 'date2' => '2008-01-01'],
            ['id' => 2, 'date1' => '2002-01-01', 'date2' => '2009-01-01']
        ]);

        $arr = [['foo' => 'zoo'], ['foo' => 'Äther']];
        usort($arr, __array_multisort([['foo', 'asc']]));
        $this->assertEquals($arr, [['foo' => 'Äther'], ['foo' => 'zoo']]);
        $arr = [['foo' => 'Äther'], ['foo' => 'zoo'], ['foo' => 'Arbeit'], ['foo' => 'Übung']];
        usort($arr, __array_multisort([['foo', 'asc']]));
        $this->assertEquals($arr, [['foo' => 'Äther'], ['foo' => 'Arbeit'], ['foo' => 'Übung'], ['foo' => 'zoo']]);

        $arr = [['foo' => 'baz', 'bar' => 'baz'], ['foo' => 'baz', 'bar' => 'gnarr']];
        usort($arr, __array_multisort([['foo', 'desc'], ['bar', 'desc']]));
        $this->assertEquals($arr, [['foo' => 'baz', 'bar' => 'gnarr'], ['foo' => 'baz', 'bar' => 'baz']]);
        $arr = [['a' => __empty(), 'b' => 42], ['a' => 13, 'b' => 19]];
        usort($arr, __array_multisort([['a', 'asc'], ['b', 'asc']]));
        $this->assertEquals($arr, [['a' => __empty(), 'b' => 42], ['a' => 13, 'b' => 19]]);
        $arr = [['a' => null, 'b' => 42], ['a' => 13, 'b' => 19]];
        usort($arr, __array_multisort([['a', 'asc'], ['b', 'asc']]));
        $this->assertEquals($arr, [['a' => null, 'b' => 42], ['a' => 13, 'b' => 19]]);
        $arr = [['a' => 13, 'b' => 19], ['a' => null, 'b' => 42]];
        usort($arr, __array_multisort([['a', 'asc'], ['b', 'asc']]));
        $this->assertEquals($arr, [['a' => null, 'b' => 42], ['a' => 13, 'b' => 19]]);
        $arr = [['a' => 13, 'b' => 19], ['a' => null, 'b' => 42]];
        usort($arr, __array_multisort([['a', 'asc'], ['b', 'asc']]));
        $this->assertEquals($arr, [['a' => null, 'b' => 42], ['a' => 13, 'b' => 19]]);
        $arr = [['a' => 17, 'b' => 42], ['a' => 13, 'b' => 19]];
        usort(
            $arr,
            __array_multisort(function ($v) {
                return [[$v['a'], 'asc'], [$v['b'], 'asc']];
            })
        );
        $this->assertEquals($arr, [['a' => 13, 'b' => 19], ['a' => 17, 'b' => 42]]);
        $arr = [['a' => __empty(), 'b' => 42], ['a' => 13, 'b' => 19]];
        usort(
            $arr,
            __array_multisort(function ($v) {
                return [[$v['a'], 'asc'], [$v['b'], 'asc']];
            })
        );
        $this->assertEquals($arr, [['a' => __empty(), 'b' => 42], ['a' => 13, 'b' => 19]]);
    }

    function test__remove_leading_zeros()
    {
        $this->assertSame(__remove_leading_zeros('00001337'), '1337');
        $this->assertSame(__remove_leading_zeros('1337'), '1337');
        $this->assertSame(__remove_leading_zeros(1337), 1337);
        $this->assertSame(__remove_leading_zeros('0'), '0');
        $this->assertSame(__remove_leading_zeros(0), 0);
        $this->assertSame(__remove_leading_zeros(null), null);
        $this->assertSame(__remove_leading_zeros(true), true);
        $this->assertSame(__remove_leading_zeros(false), false);
        $this->assertSame(__remove_leading_zeros(''), '');
        $this->assertSame(__remove_leading_zeros([]), []);
    }

    function test__remove_zero_decimals()
    {
        $this->assertSame(__remove_zero_decimals(1337), 1337);
        $this->assertSame(__remove_zero_decimals('1337'), 1337);
        $this->assertSame(__remove_zero_decimals('1337.40'), 1337.4);
        $this->assertSame(__remove_zero_decimals('1337,40'), 1337.4);
        $this->assertSame(__remove_zero_decimals(1337.0), 1337);
        $this->assertSame(__remove_zero_decimals(1337.4), 1337.4);
        $this->assertSame(__remove_zero_decimals(1337.42), 1337.42);
        $this->assertSame(__remove_zero_decimals(1337.424), 1337.424);
        $this->assertSame(__remove_zero_decimals(null), null);
        $this->assertSame(__remove_zero_decimals(false), null);
        $this->assertSame(__remove_zero_decimals(''), null);
        $this->assertSame(__remove_zero_decimals('foo'), null);
    }

    function test__strrev()
    {
        $this->assertSame(__strrev('hello❤️world'), 'dlrow❤️olleh');
        $this->assertSame(__strrev('Hello world!'), '!dlrow olleH');
        $this->assertSame(__strrev('o'), 'o');
        $this->assertSame(__strrev(''), '');
        $this->assertSame(__strrev(null), null);
        $this->assertSame(__strrev(false), false);
        $this->assertSame(__strrev(true), true);
        $this->assertSame(__strrev([]), []);
    }

    function test__array_group_by()
    {
        $a = ['a' => 17, 'b' => 42, 'c' => 'foo'];
        $b = ['a' => 19, 'b' => 20, 'c' => 'bar'];
        $c = ['a' => 17, 'b' => 42, 'c' => 'baz'];
        $arr = [$a, $b, $c];
        $this->assertSame(__array_group_by($arr, 'a'), [17 => [$a, $c], 19 => [$b]]);
        $this->assertSame(__array_group_by($arr, 'a', 'b'), [17 => [42 => [$a, $c]], 19 => [20 => [$b]]]);
        $this->assertSame(
            __array_group_by($arr, function ($v) {
                return $v['a'];
            }),
            [17 => [$a, $c], 19 => [$b]]
        );
        $this->assertSame(
            __array_group_by(
                $arr,
                function ($v) {
                    return $v['a'];
                },
                function ($v) {
                    return $v['b'];
                }
            ),
            [17 => [42 => [$a, $c]], 19 => [20 => [$b]]]
        );
    }

    function test__array_group_by_aggregate()
    {
        $a = ['a' => 17, 'b' => 42, 'c' => 'foo'];
        $b = ['a' => 19, 'b' => 20, 'c' => 'bar'];
        $c = ['a' => 17, 'b' => 42, 'c' => 'baz'];
        $arr = [$a, $b, $c];
        $this->assertSame(
            __array_group_by_aggregate($arr, 'a', [
                'b' => function ($a, $b) {
                    return $a + $b;
                },
                'c' => function ($a, $b) {
                    return $a . ', ' . $b;
                }
            ]),
            [['a' => 17, 'b' => 84, 'c' => 'foo, baz'], ['a' => 19, 'b' => 20, 'c' => 'bar']]
        );
        $this->assertSame(
            __array_group_by_aggregate(
                $arr,
                ['a', 'b'],
                [
                    'c' => function ($a, $b) {
                        return $a . ', ' . $b;
                    }
                ]
            ),
            [['a' => 17, 'b' => 42, 'c' => 'foo, baz'], ['a' => 19, 'b' => 20, 'c' => 'bar']]
        );

        $a = ['a' => 17, 'b' => 42, 'c' => 'foo'];
        $b = ['a' => 19, 'b' => 20, 'c' => 'bar'];
        $c = ['a' => 17, 'b' => 43, 'c' => 'baz'];
        $arr = [$a, $b, $c];
        $this->assertSame(
            __array_group_by_aggregate(
                $arr,
                ['a'],
                [
                    'b' => function ($a, $b) {
                        return max($a, $b);
                    },
                    'c' => function ($a, $b) {
                        return $a . ', ' . $b;
                    }
                ]
            ),
            [['a' => 17, 'b' => 43, 'c' => 'foo, baz'], ['a' => 19, 'b' => 20, 'c' => 'bar']]
        );
        $this->assertSame(
            __array_group_by_aggregate(
                $arr,
                ['a'],
                [
                    'c' => function ($a, $b) {
                        return $a . ', ' . $b;
                    }
                ]
            ),
            [['a' => 17, 'b' => 42, 'c' => 'foo, baz'], ['a' => 19, 'b' => 20, 'c' => 'bar']]
        );
    }

    function test__arr_without()
    {
        $this->assertSame(__arr_without(['foo' => 'bar', 'bar' => 'baz', 'baz' => 'foo'], ['bar', 'baz']), [
            'foo' => 'bar'
        ]);
        $this->assertSame(__arr_without(['foo' => 'bar', 'bar' => 'baz', 'baz' => 'foo'], []), [
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'foo'
        ]);
        $this->assertSame(__arr_without(['foo', 'bar'], [1]), [
            0 => 'foo'
        ]);
        $this->assertSame(__arr_without(['foo', 'bar'], [0]), [
            1 => 'bar'
        ]);
        $this->assertSame(__arr_without(['foo' => 'bar'], ['foo']), []);
        $this->assertSame(__arr_without([], []), []);
        $this->assertSame(__arr_without([], null), []);
        $this->assertSame(__arr_without(null, []), null);
    }

    function test__file_extension()
    {
        $this->assertSame(__file_extension('foo.jpg'), 'jpg');
        $this->assertSame(__file_extension('foo.jpeg'), 'jpeg');
        $this->assertSame(__file_extension('foo.png'), 'png');
        $this->assertSame(__file_extension('FooBar.PDF'), 'pdf');
        $this->assertSame(__file_extension('foo'), null);
        $this->assertSame(__file_extension(''), null);
        $this->assertSame(__file_extension(null), null);
        $this->assertSame(__file_extension(false), null);
        $this->assertSame(__file_extension([]), null);
    }

    function test__utf8()
    {
        $this->assertSame(__is_utf8('foo'), true);
        $this->assertSame(__is_utf8(''), true);
        $this->assertSame(__is_utf8(null), false);
        $this->assertSame(__is_utf8(false), false);
        $this->assertSame(__is_utf8([]), false);
        $this->assertSame(__is_utf8('This is a test älüß!'), true);
        $this->assertSame(__is_utf8(utf8_decode('This is a test älüß!')), false);
        $this->assertSame(__is_utf8(__to_utf8(utf8_decode('This is a test älüß!'))), true);
    }

    function test__array2xml()
    {
        $tests = [
            [
                [
                    [
                        'tag' => 'tag1',
                        'attrs' => ['attr1' => 'val1', 'attr2' => 'val2'],
                        'content' => [
                            [
                                'tag' => 'tag2',
                                'attrs' => ['attr3' => 'val3', 'attr4' => 'val4'],
                                'content' => 'äöüß'
                            ],
                            [
                                'tag' => 'tag3',
                                'attrs' => ['attr5' => 'val5', 'attr5' => 'val5'],
                                'content' => [
                                    [
                                        'tag' => 'tag4',
                                        'attrs' => ['attr6' => 'val6', 'attr7' => 'val7'],
                                        'content' => 'äöüß'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                '<?xml version="1.0" encoding="UTF-8"?>
<tag1 attr1="val1" attr2="val2">
 <tag2 attr3="val3" attr4="val4">äöüß</tag2>
 <tag3 attr5="val5">
  <tag4 attr6="val6" attr7="val7">äöüß</tag4>
 </tag3>
</tag1>
'
            ],
            [
                [
                    [
                        'tag' => 'tag1',
                        'attrs' => ['attr1' => 'val1', 'attr2' => 'val2']
                    ]
                ],
                '<?xml version="1.0" encoding="UTF-8"?>
<tag1 attr1="val1" attr2="val2"/>
'
            ],
            [
                [
                    [
                        'tag' => 'tag1'
                    ]
                ],
                '<?xml version="1.0" encoding="UTF-8"?>
<tag1/>
'
            ]
        ];
        foreach ($tests as $tests__value) {
            $filename = sys_get_temp_dir() . '/' . md5(uniqid());
            __array2xml($tests__value[0], $filename);
            $this->assertSame(file_get_contents($filename), $tests__value[1]);
            $arr2 = __xml2array($filename);
            $this->assertSame($arr2, $tests__value[0]);
        }
    }

    function test__iptc()
    {
        $this->assertSame(array_key_exists('2#116', __iptc_codes()), true);
        $this->assertSame(in_array('Copyright', __iptc_codes()), true);

        $this->assertSame(__iptc_code('Copyright'), '2#116');
        $this->assertSame(__iptc_code('foo'), null);
        $this->assertSame(__iptc_keyword('2#116'), 'Copyright');
        $this->assertSame(__iptc_keyword('foo'), null);

        $this->assertEquals(
            __iptc_read('tests/assets/iptc_raw.jpg', '2#116'),
            '© Copyright 2020 IPTC (Test Images) - www.iptc.org'
        );
        $this->assertSame(
            __iptc_read('tests/assets/iptc_raw.jpg', 'Copyright'),
            '© Copyright 2020 IPTC (Test Images) - www.iptc.org'
        );
        $this->assertSame(__iptc_read('tests/assets/iptc_raw.jpg', 'foobar'), null);
        $this->assertSame(__iptc_read('foobar', 'foobar'), null);

        $this->assertSame(
            __iptc_write('tests/assets/iptc_write.jpg', [
                'AuthorTitle' => 'foo',
                'Copyright' => 'bar'
            ]),
            true
        );
        $this->assertSame(__iptc_read('tests/assets/iptc_write.jpg'), [
            __iptc_code('AuthorTitle') => 'foo',
            __iptc_code('Copyright') => 'bar'
        ]);

        $this->assertSame(__iptc_write('tests/assets/iptc_write.jpg', 'Copyright', 'baz'), true);
        $this->assertSame(__iptc_read('tests/assets/iptc_write.jpg'), [
            __iptc_code('AuthorTitle') => 'foo',
            __iptc_code('Copyright') => 'baz'
        ]);

        $this->assertSame(
            __iptc_write('tests/assets/iptc_write.jpg', [
                'AuthorTitle' => 'foo'
            ]),
            true
        );
        $this->assertSame(__iptc_read('tests/assets/iptc_write.jpg'), [
            __iptc_code('AuthorTitle') => 'foo'
        ]);
        $this->assertSame(
            __iptc_write('tests/assets/iptc_write.jpg', [
                'Copyright' => 'foo'
            ]),
            true
        );
        $this->assertSame(__iptc_read('tests/assets/iptc_write.jpg'), [
            __iptc_code('Copyright') => 'foo'
        ]);
        $this->assertSame(__iptc_write('tests/assets/iptc_write.jpg', 'AuthorTitle', 'baz'), true);
        $this->assertSame(__iptc_read('tests/assets/iptc_write.jpg'), [
            __iptc_code('Copyright') => 'foo',
            __iptc_code('AuthorTitle') => 'baz'
        ]);

        $this->assertSame(__iptc_write('tests/assets/iptc_write.jpg', 'AuthorTitle', ''), true);
        $this->assertSame(__iptc_read('tests/assets/iptc_write.jpg'), [
            __iptc_code('Copyright') => 'foo',
            __iptc_code('AuthorTitle') => ''
        ]);
        $this->assertSame(__iptc_write('tests/assets/iptc_write.jpg', 'AuthorTitle', null), true);
        $this->assertSame(__iptc_read('tests/assets/iptc_write.jpg'), [
            __iptc_code('Copyright') => 'foo'
        ]);
        $this->assertSame(__iptc_write('tests/assets/iptc_write.jpg', 'Copyright', '®©–äöüß"\''), true);
        $this->assertSame(__iptc_read('tests/assets/iptc_write.jpg'), [
            __iptc_code('Copyright') => '®©–äöüß"\''
        ]);

        $this->assertSame(__iptc_write('tests/assets/iptc_write.jpg', []), true);
        $this->assertSame(__iptc_read('tests/assets/iptc_write.jpg'), []);
        $this->assertSame(__iptc_write('tests/assets/iptc_write.jpg', null), true);
        $this->assertSame(__iptc_read('tests/assets/iptc_write.jpg'), []);

        $this->assertSame(__iptc_read('tests/assets/iptc_not_supported.png'), []);
        $this->assertSame(__iptc_write('tests/assets/iptc_not_supported.png', 'Copyright', 'foo'), false);
        $this->assertSame(__iptc_read('tests/assets/iptc_not_supported.png'), []);

        shell_exec('exiftool -overwrite_original -all= ' . __DIR__ . '/assets/iptc_problem.jpg');
        $this->assertSame(
            __iptc_write('tests/assets/iptc_problem.jpg', [
                'Copyright' => 'foo'
            ]),
            true
        );
        $this->assertSame(__iptc_read('tests/assets/iptc_problem.jpg'), [
            __iptc_code('Copyright') => 'foo'
        ]);
    }

    function test__slug()
    {
        $this->assertSame(__slug('This string will be sanitized!'), 'this-string-will-be-sanitized');
        $this->assertSame(__slug('Äťśçĥ Foo'), 'aetsch-foo');
        $this->assertSame(__slug('  '), '');
        $this->assertSame(__slug(''), '');
        $this->assertSame(__slug(null), '');
        $this->assertSame(__slug(false), '');
        $this->assertSame(__slug(true), '');
        $this->assertSame(__slug([]), '');
    }

    function test__shuffle()
    {
        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(
                in_array(__shuffle(['foo', 'bar', 'baz']), [
                    ['foo', 'bar', 'baz'],
                    ['foo', 'baz', 'bar'],
                    ['bar', 'foo', 'baz'],
                    ['bar', 'baz', 'foo'],
                    ['baz', 'foo', 'bar'],
                    ['baz', 'bar', 'foo']
                ]),
                true
            );
        }
        $this->assertSame(__shuffle([]), []);
        $this->assertSame(__shuffle(['foo']), ['foo']);
        $this->assertSame(__shuffle(['']), ['']);
        $this->assertSame(__shuffle(null), null);
        $this->assertSame(__shuffle(true), true);
        $this->assertSame(__shuffle(false), false);
        $this->assertSame(__shuffle('foo'), 'foo');
        $this->assertSame(__shuffle(1337), 1337);
    }

    function test__shuffle_assoc()
    {
        $this->assertSame(__shuffle_assoc(['foo']), ['foo']);
        $this->assertSame(__shuffle_assoc(['foo']), ['foo']);
        $this->assertSame(__shuffle_assoc(['foo' => 'bar', 'bar' => 'baz', 'baz' => 'foo'])['foo'] === 'bar', true);
        $this->assertSame(__shuffle_assoc(['foo' => 'bar', 'bar' => 'baz', 'baz' => 'foo'])['bar'] === 'baz', true);
        $this->assertSame(__shuffle_assoc(['foo' => 'bar', 'bar' => 'baz', 'baz' => 'foo'])['baz'] === 'foo', true);
        $this->assertSame(__shuffle_assoc(null), null);
        $this->assertSame(__shuffle_assoc(false), false);
        $this->assertSame(__shuffle_assoc(true), true);
        $this->assertSame(__shuffle_assoc(1337), 1337);
        $this->assertSame(__shuffle_assoc([]), []);
    }

    function test__date()
    {
        $this->assertSame(__date('2000-01-01'), '2000-01-01');
        $this->assertSame(__date('2000-01-01', 'd.m.Y'), '01.01.2000');
        $this->assertSame(__date('2001-02-29', 'd.m.Y'), null);
        $this->assertSame(__date('2000-01-01', '+6 months'), '2000-07-01');
        $this->assertSame(__date('2000-01-01', null, '+6 months'), '2000-07-01');
        $this->assertSame(__date('2000-01-01', 'd.m.Y', '+6 months'), '01.07.2000');
        $this->assertSame(__date('01.01.2000'), '2000-01-01');
        $this->assertSame(__date('01.01.20'), '2020-01-01');
        $this->assertSame(__date('now'), date('Y-m-d', strtotime('now')));
        $this->assertSame(__date('2019-12-02 12:01:02', 'd.m.Y H:i:s'), '02.12.2019 12:01:02');
        $this->assertSame(__date('2019-12-02T12:01:02', 'd.m.Y H:i:s'), '02.12.2019 12:01:02');
        $this->assertSame(__date('2019-12-02T12:01:02', 'd.m.Y\TH:i:s'), '02.12.2019T12:01:02');
        $this->assertSame(__date(strtotime('2000-01-01'), 'd.m.Y'), '01.01.2000');
        $this->assertSame(__date(strtotime('2000-01-01 13:14:15'), 'd.m.Y'), '01.01.2000');
        $this->assertSame(__date(strtotime('2000-01-01 13:14:15'), 'd.m.Y H:i:s'), '01.01.2000 13:14:15');
        $this->assertSame(__date(strtotime('2000-01-01'), 'd.m.Y', '+6 months'), '01.07.2000');
        $this->assertSame(__date(strtotime('2000-01-01 13:14:15'), 'd.m.Y', '+6 months'), '01.07.2000');
        $this->assertSame(__date(strtotime('2000-01-01 13:14:15'), 'd.m.Y H:i:s', '+6 months'), '01.07.2000 13:14:15');
        $this->assertSame(__date(), date('Y-m-d', strtotime('now')));
        $this->assertSame(__date(null), null);
        $this->assertSame(__date(true), null);
        $this->assertSame(__date(false), null);
        $this->assertSame(__date(''), null);
        $this->assertSame(__date('d.m.Y', null), null);
        $this->assertSame(__date('d.m.Y', true), null);
        $this->assertSame(__date('d.m.Y', false), null);
        $this->assertSame(__date('d.m.Y', ''), null);
        $this->assertSame(__date(null, 'd.m.Y'), null);
        $this->assertSame(__date(true, 'd.m.Y'), null);
        $this->assertSame(__date(false, 'd.m.Y'), null);
        $this->assertSame(__date('', 'd.m.Y'), null);
        $this->assertSame(__date('2008-31-31'), null);
        $this->assertSame(__date('now + 6 days'), date('Y-m-d', strtotime('now + 6 days')));
        $this->assertSame(__date('rfkjh lkjerhflk kjekj'), null);
        $this->assertSame(__date(new DateTime('2000-01-01'), 'd.m.Y'), '01.01.2000');
        $this->assertSame(__date(new DateTime('2000-01-01 17:37:38'), 'd.m.Y H:i:s'), '01.01.2000 17:37:38');
        $this->assertSame(__date('d.m.Y'), date('d.m.Y', strtotime('now')));
        $this->assertSame(__date('d.m.Y', 'tomorrow'), date('d.m.Y', strtotime('tomorrow')));
        $this->assertSame(__date('d.m.Y', 'tomorrow', '+ 6 months'), date('d.m.Y', strtotime('tomorrow + 6 months')));
        $this->assertSame(__date('+6 months'), date('Y-m-d', strtotime('now +6 months')));
    }

    function test__char()
    {
        $this->assertSame(__char_to_int('D'), 4);
        $this->assertSame(__char_to_int('d'), 4);
        $this->assertSame(__char_to_int('A'), 1);
        $this->assertSame(__char_to_int('Z'), 26);
        $this->assertSame(__char_to_int('AA'), 27);

        $this->assertSame(__int_to_char(4), 'D');
        $this->assertSame(__int_to_char(1), 'A');
        $this->assertSame(__int_to_char(26), 'Z');
        $this->assertSame(__int_to_char(27), 'AA');

        $this->assertSame(__inc_char('D'), 'E');
        $this->assertSame(__inc_char('Z'), 'AA');
        $this->assertSame(__inc_char('A', 2), 'C');

        $this->assertSame(__dec_char('U'), 'T');
        $this->assertSame(__dec_char('U', 2), 'S');
        $this->assertSame(__dec_char('A'), '');
    }

    function test__validate_email()
    {
        $this->assertSame(__validate_email('david@vielhuber.de'), true);
        $this->assertSame(__validate_email('david@straßäölöälöääÄÖLÖÄL.de'), true);
        $this->assertSame(__validate_email('test@test.de﻿'), false);
        $this->assertSame(__validate_email(''), false);
        $this->assertSame(__validate_email('@'), false);
        $this->assertSame(__validate_email(true), false);
        $this->assertSame(__validate_email(false), false);
        $this->assertSame(__validate_email(null), false);
        $this->assertSame(__validate_email([]), false);
    }

    function test__remove_empty()
    {
        $this->assertSame(__remove_empty([0 => ['foo', 0, '0', null, ''], null, 2 => [['', ''], [null]]]), [
            0 => ['foo', 0, '0']
        ]);
        $this->assertSame(__remove_empty([0 => ['foo', 0, '0', null, ''], null, 2 => [['', ''], [null]]], [0, '0']), [
            0 => ['foo']
        ]);
        $this->assertSame(
            __remove_empty([0 => ['foo', 0, '0', null, ''], null, 2 => [['', ''], [null]]], null, function ($value) {
                return (is_array($value) && empty($value)) || (is_string($value) && $value === '');
            }),
            [0 => ['foo', 0, '0', null], null, 2 => [1 => [null]]]
        );
    }

    function test__remove_newlines()
    {
        $this->assertSame(__remove_newlines('foo' . PHP_EOL . 'bar<br/>' . PHP_EOL . 'baz'), 'foobarbaz');
        $this->assertSame(__remove_newlines('foo' . PHP_EOL . 'bar' . PHP_EOL . 'baz', ' '), 'foo bar baz');
        $this->assertSame(__remove_newlines(null), null);
        $this->assertSame(__remove_newlines([]), []);
        $this->assertSame(__remove_newlines(true), true);
        $this->assertSame(__remove_newlines(false), false);
        $this->assertSame(__remove_newlines(''), '');
    }

    function test__true_false_one()
    {
        $this->assertSame(__true_one(true, true, true), true);
        $this->assertSame(__true_one([true, true, null]), true);
        $this->assertSame(__true_one(true, '1'), true);
        $this->assertSame(__true_one([true, false]), true);
        $this->assertSame(__true_one(''), false);
        $this->assertSame(__true_one([]), false);
        $this->assertSame(__true_one(null), false);
        $this->assertSame(__true_one(true), true);
        $this->assertSame(__true_one(false), false);

        $this->assertSame(__false_one('foo', 'bar', null), false);
        $this->assertSame(__false_one(false), true);
    }

    function test__helpers()
    {
        $this->assertSame(__x_all('foo', 'bar', null), false);
        $this->assertSame(__x_all(['foo', 'bar', null]), false);
        $this->assertSame(__x_all('foo', 'bar', 'baz'), true);
        $this->assertSame(__x_all(['foo', 'bar', 'baz']), true);
        $this->assertSame(__nx_all('foo', 'bar', null), true);
        $this->assertSame(__nx_all('foo', 'bar', 'baz'), false);

        $this->assertSame(__x_one('foo', 'bar'), true);
        $this->assertSame(__x_one('', null), false);
        $this->assertSame(__x_one(['foo', 'bar']), true);
        $this->assertSame(__x_one(['', null]), false);
        $this->assertSame(__nx_one('foo', 'bar'), false);
        $this->assertSame(__nx_one('', null), true);

        $this->assertSame(__true_all(true, true, true), true);
        $this->assertSame(__true_all([true, true, null]), false);
        $this->assertSame(__true_all(true, '1'), true);
        $this->assertSame(__true_all([true, false]), false);
        $this->assertSame(__false_all('foo', 'bar', null), false);
        $this->assertSame(__false_all(false), true);

        $this->assertSame(__validate_url('https://vielhuber.de'), true);

        $this->assertSame(__validate_date('2000-01-01'), true);
        $this->assertSame(__validate_date('01.01.2000'), true);
        $this->assertSame(__validate_date('29.02.2001'), false);
        $this->assertSame(__validate_date('5956-09-24'), false);
        $this->assertSame(__validate_date('51956-09-24'), false);
        $this->assertSame(__validate_date(new DateTime('2000-01-01')), true);
        $this->assertSame(__validate_date(946713600), true);
        $this->assertSame(__validate_date(null), false);
        $this->assertSame(__validate_date(''), false);
        $this->assertSame(__validate_date(true), false);
        $this->assertSame(__validate_date(false), false);

        $this->assertSame(__validate_date_format('d.m.Y'), true);
        $this->assertSame(__validate_date_format('Y-m-d'), true);
        $this->assertSame(__validate_date_format('Y/m/d'), true);
        $this->assertSame(__validate_date_format('01.m.Y'), true);
        $this->assertSame(__validate_date_format('01.01.2000'), false);
        $this->assertSame(__validate_date_format('foo'), false);
        $this->assertSame(__validate_date_format(null), false);
        $this->assertSame(__validate_date_format(true), false);
        $this->assertSame(__validate_date_format(false), false);
        $this->assertSame(__validate_date_format(''), false);

        $this->assertSame(__validate_date_mod('+6 months'), true);
        $this->assertSame(__validate_date_mod('+ 6 months'), true);
        $this->assertSame(__validate_date_mod('+1 week 2 days 4 hours 2 seconds'), true);
        $this->assertSame(__validate_date_mod(''), false);
        $this->assertSame(__validate_date_mod(null), false);
        $this->assertSame(__validate_date_mod(false), false);
        $this->assertSame(__validate_date_mod(true), false);

        $this->assertSame(__phone_normalize(null), '');
        $this->assertSame(__phone_normalize(''), '');
        $this->assertSame(__phone_normalize('141'), '141');
        $this->assertSame(__phone_normalize('(0)89-12 456 666'), '+49 89 12456666');
        $this->assertSame(__phone_normalize('089 12 456 666'), '+49 89 12456666');
        $this->assertSame(__phone_normalize('08541 12 456---666'), '+49 8541 12456666');
        $this->assertSame(__phone_normalize('08541 12 456/666'), '+49 8541 12456666');
        $this->assertSame(__phone_normalize('++498541 12 456/666'), '+49 8541 12456666');
        $this->assertSame(__phone_normalize('++49(00)8541 12 456/666'), '+49 8541 12456666');
        $this->assertSame(__phone_normalize('0151 / 58-75-46-91'), '+49 151 58754691');
        $this->assertSame(__phone_tokenize('(0)89-12 456 666'), [
            'country_code' => '49',
            'area_code' => '89',
            'number' => '12456666'
        ]);
        $this->assertSame(in_array('49', __phone_country_codes()), true);
        $this->assertSame(in_array('89', __phone_area_codes()), true);
        $this->assertSame(in_array('151', __phone_area_codes()), true);
        $this->assertSame(in_array('89', __phone_area_codes_landline()), true);
        $this->assertSame(in_array('151', __phone_area_codes_mobile()), true);
        $this->assertSame(__phone_is_landline('(0)89-12 456 666'), true);
        $this->assertSame(__phone_is_landline('141'), false);
        $this->assertSame(__phone_is_mobile('(0)89-12 456 666'), false);
        $this->assertSame(__phone_is_mobile('(0)151/58 75 46 91'), true);
        $this->assertSame(__phone_is_mobile('141'), false);

        $this->assertSame(__url_normalize('www.tld.com'), 'https://www.tld.com');
        $this->assertSame(__url_normalize('http://tld.com/'), 'http://tld.com');
        $this->assertSame(__url_normalize(''), '');
        $this->assertSame(__url_normalize(true), true);
        $this->assertSame(__url_normalize(false), false);
        $this->assertSame(__url_normalize(42), 42);
        $this->assertSame(__url_normalize('http://www.foo.com/bar/'), 'http://www.foo.com/bar');

        $this->assertSame(__remove_emoji('Lorem 🤷 ipsum ❤ dolor 🥺 med'), 'Lorem  ipsum  dolor  med');
        $this->assertSame(__remove_emoji('OK!🥊'), 'OK!');
        $this->assertSame(__remove_emoji(''), '');
        $this->assertSame(__remove_emoji(null), null);
        $this->assertSame(__remove_emoji(true), true);
        $this->assertSame(__remove_emoji(false), false);
        $this->assertSame(__remove_emoji(42), 42);

        $this->assertSame(__date('Y'), date('Y', strtotime('now')));
        $this->assertSame(__date('now'), date('Y-m-d', strtotime('now')));
        $this->assertSame(__date('yesterday'), date('Y-m-d', strtotime('yesterday')));
        $this->assertSame(__date('Yesterday'), date('Y-m-d', strtotime('Yesterday')));
        $this->assertSame(__date('+5 weeks'), date('Y-m-d', strtotime('+5 weeks')));
        $this->assertSame(__date('z vDHY'), date('z vDHY', strtotime('now')));

        $this->assertSame(__datetime('01.01.2000'), '2000-01-01T00:00');
        $this->assertSame(__datetime('01.01.2000 18:00'), '2000-01-01T18:00');
        $this->assertSame(__datetime(''), null);
        $this->assertSame(__datetime(null), null);
        $this->assertSame(__datetime(false), null);
        $this->assertSame(__datetime(true), null);

        $this->assertSame(__date_reset_time('2000-01-01 16:30:00'), '2000-01-01 00:00:00');
        $this->assertSame(__date_reset_time('2000-01-01'), '2000-01-01 00:00:00');
        $this->assertSame(__date_reset_time('01.01.2000'), '2000-01-01 00:00:00');
        $this->assertSame(__date_reset_time('01.01.2000 23:59:59'), '2000-01-01 00:00:00');
        $this->assertSame(__date_reset_time('01.01.2000 00:00'), '2000-01-01 00:00:00');
        $this->assertSame(__date_reset_time(''), null);
        $this->assertSame(__date_reset_time(null), null);
        $this->assertSame(__date_reset_time(false), null);
        $this->assertSame(__date_reset_time(true), null);

        $this->assertSame(__first_char_is_uppercase(true), false);
        $this->assertSame(__first_char_is_uppercase(false), false);
        $this->assertSame(__first_char_is_uppercase(''), false);
        $this->assertSame(__first_char_is_uppercase('foo'), false);
        $this->assertSame(__first_char_is_uppercase('Foo'), true);
        $this->assertSame(__set_first_char_uppercase(true), true);
        $this->assertSame(__set_first_char_uppercase(false), false);
        $this->assertSame(__set_first_char_uppercase(''), '');
        $this->assertSame(__set_first_char_uppercase('foo'), 'Foo');
        $this->assertSame(__set_first_char_uppercase('Foo'), 'Foo');
        $this->assertSame(__set_first_char_uppercase('übel'), 'Übel');
        $this->assertSame(__set_first_char_uppercase('Übel'), 'Übel');

        $this->assertSame(mb_strlen(__random_string()), 8);
        $this->assertSame(mb_strlen(__random_string(10)), 10);
        $this->assertSame(mb_strlen(__random_string(16, 'idkfa')), 16);

        $this->assertSame(__array_unique([1, 2, 2]), [1, 2]);
        $this->assertSame(__array_unique([['foo' => 'bar'], ['bar' => 'baz'], ['foo' => 'bar']]), [
            ['foo' => 'bar'],
            ['bar' => 'baz']
        ]);
        $this->assertSame(__array_unique(null), null);
        $this->assertSame(__array_unique([]), []);
        $this->assertSame(__array_unique(''), '');
        $this->assertSame(__array_unique(0), 0);
        $this->assertSame(__array_unique(true), true);
        $this->assertSame(__array_unique(false), false);

        $this->assertSame(__uuid() === __uuid(), false);
        $this->assertSame(strlen(__uuid()) === 36, true);
        $this->assertSame(substr_count(__uuid(), '-') === 4, true);

        $this->assertSame(__pushId() === __pushId(), false);
        $this->assertSame(strlen(__pushId()) === 20, true);
        $this->assertSame(strlen(__pushId()) === 20, true);
        $this->assertSame(strlen(__pushId()) === 20, true);

        $this->assertSame(
            __strip('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam.', 12),
            'Lorem ipsum...'
        );
        $this->assertSame(__strip_numeric('the answer is 42.00'), 'the answer is ');
        $this->assertSame(__strip_nonnumeric('the answer is 42.00'), '42.00');
        $this->assertSame(__strip_digit('the answer is 42'), 'the answer is ');
        $this->assertSame(__strip_nondigit('the answer is 42'), '42');
        $this->assertSame(__strip_nonchars('the Änswer is 42.-+&!foo'), 'the Änswer is foo');
        $this->assertSame(__strip_whitespace('the answer is 42'), 'theansweris42');
        $this->assertSame(__strip_whitespace('the  answeris42'), 'theansweris42');
        $this->assertSame(__strip_whitespace_collapsed('the answer is 42'), 'the answer is 42');
        $this->assertSame(__strip_whitespace_collapsed('the     answer             is 42 '), 'the answer is 42');
        $this->assertSame(__split_newline('foo' . PHP_EOL . 'bar' . PHP_EOL . 'baz'), ['foo', 'bar', 'baz']);

        $this->assertSame(__split_whitespace('DE07123412341234123412', 4), 'DE07 1234 1234 1234 1234 12');
        $this->assertSame(__split_whitespace(' föö bäär ', 3), 'föö bää r');
        $this->assertSame(__split_whitespace(null, 3), null);
        $this->assertSame(__split_whitespace(true, 3), true);
        $this->assertSame(__split_whitespace(false, 3), false);
        $this->assertSame(__split_whitespace('', 3), '');
        $this->assertSame(__split_whitespace('foo', 0), 'foo');

        $this->assertSame(
            __remove_emptylines('foo' . PHP_EOL . '' . PHP_EOL . 'bar' . PHP_EOL . 'baz'),
            'foo' . PHP_EOL . 'bar' . PHP_EOL . 'baz'
        );

        $this->assertSame(__string_is_json('[]'), true);
        $this->assertSame(__string_is_json('{"foo":"bar"}'), true);
        $this->assertSame(__string_is_json('["foo" => "bar"]'), false);
        $this->assertSame(__string_is_json([]), false);
        $this->assertSame(__string_is_json((object) []), false);

        $this->assertSame(__string_is_html('foo'), false);
        $this->assertSame(__string_is_html('<p>foo</p>'), true);
        $this->assertSame(__string_is_html('foo bar'), false);
        $this->assertSame(__string_is_html('foo&nbsp;bar'), true);
        $this->assertSame(__string_is_html(null), false);
        $this->assertSame(__string_is_html(''), false);
        $this->assertSame(__string_is_html(false), false);
        $this->assertSame(__string_is_html(true), false);
        $this->assertSame(__string_is_html([]), false);

        $this->assertSame(__is_base64_encoded('dGhpcyBpcyBjb29sIHN0dWZm'), true);
        $this->assertSame(__is_base64_encoded('#ib3498r'), false);
        $this->assertSame(__is_base64_encoded('al3Vna##2dqa#Gdm'), false);
        $this->assertSame(__is_base64_encoded((object) []), false);

        $this->assertSame(__extract('<a href="#foo">bar</a>', 'href="', '">'), '#foo');
        $this->assertSame(__extract('<a href="#foo">bar</a>', '">', '</a'), 'bar');
        $this->assertSame(__strposx('bar foo baz foobar', 'foo'), [4, 12]);
        $this->assertSame(__strposnth('bar foo baz foobar', 'foo', 2), 12);

        $array = ['foo', 'bar'];
        foreach ($array as $array__key => $array__value) {
            if (__fkey($array__key, $array)) {
                $this->assertSame($array__value, 'foo');
            }
            if (__lkey($array__key, $array)) {
                $this->assertSame($array__value, 'bar');
            }
        }
        $this->assertSame(__last(['foo', 'bar', 'baz']), 'baz');
        $this->assertSame(__first(['foo', 'bar', 'baz']), 'foo');
        $this->assertSame(__first([0 => 'foo', 1 => 'bar', 2 => 'baz']), 'foo');
        $this->assertSame(__first(['foo' => 'bar', 'bar' => 'baz']), 'bar');
        $this->assertSame(__first_key(['foo' => 'bar', 'bar' => 'baz']), 'foo');
        $this->assertSame(__first_key(null), null);
        $this->assertSame(__first_key(true), null);
        $this->assertSame(__first_key(''), null);
        $this->assertSame(__first_key(['foo']), 0);
        $this->assertSame(in_array(__rand(['foo', 'bar', 'baz']), ['foo', 'bar', 'baz']), true);

        $this->assertSame(__remove_first(['foo', 'bar', 'baz']), ['bar', 'baz']);
        $this->assertSame(__remove_last(['foo', 'bar', 'baz']), ['foo', 'bar']);

        $this->assertSame(__can_be_looped([1, 2]), true);
        $this->assertSame(__can_be_looped((object) [1, 2]), true);
        $this->assertSame(__can_be_looped([]), false);

        $this->assertEquals(__array_to_object(['foo']), (object) ['foo']);
        $this->assertEquals(__array_to_object(['foo', 'bar']), (object) ['foo', 'bar']);
        $this->assertEquals(__array_to_object(['foo' => 'bar']), (object) ['foo' => 'bar']);
        $this->assertEquals(
            __array_to_object(['foo', 'bar' => ['foo', 'bar']]),
            (object) ['foo', 'bar' => (object) ['foo', 'bar']]
        );
        $this->assertEquals(__object_to_array((object) ['foo']), ['foo']);
        $this->assertEquals(__object_to_array((object) ['foo', 'bar']), ['foo', 'bar']);
        $this->assertEquals(__object_to_array((object) ['foo' => 'bar']), ['foo' => 'bar']);
        $this->assertEquals(__object_to_array((object) ['foo', 'bar' => (object) ['foo', 'bar']]), [
            'foo',
            'bar' => ['foo', 'bar']
        ]);

        $this->assertEquals(__array(), []);
        $this->assertEquals(__array('foo'), ['foo']);
        $this->assertEquals(__array(['foo']), ['foo']);
        $this->assertEquals(__array(['foo', 'bar']), ['foo', 'bar']);
        $this->assertEquals(__array((object) ['foo', 'bar']), ['foo', 'bar']);
        $this->assertEquals(__array((object) ['foo', 'bar' => (object) ['foo', 'bar']]), [
            'foo',
            'bar' => ['foo', 'bar']
        ]);
        $this->assertEquals(__object(), (object) []);
        $this->assertEquals(__object('foo'), (object) ['foo']);
        $this->assertEquals(__object(['foo']), (object) ['foo']);
        $this->assertEquals(__object(['foo', 'bar']), (object) ['foo', 'bar']);
        $this->assertEquals(__object(['foo' => 'bar']), (object) ['foo' => 'bar']);
        $this->assertEquals(__object((object) ['foo', 'bar']), (object) ['foo', 'bar']);
        $this->assertEquals(
            __object(['foo', 'bar' => ['foo', 'bar']]),
            (object) ['foo', 'bar' => (object) ['foo', 'bar']]
        );

        $arr = [0 => 'foo', 1 => 'bar', 2 => 'baz'];
        $arr = __remove_by_key($arr, 1);
        $this->assertSame($arr, [0 => 'foo', 1 => 'baz']);
        $arr = ['foo' => 1, 'bar' => 2, 'baz' => 3];
        $arr = __remove_by_key($arr, 'foo');
        $this->assertSame($arr, ['bar' => 2, 'baz' => 3]);
        $arr = [42 => 1, 'foo' => 2, 'bar' => 3];
        $arr = __remove_by_key($arr, 'foo');
        $this->assertSame($arr, [42 => 1, 'bar' => 3]);
        $arr = [0 => 'foo', 'foobar' => 'bar', 2 => 'baz'];
        $arr = __remove_by_key($arr, 'foobar');
        $this->assertSame($arr, [0 => 'foo', 1 => 'baz']);
        $arr = [0 => 'foo', 1 => 'bar', 2 => 'baz'];
        $arr = __remove_by_key($arr, 3);
        $this->assertSame($arr, [0 => 'foo', 1 => 'bar', 2 => 'baz']);
        $arr = (object) [0 => 'foo', 1 => 'bar', 2 => 'baz'];
        $arr = __remove_by_key($arr, 1);
        $this->assertEquals($arr, (object) [0 => 'foo', 1 => 'baz']);
        $arr = (object) ['foo' => 1, 'bar' => 2, 'baz' => 3];
        $arr = __remove_by_key($arr, 'foo');
        $this->assertEquals($arr, (object) ['bar' => 2, 'baz' => 3]);
        $arr = (object) [42 => 1, 'foo' => 2, 'bar' => 3];
        $arr = __remove_by_key($arr, 'foo');
        $this->assertEquals($arr, (object) [42 => 1, 'bar' => 3]);

        $arr = [0 => 'foo', 1 => 'bar', 2 => 'baz'];
        $arr = __remove_by_value($arr, 'bar');
        $this->assertSame($arr, [0 => 'foo', 1 => 'baz']);
        $arr = ['foo' => 1, 'bar' => 2, 'baz' => 3];
        $arr = __remove_by_value($arr, 1);
        $this->assertSame($arr, ['bar' => 2, 'baz' => 3]);
        $arr = [42 => 1, 'foo' => 2, 'bar' => 3];
        $arr = __remove_by_value($arr, 2);
        $this->assertSame($arr, [42 => 1, 'bar' => 3]);
        $arr = [0 => 'foo', 'foobar' => 'bar', 2 => 'baz'];
        $arr = __remove_by_value($arr, 'bar');
        $this->assertSame($arr, [0 => 'foo', 1 => 'baz']);
        $arr = [0 => 'foo', 1 => 'bar', 2 => 'baz'];
        $arr = __remove_by_value($arr, 'bazzz');
        $this->assertSame($arr, [0 => 'foo', 1 => 'bar', 2 => 'baz']);
        $arr = (object) [0 => 'foo', 1 => 'bar', 2 => 'baz'];
        $arr = __remove_by_value($arr, 'bar');
        $this->assertEquals($arr, (object) [0 => 'foo', 1 => 'baz']);
        $arr = (object) ['foo' => 1, 'bar' => 2, 'baz' => 3];
        $arr = __remove_by_value($arr, 1);
        $this->assertEquals($arr, (object) ['bar' => 2, 'baz' => 3]);
        $arr = (object) [42 => 1, 'foo' => 2, 'bar' => 3];
        $arr = __remove_by_value($arr, 2);
        $this->assertEquals($arr, (object) [42 => 1, 'bar' => 3]);
        $arr = [0 => 'foo', 1 => 'foo', 2 => 'foo'];
        $arr = __remove_by_value($arr, 'foo');
        $arr = __remove_by_value($arr, 'foo');
        $this->assertSame($arr, []);

        $arr = [0 => 'foo', 1 => 'bar', 2 => 'baz'];
        $arr = __arr_append($arr, 'gnarr', 42 % 7 === 0);
        $this->assertEquals($arr, [0 => 'foo', 1 => 'bar', 2 => 'baz', 3 => 'gnarr']);
        $arr = __arr_append($arr, 'gnarr', 42 % 7 === 1);
        $this->assertEquals($arr, [0 => 'foo', 1 => 'bar', 2 => 'baz', 3 => 'gnarr']);
        $arr = [0 => 'foo', 1 => 'bar', 2 => 'baz'];
        $arr = __arr_prepend($arr, 'gnarr', 0 % 1 === 0);
        $this->assertEquals($arr, [0 => 'gnarr', 1 => 'foo', 2 => 'bar', 3 => 'baz']);
        $arr = __arr_prepend($arr, 'gnarr', 0 % 1 === 1);
        $this->assertEquals($arr, [0 => 'gnarr', 1 => 'foo', 2 => 'bar', 3 => 'baz']);
        $this->assertEquals(__arr_append(__arr_append(__arr_append([], 'foo'), 'bar', false), 'baz'), [
            0 => 'foo',
            1 => 'baz'
        ]);

        $this->assertSame(
            __highlight('that is a search string', 'is'),
            'that <strong class="highlight">is</strong> a search string'
        );
        $this->assertSame(
            __highlight('that is a search isstring', 'is'),
            'that <strong class="highlight">is</strong> a search <strong class="highlight">is</strong>string'
        );
        $this->assertSame(__highlight('that is a search isstring', ''), 'that is a search isstring');
        $this->assertSame(__highlight('Maßbierkrug', 'bier'), 'Maß<strong class="highlight">bier</strong>krug');
        $this->assertSame(__highlight('', ''), '');
        $this->assertSame(__highlight(null, ''), null);
        $this->assertSame(__highlight(null, null), null);
        $this->assertSame(
            __highlight(
                'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est lorem ipsum dolor sit amet.',
                'lorem'
            ),
            '<strong class="highlight">Lorem</strong> ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est <strong class="highlight">Lorem</strong> ipsum dolor sit amet. <strong class="highlight">Lorem</strong> ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est <strong class="highlight">lorem</strong> ipsum dolor sit amet.'
        );

        $this->assertSame(
            __highlight('abc def geh ijk lmn opq rst abc def geh ijk lmn opq rst', 'ijk', true, 5),
            '... geh <strong class="highlight">ijk</strong> lmn ... geh <strong class="highlight">ijk</strong> lmn ...'
        );

        $this->assertSame(
            __highlight(
                'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est lorem ipsum dolor sit amet.',
                'elitr',
                true,
                20
            ),
            '... sadipscing <strong class="highlight">elitr</strong>, sed diam nonumy eirmod ... sadipscing <strong class="highlight">elitr</strong>, sed diam nonumy eirmod ...'
        );

        $this->assertSame(__is_integer(0), true);
        $this->assertSame(__is_integer(42), true);
        $this->assertSame(__is_integer(4.2), false);
        $this->assertSame(__is_integer(0.42), false);
        $this->assertSame(__is_integer(42.), true);
        $this->assertSame(__is_integer('42'), true);
        $this->assertSame(__is_integer('a42'), false);
        $this->assertSame(__is_integer('42a'), false);
        $this->assertSame(__is_integer(0x24), true);
        $this->assertSame(__is_integer(8372468764378627868742367883268), true);
        $this->assertSame(__is_integer('8372468764378627868742367883268'), true);
        $this->assertSame(__is_integer(' 1337'), false);
        $this->assertSame(__is_integer('1337 '), false);
        $this->assertSame(__is_integer([]), false);
        $this->assertSame(__is_integer(null), false);
        $this->assertSame(__is_integer(false), false);
        $this->assertSame(__is_integer(true), false);

        $this->assertSame(__flatten_keys(['foo' => ['bar' => 'baz']]), ['foo', 'bar']);
        $this->assertSame(__flatten_values(['foo' => 'bar', 'bar' => ['baz', 'foo']]), ['bar', 'baz', 'foo']);
        $this->assertSame(__expl(' ', 'foo bar baz', 1), 'bar');

        $this->assertSame(
            __inside_out_values([
                'field1' => [0 => 'foo', 1 => 'bar', 2 => 'baz', 3 => ''],
                'field2' => [0 => 'bar', 1 => 'baz', 2 => 'foo', 3 => null]
            ]),
            [
                0 => [
                    'field1' => 'foo',
                    'field2' => 'bar'
                ],
                1 => [
                    'field1' => 'bar',
                    'field2' => 'baz'
                ],
                2 => [
                    'field1' => 'baz',
                    'field2' => 'foo'
                ]
            ]
        );

        $this->assertEquals(
            __arrays_to_objects([
                'foo' => ['bar', 'baz'],
                'bar' => [(object) ['id' => 7, 'name' => 'foo'], (object) ['id' => 42, 'name' => 'bar']]
            ]),
            (object) [
                'foo' => (object) [0 => 'bar', 1 => 'baz'],
                'bar' => (object) [
                    7 => (object) ['id' => 7, 'name' => 'foo'],
                    42 => (object) ['id' => 42, 'name' => 'bar']
                ]
            ]
        );

        $response = __fetch('https://httpbin.org/anything');
        $this->assertSame($response->method, 'GET');
        $response = __fetch('https://httpbin.org/anything', 'curl');
        $this->assertSame($response->method, 'GET');
        $response = __fetch('https://httpbin.org/anything', 'php');
        $this->assertSame($response->method, 'GET');

        $this->assertEquals(__success(), ((object) ['success' => true, 'message' => '']));
        $this->assertEquals(__success('foo'), (object) ['success' => true, 'message' => 'foo']);
        $this->assertEquals(__error(), (object) ['success' => false, 'message' => '']);
        $this->assertEquals(__error('bar'), (object) ['success' => false, 'message' => 'bar']);

        $this->assertSame(in_array(__os(), ['windows', 'mac', 'linux', 'unknown']), true);

        $this->assertSame(__url(), 'https://github.com/vielhuber/stringhelper');
        $this->assertSame(__baseurl(), 'https://github.com');

        define('ENCRYPTION_KEY', '4736d52f85bdb63e46bf7d6d41bbd551af36e1bfb7c68164bf81e2400d291319'); // first define your encryption key (generated with hash('sha256', uniqid(mt_rand(), true)))
        $this->assertSame(__decrypt(__encrypt('foo')), 'foo');
        $this->assertSame(__decrypt(__encrypt('bar', 'known_salt')), 'bar');

        $this->assertSame(__decrypt_poor(__encrypt_poor('foo')), 'foo');
        $token = __encrypt_poor('bar');
        $this->assertSame(__decrypt_poor($token, true), 'bar');
        $this->assertSame(__decrypt_poor($token, true), null);

        define('ENCRYPTION_FOLDER', __DIR__ . '/');
        $token = __encrypt_poor('bar');
        $this->assertSame(__decrypt_poor($token, true), 'bar');
        $this->assertSame(__decrypt_poor($token, true), null);

        $this->assertSame(count(__files_in_folder()) >= 6, true);
        $this->assertSame(count(__files_in_folder('.')) >= 6, true);
        $this->assertSame(count(__files_in_folder('.', false, ['.gitignore'])) >= 5, true);
        $this->assertSame(in_array('.gitignore', __files_in_folder('.', false)), true);
        $this->assertSame(in_array('.gitignore', __files_in_folder('.', false, ['.gitignore'])), false);
        $this->assertSame(count(__files_in_folder('tests')) === 2, true);
        $this->assertSame(count(__files_in_folder('tests', false, ['Test.php'])) === 1, true);
        $this->assertSame(count(__files_in_folder('tests', true)) > 2, true);
        $this->assertSame(count(__files_in_folder('tests', true, ['assets'])) === 2, true);
        $this->assertSame(count(__files_in_folder('tests/', true)) > 2, true);
        $this->assertSame(count(__files_in_folder('foo')) === 0, true);

        mkdir('tests/foo');
        $this->assertSame(count(__files_in_folder('tests/foo')) === 0, true);
        touch('tests/foo/index.txt');
        $this->assertSame(count(__files_in_folder('tests/foo')) === 1, true);
        $this->assertSame(is_dir('tests/foo'), true);
        __rrmdir('tests/foo');
        $this->assertSame(!is_dir('tests/foo'), true);

        $this->assertSame(__is_external('https://github.com/vielhuber/stringhelper'), false);
        $this->assertSame(__is_external('https://github.com/vielhuber/stringhelper/'), false);
        $this->assertSame(__is_external('https://github.com/vielhuber/stringhelper/issues'), false);
        $this->assertSame(__is_external('https://github.com/vielhuber/stringhelper/test.pdf'), true);
        $this->assertSame(__is_external('tel:+4989215400142'), false);
        $this->assertSame(__is_external('mailto:david@vielhuber.de'), false);
        $this->assertSame(__is_external('https://vielhuber.de'), true);
        $this->assertSame(__is_external('https://vielhuber.de/test.pdf'), true);

        $_GET = ['page_id' => '13', 'code' => '<h1>Hello World!</h1>'];
        $_POST = ['foo' => 'bar', 42 => "\0"];
        $this->assertSame(__get('foo'), null);
        $this->assertSame(__get('page_id'), '13');
        $this->assertSame(__post('foo'), 'bar');
        __clean_up_get();
        __clean_up_post();
        $this->assertSame($_GET, ['page_id' => '13', 'code' => 'Hello World!']);
        $this->assertSame($_POST, ['foo' => 'bar', 42 => '']);

        $this->assertSame(__str_replace_first('foo', 'bar', 'foofoo'), 'barfoo');
        $this->assertSame(__str_replace_last('foo', 'bar', 'foofoo'), 'foobar');
        $this->assertSame(__str_replace_first('foo', 'bar', 'bar'), 'bar');
        $this->assertSame(__str_replace_last('foo', 'bar', 'bar'), 'bar');

        copy('tests/assets/compress.jpg', 'tests/assets/input.jpg');
        $filesize1 = filesize('tests/assets/input.jpg');
        __image_compress('tests/assets/input.jpg', 10, 'tests/assets/output.jpg');
        $filesize2 = filesize('tests/assets/output.jpg');
        $this->assertSame($filesize1 > $filesize2, true);
        @unlink('tests/assets/input.jpg');
        @unlink('tests/assets/output.jpg');

        file_put_contents(
            'tests/assets/file1.txt',
            __line_endings_convert(
                'foo
bar',
                'linux'
            )
        );
        file_put_contents(
            'tests/assets/file2.txt',
            __line_endings_convert(
                'foo
bar',
                'mac'
            )
        );
        file_put_contents(
            'tests/assets/file3.txt',
            __line_endings_convert(
                'foo
bar',
                'windows'
            )
        );
        $this->assertNotSame(file_get_contents('tests/assets/file1.txt'), file_get_contents('tests/assets/file2.txt'));
        $this->assertNotSame(file_get_contents('tests/assets/file1.txt'), file_get_contents('tests/assets/file3.txt'));
        $this->assertNotSame(file_get_contents('tests/assets/file2.txt'), file_get_contents('tests/assets/file3.txt'));
        $this->assertSame(
            __line_endings_convert(file_get_contents('tests/assets/file1.txt'), 'windows'),
            __line_endings_convert(file_get_contents('tests/assets/file2.txt'), 'windows')
        );
        $this->assertSame(
            __line_endings_convert(file_get_contents('tests/assets/file1.txt'), 'mac'),
            __line_endings_convert(file_get_contents('tests/assets/file2.txt'), 'mac')
        );
        $this->assertSame(
            __line_endings_convert(file_get_contents('tests/assets/file1.txt'), 'linux'),
            __line_endings_convert(file_get_contents('tests/assets/file2.txt'), 'linux')
        );
        $this->assertSame(
            __line_endings_convert(file_get_contents('tests/assets/file1.txt'), 'windows'),
            __line_endings_convert(file_get_contents('tests/assets/file3.txt'), 'windows')
        );
        $this->assertSame(
            __line_endings_convert(file_get_contents('tests/assets/file1.txt'), 'mac'),
            __line_endings_convert(file_get_contents('tests/assets/file3.txt'), 'mac')
        );
        $this->assertSame(
            __line_endings_convert(file_get_contents('tests/assets/file1.txt'), 'linux'),
            __line_endings_convert(file_get_contents('tests/assets/file3.txt'), 'linux')
        );
        $this->assertSame(
            __line_endings_convert(file_get_contents('tests/assets/file2.txt'), 'windows'),
            __line_endings_convert(file_get_contents('tests/assets/file3.txt'), 'windows')
        );
        $this->assertSame(
            __line_endings_convert(file_get_contents('tests/assets/file2.txt'), 'mac'),
            __line_endings_convert(file_get_contents('tests/assets/file3.txt'), 'mac')
        );
        $this->assertSame(
            __line_endings_convert(file_get_contents('tests/assets/file2.txt'), 'linux'),
            __line_endings_convert(file_get_contents('tests/assets/file3.txt'), 'linux')
        );
        $this->assertSame(
            __line_endings_weak_equals(
                file_get_contents('tests/assets/file1.txt'),
                file_get_contents('tests/assets/file2.txt')
            ),
            true
        );
        $this->assertSame(
            __line_endings_weak_equals(
                file_get_contents('tests/assets/file1.txt'),
                file_get_contents('tests/assets/file3.txt')
            ),
            true
        );
        $this->assertSame(
            __line_endings_weak_equals(
                file_get_contents('tests/assets/file2.txt'),
                file_get_contents('tests/assets/file3.txt')
            ),
            true
        );
        @unlink('tests/assets/file1.txt');
        @unlink('tests/assets/file2.txt');
        @unlink('tests/assets/file3.txt');

        file_put_contents(
            'tests/assets/file.txt',
            'foo
foo
bar
foo
bar
baz
gna
gna
cool; stuff;'
        );
        __sed_replace(
            ['foo' => 'bar', 'bar' => 'baz', 'gna' => 'gnarr', 'cool; stuff;' => 'foo'],
            'tests/assets/file.txt'
        );
        $this->assertSame(
            __line_endings_weak_equals(
                trim(file_get_contents('tests/assets/file.txt')),
                'baz
baz
baz
baz
baz
baz
gnarr
gnarr
foo'
            ),
            true
        );
        @unlink('tests/assets/file.txt');

        file_put_contents('tests/assets/file.txt', 'foo');
        __sed_prepend('baz gnarr; /\yoo&', 'tests/assets/file.txt');
        __sed_append('bar fuu; yoo//', 'tests/assets/file.txt');
        $this->assertSame(
            __line_endings_weak_equals(
                trim(file_get_contents('tests/assets/file.txt')),
                'baz gnarr; /\yoo&
foo
bar fuu; yoo//'
            ),
            true
        );
        @unlink('tests/assets/file.txt');

        $this->assertSame(
            __line_endings_weak_equals(
                __diff(
                    'foo
bar
baz',
                    'foo
barz
baz'
                ),
                '2c2
< bar
---
> barz'
            ),
            true
        );

        $this->assertSame(
            __line_endings_weak_equals(
                __diff(
                    'foo
bar
baz',
                    'foo
bar
baz'
                ),
                ''
            ),
            true
        );

        __array2csv([['foo', 'bar', 'baz'], ['foo', 'bar', 'baz']], 'tests/assets/file.csv');
        $this->assertSame(
            __line_endings_weak_equals(
                trim(file_get_contents('tests/assets/file.csv')),
                'foo;bar;baz
foo;bar;baz'
            ),
            true
        );
        __array2csv([['foo', 'bar', 'baz'], ['foo', 'bar', 'baz']], 'tests/assets/file.csv', ';', '"');
        $this->assertSame(
            __line_endings_weak_equals(
                trim(file_get_contents('tests/assets/file.csv')),
                'foo;bar;baz
foo;bar;baz'
            ),
            true
        );
        __array2csv([['foo bar', 'bar', 'baz'], ['foo', 'bar', 'baz']], 'tests/assets/file.csv', ',', '\'');
        $this->assertSame(
            __line_endings_weak_equals(
                trim(file_get_contents('tests/assets/file.csv')),
                '\'foo bar\',bar,baz
foo,bar,baz'
            ),
            true
        );
        __array2csv([['foo', 'bar', 'baz'], ['foo', 'bar', 'baz']], 'tests/assets/file.csv');
        $this->assertSame(__csv2array('tests/assets/file.csv'), [['foo', 'bar', 'baz'], ['foo', 'bar', 'baz']]);
        __array2csv([['foo', 'bar', 'baz'], ['foo', 'bar', 'baz']], 'tests/assets/file.csv', ';', '"');
        $this->assertSame(__csv2array('tests/assets/file.csv', ';', '"'), [
            ['foo', 'bar', 'baz'],
            ['foo', 'bar', 'baz']
        ]);
        __array2csv([['foo bar', 'bar', 'baz'], ['foo', 'bar', 'baz']], 'tests/assets/file.csv', ',', '\'');
        $this->assertSame(__csv2array('tests/assets/file.csv', ',', '\''), [
            ['foo bar', 'bar', 'baz'],
            ['foo', 'bar', 'baz']
        ]);
        @unlink('tests/assets/file.csv');

        __log_begin('foo');
        $this->assertSame($GLOBALS['performance'][0]['message'], 'foo');
        $this->assertSame(count($GLOBALS['performance']), 1);
        __log_begin('bar');
        $this->assertSame(count($GLOBALS['performance']), 2);
        __log_begin('');
        $this->assertSame(count($GLOBALS['performance']), 3);
        __log_end();
        $this->assertSame(count($GLOBALS['performance']), 2);
        __log_end();
        $this->assertSame(count($GLOBALS['performance']), 1);
        $this->assertSame(__log_end()['message'], 'foo');
        $this->assertSame(count($GLOBALS['performance']), 0);

        __log_begin('foo');
        $this->assertSame($GLOBALS['performance'][0]['message'], 'foo');
        $this->assertSame(count($GLOBALS['performance']), 1);
        __log_begin('bar');
        $this->assertSame($GLOBALS['performance'][1]['message'], 'bar');
        $this->assertSame(count($GLOBALS['performance']), 2);
        __log_end('foo');
        $this->assertSame(count($GLOBALS['performance']), 1);
        __log_end('bar');
        $this->assertSame(count($GLOBALS['performance']), 0);

        __log_begin();
        $this->assertSame($GLOBALS['performance'][0]['message'], null);
        $this->assertSame(count($GLOBALS['performance']), 1);
        __log_begin();
        $this->assertSame($GLOBALS['performance'][1]['message'], null);
        $this->assertSame(count($GLOBALS['performance']), 2);
        __log_end();
        $this->assertSame(count($GLOBALS['performance']), 1);
        __log_end();
        $this->assertSame(count($GLOBALS['performance']), 0);

        __log_begin();
        sleep(1);
        $data = __log_end(null, false);
        $this->assertSame(intval(round($data['time'])), 1);
    }

    function test__class_usage()
    {
        $arr = [0 => 'foo', 1 => 'bar', 2 => 'baz'];
        $this->assertSame(__::arr_append($arr, 'gnarr', 42 % 7 === 0), __arr_append($arr, 'gnarr', 42 % 7 === 0));
    }
}

class Person
{
    public $id;
    function __construct($id)
    {
        $this->id = $id;
    }
    static function find($id)
    {
        if ($id === 1 || $id === 2) {
            return new Person($id);
        } else {
            return __empty();
        }
    }
    function getAddress()
    {
        if ($this->id === 1) {
            return new Address();
        } else {
            return __empty();
        }
    }
}
class Address
{
    function getCountry()
    {
        return new Country();
    }
}
class Country
{
    function getName()
    {
        return 'Germany';
    }
}
