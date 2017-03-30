<?php

namespace Util\Controller;

use Think\Controller;

/**
 * php爬虫接口
 * 
 * @author Jason
 *        
 */
ini_set ( "memory_limit", "1024M" );
vendor ( 'PHPSpider.core.init' );

class PhpSpiderController extends Controller {
	
	public function crawlAmazon($keyword='') {
		
		$domain = "https://www.amazon.cn";
		$url = "https://www.amazon.cn/s/ref=nb_sb_noss?__mk_zh_CN=%E4%BA%9A%E9%A9%AC%E9%80%8A%E7%BD%91%E7%AB%99&url=search-alias%3Dstripbooks&field-keywords={$keyword}";
		
		function disguise_curl($url) {
			$curl = curl_init ();
			
			// Setup headers - I used the same headers from Firefox version 2.0.0.6
			// below was split up because php.net said the line was too long. :/
			$header [0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
			$header [0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
			$header [] = "Cache-Control: max-age=0";
			$header [] = "Connection: keep-alive";
			$header [] = "Keep-Alive: 300";
			$header [] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
			$header [] = "Accept-Language: en-us,en;q=0.5";
			$header [] = "Pragma: "; // browsers keep this blank.
			
			curl_setopt( $curl, CURLOPT_URL, $url );
			curl_setopt( $curl, CURLOPT_HTTPHEADER, $header );
			curl_setopt( $curl, CURLOPT_ENCODING, 'gzip' );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
// 			curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 10 );
			curl_setopt( $curl, CURLOPT_HEADER, true );
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false);
// 			curl_setopt( $curl, CURLOPT_USERAGENT, "phpspider-requests/1.2.0" );
// 			curl_setopt( $curl, CURLOPT_TIMEOUT, 15);
// 			curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, 1);
			// 在多线程处理场景下使用超时选项时，会忽略signals对应的处理函数，但是无耐的是还有小概率的crash情况发生
// 			curl_setopt( $curl, CURLOPT_NOSIGNAL, true);
			
			
// 			curl_setopt ( $curl, CURLOPT_REFERER, 'http://www.google.com' );
// 			curl_setopt ( $curl, CURLOPT_AUTOREFERER, true );
			
			$html = curl_exec ( $curl ); // execute the curl command
			curl_close ( $curl ); // close the connection
			
			return $html; // and finally, return $html
		}
		
		
		$html = disguise_curl( $url );
		
// 		$html = \requests::get($url);
		
		$selector = "//a[contains(@class, 'a-link-normal') and contains(@class, 's-access-detail-page') and contains(@class, 'a-text-normal')]/@href";
		$selectedUrl = \selector::select($html, $selector);
		
		$selector = "//a[contains(@class, 'a-link-normal') and contains(@class, 'a-text-normal')]/img/@src";
		$imgSrc = \selector::select($html, $selector);

		$nextHtml = disguise_curl( $selectedUrl[0] );
		
		$selector = "//span[@id='productTitle']";
		$title = \selector::select($nextHtml, $selector);
		
		$selector = "//span[contains(@class, 'author')]/a";
		$author = \selector::select($nextHtml, $selector);
		
		$selector = "//td[@class='bucket']/div[@class='content']/ul/li[1]/text()";
		$press = \selector::select($nextHtml, $selector);

		$selector = "//div[@id='s_contents']/@descurl";
		$contentsUrl = $domain . \selector::select($nextHtml, $selector);
		
		$lastHtml = disguise_curl( $contentsUrl );
		
		$selector = "//div[@id='s_contents']";
		$contents = \selector::select($lastHtml, $selector);
		
		$data = array(
			'title'    => $title,
			'author'   => $author,
			'imgUrl'   => $imgSrc[0],
			'press'    => $press,
// 			'conTitles'=> $conTitles,
			'contents' => $contents
		);
		
		return $data;
	}
	
}