<?php
class CountryFuncs{

    public static function get_language($country){
        $locales = array(
               'AE'=>'ar',
               'AF'=>'ps',
               'AL'=>'sq',
               'AM'=>'hy',
               'AR'=>'es',
               'AT'=>'de',
               'AU'=>'en',
               'AZ'=>'az',
               'BA'=>'bs',
               'BD'=>'bn',
               'BE'=>'fr',
               'BG'=>'bg',
               'BH'=>'ar',
               'BN'=>'ms',
               'BO'=>'es',
               'BR'=>'pt',
               'BY'=>'be',
               'BZ'=>'en',
               'CA'=>'en',
               'CH'=>'fr',
               'CL'=>'es',
               'CN'=>'zh',
               'CO'=>'es',
               'CR'=>'es',
               'CZ'=>'cs',
               'DE'=>'de',
               'DK'=>'da',
               'DO'=>'es',
               'DZ'=>'ar',
               'EC'=>'es',
               'EE'=>'et',
               'EG'=>'ar',
               'ES'=>'es',
               'ET'=>'am',
               'FI'=>'fi',
               'FO'=>'fo',
               'FR'=>'fr',
               'GB'=>'en',
               'GE'=>'ka',
               'GL'=>'kl',
               'GR'=>'el',
               'GT'=>'es',
               'HK'=>'zh',
               'HN'=>'es',
               'HR'=>'hr',
               'HU'=>'hu',
               'ID'=>'id',
               'IE'=>'en',
               'IL'=>'he',
               'IN'=>'hi',
               'IQ'=>'ar',
               'IR'=>'fa',
               'IS'=>'is',
               'IT'=>'it',
               'JM'=>'en',
               'JO'=>'ar',
               'JP'=>'ja',
               'KE'=>'sw',
               'KG'=>'ky',
               'KH'=>'km',
               'KR'=>'ko',
               'KW'=>'ar',
               'KZ'=>'kk',
               'LA'=>'lo',
               'LB'=>'ar',
               'LI'=>'de',
               'LK'=>'si',
               'LT'=>'lt',
               'LU'=>'fr',
               'LV'=>'lv',
               'LY'=>'ar',
               'MA'=>'ar',
               'MC'=>'fr',
               'MK'=>'mk',
               'MN'=>'mn',
               'MO'=>'zh',
               'MT'=>'mt',
               'MV'=>'dv',
               'MX'=>'es',
               'MY'=>'ms',
               'NG'=>'en',
               'NI'=>'es',
               'NL'=>'nl',
               'NO'=>'nb',
               'NP'=>'ne',
               'NZ'=>'en',
               'OM'=>'ar',
               'PA'=>'es',
               'PE'=>'es',
               'PH'=>'fil',
               'PK'=>'ur',
               'PL'=>'pl',
               'PR'=>'es',
               'PT'=>'pt',
               'PY'=>'es',
               'QA'=>'ar',
               'RO'=>'ro',
               'RS'=>'sr',
               'RU'=>'ru',
               'RW'=>'rw',
               'SA'=>'ar',
               'SE'=>'sv',
               'SG'=>'en',
               'SI'=>'sl',
               'SK'=>'sk',
               'SN'=>'wo',
               'SV'=>'es',
               'SY'=>'ar',
               'TH'=>'th',
               'TJ'=>'tg',
               'TM'=>'tk',
               'TN'=>'ar',
               'TR'=>'tr',
               'TT'=>'en',
               'TW'=>'zh',
               'UA'=>'uk',
               'US'=>'en',
               'UY'=>'es',
               'UZ'=>'uz',
               'VE'=>'es',
               'VN'=>'vi',
               'YE'=>'ar',
               'ZA'=>'en',
               'ZW'=>'en',
        );    
        $country=strtoupper($country);
        if (array_key_exists($country,$locales))
            return $locales[$country];
        return $country;
    }

    public static function get_continent($country) {
        $country=strtolower($country);
        switch($country){
            case 'ar':
            case 'bo':
            case 'cl':
            case 'co':
            case 'cr':
            case 'ec':
            case 'gt':
            case 'hn':
            case 'mx':
            case 'pe':
                return 'es';
            case 'us':
            case 'ua':
            case 'gr':
            case 'be':
            case 'fr':
            case 'ru':
            case 'bg':
            case 'it':
            case 'pt':
            case 'sk':
            case 'sl':
            case 'hr':
            case 'ro':
            case 'de':
                return 'eu';
            case 'ph':
            case 'th':
            case 'my':
            case 'id':
            case 'kh':
                return 'as';
            case 'ma':
            case 'om':
            case 'iq':
            case 'sa':
            case 'eg':
            case 'tn':
                return 'ar';
        }
        return $country;
    }
}
?>
