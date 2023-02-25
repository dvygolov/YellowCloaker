<?php
require_once __DIR__ . '/../vendor/autoload.php';
use vielhuber\stringhelper\__;
class GoogleTranslate
{
    function translate($input,$sl,$tl)
    {
        $result=array();
        $split=explode("\n",$input);
        foreach($split as $string) {
            $string = $this->parseResultPre($string);
            $args = [
                'anno' => 3,
                'client' => 'te_lib',
                'format' => 'html',
                'v' => '1.0',
                'key' => 'AIzaSyBOti4mM-6x9WDnZIjIeyEU21OpBXqWBgw',
                'logld' => 'vTE_20200210_00',
                'sl' => $sl,
                'tl' => $tl,
                'sp' => 'nmt',
                'tc' => 1,
                'sr' => 1,
                'tk' => $this->generateTk($string, $this->generateTkk()),
                'mode' => 1
            ];
            $response = __::curl(
                'https://translate.googleapis.com/translate_a/t?' . http_build_query($args),
                ['q' => $string],
                'POST',
                [
                    'User-Agent' =>
                        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36',
                    'Content-Length' => strlen('q=' . urlencode($string))
                ],
                false,
                false,
                3
            );
            $result[]=$this->parseResultPost($response->result);
        }
        return implode("\n",$result);
    }

    private function parseResultPre($input)
    {
        // google sometimes surrounds the translation with <i> and <b> tags
        // do distinguish real i-/b-tags, replace them (we undo that later on)
        $dom = __::str_to_dom($input);
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
        $output = __::dom_to_str($dom);
        return $output;
    }

    private function parseResultPost($res)
    {
        // discard the (outer) <i>-tags and take the content of the <b>-tags
        $output = '';
        $pointer = 0;
        $lvl_i = 0;
        $lvl_i_inner = 0;
        $lvl_b = 0;
        $lvl_b_inner = 0;
        $input=is_array($res)?$res[0]:$res;
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
        $dom = __::str_to_dom($output);
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
        $output = __::dom_to_str($dom);
        return $output;
    }

    private function generateTkk()
    {
        $cache = sys_get_temp_dir() . '/tkk.cache';
        if (file_exists($cache) && filemtime($cache) > strtotime('now - 1 hour')) {
            return file_get_contents($cache);
        }
        $data = __::curl('https://translate.googleapis.com/translate_a/element.js', null, 'GET');
        $response = $data->result;
        $pos1 = mb_strpos($response, 'c._ctkk=\'') + mb_strlen('c._ctkk=\'');
        $pos2 = mb_strpos($response, '\'', $pos1);
        $tkk = mb_substr($response, $pos1, $pos2 - $pos1);
        file_put_contents($cache, $tkk);
        return $tkk;
    }

    private function generateTk($f0, $w1)
    {
        // ported from js to php from https://translate.googleapis.com/element/TE_20200210_00/e/js/element/element_main.js
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
}
?>