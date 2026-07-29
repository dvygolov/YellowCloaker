[![Build Status](https://travis-ci.com/vielhuber/stringhelper.svg?branch=master)](https://travis-ci.com/vielhuber/stringhelper)

# ⛏️ stringhelper ⛏️

## motivation

tired of writing

```php
if (isset($_POST['foo']) && $_POST['foo'] === '1') {
}
```

or

```php
if (!empty($a) || (isset($a) && $a === 0)) {
}
```

or

```php
if (!empty($a)) {
    foreach ($a as $value) {
    }
}
```

or

```php
if (
    Person::find(1) !== null &&
    Person::find(1)->getAddress() !== null &&
    Person::find(1)
        ->getAddress()
        ->getCountry() !== null &&
    Person::find(1)
        ->getAddress()
        ->getCountry()
        ->getName() !== null
) {
    echo Person::find(1)
        ->getAddress()
        ->getCountry()
        ->getName();
}
```

?

## installation

```
composer require vielhuber/stringhelper
```

## usage

### existence

```php
// check existence
if (__x($var)) {
}

// check non-existence
if (__nx($var)) {
}

// check existence (bulletproof)
if (__fx(fn() => $var)) {
}

// check non-existence (bulletproof)
if (__fnx(fn() => $var)) {
}
```

### equality

```php
// php has a lot of pitfalls, when comparing loosely
if( 0 == 'true' ) // true
if( 0 == 'str' ) // true
if( 'null' == null ) // false
if( '0' == null ) // false
if( '0' == true ) // false
if( '0' == false ) // true
if( 'false' == true ) // true
if( 'false' == false ) // false
if( new stdClass == true ) // true
if( [] == false ) // true
if( [] == null ) // true
if( [''] == [] ) // false
if( [''] == [0] ) // true
if( 0 == '' ) // true
if( 0 == ' ' ) // true
if( -1 == true ) // true
if( '-1' == true ) // true

// this non-strict equality is symmetric, but not transitive
$a = ''; $b = 0; $c = 'oh';
$a == $b; // true
$b == $c; // true
$c == $a; // false

// to overcome this issue, we...

// ...use strict comparison when possible
if( $var === 'foo' )
{

}

// ...use loose comparison when appropriate
if( $_GET['number'] == 1337 )
{

}

// ...check for truthness / falsiness with these helper methods
if( __true($var) )
{

}

if( __false($var) )
{

}

// be aware, that __true is not always the logic negation of __false
__true(null) // false
__false(null) // false
```

### value

```php
// get variable if exists, otherwise null
__v( $var )

// get variable if exists, otherwise 'default'
__v( $var, 'default' )

// get first variable that exists, otherwise null
__v( $var1, $var2, $var3 )

// get variable if exists, otherwise the empty object
__e( $var )
__e( $var, 'default' )
__e( $var1, $var2, $var3 )
```

### loop

```php
// loop only if exists
foreach (__e($array) as $array__key => $array__value) {
}
```

### try

```php
// if you are unsure, if a variable is even set before checking its existence,
// simply prefix it with the stfu-operator @
if( __x(@$var) )
if( __nx(@$var) )
if( __true(@$var) )
if( __false(@$var) )
if( @$var === 'foo' )
if( @$_GET['number'] == 1337 )
echo __v(@$var)
foreach( __e(@$array) as $array__key=>$array__value)

```

A short note on the usage of @: In this concept we use @-operator that hides errors. We are aware of its potential misuse and also of its benefits.

Be careful when using @\$a['undefined'], there can be 2 possible errors: a missing variable or a missing index. In both cases, we intentionally prevent the parser from stopping and catch the resulting null value. Be aware: If $a is a string, @\$a['undefined'] evaluates to $a[0] since php coerces 'undefined' to 0 and therefore exists.

Another general rule of thumb: Don't use the operator before function calls (@\_\_x(\$a['undefined']).

A caveat is that the @-operator does not catch any fatal runtime errors since PHP 8 anymore.\
For that there is also another more sophisticated way of checking the existence of variables:

If `$var` is totally undefined, the following expressions evaluate correctly to false:

```php
__fx(fn()=>$var)
__fx(fn()=>$var['foo']['bar']['baz'])
__fx(fn()=>$var())
```

Be aware that arrow functions are only available from php 7.4; Prior versions should use:

```php
__fx(function () use (&$var) {
    return $var['foo']['bar']['baz'];
});
```

### classes

```php
// consider the following laravelish code
class Person
{
    public $id;

    function __construct($id)
    {
        $this->id = $id;
    }

    static function find($id)
    {
        // mock example (normally lookup in database)
        if( $id === 1 || $id === 2 )
        {
            return new Person($id);
        }
        else
        {
            return null;
        }
    }
    function getAddress()
    {
        if( $this->id === 1 )
        {
            return new Address();
        }
        else
        {
            return null;
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
echo Person::find(1)->getAddress()->getCountry()->getName(); // 'Germany'
echo Person::find(2)->getAddress()->getCountry()->getName(); // fails because person with id 2 has no address
echo Person::find(3)->getAddress()->getCountry()->getName(); // fails because person with id 3 does not even exist

// due to the fact that the null propagating method call operator
// (https://wiki.php.net/rfc/nullsafe_calls) is still a rfc, we cannot write
echo Person::find(3)?->getAddress()?->getCountry()?->getName(); // null

// we therefore return a null model object
class Person
{
    public $id;

    function __construct($id)
    {
        $this->id = $id;
    }

    static function find($id)
    {
        if( $id === 1 || $id === 2 )
        {
            return new Person($id);
        }
        else
        {
            return __empty();
        }
    }
    function getAddress()
    {
        if( $this->id === 1 )
        {
            return new Address();
        }
        else
        {
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

// this empty helper object is quite useful for returning an empty class which is callable with undefined functions
__empty()

// we can no conveniently call those chains...
echo Person::find(1)->getAddress()->getCountry()->getName(); // 'Germany'
echo Person::find(2)->getAddress()->getCountry()->getName(); // ''
echo Person::find(3)->getAddress()->getCountry()->getName(); // ''

// ...check for existence...
if( __x(Person::find(1)->getAddress()->getCountry()->getName()) )
{

}

// ...check for strict equality...
if( Person::find(1)->getAddress()->getCountry()->getName() === 'Germany' )
{

}

// ...get a value...
echo __v( Person::find(1)->getAddress()->getCountry()->getName(), 'default' );

// ...and loop when possible
foreach( Person::find(1)->getAddress()->getCountry() as $value )
{

}
```

### helpers

there are also some other neat little helpers available.

```php
// check if all variables exist
if( __x_all('foo', 'bar', null) ) // false
if( __x_all(['foo', 'bar', null]) ) // false
if( __x_all('foo', 'bar', 'baz') ) // true
if( __x_all(['foo', 'bar', 'baz']) ) // true
if( __nx_all('foo', 'bar', null) ) // true
if( __nx_all('foo', 'bar', 'baz') ) // false

// check if one variable exists
if( __x_one('foo', 'bar') ) // true
if( __x_one('', null) ) // false
if( __x_one(['foo', 'bar']) ) // true
if( __x_one(['', null]) ) // false
if( __nx_one('foo', 'bar') ) // false
if( __nx_one('', null) ) // true

// check truthness of all variables
if( __true_all(true, true, true) ) // true
if( __true_all([true, true, null]) ) // false
if( __true_all(true, '1') ) // true
if( __true_all([true, false]) ) // false
if( __false_all('foo', 'bar', null) ) // false
if( __false_all(false) ) // true

// check truthness of one variable
if( __true_one(true, true, true) ) // true
if( __true_one([true, true, null]) ) // true
if( __true_one(true, '1') ) // true
if( __true_one([true, false]) ) // true
if( __false_one('foo', 'bar', null) ) // false
if( __false_one(false) ) // true

// cookies
__cookie_set('cookie_name', 'cookie_value') // set cookie for 30 days
__cookie_set('cookie_name', 'cookie_value', 7) // set cookie for 7 days
__cookie_set('cookie_name2', ['can also', 'store', 'arrays'], 7)
__cookie_set('cookie_name3', 'cookie_value', 7, [
    'path' => '/',
    'domain' => '',
    'samesite' => 'None',
    'secure' => true,
    'httponly' => false
]) // more options like SameSite (also works with PHP<7.3)
__cookie_exists('cookie_name') // true
__cookie_get('cookie_name') // 'cookie_value'
__cookie_get('cookie_name2') // ['can also', 'store', 'arrays']
__cookie_delete('cookie_name')

// anonymize ipv4/ipv6 addresses
__anonymize_ip('207.142.131.005') // 207.142.131.XXX
__anonymize_ip('001:0db8:0000:08d3:0000:8a2e:0070:7344') // 2001:0db8:0000:08d3:0000:8a2e:XXXX:XXXX
__anonymize_ip() // anonymizes ip from $_SERVER['REMOTE_ADDR']

// show password strength (1-3)
__password_strength('3iu') // 1
__password_strength('3iurehkHEDJ') // 2
__password_strength('3iurehkHEDJK§$R$A') // 3

// calculate distance in meters between two lat/lng points with the haversine formula
__distance_haversine([48.576809, 13.403207], [48.127719, 11.575344]) // 143999

// checks if string is a valid url (also works with umlauts and without external lib like idna)
__validate_url('https://vielhuber.de') // true

// check if string is a valid email (also works with umlauts and without external lib like idna)
__validate_email('david@vielhuber.de') // true

// checks if a date is valid (string in english/german, timestamp or date format)
__validate_date('2000-01-01') // true
__validate_date('01.01.2000') // true
__validate_date('29.02.2001') // false
__validate_date(new DateTime('2000-01-01')) // true
__validate_date(946713600) // true

// checks if a string is a valid date format
__validate_date_format('d.m.Y') // true
__validate_date_format('Y-m-d') // true
__validate_date_format('01.m.Y') // true
__validate_date_format('foo') // false

// checks if a string is a valid date modifier
__validate_date_mod('+6 months') // true
__validate_date_mod('foo') // false

// simple date helper
__date('2000-01-01') // 2000-01-01
__date('2000-01-01', 'd.m.Y') // 01.01.2000
__date('2001-02-29', 'd.m.Y') // null; returns null if date is invalid, otherwise formatted date
__date('2000-01-01', '+6 months') // 2000-07-01; allows date modifications
__date('2000-01-01', 'd.m.Y', '+6 months') // 01.07.2000
__date('01.01.2000') // 2000-01-01; also accepts other formats
__date('01.01.20') // 2020-01-01; correctly interprets some german variants
__date('now') // 2019-05-28; also accepts strings
__date('2019-12-02 12:00:00', 'd.m.Y H:i:s') // 02.12.2019 12:00:00
__date('2019-12-02T12:00:00', 'd.m.Y H:i:s') // 02.12.2019 12:00:00
__date() // 2019-05-28
__date('') // null
__date(null) // null
__date('d.m.Y',null) // null
$unknown = null; __date($unknown) // null
__date(strtotime('2000-01-01'), 'd.m.Y') // 01.01.2000; also accepts timestamps
__date(strtotime('2000-01-01'), 'd.m.Y', '+6 months') // 01.07.2000
__date(new DateTime('2000-01-01'), 'd.m.Y') // 01.01.2000; also accepts datetime objects
__date('d.m.Y') // 01.01.2000; you can even switch arguments (they are sorted magically)
__date('d.m.Y', 'now') // 2019-05-28
__date('+6 months') // 2019-11-28

// outputs a valid formatted value for input datetime-local
__datetime('01.01.2000') // 2000-01-01T00:00
__datetime('01.01.2000 18:00') // 2000-01-01T18:00

// set time of date as string to begin of day
__date_reset_time('2000-01-01 16:30:00') // 2000-01-01 00:00:00
__date_reset_time('2000-01-01') // 2000-01-01 00:00:00
__date_reset_time('01.01.2000') // 2000-01-01 00:00:00

// get age from date
__age_from_date('2000-01-01') // 20
__age_from_date('2000-01-01', '2010-01-01') // 10
__age_from_date_weeks('2000-01-01') // 1083
__age_from_date_weeks('2000-01-01', '2010-01-01') // 521
__age_from_date_days('2000-01-01') // 7587
__age_from_date_days('2000-01-01', '2010-01-01') // 3653

// remove useless zero digits from decimals
__remove_zero_decimals(1337) // 1337
__remove_zero_decimals('1337') // 1337
__remove_zero_decimals('1337.40') // 1337.4
__remove_zero_decimals('1337,40') // 1337.4
__remove_zero_decimals(1337.0) // 1337
__remove_zero_decimals(1337.4) // 1337.4
__remove_zero_decimals(1337.42) // 1337.42
__remove_zero_decimals(1337.424) // 1337.424

// remove leading zeros
__remove_leading_zeros('01337') // 1337

// normalizes phone numbers (din, germany)
__phone_normalize('(0)89-12 456 666') // +49 89 12456666
__phone_tokenize('(0)89-12 456 666') // ['country_code' => '49', 'area_code' => '89', 'number' => '12456666']
__phone_country_codes() // ['49', ...]
__phone_area_codes() // ['89', '151', ...]
__phone_area_codes_landline() // ['89', ...]
__phone_area_codes_mobile() // ['151', ...]
__phone_is_landline('(0)89-12 456 666') // true
__phone_is_mobile('(0)89-12 456 666') // false

// normalize url
__url_normalize('www.tld.com') // https://www.tld.com
__url_normalize('http://tld.com/') // http://tld.com

// minify html
__minify_html('<!DOCTYPE html>
<title>shortest valid html5 document</title>
<p>yay</p>') // <!DOCTYPE html><title>shortest valid html5 document</title><p>yay</p>

// proper string to domdocument and domdocument to string conversion (respects original structure and fixes lots of caveats)
__dom_to_str(__str_to_dom('<ul><li></li><li></li></ul>')) // <ul><li></li><li></li></ul>
__dom_to_str(__str_to_dom('<custom-component @click.prevent="foo()"></custom-component>')) // <custom-component @click.prevent="foo()"></custom-component>

// translate strings
__translate_google('Sein oder Nichtsein; das ist hier die Frage.', 'de', 'en', '**API Key**') // To be or not to be; that is the question.
__translate_microsoft('Sein oder Nichtsein; das ist hier die Frage.', 'de', 'en', '**API Key**') // Being or not being; that is the question here.
__translate_deepl('Sein oder Nichtsein; das ist hier die Frage.', 'de', 'en', '**API Key**') // To be or not to be; that is the question here.

// remove emojis from string
__remove_emoji('Lorem 🤷 ipsum ❤ dolor 🥺 med') // Lorem  ipsum  dolor  med

// remove accents from string
__remove_accents('Çººĺ') // Cool
__remove_accents('Äťśçĥ') // Ätsch
__remove_accents('Äťśçĥ', true) // Aetsch

// remove non printable chars from string
___remove_non_printable_chars('foobar') // foobar

// string to slug (sanitize string)
__slug('This string will be sanitized!') // this-string-will-be-sanitized

// generate a random string
__random_string() // edPhi34d
__random_string(10) // abCa321aC6
__random_string(16, 'idkfa') // idifafafifaifafk

// shuffle array (no reference)
__shuffle(['foo','bar','baz']); // ['bar','baz','foo']

// shuffle associative array and preserve keys
__shuffle_assoc(['foo' => 'bar', 'bar' => 'baz', 'baz' => 'foo']) // ['bar' => 'baz', 'baz' => 'foo', 'foo' => 'bar']

// sort with umlauts (DIN-5007-2)
$arr = ['äther', 'Äther2', 'Ü12.pdf', 'Ü2.pdf'];
usort($arr, function($a, $b) { return __mb_strcmp($a, $b); }); // ['Äther2', 'Ü12.pdf', 'Ü2.pdf', 'äther']
usort($arr, function($a, $b) { return __mb_strcasecmp($a, $b); }); // ['äther', 'Äther2', 'Ü12.pdf', 'Ü2.pdf']
usort($arr, function($a, $b) { return __mb_strnatcmp($a, $b); }); // ['Äther2', 'Ü2.pdf', 'Ü12.pdf', 'äther']
usort($arr, function($a, $b) { return __mb_strnatcasecmp($a, $b); }); // ['äther', 'Äther2', 'Ü2.pdf', 'Ü12.pdf']

// array order sort by many
$arr = [['a' => 17, 'b' => 42], ['a' => 13, 'b' => 19]]
usort($arr, __array_multisort([ ['a', 'asc'], ['b', 'asc'] ])) // [['a' => 13, 'b' => 19], ['a' => 17, 'b' => 42]]
usort($arr, __array_multisort(function($v) { return [ [$v['a'], 'asc'], [$v['b'], 'asc'] ]; })) // [['a' => 13, 'b' => 19], ['a' => 17, 'b' => 42]]
collect($arr)->sort( __array_multisort([ ['a', 'asc'], ['b', 'asc'] ]) ) // can also be used by laravel collections
// considers umlauts (DIN-5007-2)
$arr = [['foo' => 'zoo'], ['foo' => 'Äther']]
usort($arr, __array_multisort([['foo', 'asc']])) // [['foo' => 'Äther'], ['foo' => 'zoo']]

// array group by
$a = ['a' => 17, 'b' => 42, 'c' => 'foo']
$b = ['a' => 19, 'b' => 20, 'c' => 'bar']
$c = ['a' => 17, 'b' => 42, 'c' => 'baz']
$arr = [$a, $b, $c]
__array_group_by($arr, 'a') // [17 => [$a, $c], 19 => [$b]]
__array_group_by($arr, 'a', 'b') // [17 => [42 => [$a, $c]], 19 => [20 => [$b]]]
__array_group_by($arr, function($v) { return $v['a']; }) // [17 => [$a, $c], 19 => [$b]]
__array_group_by($arr, function($v) { return $v['a']; }, function($v) { return $v['b']; }) // [17 => [42 => [$a, $c]], 19 => [20 => [$b]]]
$arr = collect([collect($a), collect($b), collect($c)])
__array_group_by($arr, function($v) { return $v->get('a'); })  // can also be used by laravel collections

// array group by aggregate
$a = ['a' => 17, 'b' => 42, 'c' => 'foo']
$b = ['a' => 19, 'b' => 20, 'c' => 'bar']
$c = ['a' => 17, 'b' => 42, 'c' => 'baz']
$arr = [$a, $b, $c]
__array_group_by_aggregate($arr, 'a', [
    'b' => function($a, $b) { return $a+$b; },
    'c' => function($a, $b) { return $a.', '.$b; },
]) // [['a' => 17, 'b' => 84, 'c' => 'foo, baz'], ['a' => 19, 'b' => 20, 'c' => 'bar']]
__array_group_by_aggregate($arr, ['a','b'], [
    'c' => function($a, $b) { return $a.', '.$b; },
]) // [['a' => 17, 'b' => 42, 'c' => 'foo, baz'], ['a' => 19, 'b' => 20, 'c' => 'bar']]

// array unique (that works with multidimensional arrays)
__array_unique([1,2,2]) // [1,2]
__array_unique([['foo'=>'bar'],['bar'=>'baz'],['foo'=>'bar']]) // [['foo'=>'bar'],['bar'=>'baz']]

// recursively change values of array of arrays (only leaf nodes)
__array_map_deep(['foo','bar'=>['baz','gnarr']], function($a) { return $a.'!'; }) // ['foo!','bar'=>['baz!','gnarr!']]
__array_map_deep([[[[[[[[[[[[[[[true]]]]]]]]]]]]]]], function($a) { return !$a; }) // [[[[[[[[[[[[[[[false]]]]]]]]]]]]]]]
__array_map_deep(
    [[[[[[[[[[[[[[[42 => 'no', 7 => 'ok']]]]]]]]]]]]]]],
    function ($value, $key) { return $key === 42 ? $value : $value . '!'; }
) // [[[[[[[[[[[[[[[42 => 'no', 7 => 'ok!']]]]]]]]]]]]]]]
__array_map_deep(
    ['foo'=>['bar'=>'baz'],'bar'=>['baz'=>'gnarr'],'gnarr'=>['foo'=>'gnaz']],
    function($value,$key,$key_chain) { return in_array('bar',$key_chain)?$value.'!':$value; }
) // ['foo'=>['bar'=>'baz!'],'bar'=>['baz'=>'gnarr!'],'gnarr'=>['foo'=>'gnaz']]
$output = [];
array_map_deep(
    [1=>[2=>[3=>[4=>[5=>'ok1'],6=>[7=>'ok2']]]],8=>'ok3'],
    function($value,$key,$key_chain) use(&$output) { $output[] = $value.': '.implode('.',$key_chain); }
)
echo implode(' - ', $output) // ok1: 1.2.3.4.5, ok2: 1.2.3.6.7, ok3: 8
__array_map_deep(['foo','bar'=>(object)['baz','gnarr']], function($a) { return $a.'!'; }) // ['foo!','bar'=>(object)['baz!','gnarr!']]
__array_map_deep(['foo','bar'=>json_encode(['baz','gnarr'])], function($a) { return $a.'!'; }) // ['foo!', 'bar'=>json_encode(['baz!', 'gnarr!'])]

// recursively change values of array of arrays (with parent nodes; be careful when changing the array structure)
__array_map_deep_all(['foo'=>'bar','bar'=>['baz'=>'gnarr','gnarr'=>'baz']], function($value, $key, $key_chain) {
    if (is_array($value) && array_key_exists('baz', $value) && $value['baz'] === 'gnarr') {
        $value['gnarr'] = 'baz2';
    }
    return $value;
}) // ['foo'=>'bar','bar'=>['baz'=>'gnarr','gnarr'=>'baz2']]

// array walk recursive (with parent nodes; be careful when changing the array structure)
$arr = ['foo' => 'bar', 'bar' => ['baz' => 'gnarr', 'gnarr' => 'baz']]
__array_walk_recursive_all($arr, function (&$value, $key, $key_chain) {
    if (is_array($value) && array_key_exists('baz', $value) && $value['baz'] === 'gnarr') {
        $value['gnarr'] = 'baz2';
    }
})
$arr // ['foo'=>'bar','bar'=>['baz'=>'gnarr','gnarr'=>'baz2']]

// array filter recursive
__array_filter_recursive_all(
    ['foo' => ['foo' => ['foo' => ['foo' => ['foo' => []]]]]],
    function($value, $key, $key_chain) { return $key === 'foo' && empty($value); }
) // []

// array map for keys
__array_map_keys(function($k) { return $k.'!'; }, ['foo' => 'bar', 'bar' => 'baz']) // ['foo!' => 'bar', 'bar!' => 'baz']

// ask question on cli
$answer = __ask('What\'s your name?') // free input
$answer = __ask('Choose your destiny:
[1] red pill
[2] blue pill', [1,2]) // only accept specific chars/integers

// show progress on cli
echo 'Searching for TOE (theory of everything)...'.PHP_EOL;
$i = 0;
while($i <= 100) {
    __progress($i, 100, 'Loading...', 75, '#');
    usleep($i < 90 ? 10000 : 250000);
    $i++;
}
echo PHP_EOL.'Answer: 42';
/*
Searching for TOE (theory of everything)...
Loading... [############################################################################] 100%
Answer: 42
*/

// simple multibyte version of sprintf
sprintf('%7.7s', 'mäh') // '   mäh'
__mb_sprintf('%7.7s', 'mäh') // '    mäh'

// encode arbitrary data to string
$data = ['foo' => 'bar', 'bar' => 'baz'];
$str = __encode_data($data) // 'YToyOntzOjM6ImZvbyI7czozOiJiYXIiO3M6MzoiYmFyIjtzOjM6ImJheiI7fQ=='
__decode_data($str) // ['foo' => 'bar', 'bar' => 'baz']
// support for multiple arguments
__decode_data(__encode_data(['foo', 'bar'])) // ['foo', 'bar']);
__decode_data(__encode_data('foo', 'bar')) // ['foo', 'bar']);
// this can be useful to enrich form fields with additional data
echo '<input name="foo['.__encode_data($data).']" value="bar" />';
__decode_data(array_key_first($_POST['foo'])) // ['foo' => 'bar', 'bar' => 'baz']
// this can also be useful to create a poor mans index
foreach($a as $a__value) { $index[__encode_data($a__value['foo'],$a__value['bar'])] = null; }
foreach($b as $b__value) { if(array_key_exists(__encode_data($b__value['foo'],$b__value['bar']),$index)) { /* ... */ } }

// generate uuid/guid v4
__uuid() // 19028aea-ccb6-4b32-9e5d-1243c3a77bb1

// create lexicographically ordered string ids like in firebase
__pushId() // -LMsSyccg4OavBCZxRAA

// strip string
__strip('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam.', 12) // Lorem ipsum...

// strip numeric (0-9.,)
__strip_numeric('the answer is 42.00') // the answer is

// strip non-numeric (all except 0-9.,)
__strip_nonnumeric('the answer is 42.00') // 42.00

// strip digit (0-9)
__strip_numeric('the answer is 42.00') // the answer is

// strip non-digit (all except 0-9)
__strip_nondigit('the answer is 42') // 42

// strip non-chars (all except a-z and umlauts)
__strip_nonchars('the Änswer is 42.-+&!foo') // the Änswer is foo

// strip whitespace
__strip_whitespace('the answer is 42') // theansweris42
__strip_whitespace('the     answeris42') // theansweris42

// strip whitespace (collapsed)
__strip_whitespace_collapsed('the answer is 42') // the answer is 42
__strip_whitespace_collapsed('the     answer             is 42 ') // the answer is 42

// strip specific tags (optionally with content) of html string
__strip_tags('<p>foo</p><iframe src="#"></iframe><script>alert();</script><p>bar</p>', 'script') // <p>foo</p><iframe src="#"></iframe>alert();<p>bar</p>
__strip_tags('<p>foo</p><iframe src="#"></iframe><script>alert();</script><p>bar</p>', ['iframe','script'], true) // <p>foo</p><p>bar</p>

// split string by new line
__split_newline('foo
bar
baz') // ['foo','bar','baz']

// add space after every 4th character (first remove whitespace, do it bytesafe, don't add a trailing space like chunk_split)
__split_whitespace('DE07123412341234123412', 4) // 'DE07 1234 1234 1234 1234 12'
__split_whitespace(' föö bäär ', 3) // 'föö bää r'

// remove empty lines
__remove_emptylines('foo

bar
baz') // foo\nbar\nbaz

// remove new lines
__remove_newlines('foo
bar<br/>
baz') // foobarbaz
__remove_newlines('foo
bar<br/>
baz', ' ') // foo bar baz

// reverse of nl2br
__br2nl('foo<br/>bar') // foo
//bar

// truncate/trim long strings
__truncate_string('Lorem ipsum dolor sit amet, consectetuer.', 20); // Lorem ipsum dolor ...
__truncate_string('Lorem ipsum dolor sit amet, consectetuer.', 20, '…'); // Lorem ipsum dolor …

// trim whitespace (including &nbsp; and line breaks)
__trim_whitespace('      string including nasty whitespace chars  ') // 'string including nasty whitespace chars'

// trim every item in array
__atrim(['foo
','bar','
baz']) // ['foo','bar','baz']

// trim every line in multiline array
__trim_every_line('foo
 bar
baz ') // foo
//bar
//baz

// remove keys from associative array
__arr_without(['foo' => 'bar', 'bar' => 'baz', 'baz' => 'foo'], ['bar', 'baz']) // ['foo' => 'bar']

// reverse string (grapheme safe, works also without intl extension)
__strrev('hello❤️world') // dlrow❤️olleh

// check if string is json
__string_is_json('[]') // true
__string_is_json('{"foo":"bar"}') // true
__string_is_json('["foo" => "bar"]') // false
__string_is_json([]) // false
__string_is_json((object)[]) // false

// check if string is html
__string_is_html('foo') // false
__string_is_html('<p>foo</p>') // true
__string_is_html('foo bar') // false
__string_is_html('foo&nbsp;bar') // true

// check if string is serialized
__is_serialized('a:1:{s:3:"foo";s:3:"bar";}') // true
__is_serialized('idkfa') // false
__is_serialized('b:0;') // true
__is_serialized('a:1:{s:3:\"foo\";s:3:\"bar\";}') // false
__is_serialized('a:1:{42}') // false

// check if string is base64 encoded
__is_base64_encoded('dGhpcyBpcyBjb29sIHN0dWZm') // true
__is_base64_encoded('#ib3498r') // false
__is_base64_encoded('al3Vna##2dqa#Gdm') // false
__is_base64_encoded((object)[]) // false

// check if variable is of laravel type 'Illuminate\Database\Eloquent\Builder'
__is_eloquent_builder($var) // true|false

// extract part from string
__extract('<a href="#foo">bar</a>','href="','">') // #foo
__extract('<a href="#foo">bar</a>','">','</a') // bar

// find all occurences of substring in string
__strposx('bar foo baz foobar', 'foo') // [4,12]

// find nth occurence of substring in string
__strposnth('bar foo baz foobar', 'foo', 2) // 12

// check if key is first/last key in foreach loop
$array = ['foo','bar'];
foreach($array as $array__key=>$array__value)
{
    if( __fkey($array__key, $array) ) { }
    if( __lkey($array__key, $array) ) { }
}

// get last item of array
__last(['foo', 'bar', 'baz']) // 'baz'

// get first item of array
__first(['foo', 'bar', 'baz']) // 'foo'
__first(['foo' => 'bar', 'bar' => 'baz']) // 'bar'

// get first key of array
__first_key(['foo' => 'bar', 'bar' => 'baz']) // 'foo'

// get random element from array
__rand(['foo', 'bar', 'baz']) // 'bar'

// remove first/last item of array (and reindex array)
__remove_first(['foo', 'bar', 'baz']) // ['bar','baz']
__remove_last(['foo', 'bar', 'baz']) // ['foo','bar']

// uppercase helpers (mb safe)
__first_char_is_uppercase('Foo') // true
__first_char_is_uppercase('bar') // true
__set_first_char_uppercase('baz') // Baz
__set_first_char_uppercase('übel') // Übel

// convert array to object
__array_to_object(['foo']) // {0: 'foo'}
__array_to_object(['foo','bar']) // {0: 'foo', 1: 'bar'}
__array_to_object(['foo' => 'bar']) // {foo: 'bar'}
__array_to_object(['foo','bar' => ['foo','bar']]) // {0: 'foo', bar: {0: 'foo', 1: 'bar'}}

// convert object to array
__object_to_array((object)['foo']) // ['foo']
__object_to_array((object)['foo','bar']) // ['foo','bar']
__object_to_array((object)['foo' => 'bar']) // ['foo' => 'bar']
__object_to_array((object)['foo','bar' => (object)['foo','bar']]) // ['foo', 'bar' => ['foo','bar']]

// convert variable to array
__array() // []
__array('foo') // ['foo']
__array(['foo']) // ['foo']
__array(['foo','bar']) // ['foo','bar']
__array((object)['foo','bar']) // ['foo','bar']
__array((object)['foo','bar' => (object)['foo','bar']]) // ['foo', 'bar' => ['foo','bar']]

// convert variable to object
__object() // {}
__object('foo') // {0: 'foo'}
__object(['foo']) // {0: 'foo'}
__object(['foo','bar']) // {0: 'foo', 1: 'bar'}
__object(['foo' => 'bar']) // {foo: 'bar'}
__object((object)['foo','bar']) // {0: 'foo',1: 'bar'}
__object(['foo','bar' => ['foo','bar']]) // {0: 'foo', bar: {0: 'foo', 1: 'bar'}}

// check if item can be looped (is a non empty array, object or collection)
__can_be_looped([1,2]) // true
__can_be_looped((object)[1,2]) // true
__can_be_looped([]) // false

// generate nested foreach loop of n levels
$a = [1, 2]; $b = [3, 4]; $c = [5, 6]; $output = [];
$fn = function ($x, $y, $z) use (&$output) { $output[] = $x . '' . $y . '' . $z; };
__foreach_nested($a, $b, $c, $fn);
print_r($output);
// same as foreach($a as $a__value) { foreach($b as $b__value) { foreach($c as $c__value) { $fn($x, $y, $z); } }
// ['135', '136', '145', '146', '235', '236', '245', '246']

// removes recursively all items from array or object or collection that are considered empty (indexes are not reindexed)
$arr = [0 => ['foo',0,'0',null,''], null, 2 => [['',''],[null]]];
__remove_empty($arr) // [0 => ['foo',0,'0']]
__remove_empty($arr, [0,'0']) // [0 => ['foo']] (provide additional values that are considered empty)
__remove_empty($arr, null, function ($value) {
    return (is_array($value) && empty($value)) || (is_string($value) && $value === '');
}) // [0 => ['foo',0,'0',null], null, 2 => [1 => [null]]] (provide a callback function)

// remove item from array or object and fill up gaps (if numeric keys are available)
__remove_by_key([0 => 'foo', 1 => 'bar', 2 => 'baz'], 1) // [0 => 'foo', 1 => 'baz']
__remove_by_key(['foo' => 1, 'bar' => 2, 'baz' => 3], 'foo') // ['bar' => 2, 'baz' => 3]
__remove_by_key((object)[0 => 'foo', 1 => 'bar', 2 => 'baz'], 1) // (object)[0 => 'foo', 1 => 'baz']
__remove_by_value([0 => 'foo', 1 => 'bar', 2 => 'baz'], 'bar') // [0 => 'foo', 1 => 'baz']
__remove_by_value(['foo' => 1, 'bar' => 2, 'baz' => 3], 1) // ['bar' => 2, 'baz' => 3]
__remove_by_value((object)[0 => 'foo', 1 => 'bar', 2 => 'baz'], 'bar') // (object)[0 => 'foo', 1 => 'baz']

// get max array depth
__arr_depth(['foo' => 'bar', 'bar' => ['baz' => ['gnarr' => 'gnaz']]]) // 3

// (conditional) append/prepend to array
__arr_append(['foo'], 'bar') // ['foo','bar']
__arr_prepend(['bar'], 'foo') // ['foo','bar']
__arr_append(['foo'], 'bar', 42%7 === 0) // ['foo','bar']
__arr_prepend(['bar'], 'foo', 0%1 === 1) // ['foo']
__arr_append(__arr_append(__arr_append([], 'foo'), 'bar', false), 'baz') // ['foo','baz']

// turn values of array "inside out"
// this is useful when working with html forms
/*
<li><input type="text" name="data[option1][]" /><input type="text" name="data[option2][]" /></li>
<li><input type="text" name="data[option1][]" /><input type="text" name="data[option2][]" /></li>
<li><input type="text" name="data[option1][]" /><input type="text" name="data[option2][]" /></li>
<li><input type="text" name="data[option1][]" /><input type="text" name="data[option2][]" /></li>
*/
__inside_out_values([
    'option1' => [0 => 'foo', 1 => 'bar', 2 => 'baz', 3 => ''],
    'option2' => [0 => 'bar', 1 => 'baz', 2 => 'foo', 3 => null]
])
/*
[
    0 => ['option1' => 'foo','option2' => 'bar'],
    1 => ['option1' => 'bar','option2' => 'baz'],
    2 => ['option1' => 'baz','option2' => 'foo']
]
*/

// converts recursively nested arrays into objects
// this is useful when working with state in vue/redux
// objects are automatically keyed by id (if property "id" is available)
__arrays_to_objects(['foo' => ['bar','baz'], 'bar' => [(object)['id' => 7, 'name' => 'foo'], (object)['id' => 42, 'name' => 'bar']]])
// { 'foo': { 0: 'bar', 1: 'baz' }, 'bar' => { 7: { 'id': 7, 'name': 'foo' }, 7: { 'id': 42, 'name': 'bar' } } }

// highlight strings
__highlight('that is a search string', 'is') // that <strong class="highlight">is</strong> a search string
__highlight('abc def geh ijk lmn opq rst abc def geh ijk lmn opq rst', 'ijk', true, 5) // '... geh <strong class="highlight">ijk</strong> lmn ... geh <strong class="highlight">ijk</strong> lmn ...'

// checks if variable is an integer (accepts also well formed strings)
__is_integer(0) // true
__is_integer(42) // true
__is_integer(4.2) // false
__is_integer(0.42) // false
__is_integer(42.) // true
__is_integer('42') // true
__is_integer('a42') // false
__is_integer('42a') // false
__is_integer(0x24) // true
__is_integer(8372468764378627868742367883268) // true (in comparison to is_int())
__is_integer('8372468764378627868742367883268') // true
__is_integer(' 1337') // false
__is_integer('1337 ') // false
__is_integer([]) // false
__is_integer(null) // false
__is_integer(false) // false
__is_integer(true) // false

// output arguments in a reader friendly way
__o($var)
__o($var1, $var2, $var3)
__o('<strong>foo</strong>') // html codes are not parsed: array(1) { [0]=> string(32) "<strong>foo</strong>" }

// same as __o but die afterwards
__d($var)

// flatten multidimensional array (keys)
__flatten_keys(['foo' => ['bar' => 'baz']]) // ['foo','bar']

// flatten multidimensional array (values)
__flatten_values(['foo' => 'bar', 'bar' => ['baz', 'foo']]) // ['bar','baz','foo']

// get nth element of concatenized array
__expl(' ', 'foo bar baz', 1) // bar

// redirect via php (following post/redirect/get-pattern)
__prg() // to current url without get arguments
__prg('https://test.de')

// redirect via php/html
__redirect_to() // to current url without get arguments
__redirect_to('https://test.de')
__redirect_to('https://test.de', 301) // with "Moved permanently"
__redirect_to('https://test.de', 302) // with "Moved temporarily"
__redirect_to('https://test.de', 7, 'html') // redirect in 7 seconds (via html)

// throw exceptions (with arrays as messages)
try {
    __exception('foo');
}
catch(\Throwable $t) {
     __exception_message($t) // 'foo'
}
try {
    __exception(['foo' => 'bar']);
}
catch(\Throwable $t) {
    __exception_message($t) // ['foo' => 'bar']
}
try {
    throw new \Exception('bar');
}
catch(\Throwable $t) {
    __exception_message($t) // 'bar'
}
// you also catch these specific exceptions only
try {
    __exception('foo');
}
catch(\ExtendedException $t) {
    __exception_message($t) // 'foo'
}
try {
    throw new \Exception('bar');
}
catch(\ExtendedException $t) {
    // does not trigger
}
catch(\Exception $t) {
    // triggers
}

// success/error return values
__success() // { success: true, message: '' }
__error('missing data') // { success: false, message: 'missing data' }

// simple hook system that supports actions/filters with priorities
$GLOBALS['t'] = 0;
__hook_fire('hook_name'); // $GLOBALS['t'] = 0
__hook_add('hook_name', function() { $GLOBALS['t']++; }); // $GLOBALS['t'] = 0
__hook_fire('hook_name'); // $GLOBALS['t'] = 1
__hook_fire('hook_name'); // $GLOBALS['t'] = 2
__hook_add('hook_name', function() { $GLOBALS['t'] *= 2; }); // $GLOBALS['t'] = 2
__hook_fire('hook_name'); // $GLOBALS['t'] = 6
__hook_fire('hook_name'); // $GLOBALS['t'] = 14
$foo = 1;
__hook_add('filter_name', function($a) { return $a+1; }, 20);
__hook_add('filter_name', function($a) { return $a*2; }, 10);
__hook_add('filter_name', function($a) { return $a-3; }, PHP_INT_MAX);
$foo = __hook_fire('filter_name', $foo); // $foo = 0
$foo = __hook_fire('filter_name', $foo); // $foo = -2

// get current os
__os() // ['windows','mac','linux']

// get current url (without trailing slash)
__url() // https://github.com/vielhuber/stringhelper?foo=bar (with get parameters)
__urlWithoutArgs() // https://github.com/vielhuber/stringhelper (without get parameters)
__baseurl() // https://github.com

// compress image
__image_compress('input.jpg')
__image_compress('input.jpg', 70)
__image_compress('input.jpg', 70, 'output.jpg')

// fix exif image orientation
__image_orientate('input.jpg')
__image_orientate('input.jpg', 70)
__image_orientate('input.jpg', 70, 'output.jpg')

// get file extension from filename
__file_extension('foo.jpg') // jpg

// utf8 conversions
__is_utf8('foo') // true; checks if a string is utf8 encoded
__to_utf8('foo') // tries to convert any string to utf8

// read/write iptc tags (with full utf8 support)
__iptc_codes() // returns important codes in human readable format: ['2#116' => 'Copyright', ...]
__iptc_code('Copyright') // 2#116
__iptc_keyword('2#116') // Copyright

__iptc_read('input.jpg') // ['2#116' => '© Copyright 2021 by foobar', ...]
__iptc_read('input.jpg', '2#116') // '© Copyright 2021 by foobar
__iptc_read('input.jpg', 'Copyright') // '© Copyright 2021 by foobar

__iptc_write('input.jpg', ['2#116' => '© Copyright 2021 by foobar']) // this overwrites all existing data and only sets one field
__iptc_write('input.jpg', '2#116', '© Copyright 2021 by foobar') // this only sets the specific field and leaves the rest intact
__iptc_write('input.jpg', 'Copyright', '© Copyright 2021 by foobar') // also accepts human readable codes
__iptc_write('input.jpg', 'Copyright', null) // delete specific tag
__iptc_write('input.jpg', []) // reset all tags

// poor mans encryption (via openssl)
define('ENCRYPTION_KEY', '4736d52f85bdb63e46bf7d6d41bbd551af36e1bfb7c68164bf81e2400d291319')  // first define your encryption key (generated with hash('sha256', uniqid(mt_rand(), true)))
__decrypt(__encrypt('foo')) // 'foo' (hard, with individual one-time salt)
__decrypt(__encrypt('bar','known_salt')) // 'bar' (soft, good for searching in dbs)

// very poor mans encryption (via file system)
define('ENCRYPTION_FOLDER', $_SERVER['DOCUMENT_ROOT'].'/encryption') // Deny from all this if this is public(!)
__decrypt_poor(__encrypt_poor('foo')) // 'foo'
$token = __encrypt_poor('bar')
__decrypt_poor($token, true) // 'bar' (one time decryption supported)
__decrypt_poor($token) // null

// list all files in folder (['filename1','filename2',...])
__files_in_folder() // current folder
__files_in_folder('.') // current folder
__files_in_folder('foo') // subfolder
__files_in_folder('foo', true) // do it recursively
__files_in_folder('foo', true, ['.git', '.gitkeep']) // do it recursively and exclude some folders/files

// recursively remove folder and it's contents
__rrmdir('foo')

// check if link is external or internal
__is_external('https://github.com/vielhuber/stringhelper') // false
__is_external('https://github.com/vielhuber/stringhelper/') // false
__is_external('https://github.com/vielhuber/stringhelper/issues') // false
__is_external('https://github.com/vielhuber/stringhelper/test.pdf') // true
__is_external('tel:+4989215400142') // false
__is_external('mailto:david@vielhuber.de') // false
__is_external('https://vielhuber.de') // true
__is_external('https://vielhuber.de/test.pdf') // true

$_GET = ['page_id' => '13', 'code' => '<h1>Hello World!</h1>'];
$_POST = ['foo' => 'bar', 42 => "\0"];

// fetch post/get variables if they exist
__get('foo') // null (because not set)
__get('page_id') // '13'
__post('foo') // bar

// clean up post/get from malicious content using filter_var_array
__clean_up_get() // $_GET = ['page_id' => '13', 'code' => 'Hello World!']
__clean_up_post() // $_POST = ['foo' => 'bar', 42 => '']
__clean_up() // same as __clean_up_get() and __clean_up_post()

// check for repetitive actions, e.g. to prevent mass spam mailing (based on hashed client ip address)
__is_repetitive_action(); // any action
__is_repetitive_action('name'); // specific action
__is_repetitive_action('name', 60); // allow 1 action per 60 minutes (this is the default if not specified)
__is_repetitive_action('name', 1/60); // allow 1 action per 1 second
__is_repetitive_action('name', 60, ['127.0.0.1','0.0.0.0']); // whitelist ip addresses

// check for spam words in string (blacklist blocker utilizing https://github.com/splorp/wordpress-comment-blacklist)
__has_spamwords('This is cool stuff.') // false
__has_spamwords('I do spy software your website.') // true
__has_spamwords('Hongsheng Ltd') // false
__has_spamwords('Hongsheng Ltd', ['hongsheng']) // true (add custom blacklist keywords)
__has_spamwords('I do spy software your website.', null, ['spy software']) // false (add custom whitelist keywords)

// check if ip is blacklisted on multiple dnsbls (dns based blackhole lists)
__ip_is_on_spamlist('191.101.31.148') // true
__ip_is_on_spamlist('127.0.0.1') // false

// get referrer
__referer() // $_SERVER['HTTP_REFERER'], if not available null

// do simple get requests (via curl or as a fallback with php file_get_contents [allow_url_fopen=1])
__fetch('https://httpbin.org/anything') // { "method": "GET", ... }
__fetch('https://httpbin.org/anything', 'curl') // { "method": "GET", ... }
__fetch('https://httpbin.org/anything', 'php') // { "method": "GET", ... }

// do curl requests (get/post) and get status code, body and header
__curl(
    'https://httpbin.org/anything', // url
    ['foo' => 'bar'], // data
    'POST', // method
    ['Bar' => 'baz'], // headers
    false, // store and send cookies
    true, // send as json (or application/x-www-form-urlencoded)
    60, // timeout in seconds
    ['username' => 'password'], // basic authentication
    ['foo' => 'bar'], // cookies
    true, // follow redirects
    'username:password@192.168.178.1:8080' // use proxy (username, password and port are optional)
)
__curl('https://httpbin.org/anything') // {"status": 200, "result": { "method": "GET", ... }, "headers": [ ... ], "url": "..."}
__curl('https://httpbin.org/anything', ['foo' => 'bar'], 'POST') //  {"status": 200, "result": { "method": "POST", "data": {"foo": "bar"}, ... }, "headers": [ ... ], "url": "..."}
__curl('https://httpbin.org/anything', ['foo' => 'bar'], 'POST', ['Bar' => 'baz']) //  {"status": 200, "result" => { "method": "POST", "headers" = { "Bar": "baz", ... }, ... }, "headers": [ ... ], "url": "..."}
__curl('https://vielhuber.de') // json is automatically decoded (but only if the response is of type json)
__curl('https://httpbin.org/anything', ['foo' => 'bar'], 'PUT') //  {"status": 200, "result": { "method": "PUT", "data": {"foo": "bar"}, ... }, "headers": [ ... ], "url": "..."}
__curl('https://httpbin.org/anything', null, 'DELETE') //  {"status": 200, "result": { "method": "DELETE", "data": "", ... }, "headers": [ ... ], "url": "..."}
// __curl also supports both storing and sending cookies (without leaving a trace on the local filesystem)
// with that you can do cool stuff like scraping the wordpress backend
__curl('https://vielhuber.de/wp-login.php', ['log' => 'username', 'pwd', 'password'], 'POST', null, true, false)
__curl('https://vielhuber.de/wp-admin/options.php', null, 'GET', null, true)
// you can also hijack the current browser session if logged in
__curl('https://vielhuber.de/wp-admin/options.php', null, 'GET', null, false, false, 60, null, $_COOKIE)

// check basic auth
__has_basic_auth('https://vielhuber.de'); // false
__has_basic_auth('https://httpbin.org/basic-auth/foo/bar'); // true
__check_basic_auth('https://httpbin.org/basic-auth/foo/bar', 'foo','baz'); // false
__check_basic_auth('https://httpbin.org/basic-auth/foo/bar', 'foo','bar'); // true

// output json response with status code for apis
__json_response([
    'success' => true,
    'message' => 'successfully created object',
    'public_message' => 'Object erfolgreich angelegt!',
    'data' => [
        'id' => 42,
        'foo' => 'bar'
    ],
    200 // status code
]);

// extract all urls of (potentially nested) remote sitemap
__extract_urls_from_sitemap('https://vielhuber.de/sitemap_index.xml') // ['https://vielhuber.de','https://vielhuber.de/impressum/', ...]
__extract_urls_from_sitemap('https://foo:bar@vielhuber.de/sitemap_index.xml') // supports basic auth
__extract_urls_from_sitemap('https://vielhuber.de/sitemap_index.xml', 'foo:bar')
__extract_urls_from_sitemap('https://vielhuber.de/sitemap_index.xml', null, true) // include lastmod parameter; [['url' => 'https://vielhuber.de', 'lastmod' => '2020-01-01 00:00:00'], ['url' => 'https://vielhuber.de/impressum/', 'lastmod' => '2020-01-01 00:00:00'], ...]

// get meta tags from url
__extract_title_from_url('https://vielhuber.de') // David Vielhuber > Full-Stack Developer aus München
__extract_meta_desc_from_url('https://vielhuber.de') // 🌀 Vielhuber David ist ein Web-Geek mit einem Faible für schönes Design, einer Prise Perfektionismus und Augen für klare Konturen. 🌀

// extract youtube/vimeo video information from string
__video_info('https://www.youtube.com/watch?v=WAZlcK7FUic') // ['id' => 'WAZlcK7FUic', 'provider' => 'youtube', 'thumbnail' => 'data:image/jpeg;base64,/9j/4AA...']
__video_info('https://vimeo.com/527316428') // ['id' => '527316428', 'provider' => 'vimeo', 'thumbnail' => 'data:image/jpeg;base64,/9j/4AA...']
__video_info('https://www.youtube.com/embed/WAZlcK7FUic?feature=oembed') // several different formats are accepted
__video_info('WAZlcK7FUic')
__video_info('527316428')
__video_info('https://www.youtube.com/watch?v=WAZlcK7FUiZ') // null (strong id detection is also included)

// char helpers for excel columns
__char_to_int('D') // 4
__int_to_char(4) // 'D'
__inc_char('D') // 'E'
__inc_char('Z') // 'AA'
__inc_char('A',2) // 'C'
__dec_char('U') // 'T'

// convert excel date timestamp to string and vice versa (be aware: excel does utc internally)
__timestamp_excel_to_str(36526) // '2000-01-01 00:00:00'
__timestamp_excel_to_str(36526.3440972222) // '2000-01-01 08:15:30'
__timestamp_str_to_excel('01.01.2000') // 36526
__timestamp_str_to_excel('01.01.2000 08:15:30') // 36526.3440972222

// str_replace
__str_replace_first('foo','bar','foofoo') // 'barfoo'
__str_replace_last('foo','bar','foofoo') // 'foobar'

// fun with line endings
__line_endings_convert($str, 'linux') // converts string to linux line endings (LF)
__line_endings_convert($str, 'mac') // converts string to mac line endings (CR)
__line_endings_convert($str, 'windows') // converts string to windows line endings (CRLF)
__line_endings_weak_equals($str1, $str2) // compares 2 strings ignoring its line endings

// inline text modifications using sed (also works on sed bsd)
__sed_replace(['foo' => 'bar', 'bar' => 'baz', 'gna' => 'gnarr'], 'file.txt')
__sed_prepend('foo', 'file.txt')
__sed_append('bar', 'file.txt')

// show diff of two strings
__diff('foo
bar
baz', 'foo
barz
baz')
// 2c2
// < bar
// ---
// > barz

// csv fun
__array2csv([['foo', 'bar', 'baz'],['foo', 'bar', 'baz']], 'file.csv')
__array2csv([['foo', 'bar', 'baz'],['foo', 'bar', 'baz']], 'file.csv', ';', '"')
__csv2array('file.csv') // [['foo', 'bar', 'baz'],['foo', 'bar', 'baz']]
__csv2array('file.csv', ';', '"') // [['foo', 'bar', 'baz'],['foo', 'bar', 'baz']]

// xml fun
$arr = [
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
];
$xml = '<?xml version="1.0" encoding="UTF-8"?>
    <tag1 attr1="val1" attr2="val2">
        <tag2 attr3="val3" attr4="val4">äöüß</tag2>
        <tag3 attr5="val5">
            <tag4 attr6="val6" attr7="val7">äöüß</tag4>
        </tag3>
    </tag1>
';
__array2xml($arr, 'file.xml'); // $xml
__xml2array('file.xml'); // $arr

// measure performance
__log_begin()
for($i = 0; $i < 10000; $i++) { }
__log_end() // echos script execution time

__log_begin('task')
for($i = 0; $i < 10000; $i++) { }
$data = __log_end('task', false) // ['message' => '...', 'time' => '...'];

__log_begin('task')
for($i = 0; $i < 10000; $i++) {
  __log_begin('childtask')
  for($y = 0; $y < 100; $y++) { }
  __log_end('childtask')
}
__log_end('task')

__log_begin('task1')
__log_begin('task2')
__log_end('task1')
__log_end('task2')

__log_begin()
__log_begin()
__log_end() // this ends latest entry (lifo)
__log_end()
```

## usage as class

if you don't like hotloaded functions, you also can use this library in a class-based way:

```php
use vielhuber\stringhelper\__;
__::x(42); // true
```

## js implementation

there is also a javascript implemenation [hlp](https://github.com/vielhuber/hlp) with similiar functions available.

## testing

copy `.env.example` to `.env`, fill in values, install dependencies with `composer install` and run `./vendor/bin/phpunit`.

## appendix

### existence matrix

|                                 | <sub>\_\_x()</sub> | <sub>\_\_true()</sub> | <sub>\_\_false()</sub> | <sub>!== null</sub> | <sub>!= null</sub> | <sub>!== false</sub> | <sub>!= false</sub> | <sub>=== true</sub> | <sub>== true</sub> | <sub>!is_null()</sub> | <sub>isset()</sub> | <sub>!empty()</sub> | <sub>if/else</sub> | <sub>?true:false</sub> | <sub>(??true) === true</sub> | <sub>(??true) == true</sub> | <sub>(??true) === false</sub> | <sub>(??true) == false</sub> | <sub>count() > 0</sub> | <sub>!= ''</sub> | <sub>!== ''</sub> |
| ------------------------------- | ------------------ | --------------------- | ---------------------- | ------------------- | ------------------ | -------------------- | ------------------- | ------------------- | ------------------ | --------------------- | ------------------ | ------------------- | ------------------ | ---------------------- | ---------------------------- | --------------------------- | ----------------------------- | ---------------------------- | ---------------------- | ---------------- | ----------------- |
| <sub>null</sub>                 | <sub>false</sub>   | <sub>false</sub>      | <sub>false</sub>       | <sub>false</sub>    | <sub>false</sub>   | <sub>true</sub>      | <sub>false</sub>    | <sub>false</sub>    | <sub>false</sub>   | <sub>false</sub>      | <sub>false</sub>   | <sub>false</sub>    | <sub>false</sub>   | <sub>false</sub>       | <sub>true</sub>              | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>false</sub>       | <sub>false</sub> | <sub>true</sub>   |
| <sub>false</sub>                | <sub>false</sub>   | <sub>false</sub>      | <sub>true</sub>        | <sub>true</sub>     | <sub>false</sub>   | <sub>false</sub>     | <sub>false</sub>    | <sub>false</sub>    | <sub>false</sub>   | <sub>true</sub>       | <sub>true</sub>    | <sub>false</sub>    | <sub>false</sub>   | <sub>false</sub>       | <sub>false</sub>             | <sub>false</sub>            | <sub>true</sub>               | <sub>true</sub>              | <sub>true</sub>        | <sub>false</sub> | <sub>true</sub>   |
| <sub>true</sub>                 | <sub>true</sub>    | <sub>true</sub>       | <sub>false</sub>       | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>true</sub>     | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>       | <sub>true</sub>    | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>        | <sub>true</sub>              | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>[]</sub>                   | <sub>false</sub>   | <sub>false</sub>      | <sub>false</sub>       | <sub>true</sub>     | <sub>false</sub>   | <sub>true</sub>      | <sub>false</sub>    | <sub>false</sub>    | <sub>false</sub>   | <sub>true</sub>       | <sub>true</sub>    | <sub>false</sub>    | <sub>false</sub>   | <sub>false</sub>       | <sub>false</sub>             | <sub>false</sub>            | <sub>false</sub>              | <sub>false</sub>             | <sub>false</sub>       | <sub>true</sub>  | <sub>true</sub>   |
| <sub>['']</sub>                 | <sub>false</sub>   | <sub>false</sub>      | <sub>false</sub>       | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>true</sub>     | <sub>false</sub>    | <sub>true</sub>    | <sub>true</sub>       | <sub>true</sub>    | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>        | <sub>false</sub>             | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>0</sub>                    | <sub>true</sub>    | <sub>false</sub>      | <sub>true</sub>        | <sub>true</sub>     | <sub>false</sub>   | <sub>true</sub>      | <sub>false</sub>    | <sub>false</sub>    | <sub>false</sub>   | <sub>true</sub>       | <sub>true</sub>    | <sub>false</sub>    | <sub>false</sub>   | <sub>false</sub>       | <sub>false</sub>             | <sub>false</sub>            | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>false</sub> | <sub>true</sub>   |
| <sub>1</sub>                    | <sub>true</sub>    | <sub>true</sub>       | <sub>false</sub>       | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>true</sub>     | <sub>false</sub>    | <sub>true</sub>    | <sub>true</sub>       | <sub>true</sub>    | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>        | <sub>false</sub>             | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>-1</sub>                   | <sub>true</sub>    | <sub>true</sub>       | <sub>false</sub>       | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>true</sub>     | <sub>false</sub>    | <sub>true</sub>    | <sub>true</sub>       | <sub>true</sub>    | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>        | <sub>false</sub>             | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>'0'</sub>                  | <sub>true</sub>    | <sub>false</sub>      | <sub>true</sub>        | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>false</sub>    | <sub>false</sub>    | <sub>false</sub>   | <sub>true</sub>       | <sub>true</sub>    | <sub>false</sub>    | <sub>false</sub>   | <sub>false</sub>       | <sub>false</sub>             | <sub>false</sub>            | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>'1'</sub>                  | <sub>true</sub>    | <sub>true</sub>       | <sub>false</sub>       | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>true</sub>     | <sub>false</sub>    | <sub>true</sub>    | <sub>true</sub>       | <sub>true</sub>    | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>        | <sub>false</sub>             | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>'-1'</sub>                 | <sub>true</sub>    | <sub>true</sub>       | <sub>false</sub>       | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>true</sub>     | <sub>false</sub>    | <sub>true</sub>    | <sub>true</sub>       | <sub>true</sub>    | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>        | <sub>false</sub>             | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>''</sub>                   | <sub>false</sub>   | <sub>false</sub>      | <sub>false</sub>       | <sub>true</sub>     | <sub>false</sub>   | <sub>true</sub>      | <sub>false</sub>    | <sub>false</sub>    | <sub>false</sub>   | <sub>true</sub>       | <sub>true</sub>    | <sub>false</sub>    | <sub>false</sub>   | <sub>false</sub>       | <sub>false</sub>             | <sub>false</sub>            | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>false</sub> | <sub>false</sub>  |
| <sub>' '</sub>                  | <sub>false</sub>   | <sub>false</sub>      | <sub>false</sub>       | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>true</sub>     | <sub>false</sub>    | <sub>true</sub>    | <sub>true</sub>       | <sub>true</sub>    | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>        | <sub>false</sub>             | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>'null'</sub>               | <sub>true</sub>    | <sub>false</sub>      | <sub>false</sub>       | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>true</sub>     | <sub>false</sub>    | <sub>true</sub>    | <sub>true</sub>       | <sub>true</sub>    | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>        | <sub>false</sub>             | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>'false'</sub>              | <sub>true</sub>    | <sub>false</sub>      | <sub>true</sub>        | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>true</sub>     | <sub>false</sub>    | <sub>true</sub>    | <sub>true</sub>       | <sub>true</sub>    | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>        | <sub>false</sub>             | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>'true'</sub>               | <sub>true</sub>    | <sub>true</sub>       | <sub>false</sub>       | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>true</sub>     | <sub>false</sub>    | <sub>true</sub>    | <sub>true</sub>       | <sub>true</sub>    | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>        | <sub>false</sub>             | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>'str'</sub>                | <sub>true</sub>    | <sub>true</sub>       | <sub>false</sub>       | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>true</sub>     | <sub>false</sub>    | <sub>true</sub>    | <sub>true</sub>       | <sub>true</sub>    | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>        | <sub>false</sub>             | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>[0,1]</sub>                | <sub>true</sub>    | <sub>true</sub>       | <sub>false</sub>       | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>true</sub>     | <sub>false</sub>    | <sub>true</sub>    | <sub>true</sub>       | <sub>true</sub>    | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>        | <sub>false</sub>             | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>[0]</sub>                  | <sub>true</sub>    | <sub>true</sub>       | <sub>false</sub>       | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>true</sub>     | <sub>false</sub>    | <sub>true</sub>    | <sub>true</sub>       | <sub>true</sub>    | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>        | <sub>false</sub>             | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>'a:0:{}'</sub>             | <sub>false</sub>   | <sub>false</sub>      | <sub>false</sub>       | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>true</sub>     | <sub>false</sub>    | <sub>true</sub>    | <sub>true</sub>       | <sub>true</sub>    | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>        | <sub>false</sub>             | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>'b:1;'</sub>               | <sub>true</sub>    | <sub>true</sub>       | <sub>false</sub>       | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>true</sub>     | <sub>false</sub>    | <sub>true</sub>    | <sub>true</sub>       | <sub>true</sub>    | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>        | <sub>false</sub>             | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>'b:0;'</sub>               | <sub>false</sub>   | <sub>false</sub>      | <sub>true</sub>        | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>true</sub>     | <sub>false</sub>    | <sub>true</sub>    | <sub>true</sub>       | <sub>true</sub>    | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>        | <sub>false</sub>             | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>new stdClass</sub>         | <sub>false</sub>   | <sub>false</sub>      | <sub>false</sub>       | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>      | <sub>true</sub>     | <sub>false</sub>    | <sub>true</sub>    | <sub>true</sub>       | <sub>true</sub>    | <sub>true</sub>     | <sub>true</sub>    | <sub>true</sub>        | <sub>false</sub>             | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>true</sub>        | <sub>true</sub>  | <sub>true</sub>   |
| <sub>$\_GET['undefined']</sub>  | <sub>error</sub>   | <sub>error</sub>      | <sub>error</sub>       | <sub>error</sub>    | <sub>error</sub>   | <sub>error</sub>     | <sub>error</sub>    | <sub>error</sub>    | <sub>error</sub>   | <sub>error</sub>      | <sub>false</sub>   | <sub>false</sub>    | <sub>error</sub>   | <sub>error</sub>       | <sub>false</sub>             | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>error</sub>       | <sub>error</sub> | <sub>error</sub>  |
| <sub>@$\_GET['undefined']</sub> | <sub>false</sub>   | <sub>false</sub>      | <sub>false</sub>       | <sub>false</sub>    | <sub>false</sub>   | <sub>true</sub>      | <sub>false</sub>    | <sub>false</sub>    | <sub>false</sub>   | <sub>false</sub>      | <sub>false</sub>   | <sub>false</sub>    | <sub>false</sub>   | <sub>false</sub>       | <sub>true</sub>              | <sub>true</sub>             | <sub>false</sub>              | <sub>false</sub>             | <sub>false</sub>       | <sub>false</sub> | <sub>true</sub>   |

### loose comparison matrix

| <sub>==</sub>                    | <sub>null</sub>  | <sub>false</sub> | <sub>true</sub>  | <sub>[]</sub>    | <sub>['']</sub>  | <sub>0</sub>     | <sub>1</sub>     | <sub>-1</sub>    | <sub>'0'</sub>   | <sub>'1'</sub>   | <sub>'-1'</sub>  | <sub>''</sub>    | <sub>' '</sub>   | <sub>'null'</sub> | <sub>'false'</sub> | <sub>'true'</sub> | <sub>'str'</sub> | <sub>[0,1]</sub> | <sub>[0]</sub>   | <sub>'a:0:{}'</sub> | <sub>'b:1;'</sub> | <sub>'b:0;'</sub> | <sub>new stdClass</sub> | <sub>\$\_GET['undefined']</sub> | <sub>@\$\_GET['undefined']</sub> |
| -------------------------------- | ---------------- | ---------------- | ---------------- | ---------------- | ---------------- | ---------------- | ---------------- | ---------------- | ---------------- | ---------------- | ---------------- | ---------------- | ---------------- | ----------------- | ------------------ | ----------------- | ---------------- | ---------------- | ---------------- | ------------------- | ----------------- | ----------------- | ----------------------- | ------------------------------- | -------------------------------- |
| <sub>null</sub>                  | <sub>true</sub>  | <sub>true</sub>  | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>true</sub>                  |
| <sub>false</sub>                 | <sub>true</sub>  | <sub>true</sub>  | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>true</sub>                  |
| <sub>true</sub>                  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>true</sub>  | <sub>true</sub>  | <sub>false</sub> | <sub>true</sub>  | <sub>true</sub>  | <sub>false</sub> | <sub>true</sub>  | <sub>true</sub>   | <sub>true</sub>    | <sub>true</sub>   | <sub>true</sub>  | <sub>true</sub>  | <sub>true</sub>  | <sub>true</sub>     | <sub>true</sub>   | <sub>true</sub>   | <sub>true</sub>         | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>[]</sub>                    | <sub>true</sub>  | <sub>true</sub>  | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>true</sub>                  |
| <sub>['']</sub>                  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>0</sub>                     | <sub>true</sub>  | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>true</sub>  | <sub>true</sub>   | <sub>true</sub>    | <sub>true</sub>   | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>     | <sub>true</sub>   | <sub>true</sub>   | <sub>error</sub>        | <sub>error</sub>                | <sub>true</sub>                  |
| <sub>1</sub>                     | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>error</sub>        | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>-1</sub>                    | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>error</sub>        | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>'0'</sub>                   | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>'1'</sub>                   | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>'-1'</sub>                  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>''</sub>                    | <sub>true</sub>  | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>true</sub>                  |
| <sub>' '</sub>                   | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>'null'</sub>                | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>   | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>'false'</sub>               | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>  | <sub>true</sub>    | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>'true'</sub>                | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>true</sub>   | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>'str'</sub>                 | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>[0,1]</sub>                 | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>[0]</sub>                   | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>'a:0:{}'</sub>              | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>     | <sub>false</sub>  | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>'b:1;'</sub>                | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>true</sub>   | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>'b:0;'</sub>                | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>true</sub>   | <sub>false</sub>        | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>new stdClass</sub>          | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>error</sub> | <sub>error</sub> | <sub>error</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>true</sub>         | <sub>error</sub>                | <sub>false</sub>                 |
| <sub>\$\_GET['undefined']</sub>  | <sub>error</sub> | <sub>error</sub> | <sub>error</sub> | <sub>error</sub> | <sub>error</sub> | <sub>error</sub> | <sub>error</sub> | <sub>error</sub> | <sub>error</sub> | <sub>error</sub> | <sub>error</sub> | <sub>error</sub> | <sub>error</sub> | <sub>error</sub>  | <sub>error</sub>   | <sub>error</sub>  | <sub>error</sub> | <sub>error</sub> | <sub>error</sub> | <sub>error</sub>    | <sub>error</sub>  | <sub>error</sub>  | <sub>error</sub>        | <sub>error</sub>                | <sub>error</sub>                 |
| <sub>@\$\_GET['undefined']</sub> | <sub>true</sub>  | <sub>true</sub>  | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>true</sub>  | <sub>false</sub> | <sub>false</sub>  | <sub>false</sub>   | <sub>false</sub>  | <sub>false</sub> | <sub>false</sub> | <sub>false</sub> | <sub>false</sub>    | <sub>false</sub>  | <sub>false</sub>  | <sub>false</sub>        | <sub>error</sub>                | <sub>true</sub>                  |
