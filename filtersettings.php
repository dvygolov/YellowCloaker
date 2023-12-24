<?php

class FilterSettings
{
    var array $os_white;
    var array $country_white;
    var array $lang_white;
    var array $tokens_black;
    var array $url_should_contain;
    var array $ua_black;
    var string $ip_black_filename;
    var bool $ip_black_cidr;
    var bool $block_without_referer;
    var array $referer_stopwords;
    var bool $block_vpnandtor;
    var array $isp_black;
    var bool $debug;

    /**
     * @param $os_white
     * @param $country_white
     * @param $lang_white
     * @param $tokens_black
     * @param $url_should_contain
     * @param $ua_black
     * @param $ip_black_filename
     * @param $ip_black_cidr
     * @param $block_without_referer
     * @param $referer_stopwords
     * @param $block_vpnandtor
     * @param $isp_black
     */
    public function __construct($os_white, $country_white, $lang_white, $tokens_black, $url_should_contain, $ua_black, $ip_black_filename, $ip_black_cidr, $block_without_referer, $referer_stopwords, $block_vpnandtor, $isp_black)
    {
        $this->os_white = $os_white;
        $this->country_white = $country_white;
        $this->lang_white = $lang_white;
        $this->tokens_black = $tokens_black;
        $this->url_should_contain = $url_should_contain;
        $this->ua_black = $ua_black;
        $this->ip_black_filename = $ip_black_filename;
        $this->ip_black_cidr = $ip_black_cidr;
        $this->block_without_referer = $block_without_referer;
        $this->referer_stopwords = $referer_stopwords;
        $this->block_vpnandtor = $block_vpnandtor;
        $this->isp_black = $isp_black;
        $this->debug = true;
    }
}