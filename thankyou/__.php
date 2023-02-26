<?php
namespace vielhuber\stringhelper;

class __
{
    public static function x($input)
    {
        if (
            $input === null ||
            $input === false ||
            $input === '' ||
            (is_string($input) && trim($input) === '') ||
            (is_array($input) && empty($input)) ||
            (is_object($input) && empty((array) $input))
        ) {
            return false;
        }
        if (is_array($input) && count($input) === 1 && array_values($input)[0] === '') {
            return false;
        }
        if ($input instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo && $input->count() === 0) {
            return false;
        }
        if ($input instanceof \Illuminate\Database\Eloquent\Collection && $input->count() === 0) {
            return false;
        }
        if ($input instanceof \Illuminate\Support\Collection && $input->count() === 0) {
            return false;
        }
        if ($input instanceof \__empty_helper) {
            return false;
        }
        if (self::is_serialized($input)) {
            return self::x(unserialize($input));
        }
        if (json_encode($input) === '"\ufeff"') {
            return false;
        } // file_get_content of empty file
        return true;
    }

    public static function nx($input)
    {
        return !self::x(@$input);
    }

    public static function fx($input)
    {
        if ($input instanceof \Closure) {
            try {
                $current_state = error_reporting();
                error_reporting(0);
                $return = $input();
                error_reporting($current_state);
                return self::x(@$return);
            } catch (\Error $e) {
                return false;
            }
        }
    }

    public static function fnx($input)
    {
        return !self::fx(@$input);
    }

    public static function v(...$args)
    {
        foreach ($args as $arg) {
            if (self::x(@$arg)) {
                return $arg;
            }
        }
        return null;
    }

    public static function e(...$args)
    {
        foreach ($args as $arg) {
            if (self::x(@$arg)) {
                return $arg;
            }
        }
        return self::empty();
    }

    public static function i($var)
    {
        if (self::nx(@$var)) {
            return [];
        }
        if (is_array($var) || $var instanceof \Traversable) {
            return $var;
        }
        return [];
    }

    public static function empty()
    {
        return new \__empty_helper();
    }

    public static function x_all(...$args)
    {
        if (self::x(@$args[0]) && $args[0] instanceof \Illuminate\Support\Collection) {
            $args[0] = $args[0]->toArray();
        }
        if (self::x(@$args[0]) && is_array($args[0]) && count($args) === 1) {
            $args = $args[0];
        }
        foreach ($args as $arg) {
            if (self::nx(@$arg)) {
                return false;
            }
        }
        return true;
    }

    public static function nx_all(...$args)
    {
        return !self::x_all(...@$args);
    }

    public static function x_one(...$args)
    {
        if (self::x(@$args[0]) && $args[0] instanceof \Illuminate\Support\Collection) {
            $args[0] = $args[0]->toArray();
        }
        if (self::x(@$args[0]) && is_array($args[0]) && count($args) === 1) {
            $args = $args[0];
        }
        foreach ($args as $arg) {
            if (self::x(@$arg)) {
                return true;
            }
        }
        return false;
    }

    public static function true_one(...$args)
    {
        if (self::x(@$args[0]) && $args[0] instanceof \Illuminate\Support\Collection) {
            $args[0] = $args[0]->toArray();
        }
        if (self::x(@$args[0]) && is_array($args[0]) && count($args) === 1) {
            $args = $args[0];
        }
        foreach ($args as $arg) {
            if (self::true(@$arg)) {
                return true;
            }
        }
        return false;
    }

    public static function false_one(...$args)
    {
        if (self::x(@$args[0]) && $args[0] instanceof \Illuminate\Support\Collection) {
            $args[0] = $args[0]->toArray();
        }
        if (self::x(@$args[0]) && is_array($args[0]) && count($args) === 1) {
            $args = $args[0];
        }
        foreach ($args as $arg) {
            if (self::false(@$arg)) {
                return true;
            }
        }
        return false;
    }

    public static function true_all(...$args)
    {
        if (self::x(@$args[0]) && $args[0] instanceof \Illuminate\Support\Collection) {
            $args[0] = $args[0]->toArray();
        }
        if (self::x(@$args[0]) && is_array($args[0]) && count($args) === 1) {
            $args = $args[0];
        }
        foreach ($args as $arg) {
            if (!self::true(@$arg)) {
                return false;
            }
        }
        return true;
    }

    public static function false_all(...$args)
    {
        if (self::x(@$args[0]) && $args[0] instanceof \Illuminate\Support\Collection) {
            $args[0] = $args[0]->toArray();
        }
        if (self::x(@$args[0]) && is_array($args[0]) && count($args) === 1) {
            $args = $args[0];
        }
        foreach ($args as $arg) {
            if (!self::false(@$arg)) {
                return false;
            }
        }
        return true;
    }

    public static function nx_one(...$args)
    {
        return !self::x_one(...@$args);
    }

    public static function true($val)
    {
        if ($val === null) {
            return false;
        }
        if ($val === false) {
            return false;
        }
        if ($val === []) {
            return false;
        }
        if ($val === ['']) {
            return false;
        }
        if ($val === 0) {
            return false;
        }
        if ($val === '0') {
            return false;
        }
        if ($val === '') {
            return false;
        }
        if ($val === ' ') {
            return false;
        }
        if ($val === 'null') {
            return false;
        }
        if ($val === 'false') {
            return false;
        }
        if (is_object($val) && empty((array) $val)) {
            return false;
        }
        if (self::is_serialized($val)) {
            return self::true(unserialize($val));
        }
        if (is_callable($val)) {
            try {
                $current_state = error_reporting();
                error_reporting(0);
                $return = $val();
                error_reporting($current_state);
                return self::true($return);
            } catch (\Error $e) {
                return false;
            }
        }
        return true;
    }

    public static function false($val)
    {
        if ($val === null) {
            return false;
        }
        if ($val === false) {
            return true;
        }
        if ($val === []) {
            return false;
        }
        if ($val === ['']) {
            return false;
        }
        if ($val === 0) {
            return true;
        }
        if ($val === '0') {
            return true;
        }
        if ($val === '') {
            return false;
        }
        if ($val === ' ') {
            return false;
        }
        if ($val === 'null') {
            return false;
        }
        if ($val === 'false') {
            return true;
        }
        if (is_object($val) && empty((array) $val)) {
            return false;
        }
        if (self::is_serialized($val)) {
            return self::false(unserialize($val));
        }
        if (is_callable($val)) {
            try {
                $current_state = error_reporting();
                error_reporting(0);
                $return = $val();
                error_reporting($current_state);
                return self::false($return);
            } catch (\Error $e) {
                return false;
            }
        }
        return false;
    }

    public static function cookie_exists($cookie_name)
    {
        if (self::x(@$_COOKIE[$cookie_name])) {
            return true;
        }
        return false;
    }

    public static function cookie_get($cookie_name)
    {
        $return = null;
        if (!self::cookie_exists(@$cookie_name)) {
            return $return;
        }
        $return = $_COOKIE[$cookie_name];
        $return = stripslashes($return);
        if (self::is_serialized($return)) {
            $return = unserialize($return);
        }
        return $return;
    }

    public static function cookie_set($cookie_name, $cookie_value, $days = 30, $options = [])
    {
        if (self::nx(@$options['secure'])) {
            $options['secure'] = false;
        }
        if (self::nx(@$options['httponly'])) {
            $options['httponly'] = false;
        }
        if (self::nx(@$options['expires'])) {
            $options['expires'] = time() + 60 * 60 * 24 * $days;
        }
        if (self::nx(@$options['path'])) {
            $options['path'] = '/';
        }
        if (self::nx(@$options['domain'])) {
            $options['domain'] = '';
        }
        if (is_array($cookie_value)) {
            $cookie_value = serialize($cookie_value);
        }
        if (PHP_VERSION_ID < 70300) {
            setcookie(
                $cookie_name,
                $cookie_value,
                $options['expires'],
                $options['path'] . (self::x(@$options['samesite']) ? '; samesite=' . $options['samesite'] : ''),
                $options['domain'],
                $options['secure'],
                $options['httponly']
            );
        } else {
            setcookie($cookie_name, $cookie_value, $options);
        }
        // immediately set it for current request
        $_COOKIE[$cookie_name] = $cookie_value;
    }

    public static function cookie_delete($cookie_name)
    {
        unset($_COOKIE[$cookie_name]);
        setcookie($cookie_name, '', time() - 3600, '/');
    }

    public static function strip($string, $length = 50, $dots = '...')
    {
        if (mb_strlen($string) <= $length) {
            return $string;
        }
        return rtrim(mb_substr($string, 0, $length)) . $dots;
    }

    public static function strip_numeric($string)
    {
        return preg_replace('/[0-9,.]/', '', $string);
    }

    public static function strip_nonnumeric($string)
    {
        return preg_replace('/[^0-9,.]/', '', $string);
    }

    public static function strip_digit($string)
    {
        return preg_replace('/[0-9]/', '', $string);
    }

    public static function strip_nondigit($string)
    {
        return preg_replace('/[^0-9]/', '', $string);
    }

    public static function strip_nonchars($string)
    {
        return preg_replace('/[^A-Za-zäÄöÖüÜß ]/', '', $string);
    }

    public static function strip_whitespace($string)
    {
        return preg_replace('/\s+/', '', $string);
    }

    public static function strip_whitespace_collapsed($string)
    {
        if (!is_string($string)) {
            return '';
        }
        return implode(' ', self::remove_empty(explode(' ', $string)));
    }

    public static function strip_tags($string, $tags, $with_content = false)
    {
        if (!is_string($string)) {
            return $string;
        }
        if (self::nx($tags)) {
            return $string;
        }
        if (!is_array($tags)) {
            $tags = [$tags];
        }
        foreach ($tags as $tags__value) {
            $string = preg_replace(
                '/<' . $tags__value . '.*?>(.*)?<\/' . $tags__value . '>/ims',
                $with_content === false ? '$1' : '',
                $string
            );
        }
        return $string;
    }

    public static function split_newline($string)
    {
        return preg_split('/\r\n|\n|\r/', $string);
    }

    public static function split_whitespace($string, $len)
    {
        if (self::nx($string) || !is_string($string) || !is_numeric($len) || $len <= 0) {
            return $string;
        }
        $string = trim($string);
        $string = self::strip_whitespace($string);
        $tmp = array_chunk(preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY), $len);
        $string = '';
        foreach ($tmp as $t) {
            $string .= join('', $t) . ' ';
        }
        $string = trim($string);
        return $string;
    }

    public static function remove_emptylines($string)
    {
        return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", PHP_EOL, $string);
    }

    public static function remove_newlines($string, $replace = '')
    {
        if (!is_string($string)) {
            return $string;
        }
        $string = preg_replace('~[\r\n]+~', $replace, $string); // remove nl
        $string = str_ireplace(['<br/>', '<br />', '<br>'], $replace, $string); // remove brs
        return $string;
    }

    public static function br2nl($string)
    {
        if (!is_string($string)) {
            return $string;
        }
        return preg_replace('/\<br(\s*)?\/?\>/i', PHP_EOL, $string);
    }

    public static function truncate_string($str, $len = 50, $chars = '...')
    {
        if (self::nx($str) || !is_string($str)) {
            return $str;
        }
        if (mb_strlen(self::trim_whitespace($str)) > $len) {
            $str = self::trim_whitespace($str);
            if (mb_strpos($str, ' ') === false) {
                $str = mb_substr($str, 0, $len);
            } else {
                $str = mb_substr($str, 0, $len);
                $str = self::trim_whitespace($str);
                if (mb_strrpos($str, ' ') !== false) {
                    $str = mb_substr($str, 0, mb_strrpos($str, ' '));
                    $str = self::trim_whitespace($str);
                }
            }
            $str .= ' ' . $chars;
        }
        return $str;
    }

    public static function trim_whitespace($str)
    {
        if (self::nx($str) || !is_string($str)) {
            return $str;
        }
        // the u modifier is important here for multibyte support
        // the s modifier makes \s match &nbsp;
        $str = preg_replace('/^([\s]*)(.*?)([\s]*)$/us', '$2', $str);
        return $str;
    }

    public static function trim($str, $arr, $replace = '', $mode = null)
    {
        if (!is_string($str)) {
            return $str;
        }
        if (!is_array($arr)) {
            $arr = [$arr];
        }
        $had_something_to_remove = true;
        while ($had_something_to_remove === true) {
            $had_something_to_remove = false;
            foreach ($arr as $arr__value) {
                if (!is_string($arr__value)) {
                    continue;
                }
                if ($mode === null) {
                    $regex = '/^(' . preg_quote($arr__value, '/') . ')+|(' . preg_quote($arr__value, '/') . ')+$/';
                }
                if ($mode === 'left') {
                    $regex = '/^(' . preg_quote($arr__value, '/') . ')+/';
                }
                if ($mode === 'right') {
                    $regex = '/(' . preg_quote($arr__value, '/') . ')+$/';
                }
                $str_new = preg_replace($regex, $replace, $str);
                if ($str_new !== $str) {
                    $str = $str_new;
                    $had_something_to_remove = true;
                }
            }
        }
        return $str;
    }

    public static function ltrim($str, $arr)
    {
        return self::trim($str, $arr, '', 'left');
    }

    public static function rtrim($str, $arr)
    {
        return self::trim($str, $arr, '', 'right');
    }

    public static function trim_every_line($str)
    {
        if (!is_string($str)) {
            return $str;
        }
        return implode(PHP_EOL, self::atrim(self::split_newline($str)));
    }

    public static function strrev($str)
    {
        if (self::nx($str) || !is_string($str)) {
            return $str;
        }
        $r = '';
        for ($i = self::grapheme_strlen($str); $i >= 0; $i--) {
            $r .= self::grapheme_substr($str, $i, 1);
        }
        return $r;
    }

    public static function grapheme_strlen($s)
    {
        preg_replace(
            '/' .
                '(?:\r\n|(?:[ -~\x{200C}\x{200D}]|[ᆨ-ᇹ]+|[ᄀ-ᅟ]*(?:[가개갸걔거게겨계고과괘괴교구궈궤귀규그긔기까깨꺄꺠꺼께껴꼐꼬꽈꽤꾀꾜꾸꿔꿰뀌뀨끄끠끼나내냐냬너네녀녜노놔놰뇌뇨누눠눼뉘뉴느늬니다대댜댸더데뎌뎨도돠돼되됴두둬뒈뒤듀드듸디따때땨떄떠떼뗘뗴또똬뙈뙤뚀뚜뚸뛔뛰뜌뜨띄띠라래랴럐러레려례로롸뢔뢰료루뤄뤠뤼류르릐리마매먀먜머메며몌모뫄뫠뫼묘무뭐뭬뮈뮤므믜미바배뱌뱨버베벼볘보봐봬뵈뵤부붜붸뷔뷰브븨비빠빼뺘뺴뻐뻬뼈뼤뽀뽜뽸뾔뾰뿌뿨쀄쀠쀼쁘쁴삐사새샤섀서세셔셰소솨쇄쇠쇼수숴쉐쉬슈스싀시싸쌔쌰썌써쎄쎠쎼쏘쏴쐐쐬쑈쑤쒀쒜쒸쓔쓰씌씨아애야얘어에여예오와왜외요우워웨위유으의이자재쟈쟤저제져졔조좌좨죄죠주줘줴쥐쥬즈즤지짜째쨔쨰쩌쩨쪄쪠쪼쫘쫴쬐쬬쭈쭤쮀쮜쮸쯔쯰찌차채챠챼처체쳐쳬초촤쵀최쵸추춰췌취츄츠츼치카캐캬컈커케켜켸코콰쾌쾨쿄쿠쿼퀘퀴큐크킈키타태탸턔터테텨톄토톼퇘퇴툐투퉈퉤튀튜트틔티파패퍄퍠퍼페펴폐포퐈퐤푀표푸풔풰퓌퓨프픠피하해햐햬허헤혀혜호화홰회효후훠훼휘휴흐희히]?[ᅠ-ᆢ]+|[가-힣])[ᆨ-ᇹ]*|[ᄀ-ᅟ]+|[^\p{Cc}\p{Cf}\p{Zl}\p{Zp}])[\p{Mn}\p{Me}\x{09BE}\x{09D7}\x{0B3E}\x{0B57}\x{0BBE}\x{0BD7}\x{0CC2}\x{0CD5}\x{0CD6}\x{0D3E}\x{0D57}\x{0DCF}\x{0DDF}\x{200C}\x{200D}\x{1D165}\x{1D16E}-\x{1D172}]*|[\p{Cc}\p{Cf}\p{Zl}\p{Zp}])' .
                '/u',
            '',
            $s,
            -1,
            $len
        );
        return 0 === $len && '' !== $s ? null : $len;
    }

    public static function grapheme_substr($s, $start, $len = null)
    {
        if (null === $len) {
            $len = 2147483647;
        }
        preg_match_all(
            '/' .
                '(?:\r\n|(?:[ -~\x{200C}\x{200D}]|[ᆨ-ᇹ]+|[ᄀ-ᅟ]*(?:[가개갸걔거게겨계고과괘괴교구궈궤귀규그긔기까깨꺄꺠꺼께껴꼐꼬꽈꽤꾀꾜꾸꿔꿰뀌뀨끄끠끼나내냐냬너네녀녜노놔놰뇌뇨누눠눼뉘뉴느늬니다대댜댸더데뎌뎨도돠돼되됴두둬뒈뒤듀드듸디따때땨떄떠떼뗘뗴또똬뙈뙤뚀뚜뚸뛔뛰뜌뜨띄띠라래랴럐러레려례로롸뢔뢰료루뤄뤠뤼류르릐리마매먀먜머메며몌모뫄뫠뫼묘무뭐뭬뮈뮤므믜미바배뱌뱨버베벼볘보봐봬뵈뵤부붜붸뷔뷰브븨비빠빼뺘뺴뻐뻬뼈뼤뽀뽜뽸뾔뾰뿌뿨쀄쀠쀼쁘쁴삐사새샤섀서세셔셰소솨쇄쇠쇼수숴쉐쉬슈스싀시싸쌔쌰썌써쎄쎠쎼쏘쏴쐐쐬쑈쑤쒀쒜쒸쓔쓰씌씨아애야얘어에여예오와왜외요우워웨위유으의이자재쟈쟤저제져졔조좌좨죄죠주줘줴쥐쥬즈즤지짜째쨔쨰쩌쩨쪄쪠쪼쫘쫴쬐쬬쭈쭤쮀쮜쮸쯔쯰찌차채챠챼처체쳐쳬초촤쵀최쵸추춰췌취츄츠츼치카캐캬컈커케켜켸코콰쾌쾨쿄쿠쿼퀘퀴큐크킈키타태탸턔터테텨톄토톼퇘퇴툐투퉈퉤튀튜트틔티파패퍄퍠퍼페펴폐포퐈퐤푀표푸풔풰퓌퓨프픠피하해햐햬허헤혀혜호화홰회효후훠훼휘휴흐희히]?[ᅠ-ᆢ]+|[가-힣])[ᆨ-ᇹ]*|[ᄀ-ᅟ]+|[^\p{Cc}\p{Cf}\p{Zl}\p{Zp}])[\p{Mn}\p{Me}\x{09BE}\x{09D7}\x{0B3E}\x{0B57}\x{0BBE}\x{0BD7}\x{0CC2}\x{0CD5}\x{0CD6}\x{0D3E}\x{0D57}\x{0DCF}\x{0DDF}\x{200C}\x{200D}\x{1D165}\x{1D16E}-\x{1D172}]*|[\p{Cc}\p{Cf}\p{Zl}\p{Zp}])' .
                '/u',
            $s,
            $s
        );
        $slen = \count($s[0]);
        $start = (int) $start;
        if (0 > $start) {
            $start += $slen;
        }
        if (0 > $start) {
            if (\PHP_VERSION_ID < 80000) {
                return false;
            }
            $start = 0;
        }
        if ($start >= $slen) {
            return \PHP_VERSION_ID >= 80000 ? '' : false;
        }
        $rem = $slen - $start;
        if (0 > $len) {
            $len += $rem;
        }
        if (0 === $len) {
            return '';
        }
        if (0 > $len) {
            return \PHP_VERSION_ID >= 80000 ? '' : false;
        }
        if ($len > $rem) {
            $len = $rem;
        }
        return implode('', \array_slice($s[0], $start, $len));
    }

    public static function atrim($arr)
    {
        if (self::nx($arr) || (!is_array($arr) && !($arr instanceof \Traversable))) {
            return $arr;
        }

        foreach ($arr as $arr__key => $arr__value) {
            $arr[$arr__key] = self::trim_whitespace($arr__value);
        }
        return $arr;
    }

    public static function o(...$data)
    {
        if (!is_array($data)) {
            $data = [$data];
        }
        // prevent html parsing
        array_walk_recursive($data, function (&$data__value) {
            if (is_string($data__value)) {
                $data__value = htmlspecialchars($data__value, ENT_QUOTES | ENT_SUBSTITUTE, 'utf-8');
            }
        });
        foreach ($data as $data__value) {
            echo '<pre>';
            var_dump($data__value);
            echo '</pre>';
        }
    }

    public static function d(...$data)
    {
        self::o(...$data);
        die();
    }

    public static function validate_date($date)
    {
        if (self::nx(@$date)) {
            return false;
        }
        // input timestamp
        if (is_numeric($date)) {
            $date = date('Y-m-d', $date);
        }
        // input datetime object
        if ($date instanceof \DateTime) {
            $date = $date->format('Y-m-d');
        }
        if (substr_count(explode(' ', $date)[0], '-') == 2 || substr_count(explode(' ', $date)[0], '.') == 2) {
            $date = explode(' ', $date)[0];
            if (substr_count($date, '-') == 2) {
                $date = explode('-', $date);
                if (checkdate((int) $date[1], (int) $date[2], (int) $date[0])) {
                    if ($date[0] >= 2037) {
                        return false;
                    } // prevent 32-bit problem
                    return true;
                }
            } elseif (substr_count($date, '.') == 2) {
                $date = explode('.', $date);
                if (checkdate((int) $date[1], (int) $date[0], (int) $date[2])) {
                    if ($date[2] >= 2037) {
                        return false;
                    } // prevent 32-bit problem
                    return true;
                }
            }
        } elseif (strtotime($date) !== false) {
            return true;
        }
        return false;
    }

    public static function anonymize_ip($value = null)
    {
        if ($value === null) {
            $value = @$_SERVER['REMOTE_ADDR'];
        }
        if (self::nx($value)) {
            return $value;
        }
        if (!is_string($value)) {
            return $value;
        }
        return preg_replace(['/\.\d*$/', '/[\da-f]*:[\da-f]*$/'], ['.XXX', 'XXXX:XXXX'], $value);
    }

    public static function password_strength($pwd)
    {
        if (self::nx($pwd)) {
            return 1;
        }

        if (strlen($pwd) < 8) {
            return 1;
        }

        if (!preg_match('/[0-9]+/', $pwd)) {
            return 1;
        }

        if (!preg_match('/[a-zA-Z]+/', $pwd)) {
            return 1;
        }

        if (!preg_match('/[\(\)\[\]\{\}\?\!\$\%\&\/\=\*\+\~\,\.\;\:\<\>\-\_]+/', $pwd)) {
            return 2;
        }

        if (strlen($pwd) < 12) {
            return 2;
        }

        return 3;
    }

    public static function distance_haversine($p1, $p2)
    {
        if (
            self::nx($p1) ||
            self::nx($p2) ||
            !is_array($p1) ||
            !is_array($p2) ||
            count($p1) !== 2 ||
            count($p2) !== 2 ||
            !is_numeric($p1[0]) ||
            !is_numeric($p1[0]) ||
            !is_numeric($p2[1]) ||
            !is_numeric($p2[1])
        ) {
            return null;
        }
        [$latitudeFrom, $longitudeFrom] = $p1;
        [$latitudeTo, $longitudeTo] = $p2;
        $earthRadius = 6371000;
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        $dist = $angle * $earthRadius;
        $dist = round($dist);
        $dist = intval($dist);
        return $dist;
    }

    public static function validate_url($value)
    {
        if (self::nx(@$value)) {
            return false;
        }
        $value = mb_strtolower($value);
        $value = str_replace(['ä', 'ö', 'ü'], ['ae', 'oe', 'ue'], $value);
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        return true;
    }

    public static function validate_email($value)
    {
        if (self::nx(@$value)) {
            return false;
        }
        if (!is_string($value)) {
            return false;
        }
        $value = mb_strtolower($value);
        $value = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $value);
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }
        return true;
    }

    public static function email_tokenize_str2arr($str)
    {
        $arr = [];
        $parts = explode(';', $str);
        foreach ($parts as $parts__value) {
            $sep = '<';
            $parts2 = explode($sep, trim($parts__value));

            $email = trim($parts2[0]);
            $name = null;
            if (count($parts2) > 1) {
                unset($parts2[0]);
                $parts2 = array_values($parts2);
                $parts2 = implode($sep, $parts2);
                $parts2 = trim($parts2);
                if (mb_strpos($parts2, '<') === 0) {
                    $parts2 = mb_substr($parts2, 1);
                }
                if (mb_strpos($parts2, '>') === mb_strlen($parts2) - 1) {
                    $parts2 = mb_substr($parts2, 0, -1);
                }
                $name = $email;
                $email = $parts2;
            }
            // also accept wrong order
            if (self::validate_email($name) && !self::validate_email($email)) {
                [$name, $email] = [$email, $name];
            }
            $arr[] = ['email' => $email, 'name' => $name];
        }
        return $arr;
    }

    public static function email_tokenize_arr2str($arr)
    {
        $str = [];
        if (self::nx($arr) || !is_array($arr) || empty($arr)) {
            return '';
        }
        if (array_key_exists('email', $arr)) {
            $arr = [$arr];
        }
        foreach ($arr as $arr__value) {
            $str_this = [];
            if (
                self::nx($arr__value) ||
                !is_array($arr__value) ||
                empty($arr__value) ||
                !array_key_exists('email', $arr__value)
            ) {
                continue;
            }
            if (array_key_exists('name', $arr__value) && self::x($arr__value['name'])) {
                $str_this[] = trim($arr__value['name']);
                $str_this[] = '<' . trim($arr__value['email']) . '>';
            } else {
                $str_this[] = trim($arr__value['email']);
            }

            $str[] = implode(' ', $str_this);
        }
        $str = implode('; ', $str);
        return $str;
    }

    public static function phone_tokenize($value)
    {
        $return = [
            'country_code' => '',
            'area_code' => '',
            'number' => ''
        ];

        if (self::nx($value)) {
            return $return;
        }

        $value = self::strip_nondigit($value);

        if (mb_strlen($value) <= 3) {
            $return['number'] = $value;
            return $return;
        }

        foreach (self::phone_country_codes() as $phone_country_codes__value) {
            if (strpos($value, $phone_country_codes__value) === 0) {
                $shift = 0;
                if (strpos($value, $phone_country_codes__value . '00') === 0) {
                    $shift = 2;
                } elseif (strpos($value, $phone_country_codes__value . '0') === 0) {
                    $shift = 1;
                }
                $return['country_code'] = $phone_country_codes__value;
                $value = substr($value, strlen($phone_country_codes__value) + $shift);
                break;
            }
        }
        if (self::nx($return['country_code'])) {
            $return['country_code'] = '49';
            if (strpos($value, '0') === 0) {
                $value = substr($value, 1);
            }
        }

        foreach (self::phone_area_codes() as $area_codes__value) {
            if (strpos($value, $area_codes__value) === 0) {
                $return['area_code'] = $area_codes__value;
                $value = substr($value, strlen($area_codes__value));
                break;
            }
        }
        $return['number'] = $value;

        return $return;
    }

    public static function phone_normalize($value)
    {
        $return = [];
        $value = self::phone_tokenize($value);
        if (self::x($value['country_code'])) {
            $return[] = '+' . $value['country_code'];
        }
        if (self::x($value['area_code'])) {
            $return[] = $value['area_code'];
        }
        if (self::x($value['number'])) {
            $return[] = $value['number'];
        }
        return implode(' ', $return);
    }

    public static function phone_country_codes()
    {
        return json_decode(
            '["880","32","226","359","387","+1-246","681","590","+1-441","673","591","973","257","229","975","+1-876","267","685","599","55","+1-242","+44-1534","375","501","7","250","381","670","262","993","992","40","690","245","+1-671","502","30","240","590","81","592","+44-1481","594","995","+1-473","44","241","503","224","220","299","350","233","968","216","962","385","509","36","852","504"," ","58","+1-787 and 1-939","970","680","351","47","595","964","507","689","675","51","92","63","870","48","508","260","212","372","20","27","593","39","84","677","251","252","263","966","34","291","382","373","261","590","212","377","998","95","223","853","976","692","389","230","356","265","960","596","+1-670","+1-664","222","+44-1624","256","255","60","52","972","33","246","290","358","679","500","691","298","505","31","47","264","678","687","227","672","234","64","977","674","683","682","225","41","57","86","237","56","61","1","242","236","243","420","357","61","506","599","238","53","268","963","599","996","254","211","597","686","855","+1-869","269","239","421","82","386","850","965","221","378","232","248","7","+1-345","65","46","249","+1-809 and 1-829","+1-767","253","45","+1-284","49","967","213","1","598","262","1","961","+1-758","856","688","886","+1-868","90","94","423","371","676","370","352","231","266","66","228","235","+1-649","218","379","+1-784","971","376","+1-268","93","+1-264","+1-340","354","98","374","355","244","+1-684","54","61","43","297","91","+358-18","994","353","62","380","974","258"]'
        );
    }

    public static function phone_area_codes()
    {
        return array_merge(self::phone_area_codes_landline(), self::phone_area_codes_mobile());
    }

    public static function phone_area_codes_landline()
    {
        return json_decode(
            '["201", "202", "203", "2041", "2043", "2045", "2051", "2052", "2053", "2054", "2056", "2058", "2064", "2065", "2066", "208", "209", "2102", "2103", "2104", "211", "212", "2129", "2131", "2132", "2133", "2137", "214", "2150", "2151", "2152", "2153", "2154", "2156", "2157", "2158", "2159", "2161", "2162", "2163", "2164", "2165", "2166", "2171", "2173", "2174", "2175", "2181", "2182", "2183", "2191", "2192", "2193", "2195", "2196", "2202", "2203", "2204", "2205", "2206", "2207", "2208", "221", "2222", "2223", "2224", "2225", "2226", "2227", "2228", "2232", "2233", "2234", "2235", "2236", "2237", "2238", "2241", "2242", "2243", "2244", "2245", "2246", "2247", "2248", "2251", "2252", "2253", "2254", "2255", "2256", "2257", "2261", "2262", "2263", "2264", "2265", "2266", "2267", "2268", "2269", "2271", "2272", "2273", "2274", "2275", "228", "2291", "2292", "2293", "2294", "2295", "2296", "2297", "2301", "2302", "2303", "2304", "2305", "2306", "2307", "2308", "2309", "231", "2323", "2324", "2325", "2327", "2330", "2331", "2332", "2333", "2334", "2335", "2336", "2337", "2338", "2339", "234", "2351", "2352", "2353", "2354", "2355", "2357", "2358", "2359", "2360", "2361", "2362", "2363", "2364", "2365", "2366", "2367", "2368", "2369", "2371", "2372", "2373", "2374", "2375", "2377", "2378", "2379", "2381", "2382", "2383", "2384", "2385", "2387", "2388", "2389", "2391", "2392", "2393", "2394", "2395", "2401", "2402", "2403", "2404", "2405", "2406", "2407", "2408", "2409", "241", "2421", "2422", "2423", "2424", "2425", "2426", "2427", "2428", "2429", "2431", "2432", "2433", "2434", "2435", "2436", "2440", "2441", "2443", "2444", "2445", "2446", "2447", "2448", "2449", "2451", "2452", "2453", "2454", "2455", "2456", "2461", "2462", "2463", "2464", "2465", "2471", "2472", "2473", "2474", "2482", "2484", "2485", "2486", "2501", "2502", "2504", "2505", "2506", "2507", "2508", "2509", "251", "2520", "2521", "2522", "2523", "2524", "2525", "2526", "2527", "2528", "2529", "2532", "2533", "2534", "2535", "2536", "2538", "2541", "2542", "2543", "2545", "2546", "2547", "2548", "2551", "2552", "2553", "2554", "2555", "2556", "2557", "2558", "2561", "2562", "2563", "2564", "2565", "2566", "2567", "2568", "2571", "2572", "2573", "2574", "2575", "2581", "2582", "2583", "2584", "2585", "2586", "2587", "2588", "2590", "2591", "2592", "2593", "2594", "2595", "2596", "2597", "2598", "2599", "2601", "2602", "2603", "2604", "2605", "2606", "2607", "2608", "261", "2620", "2621", "2622", "2623", "2624", "2625", "2626", "2627", "2628", "2630", "2631", "2632", "2633", "2634", "2635", "2636", "2637", "2638", "2639", "2641", "2642", "2643", "2644", "2645", "2646", "2647", "2651", "2652", "2653", "2654", "2655", "2656", "2657", "2661", "2662", "2663", "2664", "2666", "2667", "2671", "2672", "2673", "2674", "2675", "2676", "2677", "2678", "2680", "2681", "2682", "2683", "2684", "2685", "2686", "2687", "2688", "2689", "2691", "2692", "2693", "2694", "2695", "2696", "2697", "271", "2721", "2722", "2723", "2724", "2725", "2732", "2733", "2734", "2735", "2736", "2737", "2738", "2739", "2741", "2742", "2743", "2744", "2745", "2747", "2750", "2751", "2752", "2753", "2754", "2755", "2758", "2759", "2761", "2762", "2763", "2764", "2770", "2771", "2772", "2773", "2774", "2775", "2776", "2777", "2778", "2779", "2801", "2802", "2803", "2804", "281", "2821", "2822", "2823", "2824", "2825", "2826", "2827", "2828", "2831", "2832", "2833", "2834", "2835", "2836", "2837", "2838", "2839", "2841", "2842", "2843", "2844", "2845", "2850", "2851", "2852", "2853", "2855", "2856", "2857", "2858", "2859", "2861", "2862", "2863", "2864", "2865", "2866", "2867", "2871", "2872", "2873", "2874", "2902", "2903", "2904", "2905", "291", "2921", "2922", "2923", "2924", "2925", "2927", "2928", "2931", "2932", "2933", "2934", "2935", "2937", "2938", "2941", "2942", "2943", "2944", "2945", "2947", "2948", "2951", "2952", "2953", "2954", "2955", "2957", "2958", "2961", "2962", "2963", "2964", "2971", "2972", "2973", "2974", "2975", "2977", "2981", "2982", "2983", "2984", "2985", "2991", "2992", "2993", "2994", "30", "3301", "3302", "3303", "3304", "33051", "33052", "33053", "33054", "33055", "33056", "3306", "3307", "33080", "33082", "33083", "33084", "33085", "33086", "33087", "33088", "33089", "33093", "33094", "331", "33200", "33201", "33202", "33203", "33204", "33205", "33206", "33207", "33208", "33209", "3321", "3322", "33230", "33231", "33232", "33233", "33234", "33235", "33237", "33238", "33239", "3327", "3328", "3329", "3331", "3332", "33331", "33332", "33333", "33334", "33335", "33336", "33337", "33338", "3334", "3335", "33361", "33362", "33363", "33364", "33365", "33366", "33367", "33368", "33369", "3337", "3338", "33393", "33394", "33395", "33396", "33397", "33398", "3341", "3342", "33432", "33433", "33434", "33435", "33436", "33437", "33438", "33439", "3344", "33451", "33452", "33454", "33456", "33457", "33458", "3346", "33470", "33472", "33473", "33474", "33475", "33476", "33477", "33478", "33479", "335", "33601", "33602", "33603", "33604", "33605", "33606", "33607", "33608", "33609", "3361", "3362", "33631", "33632", "33633", "33634", "33635", "33636", "33637", "33638", "3364", "33652", "33653", "33654", "33655", "33656", "33657", "3366", "33671", "33672", "33673", "33674", "33675", "33676", "33677", "33678", "33679", "33701", "33702", "33703", "33704", "33708", "3371", "3372", "33731", "33732", "33733", "33734", "33741", "33742", "33743", "33744", "33745", "33746", "33747", "33748", "3375", "33760", "33762", "33763", "33764", "33765", "33766", "33767", "33768", "33769", "3377", "3378", "3379", "3381", "3382", "33830", "33831", "33832", "33833", "33834", "33835", "33836", "33837", "33838", "33839", "33841", "33843", "33844", "33845", "33846", "33847", "33848", "33849", "3385", "3386", "33870", "33872", "33873", "33874", "33875", "33876", "33877", "33878", "3391", "33920", "33921", "33922", "33923", "33924", "33925", "33926", "33927", "33928", "33929", "33931", "33932", "33933", "3394", "3395", "33962", "33963", "33964", "33965", "33966", "33967", "33968", "33969", "33970", "33971", "33972", "33973", "33974", "33975", "33976", "33977", "33978", "33979", "33981", "33982", "33983", "33984", "33986", "33989", "340", "341", "34202", "34203", "34204", "34205", "34206", "34207", "34208", "3421", "34221", "34222", "34223", "34224", "3423", "34241", "34242", "34243", "34244", "3425", "34261", "34262", "34263", "34291", "34292", "34293", "34294", "34295", "34296", "34297", "34298", "34299", "3431", "34321", "34322", "34324", "34325", "34327", "34328", "3433", "34341", "34342", "34343", "34344", "34345", "34346", "34347", "34348", "3435", "34361", "34362", "34363", "34364", "3437", "34381", "34382", "34383", "34384", "34385", "34386", "3441", "34422", "34423", "34424", "34425", "34426", "3443", "34441", "34443", "34444", "34445", "34446", "3445", "34461", "34462", "34463", "34464", "34465", "34466", "34467", "3447", "3448", "34491", "34492", "34493", "34494", "34495", "34496", "34497", "34498", "345", "34600", "34601", "34602", "34603", "34604", "34605", "34606", "34607", "34609", "3461", "3462", "34632", "34633", "34635", "34636", "34637", "34638", "34639", "3464", "34651", "34652", "34653", "34654", "34656", "34658", "34659", "3466", "34671", "34672", "34673", "34691", "34692", "3471", "34721", "34722", "3473", "34741", "34742", "34743", "34745", "34746", "3475", "3476", "34771", "34772", "34773", "34774", "34775", "34776", "34779", "34781", "34782", "34783", "34785", "34901", "34903", "34904", "34905", "34906", "34907", "34909", "3491", "34920", "34921", "34922", "34923", "34924", "34925", "34926", "34927", "34928", "34929", "3493", "3494", "34953", "34954", "34955", "34956", "3496", "34973", "34975", "34976", "34977", "34978", "34979", "3501", "35020", "35021", "35022", "35023", "35024", "35025", "35026", "35027", "35028", "35032", "35033", "3504", "35052", "35053", "35054", "35055", "35056", "35057", "35058", "351", "35200", "35201", "35202", "35203", "35204", "35205", "35206", "35207", "35208", "35209", "3521", "3522", "3523", "35240", "35241", "35242", "35243", "35244", "35245", "35246", "35247", "35248", "35249", "3525", "35263", "35264", "35265", "35266", "35267", "35268", "3528", "3529", "3531", "35322", "35323", "35324", "35325", "35326", "35327", "35329", "3533", "35341", "35342", "35343", "3535", "35361", "35362", "35363", "35364", "35365", "3537", "35383", "35384", "35385", "35386", "35387", "35388", "35389", "3541", "3542", "35433", "35434", "35435", "35436", "35439", "3544", "35451", "35452", "35453", "35454", "35455", "35456", "3546", "35471", "35472", "35473", "35474", "35475", "35476", "35477", "35478", "355", "35600", "35601", "35602", "35603", "35604", "35605", "35606", "35607", "35608", "35609", "3561", "3562", "3563", "3564", "35691", "35692", "35693", "35694", "35695", "35696", "35697", "35698", "3571", "35722", "35723", "35724", "35725", "35726", "35727", "35728", "3573", "3574", "35751", "35752", "35753", "35754", "35755", "35756", "3576", "35771", "35772", "35773", "35774", "35775", "3578", "35792", "35793", "35795", "35796", "35797", "3581", "35820", "35822", "35823", "35825", "35826", "35827", "35828", "35829", "3583", "35841", "35842", "35843", "35844", "3585", "3586", "35872", "35873", "35874", "35875", "35876", "35877", "3588", "35891", "35892", "35893", "35894", "35895", "3591", "3592", "35930", "35931", "35932", "35933", "35934", "35935", "35936", "35937", "35938", "35939", "3594", "35951", "35952", "35953", "35954", "35955", "3596", "35971", "35973", "35974", "35975", "3601", "36020", "36021", "36022", "36023", "36024", "36025", "36026", "36027", "36028", "36029", "3603", "36041", "36042", "36043", "3605", "3606", "36071", "36072", "36074", "36075", "36076", "36077", "36081", "36082", "36083", "36084", "36085", "36087", "361", "36200", "36201", "36202", "36203", "36204", "36205", "36206", "36207", "36208", "36209", "3621", "3622", "3623", "3624", "36252", "36253", "36254", "36255", "36256", "36257", "36258", "36259", "3628", "3629", "3631", "3632", "36330", "36331", "36332", "36333", "36334", "36335", "36336", "36337", "36338", "3634", "3635", "3636", "36370", "36371", "36372", "36373", "36374", "36375", "36376", "36377", "36378", "36379", "3641", "36421", "36422", "36423", "36424", "36425", "36426", "36427", "36428", "3643", "3644", "36450", "36451", "36452", "36453", "36454", "36458", "36459", "36461", "36462", "36463", "36464", "36465", "3647", "36481", "36482", "36483", "36484", "365", "36601", "36602", "36603", "36604", "36605", "36606", "36607", "36608", "3661", "36621", "36622", "36623", "36624", "36625", "36626", "36628", "3663", "36640", "36642", "36643", "36644", "36645", "36646", "36647", "36648", "36649", "36651", "36652", "36653", "36691", "36692", "36693", "36694", "36695", "36701", "36702", "36703", "36704", "36705", "3671", "3672", "36730", "36731", "36732", "36733", "36734", "36735", "36736", "36737", "36738", "36739", "36741", "36742", "36743", "36744", "3675", "36761", "36762", "36764", "36766", "3677", "36781", "36782", "36783", "36784", "36785", "3679", "3681", "3682", "3683", "36840", "36841", "36842", "36843", "36844", "36845", "36846", "36847", "36848", "36849", "3685", "3686", "36870", "36871", "36873", "36874", "36875", "36878", "3691", "36920", "36921", "36922", "36923", "36924", "36925", "36926", "36927", "36928", "36929", "3693", "36940", "36941", "36943", "36944", "36945", "36946", "36947", "36948", "36949", "3695", "36961", "36962", "36963", "36964", "36965", "36966", "36967", "36968", "36969", "371", "37200", "37202", "37203", "37204", "37206", "37207", "37208", "37209", "3721", "3722", "3723", "3724", "3725", "3726", "3727", "37291", "37292", "37293", "37294", "37295", "37296", "37297", "37298", "3731", "37320", "37321", "37322", "37323", "37324", "37325", "37326", "37327", "37328", "37329", "3733", "37341", "37342", "37343", "37344", "37346", "37347", "37348", "37349", "3735", "37360", "37361", "37362", "37363", "37364", "37365", "37366", "37367", "37368", "37369", "3737", "37381", "37382", "37383", "37384", "3741", "37421", "37422", "37423", "37430", "37431", "37432", "37433", "37434", "37435", "37436", "37437", "37438", "37439", "3744", "3745", "37462", "37463", "37464", "37465", "37467", "37468", "375", "37600", "37601", "37602", "37603", "37604", "37605", "37606", "37607", "37608", "37609", "3761", "3762", "3763", "3764", "3765", "3771", "3772", "3773", "3774", "37752", "37754", "37755", "37756", "37757", "381", "38201", "38202", "38203", "38204", "38205", "38206", "38207", "38208", "38209", "3821", "38220", "38221", "38222", "38223", "38224", "38225", "38226", "38227", "38228", "38229", "38231", "38232", "38233", "38234", "38292", "38293", "38294", "38295", "38296", "38297", "38300", "38301", "38302", "38303", "38304", "38305", "38306", "38307", "38308", "38309", "3831", "38320", "38321", "38322", "38323", "38324", "38325", "38326", "38327", "38328", "38331", "38332", "38333", "38334", "3834", "38351", "38352", "38353", "38354", "38355", "38356", "3836", "38370", "38371", "38372", "38373", "38374", "38375", "38376", "38377", "38378", "38379", "3838", "38391", "38392", "38393", "3841", "38422", "38423", "38424", "38425", "38426", "38427", "38428", "38429", "3843", "3844", "38450", "38451", "38452", "38453", "38454", "38455", "38456", "38457", "38458", "38459", "38461", "38462", "38464", "38466", "3847", "38481", "38482", "38483", "38484", "38485", "38486", "38488", "385", "3860", "3861", "3863", "3865", "3866", "3867", "3868", "3869", "3871", "38720", "38721", "38722", "38723", "38724", "38725", "38726", "38727", "38728", "38729", "38731", "38732", "38733", "38735", "38736", "38737", "38738", "3874", "38750", "38751", "38752", "38753", "38754", "38755", "38756", "38757", "38758", "38759", "3876", "3877", "38780", "38781", "38782", "38783", "38784", "38785", "38787", "38788", "38789", "38791", "38792", "38793", "38794", "38796", "38797", "3881", "38821", "38822", "38823", "38824", "38825", "38826", "38827", "38828", "3883", "38841", "38842", "38843", "38844", "38845", "38847", "38848", "38850", "38851", "38852", "38853", "38854", "38855", "38856", "38858", "38859", "3886", "38871", "38872", "38873", "38874", "38875", "38876", "39000", "39001", "39002", "39003", "39004", "39005", "39006", "39007", "39008", "39009", "3901", "3902", "39030", "39031", "39032", "39033", "39034", "39035", "39036", "39037", "39038", "39039", "3904", "39050", "39051", "39052", "39053", "39054", "39055", "39056", "39057", "39058", "39059", "39061", "39062", "3907", "39080", "39081", "39082", "39083", "39084", "39085", "39086", "39087", "39088", "39089", "3909", "391", "39200", "39201", "39202", "39203", "39204", "39205", "39206", "39207", "39208", "39209", "3921", "39221", "39222", "39223", "39224", "39225", "39226", "3923", "39241", "39242", "39243", "39244", "39245", "39246", "39247", "39248", "3925", "39262", "39263", "39264", "39265", "39266", "39267", "39268", "3928", "39291", "39292", "39293", "39294", "39295", "39296", "39297", "39298", "3931", "39320", "39321", "39322", "39323", "39324", "39325", "39327", "39328", "39329", "3933", "39341", "39342", "39343", "39344", "39345", "39346", "39347", "39348", "39349", "3935", "39361", "39362", "39363", "39364", "39365", "39366", "3937", "39382", "39383", "39384", "39386", "39387", "39388", "39389", "39390", "39391", "39392", "39393", "39394", "39395", "39396", "39397", "39398", "39399", "39400", "39401", "39402", "39403", "39404", "39405", "39406", "39407", "39408", "39409", "3941", "39421", "39422", "39423", "39424", "39425", "39426", "39427", "39428", "3943", "3944", "39451", "39452", "39453", "39454", "39455", "39456", "39457", "39458", "39459", "3946", "3947", "39481", "39482", "39483", "39484", "39485", "39487", "39488", "39489", "3949", "395", "39600", "39601", "39602", "39603", "39604", "39605", "39606", "39607", "39608", "3961", "3962", "3963", "3964", "3965", "3966", "3967", "3968", "3969", "3971", "39721", "39722", "39723", "39724", "39726", "39727", "39728", "3973", "39740", "39741", "39742", "39743", "39744", "39745", "39746", "39747", "39748", "39749", "39751", "39752", "39753", "39754", "3976", "39771", "39772", "39773", "39774", "39775", "39776", "39777", "39778", "39779", "3981", "39820", "39821", "39822", "39823", "39824", "39825", "39826", "39827", "39828", "39829", "39831", "39832", "39833", "3984", "39851", "39852", "39853", "39854", "39855", "39856", "39857", "39858", "39859", "39861", "39862", "39863", "3987", "39881", "39882", "39883", "39884", "39885", "39886", "39887", "39888", "39889", "3991", "39921", "39922", "39923", "39924", "39925", "39926", "39927", "39928", "39929", "39931", "39932", "39933", "39934", "3994", "39951", "39952", "39953", "39954", "39955", "39956", "39957", "39959", "3996", "39971", "39972", "39973", "39975", "39976", "39977", "39978", "3998", "39991", "39992", "39993", "39994", "39995", "39996", "39997", "39998", "39999", "40", "4101", "4102", "4103", "4104", "4105", "4106", "4107", "4108", "4109", "4120", "4121", "4122", "4123", "4124", "4125", "4126", "4127", "4128", "4129", "4131", "4132", "4133", "4134", "4135", "4136", "4137", "4138", "4139", "4140", "4141", "4142", "4143", "4144", "4146", "4148", "4149", "4151", "4152", "4153", "4154", "4155", "4156", "4158", "4159", "4161", "4162", "4163", "4164", "4165", "4166", "4167", "4168", "4169", "4171", "4172", "4173", "4174", "4175", "4176", "4177", "4178", "4179", "4180", "4181", "4182", "4183", "4184", "4185", "4186", "4187", "4188", "4189", "4191", "4192", "4193", "4194", "4195", "4202", "4203", "4204", "4205", "4206", "4207", "4208", "4209", "421", "4221", "4222", "4223", "4224", "4230", "4231", "4232", "4233", "4234", "4235", "4236", "4237", "4238", "4239", "4240", "4241", "4242", "4243", "4244", "4245", "4246", "4247", "4248", "4249", "4251", "4252", "4253", "4254", "4255", "4256", "4257", "4258", "4260", "4261", "4262", "4263", "4264", "4265", "4266", "4267", "4268", "4269", "4271", "4272", "4273", "4274", "4275", "4276", "4277", "4281", "4282", "4283", "4284", "4285", "4286", "4287", "4288", "4289", "4292", "4293", "4294", "4295", "4296", "4297", "4298", "4302", "4303", "4305", "4307", "4308", "431", "4320", "4321", "4322", "4323", "4324", "4326", "4327", "4328", "4329", "4330", "4331", "4332", "4333", "4334", "4335", "4336", "4337", "4338", "4339", "4340", "4342", "4343", "4344", "4346", "4347", "4348", "4349", "4351", "4352", "4353", "4354", "4355", "4356", "4357", "4358", "4361", "4362", "4363", "4364", "4365", "4366", "4367", "4371", "4372", "4381", "4382", "4383", "4384", "4385", "4392", "4393", "4394", "4401", "4402", "4403", "4404", "4405", "4406", "4407", "4408", "4409", "441", "4421", "4422", "4423", "4425", "4426", "4431", "4432", "4433", "4434", "4435", "4441", "4442", "4443", "4444", "4445", "4446", "4447", "4451", "4452", "4453", "4454", "4455", "4456", "4458", "4461", "4462", "4463", "4464", "4465", "4466", "4467", "4468", "4469", "4471", "4472", "4473", "4474", "4475", "4477", "4478", "4479", "4480", "4481", "4482", "4483", "4484", "4485", "4486", "4487", "4488", "4489", "4491", "4492", "4493", "4494", "4495", "4496", "4497", "4498", "4499", "4501", "4502", "4503", "4504", "4505", "4506", "4508", "4509", "451", "4521", "4522", "4523", "4524", "4525", "4526", "4527", "4528", "4529", "4531", "4532", "4533", "4534", "4535", "4536", "4537", "4539", "4541", "4542", "4543", "4544", "4545", "4546", "4547", "4550", "4551", "4552", "4553", "4554", "4555", "4556", "4557", "4558", "4559", "4561", "4562", "4563", "4564", "4602", "4603", "4604", "4605", "4606", "4607", "4608", "4609", "461", "4621", "4622", "4623", "4624", "4625", "4626", "4627", "4630", "4631", "4632", "4633", "4634", "4635", "4636", "4637", "4638", "4639", "4641", "4642", "4643", "4644", "4646", "4651", "4661", "4662", "4663", "4664", "4665", "4666", "4667", "4668", "4671", "4672", "4673", "4674", "4681", "4682", "4683", "4684", "4702", "4703", "4704", "4705", "4706", "4707", "4708", "471", "4721", "4722", "4723", "4724", "4725", "4731", "4732", "4733", "4734", "4735", "4736", "4737", "4740", "4741", "4742", "4743", "4744", "4745", "4746", "4747", "4748", "4749", "4751", "4752", "4753", "4754", "4755", "4756", "4757", "4758", "4761", "4762", "4763", "4764", "4765", "4766", "4767", "4768", "4769", "4770", "4771", "4772", "4773", "4774", "4775", "4776", "4777", "4778", "4779", "4791", "4792", "4793", "4794", "4795", "4796", "4802", "4803", "4804", "4805", "4806", "481", "4821", "4822", "4823", "4824", "4825", "4826", "4827", "4828", "4829", "4830", "4832", "4833", "4834", "4835", "4836", "4837", "4838", "4839", "4841", "4842", "4843", "4844", "4845", "4846", "4847", "4848", "4849", "4851", "4852", "4853", "4854", "4855", "4856", "4857", "4858", "4859", "4861", "4862", "4863", "4864", "4865", "4871", "4872", "4873", "4874", "4875", "4876", "4877", "4881", "4882", "4883", "4884", "4885", "4892", "4893", "4902", "4903", "491", "4920", "4921", "4922", "4923", "4924", "4925", "4926", "4927", "4928", "4929", "4931", "4932", "4933", "4934", "4935", "4936", "4938", "4939", "4941", "4942", "4943", "4944", "4945", "4946", "4947", "4948", "4950", "4951", "4952", "4953", "4954", "4955", "4956", "4957", "4958", "4959", "4961", "4962", "4963", "4964", "4965", "4966", "4967", "4968", "4971", "4972", "4973", "4974", "4975", "4976", "4977", "5021", "5022", "5023", "5024", "5025", "5026", "5027", "5028", "5031", "5032", "5033", "5034", "5035", "5036", "5037", "5041", "5042", "5043", "5044", "5045", "5051", "5052", "5053", "5054", "5055", "5056", "5060", "5062", "5063", "5064", "5065", "5066", "5067", "5068", "5069", "5071", "5072", "5073", "5074", "5082", "5083", "5084", "5085", "5086", "5101", "5102", "5103", "5105", "5108", "5109", "511", "5121", "5123", "5126", "5127", "5128", "5129", "5130", "5131", "5132", "5135", "5136", "5137", "5138", "5139", "5141", "5142", "5143", "5144", "5145", "5146", "5147", "5148", "5149", "5151", "5152", "5153", "5154", "5155", "5156", "5157", "5158", "5159", "5161", "5162", "5163", "5164", "5165", "5166", "5167", "5168", "5171", "5172", "5173", "5174", "5175", "5176", "5177", "5181", "5182", "5183", "5184", "5185", "5186", "5187", "5190", "5191", "5192", "5193", "5194", "5195", "5196", "5197", "5198", "5199", "5201", "5202", "5203", "5204", "5205", "5206", "5207", "5208", "5209", "521", "5221", "5222", "5223", "5224", "5225", "5226", "5228", "5231", "5232", "5233", "5234", "5235", "5236", "5237", "5238", "5241", "5242", "5244", "5245", "5246", "5247", "5248", "5250", "5251", "5252", "5253", "5254", "5255", "5257", "5258", "5259", "5261", "5262", "5263", "5264", "5265", "5266", "5271", "5272", "5273", "5274", "5275", "5276", "5277", "5278", "5281", "5282", "5283", "5284", "5285", "5286", "5292", "5293", "5294", "5295", "5300", "5301", "5302", "5303", "5304", "5305", "5306", "5307", "5308", "5309", "531", "5320", "5321", "5322", "5323", "5324", "5325", "5326", "5327", "5328", "5329", "5331", "5332", "5333", "5334", "5335", "5336", "5337", "5339", "5341", "5344", "5345", "5346", "5347", "5351", "5352", "5353", "5354", "5355", "5356", "5357", "5358", "5361", "5362", "5363", "5364", "5365", "5366", "5367", "5368", "5371", "5372", "5373", "5374", "5375", "5376", "5377", "5378", "5379", "5381", "5382", "5383", "5384", "5401", "5402", "5403", "5404", "5405", "5406", "5407", "5409", "541", "5421", "5422", "5423", "5424", "5425", "5426", "5427", "5428", "5429", "5431", "5432", "5433", "5434", "5435", "5436", "5437", "5438", "5439", "5441", "5442", "5443", "5444", "5445", "5446", "5447", "5448", "5451", "5452", "5453", "5454", "5455", "5456", "5457", "5458", "5459", "5461", "5462", "5464", "5465", "5466", "5467", "5468", "5471", "5472", "5473", "5474", "5475", "5476", "5481", "5482", "5483", "5484", "5485", "5491", "5492", "5493", "5494", "5495", "5502", "5503", "5504", "5505", "5506", "5507", "5508", "5509", "551", "5520", "5521", "5522", "5523", "5524", "5525", "5527", "5528", "5529", "5531", "5532", "5533", "5534", "5535", "5536", "5541", "5542", "5543", "5544", "5545", "5546", "5551", "5552", "5553", "5554", "5555", "5556", "5561", "5562", "5563", "5564", "5565", "5571", "5572", "5573", "5574", "5582", "5583", "5584", "5585", "5586", "5592", "5593", "5594", "5601", "5602", "5603", "5604", "5605", "5606", "5607", "5608", "5609", "561", "5621", "5622", "5623", "5624", "5625", "5626", "5631", "5632", "5633", "5634", "5635", "5636", "5641", "5642", "5643", "5644", "5645", "5646", "5647", "5648", "5650", "5651", "5652", "5653", "5654", "5655", "5656", "5657", "5658", "5659", "5661", "5662", "5663", "5664", "5665", "5671", "5672", "5673", "5674", "5675", "5676", "5677", "5681", "5682", "5683", "5684", "5685", "5686", "5691", "5692", "5693", "5694", "5695", "5696", "5702", "5703", "5704", "5705", "5706", "5707", "571", "5721", "5722", "5723", "5724", "5725", "5726", "5731", "5732", "5733", "5734", "5741", "5742", "5743", "5744", "5745", "5746", "5751", "5752", "5753", "5754", "5755", "5761", "5763", "5764", "5765", "5766", "5767", "5768", "5769", "5771", "5772", "5773", "5774", "5775", "5776", "5777", "5802", "5803", "5804", "5805", "5806", "5807", "5808", "581", "5820", "5821", "5822", "5823", "5824", "5825", "5826", "5827", "5828", "5829", "5831", "5832", "5833", "5834", "5835", "5836", "5837", "5838", "5839", "5840", "5841", "5842", "5843", "5844", "5845", "5846", "5848", "5849", "5850", "5851", "5852", "5853", "5854", "5855", "5857", "5858", "5859", "5861", "5862", "5863", "5864", "5865", "5872", "5873", "5874", "5875", "5882", "5883", "5901", "5902", "5903", "5904", "5905", "5906", "5907", "5908", "5909", "591", "5921", "5922", "5923", "5924", "5925", "5926", "5931", "5932", "5933", "5934", "5935", "5936", "5937", "5939", "5941", "5942", "5943", "5944", "5945", "5946", "5947", "5948", "5951", "5952", "5953", "5954", "5955", "5956", "5957", "5961", "5962", "5963", "5964", "5965", "5966", "5971", "5973", "5975", "5976", "5977", "5978", "6002", "6003", "6004", "6007", "6008", "6020", "6021", "6022", "6023", "6024", "6026", "6027", "6028", "6029", "6031", "6032", "6033", "6034", "6035", "6036", "6039", "6041", "6042", "6043", "6044", "6045", "6046", "6047", "6048", "6049", "6050", "6051", "6052", "6053", "6054", "6055", "6056", "6057", "6058", "6059", "6061", "6062", "6063", "6066", "6068", "6071", "6073", "6074", "6078", "6081", "6082", "6083", "6084", "6085", "6086", "6087", "6092", "6093", "6094", "6095", "6096", "6101", "6102", "6103", "6104", "6105", "6106", "6107", "6108", "6109", "611", "6120", "6122", "6123", "6124", "6126", "6127", "6128", "6129", "6130", "6131", "6132", "6133", "6134", "6135", "6136", "6138", "6139", "6142", "6144", "6145", "6146", "6147", "6150", "6151", "6152", "6154", "6155", "6157", "6158", "6159", "6161", "6162", "6163", "6164", "6165", "6166", "6167", "6171", "6172", "6173", "6174", "6175", "6181", "6182", "6183", "6184", "6185", "6186", "6187", "6188", "6190", "6192", "6195", "6196", "6198", "6201", "6202", "6203", "6204", "6205", "6206", "6207", "6209", "621", "6220", "6221", "6222", "6223", "6224", "6226", "6227", "6228", "6229", "6231", "6232", "6233", "6234", "6235", "6236", "6237", "6238", "6239", "6241", "6242", "6243", "6244", "6245", "6246", "6247", "6249", "6251", "6252", "6253", "6254", "6255", "6256", "6257", "6258", "6261", "6262", "6263", "6264", "6265", "6266", "6267", "6268", "6269", "6271", "6272", "6274", "6275", "6276", "6281", "6282", "6283", "6284", "6285", "6286", "6287", "6291", "6292", "6293", "6294", "6295", "6296", "6297", "6298", "6301", "6302", "6303", "6304", "6305", "6306", "6307", "6308", "631", "6321", "6322", "6323", "6324", "6325", "6326", "6327", "6328", "6329", "6331", "6332", "6333", "6334", "6335", "6336", "6337", "6338", "6339", "6340", "6341", "6342", "6343", "6344", "6345", "6346", "6347", "6348", "6349", "6351", "6352", "6353", "6355", "6356", "6357", "6358", "6359", "6361", "6362", "6363", "6364", "6371", "6372", "6373", "6374", "6375", "6381", "6382", "6383", "6384", "6385", "6386", "6387", "6391", "6392", "6393", "6394", "6395", "6396", "6397", "6398", "6400", "6401", "6402", "6403", "6404", "6405", "6406", "6407", "6408", "6409", "641", "6420", "6421", "6422", "6423", "6424", "6425", "6426", "6427", "6428", "6429", "6430", "6431", "6432", "6433", "6434", "6435", "6436", "6438", "6439", "6440", "6441", "6442", "6443", "6444", "6445", "6446", "6447", "6449", "6451", "6452", "6453", "6454", "6455", "6456", "6457", "6458", "6461", "6462", "6464", "6465", "6466", "6467", "6468", "6471", "6472", "6473", "6474", "6475", "6476", "6477", "6478", "6479", "6482", "6483", "6484", "6485", "6486", "6500", "6501", "6502", "6503", "6504", "6505", "6506", "6507", "6508", "6509", "651", "6522", "6523", "6524", "6525", "6526", "6527", "6531", "6532", "6533", "6534", "6535", "6536", "6541", "6542", "6543", "6544", "6545", "6550", "6551", "6552", "6553", "6554", "6555", "6556", "6557", "6558", "6559", "6561", "6562", "6563", "6564", "6565", "6566", "6567", "6568", "6569", "6571", "6572", "6573", "6574", "6575", "6578", "6580", "6581", "6582", "6583", "6584", "6585", "6586", "6587", "6588", "6589", "6591", "6592", "6593", "6594", "6595", "6596", "6597", "6599", "661", "6620", "6621", "6622", "6623", "6624", "6625", "6626", "6627", "6628", "6629", "6630", "6631", "6633", "6634", "6635", "6636", "6637", "6638", "6639", "6641", "6642", "6643", "6644", "6645", "6646", "6647", "6648", "6650", "6651", "6652", "6653", "6654", "6655", "6656", "6657", "6658", "6659", "6660", "6661", "6663", "6664", "6665", "6666", "6667", "6668", "6669", "6670", "6672", "6673", "6674", "6675", "6676", "6677", "6678", "6681", "6682", "6683", "6684", "6691", "6692", "6693", "6694", "6695", "6696", "6697", "6698", "6701", "6703", "6704", "6706", "6707", "6708", "6709", "671", "6721", "6722", "6723", "6724", "6725", "6726", "6727", "6728", "6731", "6732", "6733", "6734", "6735", "6736", "6737", "6741", "6742", "6743", "6744", "6745", "6746", "6747", "6751", "6752", "6753", "6754", "6755", "6756", "6757", "6758", "6761", "6762", "6763", "6764", "6765", "6766", "6771", "6772", "6773", "6774", "6775", "6776", "6781", "6782", "6783", "6784", "6785", "6786", "6787", "6788", "6789", "6802", "6803", "6804", "6805", "6806", "6809", "681", "6821", "6824", "6825", "6826", "6827", "6831", "6832", "6833", "6834", "6835", "6836", "6837", "6838", "6841", "6842", "6843", "6844", "6848", "6849", "6851", "6852", "6853", "6854", "6855", "6856", "6857", "6858", "6861", "6864", "6865", "6866", "6867", "6868", "6869", "6871", "6872", "6873", "6874", "6875", "6876", "6881", "6887", "6888", "6893", "6894", "6897", "6898", "69", "7021", "7022", "7023", "7024", "7025", "7026", "7031", "7032", "7033", "7034", "7041", "7042", "7043", "7044", "7045", "7046", "7051", "7052", "7053", "7054", "7055", "7056", "7062", "7063", "7066", "7071", "7072", "7073", "7081", "7082", "7083", "7084", "7085", "711", "7121", "7122", "7123", "7124", "7125", "7126", "7127", "7128", "7129", "7130", "7131", "7132", "7133", "7134", "7135", "7136", "7138", "7139", "7141", "7142", "7143", "7144", "7145", "7146", "7147", "7148", "7150", "7151", "7152", "7153", "7154", "7156", "7157", "7158", "7159", "7161", "7162", "7163", "7164", "7165", "7166", "7171", "7172", "7173", "7174", "7175", "7176", "7181", "7182", "7183", "7184", "7191", "7192", "7193", "7194", "7195", "7202", "7203", "7204", "721", "7220", "7221", "7222", "7223", "7224", "7225", "7226", "7227", "7228", "7229", "7231", "7232", "7233", "7234", "7235", "7236", "7237", "7240", "7242", "7243", "7244", "7245", "7246", "7247", "7248", "7249", "7250", "7251", "7252", "7253", "7254", "7255", "7256", "7257", "7258", "7259", "7260", "7261", "7262", "7263", "7264", "7265", "7266", "7267", "7268", "7269", "7271", "7272", "7273", "7274", "7275", "7276", "7277", "7300", "7302", "7303", "7304", "7305", "7306", "7307", "7308", "7309", "731", "7321", "7322", "7323", "7324", "7325", "7326", "7327", "7328", "7329", "7331", "7332", "7333", "7334", "7335", "7336", "7337", "7340", "7343", "7344", "7345", "7346", "7347", "7348", "7351", "7352", "7353", "7354", "7355", "7356", "7357", "7358", "7361", "7362", "7363", "7364", "7365", "7366", "7367", "7371", "7373", "7374", "7375", "7376", "7381", "7382", "7383", "7384", "7385", "7386", "7387", "7388", "7389", "7391", "7392", "7393", "7394", "7395", "7402", "7403", "7404", "741", "7420", "7422", "7423", "7424", "7425", "7426", "7427", "7428", "7429", "7431", "7432", "7433", "7434", "7435", "7436", "7440", "7441", "7442", "7443", "7444", "7445", "7446", "7447", "7448", "7449", "7451", "7452", "7453", "7454", "7455", "7456", "7457", "7458", "7459", "7461", "7462", "7463", "7464", "7465", "7466", "7467", "7471", "7472", "7473", "7474", "7475", "7476", "7477", "7478", "7482", "7483", "7484", "7485", "7486", "7502", "7503", "7504", "7505", "7506", "751", "7520", "7522", "7524", "7525", "7527", "7528", "7529", "7531", "7532", "7533", "7534", "7541", "7542", "7543", "7544", "7545", "7546", "7551", "7552", "7553", "7554", "7555", "7556", "7557", "7558", "7561", "7562", "7563", "7564", "7565", "7566", "7567", "7568", "7569", "7570", "7571", "7572", "7573", "7574", "7575", "7576", "7577", "7578", "7579", "7581", "7582", "7583", "7584", "7585", "7586", "7587", "7602", "761", "7620", "7621", "7622", "7623", "7624", "7625", "7626", "7627", "7628", "7629", "7631", "7632", "7633", "7634", "7635", "7636", "7641", "7642", "7643", "7644", "7645", "7646", "7651", "7652", "7653", "7654", "7655", "7656", "7657", "7660", "7661", "7662", "7663", "7664", "7665", "7666", "7667", "7668", "7669", "7671", "7672", "7673", "7674", "7675", "7676", "7681", "7682", "7683", "7684", "7685", "7702", "7703", "7704", "7705", "7706", "7707", "7708", "7709", "771", "7720", "7721", "7722", "7723", "7724", "7725", "7726", "7727", "7728", "7729", "7731", "7732", "7733", "7734", "7735", "7736", "7738", "7739", "7741", "7742", "7743", "7744", "7745", "7746", "7747", "7748", "7751", "7753", "7754", "7755", "7761", "7762", "7763", "7764", "7765", "7771", "7773", "7774", "7775", "7777", "7802", "7803", "7804", "7805", "7806", "7807", "7808", "781", "7821", "7822", "7823", "7824", "7825", "7826", "7831", "7832", "7833", "7834", "7835", "7836", "7837", "7838", "7839", "7841", "7842", "7843", "7844", "7851", "7852", "7853", "7854", "7903", "7904", "7905", "7906", "7907", "791", "7930", "7931", "7932", "7933", "7934", "7935", "7936", "7937", "7938", "7939", "7940", "7941", "7942", "7943", "7944", "7945", "7946", "7947", "7948", "7949", "7950", "7951", "7952", "7953", "7954", "7955", "7957", "7958", "7959", "7961", "7962", "7963", "7964", "7965", "7966", "7967", "7971", "7972", "7973", "7974", "7975", "7976", "7977", "8020", "8021", "8022", "8023", "8024", "8025", "8026", "8027", "8028", "8029", "8031", "8032", "8033", "8034", "8035", "8036", "8038", "8039", "8041", "8042", "8043", "8045", "8046", "8051", "8052", "8053", "8054", "8055", "8056", "8057", "8061", "8062", "8063", "8064", "8065", "8066", "8067", "8071", "8072", "8073", "8074", "8075", "8076", "8081", "8082", "8083", "8084", "8085", "8086", "8091", "8092", "8093", "8094", "8095", "8102", "8104", "8105", "8106", "811", "8121", "8122", "8123", "8124", "8131", "8133", "8134", "8135", "8136", "8137", "8138", "8139", "8141", "8142", "8143", "8144", "8145", "8146", "8151", "8152", "8153", "8157", "8158", "8161", "8165", "8166", "8167", "8168", "8170", "8171", "8176", "8177", "8178", "8179", "8191", "8192", "8193", "8194", "8195", "8196", "8202", "8203", "8204", "8205", "8206", "8207", "8208", "821", "8221", "8222", "8223", "8224", "8225", "8226", "8230", "8231", "8232", "8233", "8234", "8236", "8237", "8238", "8239", "8241", "8243", "8245", "8246", "8247", "8248", "8249", "8250", "8251", "8252", "8253", "8254", "8257", "8258", "8259", "8261", "8262", "8263", "8265", "8266", "8267", "8268", "8269", "8271", "8272", "8273", "8274", "8276", "8281", "8282", "8283", "8284", "8285", "8291", "8292", "8293", "8294", "8295", "8296", "8302", "8303", "8304", "8306", "831", "8320", "8321", "8322", "8323", "8324", "8325", "8326", "8327", "8328", "8330", "8331", "8332", "8333", "8334", "8335", "8336", "8337", "8338", "8340", "8341", "8342", "8343", "8344", "8345", "8346", "8347", "8348", "8349", "8361", "8362", "8363", "8364", "8365", "8366", "8367", "8368", "8369", "8370", "8372", "8373", "8374", "8375", "8376", "8377", "8378", "8379", "8380", "8381", "8382", "8383", "8384", "8385", "8386", "8387", "8388", "8389", "8392", "8393", "8394", "8395", "8402", "8403", "8404", "8405", "8406", "8407", "841", "8421", "8422", "8423", "8424", "8426", "8427", "8431", "8432", "8433", "8434", "8435", "8441", "8442", "8443", "8444", "8445", "8446", "8450", "8452", "8453", "8454", "8456", "8457", "8458", "8459", "8460", "8461", "8462", "8463", "8464", "8465", "8466", "8467", "8468", "8469", "8501", "8502", "8503", "8504", "8505", "8506", "8507", "8509", "851", "8531", "8532", "8533", "8534", "8535", "8536", "8537", "8538", "8541", "8542", "8543", "8544", "8545", "8546", "8547", "8548", "8549", "8550", "8551", "8552", "8553", "8554", "8555", "8556", "8557", "8558", "8561", "8562", "8563", "8564", "8565", "8571", "8572", "8573", "8574", "8581", "8582", "8583", "8584", "8585", "8586", "8591", "8592", "8593", "861", "8621", "8622", "8623", "8624", "8628", "8629", "8630", "8631", "8633", "8634", "8635", "8636", "8637", "8638", "8639", "8640", "8641", "8642", "8649", "8650", "8651", "8652", "8654", "8656", "8657", "8661", "8662", "8663", "8664", "8665", "8666", "8667", "8669", "8670", "8671", "8677", "8678", "8679", "8681", "8682", "8683", "8684", "8685", "8686", "8687", "8702", "8703", "8704", "8705", "8706", "8707", "8708", "8709", "871", "8721", "8722", "8723", "8724", "8725", "8726", "8727", "8728", "8731", "8732", "8733", "8734", "8735", "8741", "8742", "8743", "8744", "8745", "8751", "8752", "8753", "8754", "8756", "8761", "8762", "8764", "8765", "8766", "8771", "8772", "8773", "8774", "8781", "8782", "8783", "8784", "8785", "8801", "8802", "8803", "8805", "8806", "8807", "8808", "8809", "881", "8821", "8822", "8823", "8824", "8825", "8841", "8845", "8846", "8847", "8851", "8856", "8857", "8858", "8860", "8861", "8862", "8867", "8868", "8869", "89", "906", "9070", "9071", "9072", "9073", "9074", "9075", "9076", "9077", "9078", "9080", "9081", "9082", "9083", "9084", "9085", "9086", "9087", "9088", "9089", "9090", "9091", "9092", "9093", "9094", "9097", "9099", "9101", "9102", "9103", "9104", "9105", "9106", "9107", "911", "9120", "9122", "9123", "9126", "9127", "9128", "9129", "9131", "9132", "9133", "9134", "9135", "9141", "9142", "9143", "9144", "9145", "9146", "9147", "9148", "9149", "9151", "9152", "9153", "9154", "9155", "9156", "9157", "9158", "9161", "9162", "9163", "9164", "9165", "9166", "9167", "9170", "9171", "9172", "9173", "9174", "9175", "9176", "9177", "9178", "9179", "9180", "9181", "9182", "9183", "9184", "9185", "9186", "9187", "9188", "9189", "9190", "9191", "9192", "9193", "9194", "9195", "9196", "9197", "9198", "9199", "9201", "9202", "9203", "9204", "9205", "9206", "9207", "9208", "9209", "921", "9220", "9221", "9222", "9223", "9225", "9227", "9228", "9229", "9231", "9232", "9233", "9234", "9235", "9236", "9238", "9241", "9242", "9243", "9244", "9245", "9246", "9251", "9252", "9253", "9254", "9255", "9256", "9257", "9260", "9261", "9262", "9263", "9264", "9265", "9266", "9267", "9268", "9269", "9270", "9271", "9272", "9273", "9274", "9275", "9276", "9277", "9278", "9279", "9280", "9281", "9282", "9283", "9284", "9285", "9286", "9287", "9288", "9289", "9292", "9293", "9294", "9295", "9302", "9303", "9305", "9306", "9307", "931", "9321", "9323", "9324", "9325", "9326", "9331", "9332", "9333", "9334", "9335", "9336", "9337", "9338", "9339", "9340", "9341", "9342", "9343", "9344", "9345", "9346", "9347", "9348", "9349", "9350", "9351", "9352", "9353", "9354", "9355", "9356", "9357", "9358", "9359", "9360", "9363", "9364", "9365", "9366", "9367", "9369", "9371", "9372", "9373", "9374", "9375", "9376", "9377", "9378", "9381", "9382", "9383", "9384", "9385", "9386", "9391", "9392", "9393", "9394", "9395", "9396", "9397", "9398", "9401", "9402", "9403", "9404", "9405", "9406", "9407", "9408", "9409", "941", "9420", "9421", "9422", "9423", "9424", "9426", "9427", "9428", "9429", "9431", "9433", "9434", "9435", "9436", "9438", "9439", "9441", "9442", "9443", "9444", "9445", "9446", "9447", "9448", "9451", "9452", "9453", "9454", "9461", "9462", "9463", "9464", "9465", "9466", "9467", "9468", "9469", "9471", "9472", "9473", "9474", "9480", "9481", "9482", "9484", "9491", "9492", "9493", "9495", "9497", "9498", "9499", "9502", "9503", "9504", "9505", "951", "9521", "9522", "9523", "9524", "9525", "9526", "9527", "9528", "9529", "9531", "9532", "9533", "9534", "9535", "9536", "9542", "9543", "9544", "9545", "9546", "9547", "9548", "9549", "9551", "9552", "9553", "9554", "9555", "9556", "9560", "9561", "9562", "9563", "9564", "9565", "9566", "9567", "9568", "9569", "9571", "9572", "9573", "9574", "9575", "9576", "9602", "9603", "9604", "9605", "9606", "9607", "9608", "961", "9621", "9622", "9624", "9625", "9626", "9627", "9628", "9631", "9632", "9633", "9634", "9635", "9636", "9637", "9638", "9639", "9641", "9642", "9643", "9644", "9645", "9646", "9647", "9648", "9651", "9652", "9653", "9654", "9655", "9656", "9657", "9658", "9659", "9661", "9662", "9663", "9664", "9665", "9666", "9671", "9672", "9673", "9674", "9675", "9676", "9677", "9681", "9682", "9683", "9701", "9704", "9708", "971", "9720", "9721", "9722", "9723", "9724", "9725", "9726", "9727", "9728", "9729", "9732", "9733", "9734", "9735", "9736", "9737", "9738", "9741", "9742", "9744", "9745", "9746", "9747", "9748", "9749", "9761", "9762", "9763", "9764", "9765", "9766", "9771", "9772", "9773", "9774", "9775", "9776", "9777", "9778", "9779", "9802", "9803", "9804", "9805", "981", "9820", "9822", "9823", "9824", "9825", "9826", "9827", "9828", "9829", "9831", "9832", "9833", "9834", "9835", "9836", "9837", "9841", "9842", "9843", "9844", "9845", "9846", "9847", "9848", "9851", "9852", "9853", "9854", "9855", "9856", "9857", "9861", "9865", "9867", "9868", "9869", "9871", "9872", "9873", "9874", "9875", "9876", "9901", "9903", "9904", "9905", "9906", "9907", "9908", "991", "9920", "9921", "9922", "9923", "9924", "9925", "9926", "9927", "9928", "9929", "9931", "9932", "9933", "9935", "9936", "9937", "9938", "9941", "9942", "9943", "9944", "9945", "9946", "9947", "9948", "9951", "9952", "9953", "9954", "9955", "9956", "9961", "9962", "9963", "9964", "9965", "9966", "9971", "9972", "9973", "9974", "9975", "9976", "9977", "9978"]'
        );
    }

    public static function phone_area_codes_mobile()
    {
        return json_decode(
            '["150", "151", "152", "153", "154", "155", "156", "157", "158", "159", "160", "161", "162", "163", "164", "165", "166", "167", "168", "169", "170", "171", "172", "173", "174", "175", "176", "177", "178", "179"]'
        );
    }

    public static function phone_is_landline($value)
    {
        $value = self::phone_tokenize($value);
        if (in_array($value['area_code'], self::phone_area_codes_landline())) {
            return true;
        }
        return false;
    }

    public static function phone_is_mobile($value)
    {
        $value = self::phone_tokenize($value);
        if (in_array($value['area_code'], self::phone_area_codes_mobile())) {
            return true;
        }
        return false;
    }

    public static function url_normalize($str)
    {
        if (self::nx($str) || !is_string($str)) {
            return $str;
        }
        if (strpos($str, '://') === false) {
            $str = 'https://' . $str;
        }
        $str = rtrim($str, '/');
        return $str;
    }

    public static function remove_emoji($string)
    {
        if (self::nx($string) || !is_string($string)) {
            return $string;
        }
        $string = str_replace('?', '{%}', $string);
        $string = mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8');
        $string = mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
        $string = str_replace(['?', '? ', ' ?'], [''], $string);
        $string = str_replace('{%}', '?', $string);
        return $string;
    }

    public static function remove_accents($string, $replace_umlauts = false)
    {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }

        $chars = [
            'ª' => 'a',
            'º' => 'o',
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'Å' => 'A',
            'Æ' => 'AE',
            'Ç' => 'C',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ð' => 'D',
            'Ñ' => 'N',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ý' => 'Y',
            'Þ' => 'TH',
            'ß' => 's',
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'å' => 'a',
            'æ' => 'ae',
            'ç' => 'c',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ð' => 'd',
            'ñ' => 'n',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ø' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ý' => 'y',
            'þ' => 'th',
            'ÿ' => 'y',
            'Ø' => 'O',
            'Ā' => 'A',
            'ā' => 'a',
            'Ă' => 'A',
            'ă' => 'a',
            'Ą' => 'A',
            'ą' => 'a',
            'Ć' => 'C',
            'ć' => 'c',
            'Ĉ' => 'C',
            'ĉ' => 'c',
            'Ċ' => 'C',
            'ċ' => 'c',
            'Č' => 'C',
            'č' => 'c',
            'Ď' => 'D',
            'ď' => 'd',
            'Đ' => 'D',
            'đ' => 'd',
            'Ē' => 'E',
            'ē' => 'e',
            'Ĕ' => 'E',
            'ĕ' => 'e',
            'Ė' => 'E',
            'ė' => 'e',
            'Ę' => 'E',
            'ę' => 'e',
            'Ě' => 'E',
            'ě' => 'e',
            'Ĝ' => 'G',
            'ĝ' => 'g',
            'Ğ' => 'G',
            'ğ' => 'g',
            'Ġ' => 'G',
            'ġ' => 'g',
            'Ģ' => 'G',
            'ģ' => 'g',
            'Ĥ' => 'H',
            'ĥ' => 'h',
            'Ħ' => 'H',
            'ħ' => 'h',
            'Ĩ' => 'I',
            'ĩ' => 'i',
            'Ī' => 'I',
            'ī' => 'i',
            'Ĭ' => 'I',
            'ĭ' => 'i',
            'Į' => 'I',
            'į' => 'i',
            'İ' => 'I',
            'ı' => 'i',
            'Ĳ' => 'IJ',
            'ĳ' => 'ij',
            'Ĵ' => 'J',
            'ĵ' => 'j',
            'Ķ' => 'K',
            'ķ' => 'k',
            'ĸ' => 'k',
            'Ĺ' => 'L',
            'ĺ' => 'l',
            'Ļ' => 'L',
            'ļ' => 'l',
            'Ľ' => 'L',
            'ľ' => 'l',
            'Ŀ' => 'L',
            'ŀ' => 'l',
            'Ł' => 'L',
            'ł' => 'l',
            'Ń' => 'N',
            'ń' => 'n',
            'Ņ' => 'N',
            'ņ' => 'n',
            'Ň' => 'N',
            'ň' => 'n',
            'ŉ' => 'n',
            'Ŋ' => 'N',
            'ŋ' => 'n',
            'Ō' => 'O',
            'ō' => 'o',
            'Ŏ' => 'O',
            'ŏ' => 'o',
            'Ő' => 'O',
            'ő' => 'o',
            'Œ' => 'OE',
            'œ' => 'oe',
            'Ŕ' => 'R',
            'ŕ' => 'r',
            'Ŗ' => 'R',
            'ŗ' => 'r',
            'Ř' => 'R',
            'ř' => 'r',
            'Ś' => 'S',
            'ś' => 's',
            'Ŝ' => 'S',
            'ŝ' => 's',
            'Ş' => 'S',
            'ş' => 's',
            'Š' => 'S',
            'š' => 's',
            'Ţ' => 'T',
            'ţ' => 't',
            'Ť' => 'T',
            'ť' => 't',
            'Ŧ' => 'T',
            'ŧ' => 't',
            'Ũ' => 'U',
            'ũ' => 'u',
            'Ū' => 'U',
            'ū' => 'u',
            'Ŭ' => 'U',
            'ŭ' => 'u',
            'Ů' => 'U',
            'ů' => 'u',
            'Ű' => 'U',
            'ű' => 'u',
            'Ų' => 'U',
            'ų' => 'u',
            'Ŵ' => 'W',
            'ŵ' => 'w',
            'Ŷ' => 'Y',
            'ŷ' => 'y',
            'Ÿ' => 'Y',
            'Ź' => 'Z',
            'ź' => 'z',
            'Ż' => 'Z',
            'ż' => 'z',
            'Ž' => 'Z',
            'ž' => 'z',
            'ſ' => 's',
            'Ș' => 'S',
            'ș' => 's',
            'Ț' => 'T',
            'ț' => 't',
            '€' => 'E',
            '£' => '',
            'Ơ' => 'O',
            'ơ' => 'o',
            'Ư' => 'U',
            'ư' => 'u',
            'Ầ' => 'A',
            'ầ' => 'a',
            'Ằ' => 'A',
            'ằ' => 'a',
            'Ề' => 'E',
            'ề' => 'e',
            'Ồ' => 'O',
            'ồ' => 'o',
            'Ờ' => 'O',
            'ờ' => 'o',
            'Ừ' => 'U',
            'ừ' => 'u',
            'Ỳ' => 'Y',
            'ỳ' => 'y',
            'Ả' => 'A',
            'ả' => 'a',
            'Ẩ' => 'A',
            'ẩ' => 'a',
            'Ẳ' => 'A',
            'ẳ' => 'a',
            'Ẻ' => 'E',
            'ẻ' => 'e',
            'Ể' => 'E',
            'ể' => 'e',
            'Ỉ' => 'I',
            'ỉ' => 'i',
            'Ỏ' => 'O',
            'ỏ' => 'o',
            'Ổ' => 'O',
            'ổ' => 'o',
            'Ở' => 'O',
            'ở' => 'o',
            'Ủ' => 'U',
            'ủ' => 'u',
            'Ử' => 'U',
            'ử' => 'u',
            'Ỷ' => 'Y',
            'ỷ' => 'y',
            'Ẫ' => 'A',
            'ẫ' => 'a',
            'Ẵ' => 'A',
            'ẵ' => 'a',
            'Ẽ' => 'E',
            'ẽ' => 'e',
            'Ễ' => 'E',
            'ễ' => 'e',
            'Ỗ' => 'O',
            'ỗ' => 'o',
            'Ỡ' => 'O',
            'ỡ' => 'o',
            'Ữ' => 'U',
            'ữ' => 'u',
            'Ỹ' => 'Y',
            'ỹ' => 'y',
            'Ấ' => 'A',
            'ấ' => 'a',
            'Ắ' => 'A',
            'ắ' => 'a',
            'Ế' => 'E',
            'ế' => 'e',
            'Ố' => 'O',
            'ố' => 'o',
            'Ớ' => 'O',
            'ớ' => 'o',
            'Ứ' => 'U',
            'ứ' => 'u',
            'Ạ' => 'A',
            'ạ' => 'a',
            'Ậ' => 'A',
            'ậ' => 'a',
            'Ặ' => 'A',
            'ặ' => 'a',
            'Ẹ' => 'E',
            'ẹ' => 'e',
            'Ệ' => 'E',
            'ệ' => 'e',
            'Ị' => 'I',
            'ị' => 'i',
            'Ọ' => 'O',
            'ọ' => 'o',
            'Ộ' => 'O',
            'ộ' => 'o',
            'Ợ' => 'O',
            'ợ' => 'o',
            'Ụ' => 'U',
            'ụ' => 'u',
            'Ự' => 'U',
            'ự' => 'u',
            'Ỵ' => 'Y',
            'ỵ' => 'y',
            'ɑ' => 'a',
            'Ǖ' => 'U',
            'ǖ' => 'u',
            'Ǘ' => 'U',
            'ǘ' => 'u',
            'Ǎ' => 'A',
            'ǎ' => 'a',
            'Ǐ' => 'I',
            'ǐ' => 'i',
            'Ǒ' => 'O',
            'ǒ' => 'o',
            'Ǔ' => 'U',
            'ǔ' => 'u',
            'Ǚ' => 'U',
            'ǚ' => 'u',
            'Ǜ' => 'U',
            'ǜ' => 'u'
        ];

        if ($replace_umlauts === true) {
            $chars['Ä'] = 'Ae';
            $chars['ä'] = 'ae';
            $chars['Ö'] = 'Oe';
            $chars['ö'] = 'oe';
            $chars['Ü'] = 'Ue';
            $chars['ü'] = 'ue';
            $chars['ß'] = 'ss';
            $chars['Æ'] = 'Ae';
            $chars['æ'] = 'ae';
            $chars['Ø'] = 'Oe';
            $chars['ø'] = 'oe';
            $chars['Å'] = 'Aa';
            $chars['å'] = 'aa';
            $chars['l·l'] = 'll';
            $chars['Đ'] = 'DJ';
            $chars['đ'] = 'dj';
        }

        $string = strtr($string, $chars);

        return $string;
    }

    public static function remove_non_printable_chars($str)
    {
        if (self::nx($str) || !is_string($str)) {
            return $str;
        }
        $str = preg_replace('/[\x00-\x1F\x7F]/u', '', $str);
        return $str;
    }

    // (uses https://github.com/jenstornell/tiny-html-minifier, which is currently not installable via composer)
    public static function minify_html($html)
    {
        if (self::nx($html) || !is_string($html)) {
            return $html;
        }
        if (class_exists('TinyHtmlMinifier') === false) {
            require __DIR__ . '/../libs/TinyHtmlMinifier.php';
        }
        $minifier = new \TinyHtmlMinifier([
            'collapse_whitespace' => true,
            'disable_comments' => true
        ]);
        // this does not work: <br/> => <br>, so we fix it
        $html = str_replace('<br/>', '<br />', $html);
        // simple strings also don't work
        if (!self::string_is_html($html)) {
            return trim($html);
        }
        $is_simple_string_with_tags = mb_strpos($html, '<') !== 0;
        if ($is_simple_string_with_tags) {
            $html = '<template>' . $html . '</template>';
        }
        $html = $minifier->minify($html);
        if ($is_simple_string_with_tags) {
            $html = str_replace(['<template>', '</template>'], '', $html);
        }
        return $html;
    }

    public static function str_to_dom($html)
    {
        $dom = new \DOMDocument();

        // support XML
        if (mb_strpos($html, '<?xml') === 0) {
            @$dom->loadXML($html);
            return $dom;
        }

        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        $has_wrapper = strpos($html, '<html') !== false;
        if ($has_wrapper === false) {
            $html = '<!DOCTYPE html><html data-please-remove-wrapper><body>' . $html . '</body></html>';
        }
        if (mb_strpos($html, '</head>') !== false) {
            $html = str_replace(
                '</head>',
                '<!--remove--><meta http-equiv="Content-type" content="text/html; charset=utf-8" /><!--/remove--></head>',
                $html
            );
        } elseif (mb_strpos($html, '<body') !== false) {
            $html = str_replace(
                '<body',
                '<!--remove--><head><meta http-equiv="content-type" content="text/html;charset=utf-8" /></head><!--/remove--><body',
                $html
            );
        } else {
            $html =
                '<!--remove--><head><meta http-equiv="content-type" content="text/html;charset=utf-8" /></head><!--/remove-->' .
                $html;
        }
        // vuejs attribute names like @click get stripped out by domdocument: prevent that
        $html = preg_replace('/( |\r\n|\r|\n)(@)([a-zA-Z-_:\.]+=)/', '$1___$3', $html);

        // domdocument has problems with script tags (see: https://stackoverflow.com/questions/33426788/domdocument-removing-closing-tag-within-script-tags)
        preg_match_all('/<script\b[^>]*>.*?<\/script>/s', $html, $matches);
        $matches = array_unique($matches[0]);
        if (!empty($matches)) {
            foreach ($matches as $matches__value) {
                $before = $matches__value;
                $after = $matches__value;
                preg_match_all('/<\/[a-zA-Z][a-zA-Z0-9]*>/', $matches__value, $matches_inner);
                $matches_inner = array_unique($matches_inner[0]);
                if (!empty($matches_inner)) {
                    foreach ($matches_inner as $matches_inner__value) {
                        if ($matches_inner__value === '</script>') {
                            continue;
                        }
                        $after = str_replace(
                            $matches_inner__value,
                            str_replace('/', '\/', $matches_inner__value),
                            $after
                        );
                    }
                    $simple[] = $after;
                    $complete[] = $before;
                }
            }
            $html = str_replace($complete, $simple, $html);
        }

        @$dom->loadHTML($html);
        return $dom;
    }

    public static function dom_to_str($dom)
    {
        // support XML
        if ($dom->xmlVersion != '') {
            $html = $dom->saveXML();
            return $html;
        }

        // domdocument does not close empty li tags (because they're valid html)
        // to circumvent that, use:
        $DOMXPath = new \DOMXPath($dom);
        $nodes = $DOMXPath->query('/html/body//*[not(node())]');
        foreach ($nodes as $nodes__value) {
            $nodes__value->nodeValue = '';
        }
        $html = $dom->saveHTML();

        // domdocument closes <source> tags (see: https://bugs.php.net/bug.php?id=73175)
        $DOMXPath = new \DOMXPath($dom);
        $nodes = $DOMXPath->query(
            '/html/body//command|/html/body//embed|/html/body//keygen|/html/body//source|/html/body//track|/html/body//wbr'
        );
        foreach ($nodes as $nodes__value) {
            $nodes__value->appendChild($dom->createTextNode('DELETE'));
        }
        $html = $dom->saveHTML();
        $html = preg_replace('/DELETE<\/(command|embed|keygen|source|track|wbr)>/', '', $html);

        // revert vuejs modifications
        $html = preg_replace('/( |\r\n|\r|\n)(___)([a-zA-Z-_:\.]+=)/', '$1@$3', $html);

        // domdocument converts all umlauts to html entities, revert that
        // $html = html_entity_decode($html);
        // this method is bad when we use intentionally encoded code e.g. in <pre> tags; another option to prevent html entities (and leave everything intact)
        // is to add <meta http-equiv="content-type" content="text/html;charset=utf-8" /> (see above)
        // warning: this still encodes < to &gt; because < is invalid html!
        // undo above changes
        if (mb_strpos($html, '<!--remove-->') !== false && mb_strpos($html, '<!--/remove-->') !== false) {
            $html =
                mb_substr($html, 0, mb_strpos($html, '<!--remove-->')) .
                mb_substr($html, mb_strpos($html, '<!--/remove-->') + mb_strlen('<!--/remove-->'));
        }
        // if domdocument added previously a default header, we squish that
        if (mb_stripos($html, 'data-please-remove-wrapper') !== false) {
            $pos1 = mb_strpos($html, '>', mb_strpos($html, '<body')) + 1;
            $pos2 = mb_strpos($html, '</body>');
            $html = mb_substr($html, $pos1, $pos2 - $pos1);
        }
        return $html;
    }

    public static function translate_google($str, $from_lng, $to_lng, $api_key, $proxy = null)
    {
        if (self::nx($str)) {
            return $str;
        }
        if ($api_key === 'free' || $api_key == '42') {
            return self::translate_google_inofficial($str, $from_lng, $to_lng, $proxy);
        }
        return self::translate_google_api($str, $from_lng, $to_lng, $api_key, $proxy);
    }

    public static function translate_google_api($str, $from_lng, $to_lng, $api_key, $proxy = null)
    {
        $response = self::curl(
            'https://translation.googleapis.com/language/translate/v2?key=' . $api_key,
            [
                'q' => $str,
                'source' => $from_lng,
                'target' => $to_lng,
                'format' => self::string_is_html($str) ? 'html' : 'text',
                'model' => 'nmt'
            ],
            'POST',
            null,
            false,
            true,
            6,
            null,
            null,
            true,
            $proxy
        );

        if ($response->status == 200 && @$response->result->data->translations[0]->translatedText != '') {
            $trans = $response->result->data->translations[0]->translatedText;
        } else {
            self::exception($response);
            return null;
        }

        // the api returns some characters in their html characters form (e.g. "'" is returned as "&#39;")
        // we want to store the real values
        // this is disabled, because it destroys valid html(!)
        //$trans = html_entity_decode($trans, ENT_QUOTES);

        // uppercase
        // the google translation api does a very bad job at keeping uppercased words at the beginning
        // we fix this here
        if (self::first_char_is_uppercase($str) && !self::first_char_is_uppercase($trans)) {
            $trans = self::set_first_char_uppercase($trans);
        }

        return $trans;
    }

    public static function translate_google_inofficial($str, $from_lng, $to_lng, $proxy = null)
    {
        /* more info: https://vielhuber.de/blog/google-translate-api-hacking */
        $str = self::translate_google_inofficial_parse_result_pre($str);
        $args = [
            'anno' => 3,
            'client' => 'te_lib',
            // format "text" does not work here!
            'format' => 'html',
            'v' => '1.0',
            'key' => 'AIzaSyBOti4mM-6x9WDnZIjIeyEU21OpBXqWBgw',
            'logld' => 'vTE_20200210_00',
            'sl' => $from_lng,
            'tl' => $to_lng,
            'sp' => 'nmt',
            'tc' => 1,
            'sr' => 1,
            'tk' => self::translate_google_inofficial_generate_tk(
                $str,
                self::translate_google_inofficial_generate_tkk($proxy)
            ),
            'mode' => 1
        ];
        $response = self::curl(
            'https://translate.googleapis.com/translate_a/t?' . http_build_query($args),
            ['q' => $str],
            'POST',
            [
                'User-Agent' =>
                    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36',
                'Content-Length' => strlen('q=' . urlencode($str))
            ],
            false,
            false,
            6,
            null,
            null,
            true,
            $proxy
        );
        if ($response->status != 200 || self::nx($response->result)) {
            self::exception($response);
            return null;
        }
        $trans = self::translate_google_inofficial_parse_result_post($response->result);
        if (self::first_char_is_uppercase($str) && !self::first_char_is_uppercase($trans)) {
            $trans = self::set_first_char_uppercase($trans);
        }
        return $trans;
    }

    private static function translate_google_inofficial_parse_result_pre($input)
    {
        // google sometimes surrounds the translation with <i> and <b> tags
        // do distinguish real i-/b-tags, replace them (we undo that later on)
        $dom = self::str_to_dom($input);
        $xpath = new \DOMXPath($dom);
        foreach (['i', 'b'] as $tags__value) {
            foreach ($dom->getElementsByTagName($tags__value) as $divs__value) {
                $divs__value->setAttribute('data-native', 'true');
            }
        }
        $nodes = $xpath->query('/html/body//*');
        if (count($nodes) > 0) {
            $id = 1;
            foreach ($nodes as $nodes__value) {
                $nodes__value->setAttribute('gtid', $id);
                $id++;
            }
        }
        $output = self::dom_to_str($dom);
        return $output;
    }

    private static function translate_google_inofficial_parse_result_post($input)
    {
        // sometimes google returns an array
        if (is_array($input) && !empty($input)) {
            $input = $input[0];
        }
        // discard the (outer) <i>-tags and take the content of the <b>-tags
        $output = '';
        $pointer = 0;
        $lvl_i = 0;
        $lvl_i_inner = 0;
        $lvl_b = 0;
        $lvl_b_inner = 0;
        // multibyte split to array of chars
        foreach (preg_split('//u', $input, -1, PREG_SPLIT_NO_EMPTY) as $chars__value) {
            if ($pointer >= 3 && mb_substr($input, $pointer - 3, 3) === '<i>') {
                $lvl_i_inner++;
            }
            if ($pointer >= 3 && mb_substr($input, $pointer - 3, 3) === '<b>') {
                $lvl_b_inner++;
            }
            if (mb_substr($input, $pointer, 4) === '</i>' && $lvl_i_inner > 0) {
                $lvl_i_inner--;
            }
            if (mb_substr($input, $pointer, 4) === '</b>' && $lvl_b_inner > 0) {
                $lvl_b_inner--;
            }
            if (mb_substr($input, $pointer, 3) === '<i>') {
                $lvl_i++;
            }
            if (mb_substr($input, $pointer, 3) === '<b>') {
                $lvl_b++;
            }
            if ($pointer >= 4 && mb_substr($input, $pointer - 4, 4) === '</i>' && $lvl_i > 0) {
                $lvl_i--;
            }
            if ($pointer >= 4 && mb_substr($input, $pointer - 4, 4) === '</b>' && $lvl_b > 0) {
                $lvl_b--;
            }
            $pointer++;
            // discard multiple spaces
            if ($chars__value === ' ' && mb_strlen($output) > 0 && mb_substr($output, -1) === ' ') {
                continue;
            }
            // save
            if (($lvl_b_inner >= 1 && $lvl_i_inner === 0) || ($lvl_b === 0 && $lvl_i === 0)) {
                $output .= $chars__value;
            }
        }
        $output = trim($output);
        $dom = self::str_to_dom($output);
        $xpath = new \DOMXPath($dom);
        foreach (['i', 'b'] as $tags__value) {
            foreach ($dom->getElementsByTagName($tags__value) as $divs__value) {
                $divs__value->removeAttribute('data-native');
            }
        }
        // merge neighbour elements with the same id together
        $nodes = $xpath->query('/html/body//*[@gtid]');
        if (count($nodes) > 0) {
            foreach ($nodes as $nodes__value) {
                if ($nodes__value->hasAttribute('please-remove')) {
                    continue;
                }
                $id = $nodes__value->getAttribute('gtid');
                $html = $nodes__value->nodeValue;
                $nextSibling = $nodes__value->nextSibling;
                if ($nextSibling === null) {
                    continue;
                }
                if ($nextSibling->nodeName === '#text' && trim($nextSibling->textContent) == '') {
                    $nextSibling = $nextSibling->nextSibling;
                }
                if ($nextSibling === null || $nextSibling->nodeName === '#text') {
                    continue;
                }
                $id2 = $nextSibling->getAttribute('gtid');
                if ($id !== $id2) {
                    continue;
                }
                $nextSibling->setAttribute('please-remove', '1');
                $html .= ' ' . $nextSibling->nodeValue;
                $nodes__value->nodeValue = $html;
            }
            foreach ($nodes as $nodes__value) {
                $nodes__value->removeAttribute('gtid');
                if ($nodes__value->hasAttribute('please-remove')) {
                    $nodes__value->parentNode->removeChild($nodes__value);
                }
            }
        }
        $output = self::dom_to_str($dom);
        return $output;
    }

    private static function translate_google_inofficial_generate_tkk($proxy = null)
    {
        $cache = sys_get_temp_dir() . '/tkk.cache';
        if (
            file_exists($cache) &&
            filemtime($cache) > strtotime('now - 1 hour') &&
            self::x(file_get_contents($cache))
        ) {
            return file_get_contents($cache);
        }
        $data = self::curl(
            'https://translate.googleapis.com/translate_a/element.js',
            null,
            'GET',
            null,
            false,
            true,
            6,
            null,
            null,
            true,
            $proxy
        );
        $response = $data->result;
        $pos1 = mb_strpos($response, 'c._ctkk=\'') + mb_strlen('c._ctkk=\'');
        $pos2 = mb_strpos($response, '\'', $pos1);
        $tkk = mb_substr($response, $pos1, $pos2 - $pos1);
        file_put_contents($cache, $tkk);
        return $tkk;
    }

    private static function translate_google_inofficial_generate_tk($f0, $w1)
    {
        $w1 = explode('.', $w1);
        $n2 = $w1[0];
        for ($j3 = [], $t4 = 0, $h5 = 0; $h5 < strlen(mb_convert_encoding($f0, 'UTF-16LE', 'UTF-8')) / 2; $h5++) {
            $z6 =
                ord(mb_convert_encoding($f0, 'UTF-16LE', 'UTF-8')[$h5 * 2]) +
                (ord(mb_convert_encoding($f0, 'UTF-16LE', 'UTF-8')[$h5 * 2 + 1]) << 8);
            if (128 > $z6) {
                $j3[$t4++] = $z6;
            } else {
                if (2048 > $z6) {
                    $j3[$t4++] = ($z6 >> 6) | 192;
                } else {
                    if (
                        55296 == ($z6 & 64512) &&
                        $h5 + 1 < strlen(mb_convert_encoding($f0, 'UTF-16LE', 'UTF-8')) / 2 &&
                        56320 ==
                            ((ord(mb_convert_encoding($f0, 'UTF-16LE', 'UTF-8')[($h5 + 1) * 2]) +
                                (ord(mb_convert_encoding($f0, 'UTF-16LE', 'UTF-8')[($h5 + 1) * 2 + 1]) << 8)) &
                                64512)
                    ) {
                        $h5++;
                        $z6 =
                            65536 +
                            (($z6 & 1023) << 10) +
                            ((ord(mb_convert_encoding($f0, 'UTF-16LE', 'UTF-8')[$h5 * 2]) +
                                (ord(mb_convert_encoding($f0, 'UTF-16LE', 'UTF-8')[$h5 * 2 + 1]) << 8)) &
                                1023);
                        $j3[$t4++] = ($z6 >> 18) | 240;
                        $j3[$t4++] = (($z6 >> 12) & 63) | 128;
                    } else {
                        $j3[$t4++] = ($z6 >> 12) | 224;
                    }
                    $j3[$t4++] = (($z6 >> 6) & 63) | 128;
                }
                $j3[$t4++] = ($z6 & 63) | 128;
            }
        }
        $f0 = $n2;
        for ($t4 = 0; $t4 < count($j3); $t4++) {
            $f0 += $j3[$t4];
            $c7 = $f0;
            $x8 = '+-a^+6';
            for ($r9 = 0; $r9 < strlen($x8) - 2; $r9 += 3) {
                $u10 = $x8[$r9 + 2];
                $u10 = 'a' <= $u10 ? ord($u10[0]) - 87 : intval($u10);
                $a11 = $c7;
                $c12 = $u10;
                if ($c12 >= 32 || $c12 < -32) {
                    $c13 = (int) ($c12 / 32);
                    $c12 = $c12 - $c13 * 32;
                }
                if ($c12 < 0) {
                    $c12 = 32 + $c12;
                }
                if ($c12 == 0) {
                    return (($a11 >> 1) & 0x7fffffff) * 2 + (($a11 >> $c12) & 1);
                }
                if ($a11 < 0) {
                    $a11 = $a11 >> 1;
                    $a11 &= 2147483647;
                    $a11 |= 0x40000000;
                    $a11 = $a11 >> $c12 - 1;
                } else {
                    $a11 = $a11 >> $c12;
                }
                $b14 = $a11;
                $u10 = '+' == $x8[$r9 + 1] ? $b14 : $c7 << $u10;
                $c7 = '+' == $x8[$r9] ? ($c7 + $u10) & 4294967295 : $c7 ^ $u10;
            }
            $f0 = $c7;
        }
        $c7 = $f0;
        $x8 = '+-3^+b+-f';
        for ($r9 = 0; $r9 < strlen($x8) - 2; $r9 += 3) {
            $u10 = $x8[$r9 + 2];
            $u10 = 'a' <= $u10 ? ord($u10[0]) - 87 : intval($u10);
            $a11 = $c7;
            $c12 = $u10;
            if ($c12 >= 32 || $c12 < -32) {
                $c13 = (int) ($c12 / 32);
                $c12 = $c12 - $c13 * 32;
            }
            if ($c12 < 0) {
                $c12 = 32 + $c12;
            }
            if ($c12 == 0) {
                return (($a11 >> 1) & 0x7fffffff) * 2 + (($a11 >> $c12) & 1);
            }
            if ($a11 < 0) {
                $a11 = $a11 >> 1;
                $a11 &= 2147483647;
                $a11 |= 0x40000000;
                $a11 = $a11 >> $c12 - 1;
            } else {
                $a11 = $a11 >> $c12;
            }
            $b14 = $a11;
            $u10 = '+' == $x8[$r9 + 1] ? $b14 : $c7 << $u10;
            $c7 = '+' == $x8[$r9] ? ($c7 + $u10) & 4294967295 : $c7 ^ $u10;
        }
        $f0 = $c7;
        $f0 ^= $w1[1] ? $w1[1] + 0 : 0;
        if (0 > $f0) {
            $f0 = ($f0 & 2147483647) + 2147483648;
        }
        $f0 = fmod($f0, pow(10, 6));
        return $f0 . '.' . ($f0 ^ $n2);
    }

    public static function translate_microsoft($str, $from_lng, $to_lng, $api_key, $proxy = null)
    {
        if (self::nx($str)) {
            return $str;
        }
        if ($api_key === 'free' || $api_key == '42') {
            return self::translate_microsoft_inofficial($str, $from_lng, $to_lng, $proxy);
        }
        return self::translate_microsoft_api($str, $from_lng, $to_lng, $api_key, $proxy);
    }

    public static function translate_microsoft_api($str, $from_lng, $to_lng, $api_key, $proxy = null)
    {
        $response = self::curl(
            'https://api.cognitive.microsofttranslator.com/translate?api-version=3.0&from=' .
                $from_lng .
                '&to=' .
                $to_lng .
                '&textType=html',
            [['Text' => $str]],
            'POST',
            [
                'Ocp-Apim-Subscription-Key' => $api_key
            ],
            false,
            true,
            6,
            null,
            null,
            true,
            $proxy
        );

        if ($response->status == 200 && @$response->result[0]->translations[0]->text != '') {
            $trans = $response->result[0]->translations[0]->text;
        } else {
            self::exception($response);
            return null;
        }

        return $trans;
    }

    public static function translate_microsoft_inofficial($str, $from_lng, $to_lng, $proxy = null)
    {
        $response = self::curl(
            'https://www.bing.com/translator',
            null,
            'GET',
            null,
            false,
            true,
            6,
            null,
            null,
            true,
            $proxy
        );
        $html = $response->result;
        preg_match('/,IG:"(.+?)",/', $html, $matches);
        if (empty($matches) || !isset($matches[1]) || $matches[1] == '') {
            self::exception($response);
            return null;
        }
        $ig = $matches[1];
        preg_match('/data-iid="(.+?)"/', $html, $matches);
        if (empty($matches) || !isset($matches[1]) || $matches[1] == '') {
            self::exception($response);
            return null;
        }
        $iid = $matches[1];

        $response = self::curl(
            'https://www.bing.com/ttranslatev3?isVertical=1&IG=' . $ig . '&IID=' . $iid,
            [
                'fromLang' => $from_lng,
                'text' => $str,
                'to' => $to_lng
            ],
            'POST',
            null,
            false,
            false,
            6,
            null,
            null,
            true,
            $proxy
        );

        if (
            $response->status != 200 ||
            self::nx($response->result) ||
            !is_array($response->result) ||
            self::nx(@$response->result[0]->translations[0]->text)
        ) {
            self::exception($response);
            return null;
        }

        return $response->result[0]->translations[0]->text;
    }

    public static function translate_deepl($str, $from_lng, $to_lng, $api_key, $proxy = null)
    {
        if (self::nx($str)) {
            return $str;
        }

        // deepl has problems on self enclosed tags (<br>, <hr>)
        // see http://xahlee.info/js/html5_non-closing_tag.html
        // fix that
        $str = preg_replace(
            '/(<(area|base|br|col|embed|hr|img|input|link|meta|param|source|track|wbr)[^\/]*?)(>)/i',
            '$1/>',
            $str
        );

        if ($api_key === 'free' || $api_key == '42') {
            return self::translate_deepl_inofficial($str, $from_lng, $to_lng, $proxy);
        }

        return self::translate_deepl_api($str, $from_lng, $to_lng, $api_key, $proxy);
    }

    public static function translate_deepl_api($str, $from_lng, $to_lng, $api_key, $proxy = null)
    {
        $domain = 'api.deepl.com';
        if (strpos($api_key, ':fx') !== false) {
            $domain = 'api-free.deepl.com';
        }
        $response = self::curl(
            'https://' . $domain . '/v2/translate?auth_key=' . $api_key,
            [
                'text' => $str,
                'source_lang' => $from_lng,
                'target_lang' => $to_lng,
                'split_sentences' => 0,
                'preserve_formatting' => 0,
                'formality' => 'default',
                'tag_handling' => 'xml'
            ],
            'POST',
            null,
            false,
            false,
            6,
            null,
            null,
            true,
            $proxy
        );

        if ($response->status != 200 || self::nx(@$response->result->translations[0]->text)) {
            self::exception($response);
            return null;
        }

        return $response->result->translations[0]->text;
    }

    public static function translate_deepl_inofficial($str, $from_lng, $to_lng, $proxy = null)
    {
        $id = pow(1 * 10, 4) * round((pow(1 * 10, 4) * (float) rand()) / (float) getrandmax());
        $priority = 1;
        $timestamp_shift = 1;
        $timestamp = round(microtime(true) * 1000);
        $timestamp_shift += substr_count($str, 'i');
        $timestamp = $timestamp + ($timestamp_shift - ($timestamp % $timestamp_shift));

        $data = [
            'jsonrpc' => '2.0',
            'method' => 'LMT_handle_jobs',
            'params' => [
                'jobs' => [
                    [
                        'kind' => 'default',
                        'raw_en_sentence' => $str,
                        'raw_en_context_before' => [],
                        'raw_en_context_after' => [],
                        'preferred_num_beams' => 1,
                        'quality' => 'fast'
                    ]
                ],
                'lang' => [
                    'user_preferred_langs' => ['DE', 'EN'],
                    'source_lang_user_selected' => mb_strtoupper($from_lng),
                    'target_lang' => mb_strtoupper($to_lng)
                ],
                'priority' => $priority,
                'commonJobParams' => [
                    'formality' => null
                ],
                'timestamp' => $timestamp
            ],
            'id' => $id
        ];
        $data = json_encode($data);
        $data = str_replace('hod":"', ($id + 3) % 13 == 0 || ($id + 5) % 29 == 0 ? 'hod" : "' : 'hod": "', $data);

        $response = self::curl(
            'https://www2.deepl.com/jsonrpc',
            $data,
            'POST',
            null,
            false,
            true,
            6,
            null,
            null,
            true,
            $proxy
        );

        if (
            $response->status != 200 ||
            self::nx(@$response->result->result->translations[0]->beams[0]->postprocessed_sentence)
        ) {
            self::exception($response);
            return null;
        }

        return $response->result->result->translations[0]->beams[0]->postprocessed_sentence;
    }

    public static function first_char_is_uppercase($str)
    {
        if (self::nx($str) || !is_string($str)) {
            return false;
        }
        return mb_substr($str, 0, 1) == mb_strtoupper(mb_substr($str, 0, 1));
    }

    public static function set_first_char_uppercase($str)
    {
        if (self::nx($str) || !is_string($str)) {
            return $str;
        }
        $fc = mb_strtoupper(mb_substr($str, 0, 1));
        return $fc . mb_substr($str, 1);
    }

    public static function slug($string)
    {
        if (!is_string($string) || trim($string) == '') {
            return '';
        }

        $string = self::remove_accents($string, true);

        // replace non letter or digits by -
        $string = preg_replace('~[^\pL\d]+~u', '-', $string);
        // transliterate
        $string = iconv('utf-8', 'us-ascii//TRANSLIT', $string);
        // remove unwanted characters
        $string = preg_replace('~[^-\w]+~', '', $string);
        // trim
        $string = trim($string, '-');
        // remove duplicate -
        $string = preg_replace('~-+~', '-', $string);
        // lowercase
        $string = strtolower($string);
        if (empty($string)) {
            return 'n-a';
        }
        return $string;
    }

    public static function random_string($length = 8, $chars = null)
    {
        if ($chars === null) {
            $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        $chars_length = strlen($chars);
        $random_string = '';
        for ($i = 0; $i < $length; $i++) {
            $random_string .= $chars[mt_rand(0, $chars_length - 1)];
        }
        return $random_string;
    }

    public static function arr_without($array, $cols)
    {
        if (!is_array($array) || !is_array($cols)) {
            return $array;
        }
        foreach ($cols as $cols__value) {
            unset($array[$cols__value]);
        }
        return $array;
    }

    public static function shuffle($array)
    {
        if (!is_array($array)) {
            return $array;
        }
        shuffle($array);
        return $array;
    }

    public static function shuffle_assoc($array)
    {
        if (!is_array($array)) {
            return $array;
        }
        $new = [];
        $keys = array_keys($array);
        shuffle($keys);
        foreach ($keys as $keys__value) {
            $new[$keys__value] = $array[$keys__value];
        }
        return $new;
    }

    public static function str_convert_din_5007_2($str)
    {
        if (is_string($str)) {
            $str = str_replace(
                ['Ä', 'ä', 'Ö', 'ö', 'Ü', 'ü', 'ß', '-'],
                ['Ae', 'ae', 'Oe', 'oe', 'Ue', 'ue', 'ss', ' '],
                $str
            );
        }
        return $str;
    }

    public static function mb_strcmp($a, $b)
    {
        foreach (['a', 'b'] as $strings__value) {
            ${$strings__value} = self::str_convert_din_5007_2(${$strings__value});
        }
        return strcmp($a, $b);
    }

    public static function mb_strcasecmp($a, $b)
    {
        foreach (['a', 'b'] as $strings__value) {
            ${$strings__value} = self::str_convert_din_5007_2(${$strings__value});
        }
        return strcasecmp($a, $b);
    }

    public static function mb_strnatcmp($a, $b)
    {
        foreach (['a', 'b'] as $strings__value) {
            if (is_string(${$strings__value})) {
                ${$strings__value} = self::str_convert_din_5007_2(${$strings__value});
            }
        }
        return strnatcmp($a, $b);
    }

    public static function mb_strnatcasecmp($a, $b)
    {
        foreach (['a', 'b'] as $strings__value) {
            if (is_string(${$strings__value})) {
                ${$strings__value} = self::str_convert_din_5007_2(${$strings__value});
            }
        }
        return strnatcasecmp($a, $b);
    }

    public static function array_multisort($args)
    {
        return function ($a, $b) use ($args) {
            $position = [true => -1, false => 1];
            $order = true;
            if (is_array($args)) {
                foreach ($args as $args__value) {
                    if (self::v($a[$args__value[0]]) != self::v($b[$args__value[0]])) {
                        return $position[
                            self::array_multisort_get_order($a[$args__value[0]], $b[$args__value[0]], $args__value[1])
                        ];
                    }
                }
            } elseif (is_callable($args)) {
                $args_a = $args($a);
                $args_b = $args($b);
                foreach ($args_a as $args__key => $args__value) {
                    if (self::v($args_a[$args__key][0]) != self::v($args_b[$args__key][0])) {
                        return $position[
                            self::array_multisort_get_order(
                                $args_a[$args__key][0],
                                $args_b[$args__key][0],
                                $args_a[$args__key][1]
                            )
                        ];
                    }
                }
            }
            return $position[$order];
        };
    }

    public static function array_multisort_get_order($a, $b, $dir = 'asc')
    {
        $order = null;
        if (is_string($a) && is_string($b)) {
            foreach (['a', 'b'] as $strings__value) {
                ${$strings__value} = self::str_convert_din_5007_2(${$strings__value});
            }
            $order = strnatcasecmp($a, $b) < 0;
        } elseif (self::nx($a) || self::nx($b)) {
            if (self::nx($a)) {
                $order = true;
            } else {
                $order = false;
            }
        } else {
            $order = $a < $b;
        }
        if ($dir === 'desc') {
            $order = !$order;
        }
        return $order;
    }

    public static function array_group_by($array, $key)
    {
        if (!is_string($key) && !is_int($key) && !is_float($key) && !is_callable($key)) {
            trigger_error('array_group_by(): The key should be a string, an integer, or a callback', E_USER_ERROR);
            return null;
        }
        $func = !is_string($key) && is_callable($key) ? $key : null;
        $_key = $key;
        $grouped = [];
        foreach ($array as $value) {
            $key = null;
            if (is_callable($func)) {
                $key = call_user_func($func, $value);
            } elseif (is_object($value) && property_exists($value, $_key)) {
                $key = $value->{$_key};
            } elseif (isset($value[$_key])) {
                $key = $value[$_key];
            }
            if ($key === null) {
                continue;
            }
            $grouped[$key][] = $value;
        }
        if (func_num_args() > 2) {
            $args = func_get_args();
            foreach ($grouped as $key => $value) {
                $params = array_merge([$value], array_slice($args, 2, func_num_args()));
                $grouped[$key] = call_user_func_array([self::class, 'array_group_by'], $params);
            }
        }
        return $grouped;
    }

    public static function array_group_by_aggregate($array, $keys, $aggregate_functions)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        $grouped = [];
        foreach ($array as $array__value) {
            $grouped_key = [];
            foreach ($keys as $keys__value) {
                if (!array_key_exists($keys__value, $array__value)) {
                    continue;
                }
                $grouped_key[] = $array__value[$keys__value];
            }
            $grouped_key = implode('❤️', $grouped_key);
            if (!array_key_exists($grouped_key, $grouped)) {
                $grouped[$grouped_key] = $array__value;
            } else {
                foreach ($array__value as $array__value__key => $array__value__value) {
                    if (in_array($array__value__key, $keys, true)) {
                        continue;
                    }
                    if (!array_key_exists($array__value__key, $aggregate_functions)) {
                        continue;
                    }
                    $grouped[$grouped_key][$array__value__key] = $aggregate_functions[$array__value__key](
                        $grouped[$grouped_key][$array__value__key],
                        $array__value__value
                    );
                }
            }
        }
        $grouped = array_values($grouped);
        return $grouped;
    }

    public static function array_unique($array)
    {
        if (self::nx($array) || !is_array($array)) {
            return $array;
        }
        return array_map('unserialize', array_unique(array_map('serialize', $array)));
    }

    public static function array_map_deep($array, $callback, $array__key = null, $key_chain = [])
    {
        // auto decode json strings (and encode them later on)
        $is_json = self::string_is_json($array);
        if ($is_json) {
            $array = json_decode($array);
        }

        if (is_array($array) || (is_object($array) && !($array instanceof \__empty_helper))) {
            $new = [];
            if (is_object($array) && !($array instanceof \__empty_helper)) {
                $new = (object) $new;
            }
            foreach ($array as $array__key => $array__value) {
                $is_json_iterable =
                    self::string_is_json($array__value) &&
                    (is_array(json_decode($array__value)) || is_object(json_decode($array__value)));

                $key_chain_this = $key_chain;
                $key_chain_this[] = $array__key;
                if (is_array($array)) {
                    if (
                        is_array($array__value) ||
                        (is_object($array__value) && !($array__value instanceof \__empty_helper)) ||
                        $is_json_iterable
                    ) {
                        $new[$array__key] = self::array_map_deep(
                            $array__value,
                            $callback,
                            $array__key,
                            $key_chain_this
                        );
                    } else {
                        $new[$array__key] = call_user_func($callback, $array__value, $array__key, $key_chain_this);
                    }
                }
                if (is_object($array) && !($array instanceof \__empty_helper)) {
                    if (
                        is_array($array__value) ||
                        (is_object($array__value) && !($array__value instanceof \__empty_helper)) ||
                        $is_json_iterable
                    ) {
                        $new->{$array__key} = self::array_map_deep(
                            $array__value,
                            $callback,
                            $array__key,
                            $key_chain_this
                        );
                    } else {
                        $new->{$array__key} = call_user_func($callback, $array__value, $array__key, $key_chain_this);
                    }
                }
            }
        } else {
            $new = call_user_func($callback, $array, $array__key, $key_chain);
        }

        if ($is_json) {
            $new = json_encode($new);
        }

        return $new;
    }

    public static function array_filter_recursive_all($arr, $callback)
    {
        if (!is_array($arr)) {
            return $arr;
        }
        $depth = self::arr_depth($arr);
        $random_str = null;
        $match = true;
        while ($match === true) {
            $random_str = self::random_string();
            $match = false;
            self::array_walk_recursive_all($arr, function ($value) use ($random_str, &$match) {
                if (is_string($value) && $value === $random_str) {
                    $match = true;
                }
            });
        }
        for ($i = 0; $i < $depth; $i++) {
            self::array_walk_recursive_all($arr, function (&$value, $key, $key_chain) use ($callback, $random_str) {
                $ret = $callback($value, $key, $key_chain);
                if ($ret === true) {
                    $value = $random_str;
                }
            });
            if (is_string($arr) && $arr === $random_str) {
                $arr = [];
            }
            $arr = self::remove_empty($arr, null, function ($arr__value) use ($random_str) {
                return $arr__value === $random_str;
            });
        }
        return $arr;
    }

    public static function array_walk_recursive_all(&$array, $callback, $array__key = null, $key_chain = [])
    {
        call_user_func_array($callback, [&$array, $array__key, $key_chain]);
        if (is_array($array)) {
            foreach ($array as $array__key => $array__value) {
                $key_chain_this = $key_chain;
                $key_chain_this[] = $array__key;
                if (!is_array($array__value)) {
                    call_user_func_array($callback, [&$array__value, $array__key, $key_chain_this]);
                } else {
                    self::array_walk_recursive_all($array__value, $callback, $array__key, $key_chain_this);
                    $array[$array__key] = $array__value;
                }
            }
        }
    }

    public static function array_map_deep_all($array, $callback, $array__key = null, $key_chain = [])
    {
        $new = [];
        $new = call_user_func($callback, $array, $array__key, $key_chain);
        $array = $new; // modify!
        if (is_array($array)) {
            foreach ($array as $array__key => $array__value) {
                $key_chain_this = $key_chain;
                $key_chain_this[] = $array__key;
                $new[$array__key] = call_user_func($callback, $array__value, $array__key, $key_chain_this);
                $array__value = $new[$array__key]; // modify!
                if (is_array($array__value)) {
                    $new[$array__key] = self::array_map_deep_all(
                        $array__value,
                        $callback,
                        $array__key,
                        $key_chain_this
                    );
                }
            }
        }
        return $new;
    }

    public static function array_map_keys($callback, $arr)
    {
        if (self::nx($arr)) {
            return $arr;
        }
        if (!is_array($arr)) {
            return $arr;
        }
        if (!is_callable($callback)) {
            return $arr;
        }
        return array_combine(array_map($callback, array_keys($arr)), $arr);
    }

    public static function encode_data(...$data)
    {
        if (count($data) === 1) {
            $data = $data[0];
        }
        return base64_encode(serialize($data));
    }

    public static function decode_data($str)
    {
        return unserialize(base64_decode($str));
    }

    public static function ask($question, $whitelist = [])
    {
        echo $question;
        echo PHP_EOL;

        if (empty($whitelist)) {
            $handle = fopen('php://stdin', 'r');
            $line = fgets($handle);
            fclose($handle);
            return trim($line);
        } else {
            $answer = '';
            // read char by char and hide output
            system('stty -icanon -echo');
            while ($c = fread(STDIN, 16)) {
                // clean
                if (!in_array($c, $whitelist)) {
                    continue;
                }
                // restore tty
                system('stty sane');
                // output
                $answer = trim($c);
                echo $answer;
                break;
            }
            return $answer;
        }
    }

    public static function progress($done, $total, $info = '', $width = 50, $char = '=')
    {
        $perc = round(($done * 100) / $total);
        $bar = round(($width * $perc) / 100);
        echo sprintf(
            "%s[%s%s] %s\r",
            $info != '' ? $info . ' ' : '',
            str_repeat($char, $bar) . ($perc < 100 ? '>' : ''),
            $perc == 100 ? $char : str_repeat(' ', $width - $bar),
            str_pad($perc, 3, ' ', STR_PAD_LEFT) . '%'
        );
    }

    public static function mb_sprintf($format, ...$args)
    {
        foreach ($args as $args__key => $args__value) {
            $args[$args__key] = utf8_decode($args__value);
        }
        return utf8_encode(sprintf($format, ...$args));
    }

    public static function is_serialized($data)
    {
        if (!is_string($data) || $data == '') {
            return false;
        }
        set_error_handler(function ($errno, $errstr) {});
        $unserialized = unserialize($data);
        restore_error_handler();
        if ($data !== 'b:0;' && $unserialized === false) {
            return false;
        }
        return true;

        /* this approach uses @ and leads to an ugly margin at the top when debug mode is on in wordpress */
        /*
        if (!is_string($string) || $string == '') {
            return false;
        }
        $data = @unserialize($string);
        if ($string !== 'b:0;' && $data === false) {
            return false;
        }
        return true;
        */

        /* the following function borrowed from https://developer.wordpress.org/reference/functions/is_serialized/
         /* falsely detects strings like a:1:{s:3:\"foo\";s:3:\"bar\";} and also does not check e.g. arrays */
        /*
        $strict = true;
        // If it isn't a string, it isn't serialized.
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' === $data) {
            return true;
        }
        if (strlen($data) < 4) {
            return false;
        }
        if (':' !== $data[1]) {
            return false;
        }
        if ($strict) {
            $lastc = substr($data, -1);
            if (';' !== $lastc && '}' !== $lastc) {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace = strpos($data, '}');
            // Either ; or } must exist.
            if (false === $semicolon && false === $brace) {
                return false;
            }
            // But neither must be in the first X characters.
            if (false !== $semicolon && $semicolon < 3) {
                return false;
            }
            if (false !== $brace && $brace < 4) {
                return false;
            }
        }
        $token = $data[0];
        switch ($token) {
            case 's':
                if ($strict) {
                    if ('"' !== substr($data, -2, 1)) {
                        return false;
                    }
                } elseif (false === strpos($data, '"')) {
                    return false;
                }
            // Or else fall through.
            case 'a':
            case 'O':
                return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b':
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';
                return (bool) preg_match("/^{$token}:[0-9.E+-]+;$end/", $data);
        }
        return false;
        */
    }

    public static function is_integer($input)
    {
        if (self::nx(@$input)) {
            return false;
        }
        if ($input === true) {
            return false;
        }
        if (is_int($input)) {
            return true;
        }
        if (is_numeric($input) && $input != (string) (float) $input) {
            return true;
        }
        return preg_match('/^\d+$/', $input) === 1;
    }

    public static function float_to_ratio($n)
    {
        $tolerance = 1e-6;
        $h1 = 1;
        $h2 = 0;
        $k1 = 0;
        $k2 = 1;
        $b = 1 / $n;
        do {
            $b = 1 / $b;
            $a = floor($b);
            $aux = $h1;
            $h1 = $a * $h1 + $h2;
            $h2 = $aux;
            $aux = $k1;
            $k1 = $a * $k1 + $k2;
            $k2 = $aux;
            $b = $b - $a;
        } while (abs($n - $h1 / $k1) > $n * $tolerance);
        return "$h1:$k1";
    }

    public static function extract($string, $begin, $end)
    {
        $pos1 = strpos($string, $begin) + strlen($begin);
        if ($pos1 === false) {
            return false;
        }
        $pos2 = strpos($string, $end, $pos1);
        if ($pos2 === false) {
            return false;
        }
        return substr($string, $pos1, $pos2 - $pos1);
    }

    public static function strposx($haystack, $needle)
    {
        $positions = [];
        $last_pos = 0;
        while (($last_pos = strpos($haystack, $needle, $last_pos)) !== false) {
            $positions[] = $last_pos;
            $last_pos += strlen($needle);
        }
        return $positions;
    }

    public static function strposnth($haystack, $needle, $index)
    {
        $positions = self::strposx($haystack, $needle);
        if (empty($positions) || $index <= 0 || $index > count($positions)) {
            return null;
        }
        return $positions[$index - 1];
    }

    public static function remove_empty($a, $additional = null, $callback = null)
    {
        if (
            ($a instanceof \Illuminate\Database\Eloquent\Collection || $a instanceof \Illuminate\Support\Collection) &&
            $a->count() > 0
        ) {
            foreach ($a as $a__key => $a__value) {
                if (self::can_be_looped(@$a__value)) {
                    $result = self::remove_empty(@$a__value, $additional, $callback);
                    if (
                        ($callback !== null && $callback($result) === true) ||
                        ($callback === null && self::nx($result))
                    ) {
                        $a->forget($a__key);
                    } else {
                        $a->put($a__key, $result);
                    }
                } elseif (
                    ($callback !== null && $callback(@$a__value) === true) ||
                    ($callback === null &&
                        (self::nx(@$a__value) || ($additional !== null && in_array($a__value, $additional))))
                ) {
                    $a->forget($a__key);
                }
            }
        } elseif (is_array($a) && !empty($a)) {
            foreach ($a as $a__key => $a__value) {
                if (self::can_be_looped(@$a__value)) {
                    $result = self::remove_empty(@$a__value, $additional, $callback);
                    if (
                        ($callback !== null && $callback($result) === true) ||
                        ($callback === null && self::nx($result))
                    ) {
                        unset($a[$a__key]);
                    } else {
                        $a[$a__key] = $result;
                    }
                } elseif (
                    ($callback !== null && $callback(@$a__value) === true) ||
                    ($callback === null &&
                        (self::nx(@$a__value) || ($additional !== null && in_array($a__value, $additional, true))))
                ) {
                    unset($a[$a__key]);
                }
            }
        } elseif (is_object($a) && !empty((array) $a)) {
            foreach ($a as $a__key => $a__value) {
                if (self::can_be_looped(@$a__value)) {
                    $result = self::remove_empty(@$a__value, $additional, $callback);
                    if (
                        ($callback !== null && $callback($result) === true) ||
                        ($callback === null && self::nx($result))
                    ) {
                        unset($a->$a__key);
                    } else {
                        $a->$a__key = $result;
                    }
                } elseif (
                    ($callback !== null && $callback(@$a__value) === true) ||
                    ($callback === null &&
                        (self::nx(@$a__value) || ($additional !== null && in_array($a__value, $additional, true))))
                ) {
                    unset($a->$a__key);
                }
            }
        }
        return $a;
    }

    public static function remove($arr, $key)
    {
        return self::remove_by_key($arr, $key);
    }

    public static function remove_by_key($arr, $key)
    {
        if (self::nx($arr)) {
            return $arr;
        }
        $was_object = false;
        if (is_object($arr)) {
            $was_object = true;
            $arr = (array) $arr;
        }
        if (is_array($arr)) {
            unset($arr[$key]);
            $all_numeric = true;
            foreach ($arr as $arr__key => $arr__value) {
                if (!is_numeric($arr__key)) {
                    $all_numeric = false;
                }
            }
            if ($all_numeric === true) {
                $arr = array_values($arr);
            }
        }
        if ($was_object === true) {
            $arr = (object) $arr;
        }
        return $arr;
    }

    public static function remove_by_value($arr, $value)
    {
        if (self::nx($arr)) {
            return $arr;
        }
        $was_object = false;
        if (is_object($arr)) {
            $was_object = true;
            $arr = (array) $arr;
        }
        if (is_array($arr)) {
            foreach (array_keys($arr, $value, true) as $key) {
                unset($arr[$key]);
            }
            $all_numeric = true;
            foreach ($arr as $arr__key => $arr__value) {
                if (!is_numeric($arr__key)) {
                    $all_numeric = false;
                }
            }
            if ($all_numeric === true) {
                $arr = array_values($arr);
            }
        }
        if ($was_object === true) {
            $arr = (object) $arr;
        }
        return $arr;
    }

    public static function arr_depth($a)
    {
        if (!is_array($a)) {
            return 0;
        }
        $max = 0;
        foreach ($a as $val) {
            if (is_array($val)) {
                $tmp_depth = self::arr_depth($val);
                if ($max < $tmp_depth) {
                    $max = $tmp_depth;
                }
            }
        }
        return $max + 1;
    }

    public static function arr_append($array, $value, $condition = true)
    {
        if ($condition === false || !is_array($array)) {
            return $array;
        }
        $array[] = $value;
        return $array;
    }

    public static function arr_prepend($array, $value, $condition = true)
    {
        if ($condition === false || !is_array($array)) {
            return $array;
        }
        array_unshift($array, $value);
        return $array;
    }

    public static function foreach_nested(...$args)
    {
        $fn = array_pop($args);
        foreach (self::combinations($args) as $combinations__value) {
            $fn(...$combinations__value);
        }
    }

    public static function combinations($arrays, $i = 0)
    {
        if (!isset($arrays[$i])) {
            return [];
        }
        if ($i == count($arrays) - 1) {
            return $arrays[$i];
        }
        $tmp = self::combinations($arrays, $i + 1);
        $result = [];
        foreach ($arrays[$i] as $v) {
            foreach ($tmp as $t) {
                $result[] = is_array($t) ? array_merge([$v], $t) : [$v, $t];
            }
        }
        return $result;
    }

    public static function can_be_looped($a)
    {
        if (is_array($a) && !empty($a)) {
            return true;
        }
        if (is_object($a) && !empty((array) $a)) {
            return true;
        }
        if (
            ($a instanceof \Illuminate\Database\Eloquent\Collection || $a instanceof \Illuminate\Support\Collection) &&
            $a->count() > 0
        ) {
            return true;
        }
        return false;
    }

    public static function array($a = null)
    {
        if (self::nx($a)) {
            return [];
        }
        if (is_array($a)) {
            return self::object_to_array(self::array_to_object($a));
        }
        if (is_object($a)) {
            return self::object_to_array($a);
        }
        return [$a];
    }

    public static function object($a = null)
    {
        if (self::nx($a)) {
            return (object) [];
        }
        if (is_object($a)) {
            return self::array_to_object(self::object_to_array($a));
        }
        if (is_array($a)) {
            return self::array_to_object($a);
        }
        return (object) [$a];
    }

    public static function object_to_array($obj)
    {
        return json_decode(json_encode($obj), true);
    }

    public static function array_to_object($arr)
    {
        $obj = new \stdClass();
        foreach ($arr as $k => $v) {
            if (strlen($k)) {
                if (is_array($v)) {
                    $obj->$k = self::array_to_object($v);
                } else {
                    $obj->$k = $v;
                }
            }
        }
        return $obj;
    }

    public static function highlight($string, $query, $strip = false, $strip_length = 500)
    {
        if (self::nx($string) || self::nx($query)) {
            return $string;
        }
        if ($strip === true) {
            $dots = '...';
            // get all query begin positions in spot
            $lastPos = 0;
            $positions = [];
            while (($lastPos = mb_stripos($string, $query, $lastPos)) !== false) {
                $positions[] = $lastPos;
                $lastPos = $lastPos + mb_strlen($query);
            }
            // strip away parts
            $words = explode(' ', $string);
            $i = 0;
            foreach ($words as $words__key => $words__value) {
                $strip_now = true;
                foreach ($positions as $positions__value) {
                    if (
                        $i >= $positions__value - $strip_length &&
                        $i <= $positions__value + mb_strlen($query) + $strip_length - 1
                    ) {
                        $strip_now = false;
                    }
                }
                if ($strip_now === true) {
                    $words[$words__key] = $dots;
                }
                $i += mb_strlen($words__value) + 1;
            }
            $string = implode(' ', $words);
            while (mb_strpos($string, $dots . ' ' . $dots) !== false) {
                $string = str_replace($dots . ' ' . $dots, $dots, $string);
            }
            $string = trim($string);
        }
        // again: get all query begin positions in spot
        $lastPos = 0;
        $positions = [];
        while (($lastPos = mb_stripos($string, $query, $lastPos)) !== false) {
            $positions[] = $lastPos;
            $lastPos = $lastPos + mb_strlen($query);
        }
        // wrap span element around them
        $wrap_begin = '<strong class="highlight">';
        $wrap_end = '</strong>';
        for ($x = 0; $x < count($positions); $x++) {
            $string =
                mb_substr($string, 0, $positions[$x]) .
                $wrap_begin .
                mb_substr($string, $positions[$x], mb_strlen($query)) .
                $wrap_end .
                mb_substr($string, $positions[$x] + mb_strlen($query));
            // shift other positions
            for ($y = $x + 1; $y < count($positions); $y++) {
                $positions[$y] = $positions[$y] + mb_strlen($wrap_begin) + mb_strlen($wrap_end);
            }
        }
        return $string;
    }

    public static function referer()
    {
        if (self::nx(@$_SERVER['HTTP_REFERER'])) {
            return null;
        }
        return $_SERVER['HTTP_REFERER'];
    }

    public static function clean_up_get()
    {
        if (self::x(@$_GET)) {
            foreach ($_GET as $key => $value) {
                $_GET[$key] = filter_var(strip_tags($value), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            }
        }
    }

    public static function clean_up_post()
    {
        if (self::x(@$_POST)) {
            foreach ($_POST as $key => $value) {
                $_POST[$key] = filter_var(strip_tags($value), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            }
        }
    }

    public static function clean_up()
    {
        self::clean_up_get();
        self::clean_up_post();
    }

    public static function read_env($filename)
    {
        $env_data = [];
        if (file_exists($filename)) {
            $env_content = file_get_contents($filename);
            $env_content = preg_split('/\r\n|\n|\r/', $env_content);
            if (!empty($env_content)) {
                foreach ($env_content as $env_content__value) {
                    if (strpos($env_content__value, '=') === false) {
                        continue;
                    }
                    $env_data[explode('=', $env_content__value)[0]] = trim(
                        trim(explode('=', $env_content__value)[1], '"')
                    );
                }
            }
        }
        return $env_data;
    }

    public static function is_repetitive_action($name = '', $minutes = 60, $whitelist = [])
    {
        if (!isset($_SERVER['REMOTE_ADDR']) || $_SERVER['REMOTE_ADDR'] == '') {
            return false;
        }
        $ip = $_SERVER['REMOTE_ADDR'];

        if (is_string($whitelist)) {
            $whitelist = [$whitelist];
        }
        if (!is_array($whitelist)) {
            $whitelist = [];
        }
        if (in_array($ip, $whitelist)) {
            return false;
        }

        if (!is_numeric($minutes)) {
            $minutes = 60;
        }
        if (!self::is_integer($minutes)) {
            $seconds = round($minutes * 60);
        } else {
            $seconds = $minutes * 60;
        }

        $filename = sys_get_temp_dir() . '/' . md5($name . $ip) . '.throttle';

        if (!file_exists($filename)) {
            touch($filename);
            return false;
        }
        $time = filemtime($filename);
        touch($filename);
        if (strtotime('now - ' . $seconds . ' seconds') < $time) {
            return true;
        }
        return false;
    }

    public static function ip_is_on_spamlist($ip)
    {
        $dnsbl_lookup = [
            'dnsbl-1.uceprotect.net',
            'dnsbl-2.uceprotect.net',
            'dnsbl-3.uceprotect.net',
            'dnsbl.dronebl.org',
            'dnsbl.sorbs.net',
            //'zen.spamhaus.org', // detects lots of false positives
            'bl.spamcop.net',
            'list.dsbl.org'
        ];
        if (self::x($ip) && is_string($ip)) {
            $reverse_ip = implode('.', array_reverse(explode('.', $ip)));
            foreach ($dnsbl_lookup as $host) {
                if (checkdnsrr($reverse_ip . '.' . $host . '.', 'A')) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function has_spamwords($message, $custom_blacklist = [], $custom_whitelist = [])
    {
        if (!is_string($message) || trim($message) == '') {
            return false;
        }
        if (!is_array($custom_blacklist)) {
            $custom_blacklist = [];
        }
        if (!is_array($custom_whitelist)) {
            $custom_whitelist = [];
        }
        // always provide the following strings (blacklist.txt has some weaknesses)
        $custom_whitelist[] = 't-online.de';
        $filename = sys_get_temp_dir() . '/spam-blacklist.txt';
        if (!file_exists($filename) || filemtime($filename) < strtotime('now - 1 month')) {
            $content = @file_get_contents(
                'https://raw.githubusercontent.com/splorp/wordpress-comment-blacklist/master/blacklist.txt'
            );
            if ($content != '') {
                file_put_contents($filename, $content);
            }
        }
        $blacklist = file_get_contents($filename);
        if ($blacklist != '' && strpos($blacklist, 'cannabis') !== false) {
            $message = strip_tags($message);
            $message = str_replace('*', '', $message);
            $blacklist = trim($blacklist);
            $words = explode(PHP_EOL, $blacklist);
            if (is_array($custom_blacklist) && !empty($custom_blacklist)) {
                $words = array_merge($words, $custom_blacklist);
            }
            foreach ($words as $words__value) {
                $words__value = trim($words__value);
                if ($words__value == '') {
                    continue;
                }
                $words__value = preg_quote($words__value, '#');
                $pattern = '#' . $words__value . '#i';
                if (preg_match($pattern, $message)) {
                    if (is_array($custom_whitelist) && !empty($custom_whitelist)) {
                        foreach ($custom_whitelist as $custom_whitelist__value) {
                            $custom_whitelist__value = trim($custom_whitelist__value);
                            if ($custom_whitelist__value == '') {
                                continue;
                            }
                            $custom_whitelist__value = preg_quote($custom_whitelist__value, '#');
                            $custom_whitelist_pattern = '#' . $custom_whitelist__value . '#i';
                            if (preg_match($custom_whitelist_pattern, $message)) {
                                continue 2;
                            }
                        }
                    }
                    return true;
                }
            }
        }
        return false;
    }

    public static function get($var)
    {
        if (self::nx(@$_GET[$var])) {
            return null;
        }
        return $_GET[$var];
    }

    public static function post($var)
    {
        if (self::nx(@$_POST[$var])) {
            return null;
        }
        return $_POST[$var];
    }

    public static function filter_url_args($url, $filter = [])
    {
        if (self::nx($url) || self::nx($filter)) {
            return $url;
        }
        if (!empty($filter) && strpos($url, '?') !== false) {
            $url_components = parse_url($url);
            if (!empty($url_components) && @$url_components['query'] != '') {
                parse_str($url_components['query'], $params);
                $params_new = [];
                foreach ($params as $params__key => $params__value) {
                    if (in_array($params__key, $filter)) {
                        continue;
                    }
                    $params_new[$params__key] = $params__value;
                }
                $query_new = http_build_query($params_new, '', '&');
                $url = str_replace('?' . $url_components['query'], $query_new != '' ? '?' . $query_new : '', $url);
            }
        }
        return $url;
    }

    public static function expl($separator = ' ', $array = [], $pos = 0)
    {
        return current(array_slice(explode($separator, $array), $pos, 1));
    }

    public static function prg($url = null)
    {
        if ($url == null) {
            $url = @$_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
            $url .= $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
            if (self::x(@$_GET['page_id'])) {
                $url .= '?page_id=' . $_GET['page_id'];
            }
        }
        header('Location: ' . $url);
        die();
    }

    public static function system_message($content, $type = 'success')
    {
        if (isset($_COOKIE['system_messages']) && $_COOKIE['system_messages'] != '') {
            $messages = unserialize(base64_decode($_COOKIE['system_messages']));
        } else {
            $messages = [];
        }
        $messages[] = (object) ['content' => $content, 'type' => $type];
        $messages = base64_encode(serialize($messages));
        setcookie('system_messages', $messages, time() + 60 * 60 * 24 * 1, '/');
        $_COOKIE['system_messages'] = $messages;
    }

    public static function system_messages()
    {
        $messages = [];
        if (isset($_COOKIE['system_messages']) && $_COOKIE['system_messages'] != '') {
            $messages = unserialize(base64_decode($_COOKIE['system_messages']));
            unset($_COOKIE['system_messages']);
            setcookie('system_messages', '', time() - 3600, '/');
        }
        return $messages;
    }

    public static function redirect_to($url = null, $code_or_seconds = null, $mode = 'php')
    {
        if ($mode === 'php') {
            header('Location: ' . self::v($url, self::baseurl()) . '', true, $code_or_seconds);
            die();
        }
        if ($mode === 'html') {
            echo '<meta http-equiv="refresh" content="' .
                self::v($code_or_seconds, 0) .
                '; url=\'' .
                self::v($url, self::baseurl()) .
                '\'">';
            die();
        }
    }

    public static function date($date = null, $format = null, $mod = null)
    {
        if (func_num_args() === 0) {
            $date = 'now';
        }
        if (self::nx($date) || $date === true || $date === false) {
            return null;
        }

        /*
        /* special case: if only one argument is provided which can be understood both as date and date format
        /* strategy: always prefer as format (except keywords below)
        /* examples:
        /*   now
        /*   Y
        /*   yesterday
        */
        if ($date !== null && $format === null && $mod === null) {
            if (self::validate_date($date) && self::validate_date_format($date)) {
                /* see: https://www.php.net/manual/de/datetime.formats.relative.php */
                $whitelist = [
                    'sunday',
                    'monday',
                    'tuesday',
                    'wednesday',
                    'thursday',
                    'friday',
                    'saturday',
                    'sun',
                    'mon',
                    'tue',
                    'wed',
                    'thu',
                    'fri',
                    'sat',
                    'weekday',
                    'weekdays',
                    'first',
                    'second',
                    'third',
                    'fourth',
                    'fifth',
                    'sixth',
                    'seventh',
                    'eighth',
                    'ninth',
                    'tenth',
                    'eleventh',
                    'twelfth',
                    'next',
                    'last',
                    'previous',
                    'this',
                    'next',
                    'last',
                    'previous',
                    'this',
                    'sec',
                    'second',
                    'min',
                    'minute',
                    'hour',
                    'day',
                    'fortnight',
                    'forthnight',
                    'month',
                    'year',
                    'weeks',
                    'yesterday',
                    'midnight',
                    'today',
                    'now',
                    'noon',
                    'tomorrow',
                    'back of',
                    'front of',
                    'first day of',
                    'last day of',
                    'last',
                    'week',
                    'ago',
                    'week'
                ];
                $found = false;
                foreach ($whitelist as $whitelist__value) {
                    if (stripos($date, $whitelist__value) !== false) {
                        $found = true;
                        break;
                    }
                }
                if ($found === false) {
                    [$date, $format] = [$format, $date];
                }
            }
        }

        // sort arguments magically
        if (
            ($date === null || self::validate_date($date)) &&
            ($format === null || self::validate_date_format($format)) &&
            ($mod === null || self::validate_date_mod($mod))
        ) {
            // default case
        } elseif (
            ($format === null || self::validate_date($format)) &&
            ($date === null || self::validate_date_format($date)) &&
            ($mod === null || self::validate_date_mod($mod))
        ) {
            [$format, $date] = [$date, $format];
        } elseif (
            ($date === null || self::validate_date($date)) &&
            ($mod === null || self::validate_date_format($mod)) &&
            ($format === null || self::validate_date_mod($format))
        ) {
            [$format, $mod] = [$mod, $format];
        } elseif (
            ($format === null || self::validate_date($format)) &&
            ($mod === null || self::validate_date_format($mod)) &&
            ($date === null || self::validate_date_mod($date))
        ) {
            [$date, $format] = [$format, $date];
            [$format, $mod] = [$mod, $format];
        } elseif (
            ($mod === null || self::validate_date($mod)) &&
            ($date === null || self::validate_date_format($date)) &&
            ($format === null || self::validate_date_mod($format))
        ) {
            [$date, $mod] = [$mod, $date];
            [$format, $mod] = [$mod, $format];
        } elseif (
            ($mod === null || self::validate_date($mod)) &&
            ($format === null || self::validate_date_format($format)) &&
            ($date === null || self::validate_date_mod($date))
        ) {
            [$date, $mod] = [$mod, $date];
        } else {
            return null;
        }

        if (self::nx($date)) {
            if (func_num_args() >= 2) {
                return null;
            }
            $date = 'now';
        }
        // input timestamp
        elseif (is_numeric($date)) {
            $date = date('Y-m-d H:i:s', $date);
        }
        // input datetime object
        elseif ($date instanceof \DateTime) {
            $date = $date->format('Y-m-d H:i:s');
        }
        // special fix for 01.01.20
        elseif (preg_match('/^\d{1,2}\.\d{1,2}\.\d{1,2}$/', $date)) {
            $date =
                explode('.', $date)[0] .
                '.' .
                explode('.', $date)[1] .
                '.' .
                substr(date('Y'), 0, 2) .
                explode('.', $date)[2];
        }

        // default value for format
        if (self::nx($format)) {
            $format = 'Y-m-d';
        }
        return date($format, strtotime($date . (self::x($mod) ? ' ' . $mod : '')));
    }

    public static function validate_date_format($str)
    {
        if (self::nx($str)) {
            return false;
        }
        if ($str === true) {
            return false;
        }
        // remove escaped chars like \T
        $str = preg_replace('/(\\\[a-zA-Z])/', '', $str);
        $chars_needed = [
            'd',
            'D',
            'j',
            'l',
            'N',
            'S',
            'w',
            'z',
            'W',
            'F',
            'm',
            'M',
            'n',
            't',
            'L',
            'o',
            'Y',
            'y',
            'a',
            'A',
            'B',
            'g',
            'G',
            'h',
            'H',
            'i',
            's',
            'u',
            'v',
            'e',
            'I',
            'O',
            'P',
            'T',
            'Z',
            'z',
            'c',
            'r',
            'U'
        ];
        $chars_optional = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ' ', '-', '.', ':', '/'];
        $min_one_needed_char = false;
        foreach (str_split($str) as $str__value) {
            if (!in_array($str__value, array_merge($chars_needed, $chars_optional))) {
                return false;
            }
            if (in_array($str__value, $chars_needed)) {
                $min_one_needed_char = true;
            }
        }
        return $min_one_needed_char;
    }

    public static function validate_date_mod($mod)
    {
        if (self::nx($mod)) {
            return false;
        }
        return strtotime('2000-01-01 ' . $mod) !== false;
    }

    public static function datetime($datetime)
    {
        if (self::nx(@$datetime) || !is_string($datetime)) {
            return null;
        }
        return date('Y-m-d', strtotime($datetime)) . 'T' . date('H:i', strtotime($datetime));
    }

    public static function date_reset_time($date)
    {
        if (self::nx(@$date) || !is_string($date)) {
            return null;
        }
        return date('Y-m-d 00:00:00', strtotime($date));
    }

    public static function age_from_date($date_birth, $date_relative = null)
    {
        return self::age_from_date_generic($date_birth, $date_relative, 'years');
    }

    public static function age_from_date_weeks($date_birth, $date_relative = null)
    {
        return self::age_from_date_generic($date_birth, $date_relative, 'weeks');
    }

    public static function age_from_date_days($date_birth, $date_relative = null)
    {
        return self::age_from_date_generic($date_birth, $date_relative, 'days');
    }

    private static function age_from_date_generic($date_birth, $date_relative = null, $type = 'years')
    {
        if (self::nx(@$date_birth)) {
            return null;
        }
        $date_birth = self::date($date_birth, 'Y-m-d');
        if (self::nx($date_birth)) {
            return null;
        }
        if (self::x($date_relative)) {
            $date_relative = self::date($date_relative, 'Y-m-d');
            if (self::nx($date_relative)) {
                return null;
            }
        } else {
            $date_relative = self::date('now');
        }
        $date_birth = new \DateTime($date_birth);
        $date_relative = new \DateTime($date_relative);
        $age = $date_relative->diff($date_birth);
        if ($type === 'years') {
            $age = $age->y;
        }
        if ($type === 'weeks') {
            $age = floor($age->days / 7);
        }
        if ($type === 'days') {
            $age = $age->days;
        }
        $age = intval($age);
        return $age;
    }

    public static function remove_zero_decimals($num)
    {
        if (self::nx($num)) {
            return null;
        }
        if (is_string($num)) {
            $num = str_replace(',', '.', $num);
        }
        if (!is_numeric($num)) {
            return null;
        }
        $num = $num + 0;
        if (floor($num) == $num) {
            $num = intval($num);
        }
        return $num;
    }

    public static function remove_leading_zeros($str)
    {
        if (self::nx($str)) {
            return $str;
        }
        if (!is_string($str)) {
            return $str;
        }
        if (ltrim($str, '0') != '') {
            return ltrim($str, '0');
        }
        return '0';
    }

    public static function flatten_keys($array)
    {
        $return = [];
        foreach ($array as $key => $value) {
            $return[] = $key;
            if (is_array($value)) {
                $return = array_merge($return, self::flatten_keys($value));
            }
        }
        return $return;
    }

    public static function flatten_values($array)
    {
        $return = [];
        array_walk_recursive($array, function ($a) use (&$return) {
            $return[] = $a;
        });
        return $return;
    }

    public static function inside_out_values($array)
    {
        if (self::nx($array) || !is_array($array)) {
            return false;
        }
        if (empty($array)) {
            return [];
        }
        $first_key = null;
        foreach ($array as $array__key => $array__value) {
            $first_key = $array__key;
            break;
        }
        $return = [];
        foreach ($array[$first_key] as $array__index => $array__value) {
            foreach ($array as $array__field => $array__data) {
                if (self::x(@$array[$array__field][$array__index])) {
                    $return[$array__index][$array__field] = $array[$array__field][$array__index];
                }
            }
        }
        $return = self::remove_empty($return);
        return $return;
    }

    public static function arrays_to_objects($arr)
    {
        if (!is_object($arr) && !is_array($arr)) {
            return $arr;
        }
        if (is_array($arr)) {
            $new = new \stdClass();
            foreach ($arr as $arr__key => $arr__value) {
                if (is_object($arr__value) && isset($arr__value->id)) {
                    $new->{intval($arr__value->id)} = $arr__value;
                } elseif (is_array($arr__value) && isset($arr__value['id'])) {
                    $new->{intval($arr__value['id'])} = $arr__value;
                } else {
                    $new->$arr__key = $arr__value;
                }
            }
            $arr = $new;
        }
        foreach ($arr as $arr__key => $arr__value) {
            if (is_object($arr)) {
                $arr->$arr__key = self::arrays_to_objects($arr__value);
            } elseif (is_array($arr)) {
                $arr[$arr__key] = self::arrays_to_objects($arr__value);
            }
        }
        return $arr;
    }

    public static function fkey($array__key, $array)
    {
        if (array_keys($array)[0] === $array__key) {
            return true;
        }
        return false;
    }

    public static function lkey($array__key, $array)
    {
        if (array_keys($array)[count(array_keys($array)) - 1] === $array__key) {
            return true;
        }
        return false;
    }

    public static function last($array)
    {
        return array_values(array_slice($array, -1))[0];
    }

    public static function first($array)
    {
        return array_values(array_slice($array, 0, 1))[0];
    }

    public static function first_key($array)
    {
        if (self::nx($array) || !is_array($array)) {
            return null;
        }
        foreach ($array as $array__key => $array__value) {
            return $array__key;
        }
        return null;
    }

    public static function rand($array)
    {
        return $array[array_rand($array)];
    }

    public static function remove_first($array)
    {
        return array_values(array_slice($array, 1));
    }

    public static function remove_last($array)
    {
        return array_values(array_slice($array, 0, -1));
    }

    public static function string_is_json($str)
    {
        if (!is_string($str)) {
            return false;
        }
        json_decode($str);
        return json_last_error() == JSON_ERROR_NONE;
    }

    public static function string_is_html($str)
    {
        if (!is_string($str)) {
            return false;
        }
        if ($str != strip_tags($str)) {
            return true;
        }
        if ($str != html_entity_decode($str)) {
            return true;
        }
        return false;
    }

    public static function fetch($url, $method = null)
    {
        if ($method === 'curl' || ($method === null && function_exists('curl_version'))) {
            $result = self::curl($url)->result;
        } elseif (
            $method === 'php' ||
            ($method === null && file_get_contents(__FILE__) && ini_get('allow_url_fopen'))
        ) {
            $result = @file_get_contents(
                $url,
                false,
                stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false
                    ]
                ])
            );
        } else {
            $result = json_encode([]);
        }
        if (self::string_is_json($result)) {
            $result = json_decode($result);
        }
        return $result;
    }

    public static function json_response($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }

    public static function input($key = null, $fallback = null)
    {
        $p1 = $_POST;
        $p2 = json_decode(file_get_contents('php://input'), true);
        parse_str(file_get_contents('php://input'), $p3);
        if (isset($p3) && !empty($p3)) {
            foreach ($p3 as $p3__key => $p3__value) {
                unset($p3[$p3__key]);
                $p3[str_replace('amp;', '', $p3__key)] = $p3__value;
            }
        }
        if ($key !== null && $key != '') {
            if (isset($p1) && !empty($p1) && array_key_exists($key, $p1)) {
                return $p1[$key];
            }
            if (isset($p2) && !empty($p2) && array_key_exists($key, $p2)) {
                return $p2[$key];
            }
            if (isset($p3) && !empty($p3) && array_key_exists($key, $p3)) {
                return $p3[$key];
            }
        } else {
            if (isset($p1) && !empty($p1)) {
                return $p1;
            }
            if (isset($p2) && !empty($p2)) {
                return $p2;
            }
            if (isset($p3) && !empty($p3)) {
                return $p3;
            }
        }
        if ($fallback !== null) {
            return $fallback;
        }
        return null;
    }

    public static function extract_urls_from_sitemap($url, $basic_auth = null, $include_last_modified = false)
    {
        // auto detect basic auth
        if ($basic_auth === null && strpos($url, '@') !== false) {
            preg_match_all('/^https?:\/\/(.+)?:(.+)?@.*$/', $url, $matches, PREG_SET_ORDER);
            if (!empty($matches) && !empty($matches[0])) {
                $basic_auth = $matches[0][1] . ':' . $matches[0][2];
            }
        }
        // inject basic auth in url
        if ($basic_auth !== null && strpos($url, '@') === false) {
            $url = str_replace('http://', 'http://' . $basic_auth . '@', $url);
            $url = str_replace('https://', 'https://' . $basic_auth . '@', $url);
        }

        $return = [];
        if (self::x($url)) {
            // prevent cached sitemaps
            if (strpos($url, '?') === false) {
                $url .= '?no_cache=1';
            }

            $data = self::fetch($url);
            $data = @simplexml_load_string($data);

            // fallback
            if (self::nx($data)) {
                $data = @simplexml_load_file($url);
            }

            $data = json_decode(json_encode($data), true);

            if (self::x($data)) {
                // normalize
                if (isset($data['url']) && is_array($data['url']) && isset($data['url']['loc'])) {
                    $data['url'] = [$data['url']];
                }
                if (isset($data['sitemap']) && is_array($data['sitemap']) && isset($data['sitemap']['loc'])) {
                    $data['sitemap'] = [$data['sitemap']];
                }
                if (isset($data['url']) && is_array($data['url']) && !empty($data['url'])) {
                    foreach ($data['url'] as $url__value) {
                        if (isset($url__value['loc']) && is_string($url__value['loc']) && $url__value['loc'] != '') {
                            $return[] = [
                                'url' => $url__value['loc'],
                                'lastmod' =>
                                    isset($url__value['lastmod']) && $url__value['lastmod'] != ''
                                        ? self::date('Y-m-d H:i:s', $url__value['lastmod'])
                                        : null
                            ];
                        }
                    }
                }
                if (isset($data['sitemap']) && is_array($data['sitemap']) && !empty($data['sitemap'])) {
                    foreach ($data['sitemap'] as $sitemap__value) {
                        if (
                            isset($sitemap__value['loc']) &&
                            is_string($sitemap__value['loc']) &&
                            $sitemap__value['loc'] != ''
                        ) {
                            $return = array_merge(
                                $return,
                                self::extract_urls_from_sitemap($sitemap__value['loc'], $basic_auth, true)
                            );
                        }
                    }
                }
            }
        }
        $return = self::array_unique($return);
        usort($return, function ($a, $b) {
            return strnatcmp($a['url'], $b['url']);
        });
        if ($include_last_modified === false) {
            $return = array_map(function ($a) {
                return $a['url'];
            }, $return);
        }
        return $return;
    }

    public static function extract_title_from_url($url)
    {
        if (!__validate_url($url)) {
            return '';
        }
        $str = @file_get_contents($url);
        if (strlen($str) > 0) {
            $str = trim(preg_replace('/\s+/', ' ', $str));
            preg_match('/\<title\>(.*)\<\/title\>/i', $str, $title);
            return htmlspecialchars_decode($title[1]);
        }
        return '';
    }

    public static function extract_meta_desc_from_url($url)
    {
        if (!__validate_url($url)) {
            return '';
        }
        $tags = get_meta_tags($url);
        return @$tags['description'] ? htmlspecialchars_decode($tags['description']) : '';
    }

    public static function get_mime_map()
    {
        return [
            'video/3gpp2' => '3g2',
            'video/3gp' => '3gp',
            'video/3gpp' => '3gp',
            'application/x-compressed' => '7zip',
            'audio/x-acc' => 'aac',
            'audio/ac3' => 'ac3',
            'application/postscript' => 'ai',
            'audio/x-aiff' => 'aif',
            'audio/aiff' => 'aif',
            'audio/x-au' => 'au',
            'video/avi' => 'avi',
            'video/msvideo' => 'avi',
            'video/x-msvideo' => 'avi',
            'application/x-troff-msvideo' => 'avi',
            'application/x-binary' => 'bin',
            'application/macbinary' => 'bin',
            'application/mac-binary' => 'bin',
            'application/x-macbinary' => 'bin',
            'image/bmp' => 'bmp',
            'image/x-bmp' => 'bmp',
            'image/x-bitmap' => 'bmp',
            'image/x-xbitmap' => 'bmp',
            'image/x-win-bitmap' => 'bmp',
            'image/x-windows-bmp' => 'bmp',
            'image/ms-bmp' => 'bmp',
            'image/x-ms-bmp' => 'bmp',
            'application/bmp' => 'bmp',
            'application/x-bmp' => 'bmp',
            'application/x-win-bitmap' => 'bmp',
            'application/cdr' => 'cdr',
            'application/coreldraw' => 'cdr',
            'application/x-cdr' => 'cdr',
            'application/x-coreldraw' => 'cdr',
            'image/cdr' => 'cdr',
            'image/x-cdr' => 'cdr',
            'zz-application/zz-winassoc-cdr' => 'cdr',
            'application/mac-compactpro' => 'cpt',
            'application/pkix-crl' => 'crl',
            'application/pkcs-crl' => 'crl',
            'application/x-x509-ca-cert' => 'crt',
            'application/pkix-cert' => 'crt',
            'text/css' => 'css',
            'text/comma-separated-values' => 'csv',
            'text/x-comma-separated-values' => 'csv',
            'application/vnd.msexcel' => 'csv',
            'application/x-director' => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/x-dvi' => 'dvi',
            'message/rfc822' => 'eml',
            'application/x-msdownload' => 'exe',
            'video/x-f4v' => 'f4v',
            'audio/x-flac' => 'flac',
            'video/x-flv' => 'flv',
            'image/gif' => 'gif',
            'application/gpg-keys' => 'gpg',
            'application/x-gtar' => 'gtar',
            'application/x-gzip' => 'gzip',
            'application/x-binhex40' => 'hqx',
            'application/mac-binhex40' => 'hqx',
            'application/mac-binhex' => 'hqx',
            'application/x-mac-binhex40' => 'hqx',
            'text/html' => 'html',
            'image/x-ico' => 'ico',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'text/calendar' => 'ics',
            'application/java-archive' => 'jar',
            'application/x-java-application' => 'jar',
            'application/x-jar' => 'jar',
            'image/jp2' => 'jp2',
            'video/mj2' => 'jp2',
            'image/jpx' => 'jp2',
            'image/jpm' => 'jp2',
            'image/jpeg' => 'jpeg',
            'image/pjpeg' => 'jpeg',
            'application/x-javascript' => 'js',
            'application/json' => 'json',
            'text/json' => 'json',
            'application/vnd.google-earth.kml+xml' => 'kml',
            'application/vnd.google-earth.kmz' => 'kmz',
            'text/x-log' => 'log',
            'audio/x-m4a' => 'm4a',
            'audio/mp4' => 'm4a',
            'application/vnd.mpegurl' => 'm4u',
            'audio/midi' => 'mid',
            'application/vnd.mif' => 'mif',
            'video/quicktime' => 'mov',
            'video/x-sgi-movie' => 'movie',
            'audio/mp3' => 'mp3',
            'audio/mpeg' => 'mp3',
            'audio/mpg' => 'mp3',
            'audio/mpeg3' => 'mp3',
            'video/mp4' => 'mp4',
            'video/mpeg' => 'mpeg',
            'application/oda' => 'oda',
            'audio/ogg' => 'ogg',
            'video/ogg' => 'ogg',
            'application/ogg' => 'ogg',
            'font/otf' => 'otf',
            'application/x-pkcs10' => 'p10',
            'application/pkcs10' => 'p10',
            'application/x-pkcs12' => 'p12',
            'application/x-pkcs7-signature' => 'p7a',
            'application/pkcs7-mime' => 'p7c',
            'application/x-pkcs7-mime' => 'p7c',
            'application/x-pkcs7-certreqresp' => 'p7r',
            'application/pkcs7-signature' => 'p7s',
            'application/pdf' => 'pdf',
            'application/octet-stream' => 'pdf',
            'application/x-x509-user-cert' => 'pem',
            'application/x-pem-file' => 'pem',
            'application/pgp' => 'pgp',
            'application/php' => 'php',
            'application/x-httpd-php' => 'php',
            'application/x-php' => 'php',
            'text/php' => 'php',
            'text/x-php' => 'php',
            'application/x-httpd-php-source' => 'php',
            'image/png' => 'png',
            'image/x-png' => 'png',
            'application/powerpoint' => 'ppt',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.ms-office' => 'ppt',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop' => 'psd',
            'image/vnd.adobe.photoshop' => 'psd',
            'audio/x-realaudio' => 'ra',
            'audio/x-pn-realaudio' => 'ram',
            'application/x-rar' => 'rar',
            'application/rar' => 'rar',
            'application/x-rar-compressed' => 'rar',
            'audio/x-pn-realaudio-plugin' => 'rpm',
            'application/x-pkcs7' => 'rsa',
            'text/rtf' => 'rtf',
            'text/richtext' => 'rtx',
            'video/vnd.rn-realvideo' => 'rv',
            'application/x-stuffit' => 'sit',
            'application/smil' => 'smil',
            'text/srt' => 'srt',
            'image/svg+xml' => 'svg',
            'application/x-shockwave-flash' => 'swf',
            'application/x-tar' => 'tar',
            'application/x-gzip-compressed' => 'tgz',
            'image/tiff' => 'tiff',
            'font/ttf' => 'ttf',
            'text/plain' => 'txt',
            'text/x-vcard' => 'vcf',
            'application/videolan' => 'vlc',
            'text/vtt' => 'vtt',
            'audio/x-wav' => 'wav',
            'audio/wave' => 'wav',
            'audio/wav' => 'wav',
            'application/wbxml' => 'wbxml',
            'video/webm' => 'webm',
            'image/webp' => 'webp',
            'audio/x-ms-wma' => 'wma',
            'application/wmlc' => 'wmlc',
            'video/x-ms-wmv' => 'wmv',
            'video/x-ms-asf' => 'wmv',
            'font/woff' => 'woff',
            'font/woff2' => 'woff2',
            'application/xhtml+xml' => 'xhtml',
            'application/excel' => 'xl',
            'application/msexcel' => 'xls',
            'application/x-msexcel' => 'xls',
            'application/x-ms-excel' => 'xls',
            'application/x-excel' => 'xls',
            'application/x-dos_ms_excel' => 'xls',
            'application/xls' => 'xls',
            'application/x-xls' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel' => 'xlsx',
            'application/xml' => 'xml',
            'text/xml' => 'xml',
            'text/xsl' => 'xsl',
            'application/xspf+xml' => 'xspf',
            'application/x-compress' => 'z',
            'application/x-zip' => 'zip',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'application/s-compressed' => 'zip',
            'multipart/x-zip' => 'zip',
            'text/x-scriptzsh' => 'zsh'
        ];
    }

    public static function get_mime_type($filename)
    {
        $mime = null;
        // method 1
        if (function_exists('finfo_open')) {
            if (file_exists($filename)) {
                $finfo = finfo_open(FILEINFO_MIME);
                if ($finfo !== false) {
                    $mime = finfo_file($finfo, $filename);
                    finfo_close($finfo);
                }
            }
        }
        // method 2
        if (is_null($mime) && function_exists('mime_content_type')) {
            if (file_exists($filename)) {
                $mime = mime_content_type($filename);
            }
        }
        // method 3
        if (is_null($mime)) {
            $extension = mb_strtolower(explode('.', $filename)[count(explode('.', $filename)) - 1]);
            $mime_types = self::extension_to_mime_types($extension);
            if (self::x($mime_types)) {
                $mime = $mime_types[0];
            } else {
                $mime = 'application/octet-stream';
            }
        }
        return $mime;
    }

    public static function mime_type_to_extension($mime_type)
    {
        $mime_map = self::get_mime_map();
        return isset($mime_map[$mime_type]) ? $mime_map[$mime_type] : null;
    }

    public static function extension_to_mime_types($extension)
    {
        $mime_map = self::get_mime_map();
        $mime_types = [];
        foreach ($mime_map as $mime_map__key => $mime_map__value) {
            if ($mime_map__value === mb_strtolower($extension)) {
                $mime_types[] = $mime_map__key;
            }
        }
        return $mime_types;
    }

    public static function reverse_proxy($url, $receipts, $output_and_die = true)
    {
        if (!isset($url) || $url == '') {
            die();
        }

        $mime_type = 'text/html';
        if (mb_strrpos($url, '.') !== false && mb_substr($url, -1) !== '/') {
            $mime_type = self::get_mime_type(mb_substr($url, mb_strrpos($url, '.')));
            if ($mime_type === 'application/octet-stream') {
                $mime_type = 'text/html';
            }
        }

        if ($output_and_die === true) {
            header('Content-Type: ' . $mime_type);
        }

        $response = self::curl($url);

        $output = $response->result;

        if (!is_string($output) && is_object($output)) {
            $output = json_encode($output);
        }

        foreach ($receipts as $receipts__key => $receipts__value) {
            $is_regex_key = preg_match('/^\/.+\/[a-z]*$/i', $receipts__key);
            if (
                $receipts__key === '*' ||
                ($is_regex_key && preg_match($receipts__key, $url)) ||
                (!$is_regex_key && stripos($url, $receipts__key) !== false)
            ) {
                if ($mime_type === 'text/html') {
                    if (isset($receipts__value['dom']) && $receipts__value['dom'] != '') {
                        $domdocument = self::str_to_dom($output);
                        $domxpath = new \DOMXPath($domdocument);
                        $receipts__value['dom']($domdocument, $domxpath);
                        $output = self::dom_to_str($domdocument);
                    }
                    foreach (['css' => 'style', 'js' => 'script'] as $embed__key => $embed__value) {
                        if (isset($receipts__value[$embed__key]) && $receipts__value[$embed__key] != '') {
                            $domdocument = self::str_to_dom($output);
                            $domxpath = new \DOMXPath($domdocument);
                            $target = $domxpath->query('/html/head');
                            if ($target->length === 0) {
                                $target = $domxpath->query('/html/body');
                            }
                            if ($target->length === 0) {
                                continue;
                            }
                            $child = $domdocument->createElement($embed__value, '');
                            $child->nodeValue = $receipts__value[$embed__key];
                            $target[0]->appendChild($child);
                            $output = self::dom_to_str($domdocument);
                        }
                    }
                }
                if (isset($receipts__value['replacements']) && !empty($receipts__value['replacements'])) {
                    foreach ($receipts__value['replacements'] as $receipts__value__value) {
                        $is_regex_value = preg_match('/^\/.+\/[a-z]*$/i', $receipts__value__value[0]);
                        if ($is_regex_value) {
                            $output = preg_replace($receipts__value__value[0], $receipts__value__value[1], $output);
                        } else {
                            $output = str_replace($receipts__value__value[0], $receipts__value__value[1], $output);
                        }
                    }
                }
            }
        }

        if ($output_and_die === true) {
            echo $output;
            die();
        }
        return $output;
    }

    public static function curl(
        $url = '',
        $data = null,
        $method = null,
        $headers = null,
        $enable_cookies = false,
        $send_as_json = true,
        $timeout = 60,
        $basic_auth = null,
        $cookies = null,
        $follow_redirects = true,
        $proxy = null
    ) {
        // guess method based on data
        if ($method === null) {
            if (self::nx($data)) {
                $method = 'GET';
            } else {
                $method = 'POST';
            }
        }
        if ($method == 'GET' && self::x($data)) {
            $url .= '?' . http_build_query($data);
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // don't verify certificate
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

        if ($follow_redirects === true) {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        }

        // while you can provide your basic auth information inside the url,
        // this sometimes leads to problems (e.g. by redirects)
        // this function strips out the basic auth if available
        // and uses it properly
        if (self::nx($basic_auth) && strpos($url, '@') !== false) {
            preg_match_all('/^https?:\/\/(.+)?:(.+)?@.*$/', $url, $matches, PREG_SET_ORDER);
            if (!empty($matches) && !empty($matches[0])) {
                $url = str_replace($matches[0][1] . ':' . $matches[0][2] . '@', '', $url);
                $basic_auth = [$matches[0][1] => $matches[0][2]];
            }
        }

        if (self::x($basic_auth)) {
            curl_setopt(
                $curl,
                CURLOPT_USERPWD,
                self::first_key($basic_auth) . ':' . $basic_auth[self::first_key($basic_auth)]
            );
        }

        if (self::x($cookies)) {
            $cookies_curl = [];
            foreach ($cookies as $cookies__key => $cookies__value) {
                if ($cookies__key != 'Array') {
                    $cookies_curl[] = $cookies__key . '=' . $cookies__value;
                }
            }
            curl_setopt($curl, CURLOPT_COOKIE, implode(';', $cookies_curl));
        }

        /* set proxy */
        if (self::x($proxy)) {
            curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, 1);
            if (mb_strpos($proxy, '@') !== false) {
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, explode('@', $proxy)[0]);
                $proxy = explode('@', $proxy)[1];
            }
            if (mb_strpos($proxy, ':') !== false) {
                curl_setopt($curl, CURLOPT_PROXYPORT, explode(':', $proxy)[1]);
                $proxy = explode(':', $proxy)[0];
            }
            curl_setopt($curl, CURLOPT_PROXY, $proxy);
        }

        /* encode data */
        if (self::x($data) && (is_array($data) || is_object($data))) {
            if ($send_as_json === true) {
                $data = json_encode($data);
            } else {
                $data = http_build_query($data);
            }
        }

        /* prepare headers */
        $curl_headers = [];
        if (($method == 'POST' || $method === 'PUT') && self::x($data)) {
            if ($send_as_json === true) {
                $curl_headers[] = 'Content-Type: application/json';
                $curl_headers[] = 'Content-Length: ' . strlen($data);
            } else {
                $curl_headers[] = 'Content-Type: application/x-www-form-urlencoded';
            }
        }
        foreach (self::i($headers) as $headers__key => $headers__value) {
            $curl_headers[] = $headers__key . ': ' . $headers__value;
        }
        if (self::nx($curl_headers)) {
            curl_setopt($curl, CURLOPT_HEADER, false);
        } else {
            curl_setopt($curl, CURLOPT_HTTPHEADER, self::nx($curl_headers) ? false : $curl_headers);
        }
        if ($method == 'GET') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        }
        if ($method == 'DELETE') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        if ($method == 'POST' || $method === 'PUT') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            if ($method === 'POST') {
                curl_setopt($curl, CURLOPT_POST, 1);
            }
            if (self::x($data)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        }
        if ($enable_cookies === true) {
            $cookie_filename = 'stringhelper-curl-cookies.txt';
            if (isset($GLOBALS[$cookie_filename])) {
                file_put_contents($cookie_filename, $GLOBALS[$cookie_filename]);
            }
            curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_filename); // read
            curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_filename); // store
        }

        $headers = [];
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headers) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) {
                return $len;
            }
            $headers[strtolower(trim($header[0]))][] = trim($header[1]);
            return $len;
        });

        $result = curl_exec($curl);

        $curl_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);

        curl_close($curl);

        if ($enable_cookies === true) {
            if (file_exists($cookie_filename)) {
                $GLOBALS[$cookie_filename] = file_get_contents($cookie_filename);
                @unlink($cookie_filename);
            }
        }

        if (self::string_is_json($result)) {
            $result = json_decode($result);
        }

        return (object) [
            'result' => $result,
            'status' => $curl_status,
            'headers' => $headers,
            'url' => $curl_url
        ];
    }

    public static function has_basic_auth($url)
    {
        if (self::nx($url)) {
            return false;
        }
        if (!self::validate_url($url)) {
            return false;
        }
        $response = self::curl($url, null, 'GET');
        if (self::nx($response)) {
            return false;
        }
        if (
            is_object($response) &&
            isset($response->headers) &&
            is_array($response->headers) &&
            array_key_exists('www-authenticate', $response->headers)
        ) {
            return true;
        }
        return false;
    }

    public static function check_basic_auth($url, $username = '', $password = '')
    {
        if (self::nx($url)) {
            return true;
        }
        if (!self::validate_url($url)) {
            return true;
        }
        $response = self::curl($url, null, 'GET', null, false, false, 5, [$username => $password]);
        if (self::nx($response)) {
            return true;
        }
        if (
            is_object($response) &&
            isset($response->headers) &&
            is_array($response->headers) &&
            array_key_exists('www-authenticate', $response->headers)
        ) {
            return false;
        }
        return true;
    }

    public static function exception($message = '')
    {
        throw new \ExtendedException($message);
    }

    public static function exception_message($t)
    {
        $message = $t->getMessage();
        if (self::is_serialized($message)) {
            $message = unserialize($message);
        }
        return $message;
    }

    public static function success($message = '')
    {
        return (object) ['success' => true, 'message' => $message];
    }

    public static function error($message = '')
    {
        return (object) ['success' => false, 'message' => $message];
    }

    public static function hook_fire($hook_name, $arg = null)
    {
        if (!isset($GLOBALS['hook']) || !is_array($GLOBALS['hook'])) {
            $GLOBALS['hook'] = [];
        }
        if (isset($GLOBALS['hook'][$hook_name])) {
            usort($GLOBALS['hook'][$hook_name], function ($a, $b) {
                if ($a['priority'] < $b['priority']) {
                    return -1;
                }
                if ($a['priority'] > $b['priority']) {
                    return 1;
                }
                if ($a['timestamp'] < $b['timestamp']) {
                    return -1;
                }
                if ($a['timestamp'] > $b['timestamp']) {
                    return 1;
                }
                return 0;
            });
            foreach ($GLOBALS['hook'][$hook_name] as $value) {
                $arg = $value['function']($arg);
            }
        }
        return $arg;
    }

    public static function hook_add($hook_name, $fun, $priority = 0)
    {
        if (!isset($GLOBALS['hook']) || !is_array($GLOBALS['hook'])) {
            $GLOBALS['hook'] = [];
        }
        if (!isset($GLOBALS['hook'][$hook_name])) {
            $GLOBALS['hook'][$hook_name] = [];
        }
        $GLOBALS['hook'][$hook_name][] = ['function' => $fun, 'priority' => $priority, 'timestamp' => microtime()];
    }

    public static function os()
    {
        if (stristr(PHP_OS, 'DAR')) {
            return 'mac';
        }
        if (stristr(PHP_OS, 'WIN') || stristr(PHP_OS, 'CYGWIN')) {
            return 'windows';
        }
        if (stristr(PHP_OS, 'LINUX')) {
            return 'linux';
        }
        return 'unknown';
    }

    public static function uuid()
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // set version to 0100
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function validate_uuid($str, $strict_check = true)
    {
        if (self::nx($str)) {
            return false;
        }
        if ($strict_check === true) {
            $regex = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
        } else {
            $regex = '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i';
        }
        return preg_match($regex, $str) ? true : false;
    }

    public static function url()
    {
        if (self::nx(@$_SERVER['HTTP_HOST']) || self::nx(@$_SERVER['REQUEST_URI'])) {
            return null;
        }
        $url =
            'http' .
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '') .
            '://' .
            $_SERVER['HTTP_HOST'] .
            $_SERVER['REQUEST_URI'];
        return rtrim($url, '/');
    }

    public static function urlWithoutArgs()
    {
        if (self::nx(@$_SERVER['HTTP_HOST']) || self::nx(@$_SERVER['REQUEST_URI'])) {
            return null;
        }
        $url =
            'http' .
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '') .
            '://' .
            $_SERVER['HTTP_HOST'] .
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return rtrim($url, '/');
    }

    public static function baseurl()
    {
        if (self::nx(@$_SERVER['HTTP_HOST'])) {
            return null;
        }
        $url =
            'http' .
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '') .
            '://' .
            $_SERVER['HTTP_HOST'];
        return rtrim($url, '/');
    }

    public static function timestamp_excel_to_str($timestamp)
    {
        return gmdate('Y-m-d H:i:s', round(($timestamp - 25569) * 86400));
    }

    public static function timestamp_str_to_excel($str)
    {
        $utc = new \DateTimeZone('UTC');
        $dt = new \DateTime($str, $utc);
        return 25569 + $dt->getTimestamp() / 86400;
    }

    public static function video_info($str)
    {
        $response = __::video_info_suggestion($str);
        if ($response === null) {
            return null;
        }
        if (__::video_info_check($response['id'], $response['provider']) === false) {
            return null;
        }
        return [
            'id' => $response['id'],
            'provider' => $response['provider'],
            'thumbnail' => __::video_info_thumbnail($response['id'], $response['provider'])
        ];
    }

    public static function video_info_suggestion($str)
    {
        if (__::nx($str)) {
            return null;
        }
        preg_match(
            '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i',
            $str,
            $matches
        );
        if (!empty($matches)) {
            return ['id' => $matches[1], 'provider' => 'youtube'];
        }
        preg_match(
            '/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)(?:[a-zA-Z0-9_\-]+)?/i',
            $str,
            $matches
        );
        if (!empty($matches)) {
            return ['id' => $matches[1], 'provider' => 'vimeo'];
        }
        if (strpos($str, 'http') === false) {
            if (mb_strlen($str) == 11) {
                return ['id' => $str, 'provider' => 'youtube'];
            }
            if (__::is_integer($str)) {
                return ['id' => $str, 'provider' => 'vimeo'];
            }
        }
        return null;
    }

    public static function video_info_check($id, $provider)
    {
        if ($provider === 'youtube') {
            $url = 'https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=' . $id . '&format=json';
            $code = substr(get_headers($url)[0], 9, 3);
            return strpos($code, '4') !== 0;
        }
        if ($provider === 'vimeo') {
            return @file_get_contents('http://vimeo.com/api/v2/video/' . $id . '.json') != '';
        }
    }

    public static function video_info_thumbnail($id, $provider)
    {
        $content = '';
        if ($provider === 'youtube') {
            $content = @file_get_contents('https://img.youtube.com/vi/' . $id . '/0.jpg');
        }
        if ($provider === 'vimeo') {
            $content = @file_get_contents('http://vimeo.com/api/v2/video/' . $id . '.json');
            if ($content != '') {
                $content = json_decode($content);
                $content = @file_get_contents($content[0]->thumbnail_large);
            }
        }
        if ($content == '') {
            return null;
        }
        return 'data:image/jpeg;base64,' . base64_encode($content);
    }

    public static function char_to_int($letters)
    {
        $num = 0;
        $arr = array_reverse(str_split($letters));
        for ($i = 0; $i < count($arr); $i++) {
            $num += (ord(strtolower($arr[$i])) - 96) * pow(26, $i);
        }
        return $num;
    }

    public static function int_to_char($num)
    {
        $letters = '';
        while ($num > 0) {
            $code = $num % 26 == 0 ? 26 : $num % 26;
            $letters .= chr($code + 64);
            $num = ($num - $code) / 26;
        }
        return strtoupper(strrev($letters));
    }

    public static function inc_char($char, $shift = 1)
    {
        return self::int_to_char(self::char_to_int($char) + $shift);
    }

    public static function dec_char($char, $shift = 1)
    {
        return self::int_to_char(self::char_to_int($char) - $shift);
    }

    public static function str_replace_first($search, $replace, $str)
    {
        $newstring = $str;
        $pos = strpos($str, $search);
        if ($pos !== false) {
            $newstring = substr_replace($str, $replace, $pos, strlen($search));
        }
        return $newstring;
    }

    public static function str_replace_last($search, $replace, $str)
    {
        $newstring = $str;
        $pos = strrpos($str, $search);
        if ($pos !== false) {
            $newstring = substr_replace($str, $replace, $pos, strlen($search));
        }
        return $newstring;
    }

    public static function csv2array($filename, $delimiter = ';', $enclosure = '"')
    {
        if (self::nx($filename) || !file_exists($filename)) {
            return false;
        }
        $array = array_map(function ($d) use ($delimiter, $enclosure) {
            return array_map(function ($d2) {
                if (!mb_detect_encoding($d2, 'UTF-8', true)) {
                    $d2 = utf8_encode($d2);
                }
                return $d2;
            }, str_getcsv($d, $delimiter, $enclosure));
        }, file($filename));
        return $array;
    }

    public static function array2csv($array, $filename, $delimiter = ';', $enclosure = '"')
    {
        $fp = fopen($filename, 'wb');
        foreach ($array as $array__fields) {
            fputcsv($fp, $array__fields, $delimiter, $enclosure);
        }
        fclose($fp);
        return true;
    }

    public static function array2xml($arr, $filename = null, $prolog_attrs = null, $strip_empty_tags = false)
    {
        if ($strip_empty_tags === true) {
            $arr = self::array_filter_recursive_all($arr, function ($value, $key, $key_chain) {
                if ($key === 'content' && self::nx($value)) {
                    return true;
                }
                if (is_array($value) && array_key_exists('content', $value) && self::nx($value['content'])) {
                    return true;
                }
                if (is_array($value) && array_key_exists('tag', $value) && !array_key_exists('content', $value)) {
                    return true;
                }
                return false;
            });
        }

        $xw = xmlwriter_open_memory();
        xmlwriter_set_indent($xw, 1);
        $res = xmlwriter_set_indent_string($xw, ' ');
        xmlwriter_start_document($xw, '1.0', 'UTF-8');
        if (is_array($arr)) {
            self::array2xml_rec($arr, $xw);
        }
        xmlwriter_end_document($xw);
        $xml = xmlwriter_output_memory($xw);
        // check (this throws an exception is something is wrong, e.g. 2 root elements)
        simplexml_load_string($xml);
        // finally modify prolog attributes (this must be done manually since php has no functions for it)
        if (is_array($prolog_attrs) && !empty($prolog_attrs)) {
            $prolog_attrs_prep = [];
            foreach ($prolog_attrs as $prolog_attrs__key => $prolog_attrs__value) {
                $prolog_attrs_prep[] = $prolog_attrs__key . '="' . $prolog_attrs__value . '"';
            }
            $xml = str_replace('version="1.0" encoding="UTF-8"', implode(' ', $prolog_attrs_prep), $xml);
        }
        if ($filename !== null) {
            file_put_contents($filename, $xml);
        } else {
            return $xml;
        }
    }
    private static function array2xml_rec($arr, &$xw)
    {
        foreach ($arr as $arr__value) {
            if (!isset($arr__value['tag'])) {
                continue;
            }
            xmlwriter_start_element($xw, $arr__value['tag']);
            if (isset($arr__value['attrs'])) {
                foreach ($arr__value['attrs'] as $attrs__key => $attrs__value) {
                    xmlwriter_start_attribute($xw, $attrs__key);
                    xmlwriter_text($xw, $attrs__value);
                    xmlwriter_end_attribute($xw);
                }
            }
            if (isset($arr__value['content'])) {
                if (is_array($arr__value['content']) && !empty($arr__value['content'])) {
                    self::array2xml_rec($arr__value['content'], $xw);
                } elseif (!is_array($arr__value['content'])) {
                    // map to string
                    if (!is_string($arr__value['content'])) {
                        $arr__value['content'] = (string) $arr__value['content'];
                    }
                    xmlwriter_text($xw, $arr__value['content']);
                }
            }
            xmlwriter_end_element($xw);
        }
    }

    public static function xml2array($filename)
    {
        $xml = file_get_contents($filename);
        // the parser accepts only version 1.0; try to fallback
        $xml = preg_replace('/^(<\?xml version=")(.+)(")/i', '${1}1.0${3}', $xml);
        $SimpleXMLElement = simplexml_load_string($xml);
        $data = self::xml2array_rec($SimpleXMLElement);
        return $data;
    }
    private static function xml2array_rec($SimpleXMLElement)
    {
        $data = [];
        $data_this = [];
        $data_this['tag'] = $SimpleXMLElement->getName();
        if (!empty($SimpleXMLElement->attributes())) {
            $data_this['attrs'] = [];
            foreach ($SimpleXMLElement->attributes() as $attrs__key => $attrs__value) {
                $data_this['attrs'][$attrs__key] = (string) $attrs__value;
            }
        }
        if ($SimpleXMLElement->count() === 0) {
            if ((string) $SimpleXMLElement != '') {
                $data_this['content'] = (string) $SimpleXMLElement;
            }
        } else {
            $data_this['content'] = [];
            foreach ($SimpleXMLElement->children() as $children__value) {
                $data_this['content'] = array_merge($data_this['content'], self::xml2array_rec($children__value));
            }
        }
        $data[] = $data_this;
        return $data;
    }

    public static function sed_replace($replacements, $filename)
    {
        if (!file_exists($filename)) {
            return;
        }
        if (!is_array($replacements)) {
            return;
        }
        $command = 'sed -i' . (self::os() === 'mac' ? " ''" : '') . '';
        foreach ($replacements as $replacements__key => $replacements__value) {
            $command .=
                " -e 's/" . self::sed_escape($replacements__key) . '/' . self::sed_escape($replacements__value) . "/g'";
        }
        $command .= ' "' . $filename . '"';
        shell_exec($command);
    }

    public static function sed_prepend($str, $filename)
    {
        if (!file_exists($filename)) {
            return;
        }
        $new_line = "\\n";
        if (self::os() === 'mac') {
            $new_line = "'$'\\\n''";
        }
        $command = 'sed -i' . (self::os() === 'mac' ? " ''" : '') . '';
        $command .= " '1s;^;" . self::sed_escape($str) . '' . $new_line . ";'";
        $command .= ' "' . $filename . '"';
        shell_exec($command);
    }

    public static function sed_append($str, $filename)
    {
        if (!file_exists($filename)) {
            return;
        }
        $command = '';
        if (self::os() === 'mac') {
            // sed does not work properly here in all environments
            $command .= 'printf "\n' . $str . '" >> "' . $filename . '"';
        } else {
            $command = 'sed -i' . (self::os() === 'mac' ? " ''" : '') . '';
            $command .= " '$ a\\" . self::sed_escape($str) . "'";
            $command .= ' "' . $filename . '"';
        }
        shell_exec($command);
    }

    public static function sed_escape($str)
    {
        return str_replace('/', '\/', str_replace('&', '\&', str_replace(';', '\;', preg_quote($str))));
    }

    public static function diff($str1, $str2)
    {
        $filename1 = sys_get_temp_dir() . '/' . md5(uniqid());
        $filename2 = sys_get_temp_dir() . '/' . md5(uniqid());
        file_put_contents($filename1, $str1);
        file_put_contents($filename2, $str2);
        $result = shell_exec('diff ' . $filename1 . ' ' . $filename2);
        if ($result === null) {
            $result = '';
        }
        $diff = trim($result);
        @unlink($filename1);
        @unlink($filename2);
        return $diff;
    }

    public static function line_endings_convert($str, $os)
    {
        if (self::nx($str) || !is_string($str) || self::nx($os) || !in_array($os, ['linux', 'mac', 'windows'])) {
            return '';
        }
        if ($os === 'linux') {
            $str = str_replace("\r\n", "\n", $str);
            $str = str_replace("\r", "\n", $str);
        }
        if ($os === 'mac') {
            $str = str_replace("\r\n", "\n", $str);
            $str = str_replace("\r", "\n", $str);
            $str = str_replace("\n", "\r", $str);
        }
        if ($os === 'windows') {
            $str = str_replace("\r\n", "\n", $str);
            $str = str_replace("\r", "\n", $str);
            $str = str_replace("\n", "\r\n", $str);
        }
        return $str;
    }

    public static function line_endings_weak_equals($str1, $str2)
    {
        if ((!is_string($str1) || !is_string($str2)) && $str1 !== $str2) {
            return false;
        }
        return self::line_endings_convert($str1, 'linux') === self::line_endings_convert($str2, 'linux');
    }

    public static function log_begin($message = null)
    {
        if (!isset($GLOBALS['performance'])) {
            $GLOBALS['performance'] = [];
        }
        $GLOBALS['performance'][] = [
            'message' => $message,
            'time' => microtime(true)
        ];
    }

    public static function log_end($message = null, $echo = true)
    {
        if (!isset($GLOBALS['performance'])) {
            $GLOBALS['performance'] = [];
        }
        $performance_active_key = null;
        foreach (array_reverse($GLOBALS['performance'], true) as $performance__key => $performance__value) {
            if ($performance__value['message'] === $message || $message === null) {
                $performance_active_key = $performance__key;
                break;
            }
        }
        if ($performance_active_key === null) {
            return null;
        }
        $message = $GLOBALS['performance'][$performance_active_key]['message'];
        $time = number_format(microtime(true) - $GLOBALS['performance'][$performance_active_key]['time'], 5);
        if ($echo === true) {
            echo 'script' . ($message != '' ? ' ' . $message : '') . ' execution time: ' . $time . ' seconds' . PHP_EOL;
        }
        unset($GLOBALS['performance'][$performance_active_key]);
        $GLOBALS['performance'] = array_values($GLOBALS['performance']);
        return [
            'message' => $message,
            'time' => $time
        ];
    }

    public static function image_compress($source, $quality = 90, $destination = null)
    {
        if ($destination === null) {
            $destination = $source;
        }
        $info = getimagesize($source);
        if ($info['mime'] === 'image/jpeg') {
            $image = imagecreatefromjpeg($source);
            imagejpeg($image, $destination, $quality);
        } elseif ($info['mime'] === 'image/png') {
            $image = imagecreatefrompng($source);
            imagepng($image, $destination, $quality);
        } elseif ($info['mime'] === 'image/gif') {
            $image = imagecreatefromgif($source);
            imagetruecolortopalette($image, false, 16);
            imagegif($image, $destination);
        }
        return true;
    }

    public static function image_orientate($source, $quality = 90, $destination = null)
    {
        if ($destination === null) {
            $destination = $source;
        }
        $info = getimagesize($source);
        if ($info['mime'] === 'image/jpeg') {
            $exif = @exif_read_data($source);
            if (!empty($exif['Orientation']) && in_array($exif['Orientation'], [2, 3, 4, 5, 6, 7, 8])) {
                $image = imagecreatefromjpeg($source);
                if (in_array($exif['Orientation'], [3, 4])) {
                    $image = imagerotate($image, 180, 0);
                }
                if (in_array($exif['Orientation'], [5, 6])) {
                    $image = imagerotate($image, -90, 0);
                }
                if (in_array($exif['Orientation'], [7, 8])) {
                    $image = imagerotate($image, 90, 0);
                }
                if (in_array($exif['Orientation'], [2, 5, 7, 4])) {
                    imageflip($image, IMG_FLIP_HORIZONTAL);
                }
                imagejpeg($image, $destination, $quality);
            }
        }
        return true;
    }

    public static function file_extension($filename)
    {
        if (!is_string($filename)) {
            return null;
        }
        if (strpos($filename, '.') === false) {
            return null;
        }
        $filename = explode('.', $filename);
        $filename = $filename[count($filename) - 1];
        $filename = mb_strtolower($filename);
        return $filename;
    }

    public static function is_utf8($str)
    {
        if (!is_string($str)) {
            return false;
        }
        if ($str == '') {
            return true;
        }
        $current_encoding = mb_detect_encoding($str, ['UTF-8', 'ISO-8859-1', 'WINDOWS-1252']);
        return $current_encoding === 'UTF-8';
    }

    public static function to_utf8($str)
    {
        if (!is_string($str) || $str == '') {
            return $str;
        }
        $current_encoding = mb_detect_encoding($str, ['UTF-8', 'ISO-8859-1', 'WINDOWS-1252']);
        if ($current_encoding !== 'UTF-8') {
            $str = iconv($current_encoding, 'UTF-8', $str);
        }
        return $str;
    }

    public static function iptc_codes()
    {
        return [
            '2#005' => 'DocumentTitle',
            '2#010' => 'Urgency',
            '2#015' => 'Category',
            '2#020' => 'Subcategories',
            '2#040' => 'SpecialInstructions',
            '2#055' => 'CreationDate',
            '2#060' => 'CreationTime',
            '2#080' => 'AuthorByline',
            '2#085' => 'AuthorTitle',
            '2#090' => 'City',
            '2#095' => 'State',
            '2#101' => 'Country',
            '2#103' => 'OTR',
            '2#105' => 'Headline',
            '2#110' => 'Source',
            '2#115' => 'PhotoSource',
            '2#116' => 'Copyright',
            '2#120' => 'Caption',
            '2#122' => 'CaptionWriter',
            '2#000' => 'Unknown'
        ];
    }

    public static function iptc_code($code)
    {
        if ($code !== null && is_string($code) && in_array($code, self::iptc_codes())) {
            foreach (self::iptc_codes() as $codes__key => $codes__value) {
                if ($codes__value == $code) {
                    return $codes__key;
                    break;
                }
            }
        }
        return null;
    }

    public static function iptc_keyword($code)
    {
        if ($code !== null && is_string($code) && array_key_exists($code, self::iptc_codes())) {
            return self::iptc_codes()[$code];
        }
        return null;
    }

    public static function iptc_read($filename, $field = null)
    {
        if (
            !is_string($filename) ||
            self::nx($filename) ||
            !file_exists($filename) ||
            !in_array(self::file_extension($filename), ['jpg', 'jpeg'])
        ) {
            return $field === null ? [] : null;
        }
        if ($field !== null && self::iptc_code($field) !== null) {
            $field = self::iptc_code($field);
        }
        $size = getimagesize($filename, $info);
        if (isset($info['APP13'])) {
            $iptc = iptcparse($info['APP13']);
            if (self::x($iptc)) {
                if ($field !== null && !array_key_exists($field, $iptc)) {
                    return null;
                }
                $ret = [];
                foreach ($iptc as $iptc__key => $iptc__value) {
                    // utf8 support: always convert from iso to utf8
                    //if (isset($iptc['1#090']) && $iptc['1#090'][0] == "\x1B%G") {}
                    $iptc__value[0] = self::to_utf8($iptc__value[0]);
                    if ($field !== null && $iptc__key == $field) {
                        return $iptc__value[0];
                    }
                    if ($iptc__key === '1#090') {
                        continue;
                    }
                    $ret[$iptc__key] = $iptc__value[0];
                }
                return $ret;
            }
        }
        return $field === null ? [] : null;
    }
    public static function iptc_write($filename, $field, $value = null)
    {
        if ($field === null) {
            $field = [];
        }

        if (
            !is_string($filename) ||
            self::nx($filename) ||
            !file_exists($filename) ||
            !in_array(self::file_extension($filename), ['jpg', 'jpeg'])
        ) {
            return false;
        }

        // files without any iptc tag won't work when using iptcembed
        // we first have to add a header by using this workaround
        // (this is the only workaround i could find that works properly)
        getimagesize($filename, $info);
        if (!isset($info['APP13'])) {
            // this works and does not change the compression
            if (extension_loaded('imagick')) {
                $img = new \Imagick($filename);
                $profiles = $img->getImageProfiles('icc', true);
                $img->stripImage();
                if (!empty($profiles)) {
                    $img->profileImage('icc', $profiles['icc']);
                }
                $img->writeImage($filename);
                $img->clear();
                $img->destroy();
            }
            // problem: this changes the compression of the file(!)
            else {
                $img = imagecreatefromjpeg($filename);
                imagejpeg($img, $filename, -1);
                imagedestroy($img);
            }
        }

        $values = [];
        if (is_string($field)) {
            $values = [$field => $value];
        } elseif (is_array($field)) {
            $values = $field;
        }

        $iptc = [];
        if (is_string($field)) {
            $iptc = self::iptc_read($filename);
        }
        foreach ($values as $values__key => $values__value) {
            if (self::iptc_code($values__key) !== null) {
                $values__key = self::iptc_code($values__key);
            }
            $iptc[$values__key] = $values__value;
        }

        $data = '';
        $utf8seq = chr(0x1b) . chr(0x25) . chr(0x47);
        $utf8length = strlen($utf8seq);
        $data .= chr(0x1c) . chr(1) . chr('090') . chr($utf8length >> 8) . chr($utf8length & 0xff) . $utf8seq;
        foreach ($iptc as $tag => $string) {
            if ($string === null) {
                continue;
            }
            $tag = substr($tag, 2);
            $iptc_rec = 2;
            $iptc_data = $tag;
            $iptc_value = $string;
            // always store in utf8
            $iptc_value = self::to_utf8($iptc_value);
            $iptc_length = strlen($iptc_value);
            $iptc_retval = chr(0x1c) . chr($iptc_rec) . chr($iptc_data);
            if ($iptc_length < 0x8000) {
                $iptc_retval .= chr($iptc_length >> 8) . chr($iptc_length & 0xff);
            } else {
                $iptc_retval .=
                    chr(0x80) .
                    chr(0x04) .
                    chr(($iptc_length >> 24) & 0xff) .
                    chr(($iptc_length >> 16) & 0xff) .
                    chr(($iptc_length >> 8) & 0xff) .
                    chr($iptc_length & 0xff);
            }
            $data .= $iptc_retval . $iptc_value;
        }
        $content = iptcembed($data, $filename);
        $fp = fopen($filename, 'wb');
        fwrite($fp, $content);
        fclose($fp);

        return true;
    }

    public static function encrypt($string, $salt = null)
    {
        if ($salt === null) {
            $salt = hash('sha256', uniqid(mt_rand(), true));
        } // this is an unique salt per entry and directly stored within a password
        return base64_encode(
            openssl_encrypt(
                $string,
                'AES-256-CBC',
                \ENCRYPTION_KEY,
                0,
                str_pad(substr($salt, 0, 16), 16, '0', STR_PAD_LEFT)
            )
        ) .
            ':' .
            $salt;
    }

    public static function decrypt($string)
    {
        if (count(explode(':', $string)) !== 2) {
            return $string;
        }
        $salt = explode(':', $string)[1];
        $string = explode(':', $string)[0]; // read salt from entry
        return openssl_decrypt(
            base64_decode($string),
            'AES-256-CBC',
            \ENCRYPTION_KEY,
            0,
            str_pad(substr($salt, 0, 16), 16, '0', STR_PAD_LEFT)
        );
    }

    public static function encrypt_poor($string)
    {
        $folder = sys_get_temp_dir();
        if (defined('ENCRYPTION_FOLDER')) {
            $folder = \ENCRYPTION_FOLDER;
        }
        $folder = rtrim($folder, '/');
        $token = md5(uniqid(mt_rand(), true));
        file_put_contents($folder . '/' . $token, $string);
        return $token;
    }

    public static function decrypt_poor($token, $once = false)
    {
        $folder = sys_get_temp_dir();
        if (defined('ENCRYPTION_FOLDER')) {
            $folder = \ENCRYPTION_FOLDER;
        }
        $folder = rtrim($folder, '/');
        if (!file_exists($folder . '/' . $token)) {
            return null;
        }
        $string = file_get_contents($folder . '/' . $token);
        if ($once === true) {
            @unlink($folder . '/' . $token);
        }
        return $string;
    }

    public static function files_in_folder($folder = '.', $recursive = false, $exclude = [])
    {
        $folder = rtrim($folder, '/');
        $files = [];
        if (file_exists($folder) && is_dir($folder)) {
            if ($handle = opendir($folder)) {
                while (false !== ($fileOrFolder = readdir($handle))) {
                    if ($fileOrFolder != '.' && $fileOrFolder != '..' && !in_array($fileOrFolder, $exclude)) {
                        if (!is_dir($folder . '/' . $fileOrFolder)) {
                            $files[] = $fileOrFolder;
                        } elseif ($recursive === true) {
                            $files = array_merge(
                                $files,
                                self::files_in_folder($folder . '/' . $fileOrFolder, $recursive)
                            );
                        }
                    }
                }
                closedir($handle);
            }
        }
        return $files;
    }

    public static function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir . '/' . $object) == 'dir') {
                        self::rrmdir($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    public static function is_base64_encoded($str)
    {
        if (!is_string($str)) {
            return false;
        }
        if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $str)) {
            return true;
        } else {
            return false;
        }
    }

    public static function is_eloquent_builder($data)
    {
        return is_object($data) && get_class($data) === 'Illuminate\Database\Eloquent\Builder';
    }

    public static function is_external($link)
    {
        if (self::nx($link)) {
            return false;
        }
        return (strpos($link, self::baseurl()) === false &&
            strpos($link, 'mailto') === false &&
            strpos($link, 'tel') === false) ||
            strpos($link, '.pdf') !== false;
    }

    public static function pushId()
    {
        /* https://gist.github.com/datasage/fbd4cdc725598e184c7d */
        if (!isset($GLOBALS['lastPushTime'])) {
            $GLOBALS['lastPushTime'] = 0;
        }
        if (!isset($GLOBALS['lastRandChars'])) {
            $GLOBALS['lastRandChars'] = [];
        }
        $PUSH_CHARS = '-0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';
        $now = (int) (microtime(true) * 1000);
        $isDuplicateTime = $now === $GLOBALS['lastPushTime'];
        $GLOBALS['lastPushTime'] = $now;
        $timeStampChars = new \SplFixedArray(8);
        for ($i = 7; $i >= 0; $i--) {
            $timeStampChars[$i] = substr($PUSH_CHARS, $now % 64, 1);
            // NOTE: Can't use << here because javascript will convert to int and lose the upper bits.
            $now = (int) floor($now / 64);
        }
        $id = implode('', $timeStampChars->toArray());
        if (!$isDuplicateTime) {
            for ($i = 0; $i < 12; $i++) {
                $GLOBALS['lastRandChars'][$i] = (int) floor(rand(0, 63));
            }
        } else {
            // If the timestamp hasn't changed since last push, use the same random number, except incremented by 1.
            usleep(10); // additionally prevent duplicates
            for ($i = 11; $i >= 0 && $GLOBALS['lastRandChars'][$i] === 63; $i--) {
                $GLOBALS['lastRandChars'][$i] = 0;
            }
            $GLOBALS['lastRandChars'][$i]++;
        }
        for ($i = 0; $i < 12; $i++) {
            $id .= substr($PUSH_CHARS, $GLOBALS['lastRandChars'][$i], 1);
        }
        return $id;
    }

    /* LEGACY CODE */

    // like redirect_to, but with different arguments
    public static function redirect($url = null)
    {
        if ($url == null) {
            $url = @$_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
            $url .= $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
            if (self::x(@$_GET['page_id'])) {
                $url .= '?page_id=' . $_GET['page_id'];
            }
        }
        echo '<meta http-equiv="refresh" content="0; url=\'' . $url . '\'">';
        die();
    }

    // same as __v
    public static function f(...$args)
    {
        foreach ($args as $arg) {
            if (self::x(@$arg)) {
                return $arg;
            }
        }
        return null;
    }

    // swap variables
    public static function swap(&$x, &$y)
    {
        $tmp = $x;
        $x = $y;
        $y = $tmp;
    }

    // check if multiple variables exists
    public static function mx()
    {
        for ($i = 0; $i < func_num_args(); $i++) {
            $arg = func_get_arg($i);
            if (self::nx(@$arg)) {
                return false;
            }
        }
        return true;
    }

    // check if at least one variable exists
    public static function ox()
    {
        for ($i = 0; $i < func_num_args(); $i++) {
            $arg = func_get_arg($i);
            if (self::x(@$arg)) {
                return true;
            }
        }
        return false;
    }

    // check if array has minimum one variable that exists
    public static function aox($var)
    {
        if (!is_array($var)) {
            return false;
        }
        foreach ($var as $key => $value) {
            if (self::x(@$value)) {
                return true;
            }
        }
        return false;
    }

    // check if every variable in an array exists
    public static function amx($var)
    {
        if (!is_array($var)) {
            return false;
        }
        foreach ($var as $key => $value) {
            if (self::nx(@$value)) {
                return false;
            }
        }
        return true;
    }

    // if first value exists, return second value, otherwise third
    public static function xe($var, $return, $fallback = null)
    {
        if (self::x(@$var)) {
            return $return;
        }
        return $fallback;
    }

    // check equality of two values (only if they both exist, weak check)
    public static function eq($a, $b)
    {
        if (self::nx(@$a) && self::nx(@$b)) {
            return false;
        }
        if (self::nx(@$a) && self::x(@$b)) {
            return false;
        }
        if (self::x(@$a) && self::nx(@$b)) {
            return false;
        }
        if ($a == $b) {
            return true;
        }
        return false;
    }

    // check inequality of two values (only if they both exist, weak check)
    public static function neq($a, $b)
    {
        return !self::eq(@$a, @$b);
    }
}
