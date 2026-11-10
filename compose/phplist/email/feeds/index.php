<?php
require_once __DIR__.'/../config.php';
global $loadFilePath;


// Prepare rss / xml output.
header('Content-type: application/rss+xml');
$rss = new SimpleXMLElement('<rss></rss>');
$rss["xmlns:dbc"]="https://www.drivebc.ca/rss";
$rss["version"] = "2.0";
$channel = $rss->addChild("channel");
$channel->title="DriveBC Load Restrictions";
$channel->link = "https://localhost/loads_email/feeds";
$channel->description="All DriveBC Load Restriction Information";
$channel->ttl="1";

// Map the filename to the ServiceArea value (and title)
$fileMap = array (
     "dist01.txt" => array('srvcArea'=>1, 'title'=>'South Vancouver Island')
    ,"dist02.txt" => array('srvcArea'=>2, 'title'=>'Central Vancouver Island')
    ,"dist03.txt" => array('srvcArea'=>3, 'title'=>'North Vancouver Island')
    ,"dist04.txt" => array('srvcArea'=>4, 'title'=>'Howe Sound')
    ,"dist05.txt" => array('srvcArea'=>5, 'title'=>'Sunshine Coast')
    ,"dist06.txt" => array('srvcArea'=>6, 'title'=>'Lower Mainland')
    ,"dist07.txt" => array('srvcArea'=>7, 'title'=>'Fraser Valley')
    ,"dist08.txt" => array('srvcArea'=>8, 'title'=>'South Okanagan')
    ,"dist09.txt" => array('srvcArea'=>9, 'title'=>'Kootenay Boundary')
    ,"dist10.txt" => array('srvcArea'=>10, 'title'=>'Central Kootenay')
    ,"dist11.txt" => array('srvcArea'=>11, 'title'=>'East Kootenay')
    ,"dist12.txt" => array('srvcArea'=>12, 'title'=>'Selkirk')
    ,"dist13.txt" => array('srvcArea'=>13, 'title'=>'Okanagan-Shuswap')
    ,"dist14.txt" => array('srvcArea'=>14, 'title'=>'Nicola')
    ,"dist15.txt" => array('srvcArea'=>15, 'title'=>'Thompson')
    ,"dist16.txt" => array('srvcArea'=>16, 'title'=>'South Cariboo')
    ,"dist17.txt" => array('srvcArea'=>17, 'title'=>'Central Cariboo')
    ,"dist18.txt" => array('srvcArea'=>18, 'title'=>'North Cariboo')
    ,"dist19.txt" => array('srvcArea'=>19, 'title'=>'Fort George')
    ,"dist20.txt" => array('srvcArea'=>20, 'title'=>'Robson')
    ,"dist21.txt" => array('srvcArea'=>21, 'title'=>'South Peace')
    ,"dist22.txt" => array('srvcArea'=>22, 'title'=>'North Peace')
    ,"dist23.txt" => array('srvcArea'=>23, 'title'=>'Nechako')
    ,"dist24.txt" => array('srvcArea'=>24, 'title'=>'Lakes')
    ,"dist25.txt" => array('srvcArea'=>25, 'title'=>'Bulkley Nass')
    ,"dist26.txt" => array('srvcArea'=>26, 'title'=>'Skeena')
    ,"dist27.txt" => array('srvcArea'=>27, 'title'=>'North Coast')
    ,"dist28.txt" => array('srvcArea'=>28, 'title'=>'Stikine')
    );

                                                                                                                                
// Loop on each text file...
foreach ($fileMap as $fname => $info) {
    // Load restriction data read in.
    $content = fetchWithLastModified($loadFilePath.$fname, $srcTs);
    if ($content === false) {
        continue;   // skip this area rather than emit an empty item
    }
    if ($srcTs === null) {
        error_log("No Last-Modified for $fname, using request time");
    }
    $fingerprint = sprintf("%u", crc32($content));

    $rssItem = $channel->addChild("item");
    $rssItem->title = $info['title'];
    $rssItem->link = $loadFilePath.$fname."?".$fingerprint;
    $rssItem->guid = $loadFilePath.$fname."?".$fingerprint;
    $rssItem->category = "Load restriction update";
    $rssItem->{'dbc:servicearea'} = $info['title'];

    $fileData = filterTextChars($content);
    add_cdata($rssItem, "description", $fileData);

    $ftstamp = $srcTs ?? $_SERVER['REQUEST_TIME'];

    $pubdate = new DateTime("now", new DateTimeZone('America/Vancouver'));
    $pubdate->setTimestamp($ftstamp);
    $rssItem->pubDate = $pubdate->format(DateTime::RSS);
}

// Echo for php_list
echo $rss->asXML();



/**
 * Adds a tag with CDATA info
 */
function add_cdata($path, $tagname, $data){
    $dom = dom_import_simplexml($path);
    $element = $dom->ownerDocument->createElement($tagname,'');
    $cdataNode = $dom->ownerDocument->createCDATASection($data);
    $element->appendChild($cdataNode);
    $dom->appendChild($element);
}


/**
 * Replace characters that are not valid in xml.
 */
/**
 * Replace non-standard characters and clean text for XML CDATA.
 */
function filterTextChars($text) {
    if (empty($text)) {
        return '';
    }

    // Replace specific non-standard punctuation / smart quotes with standard equivalents
    $search  = array('’', '‘', '–', '—', '“', '”');
    $replace = array("'", "'", "-", "-", '"', '"');
    $text = str_replace($search, $replace, $text);

    // Keep standard keyboard characters, space, newlines, tabs, and punctuation
    // Remove unwanted non-printable control characters
    $text = preg_replace('/[^\x20-\x7E\r\n\t]/', ' ', $text);

    // Convert line breaks to <br /> formatting
    $text = nl2br($text);
    $text = str_replace(array("\r", "\n"), '', $text);

    return $text;
}

/**
 * Fetch a URL and return its body, with the server's Last-Modified time (unix) in $lastMod.
 * $lastMod is null if the header is missing or unparseable.
 */
function fetchWithLastModified($url, &$lastMod) {
    $lastMod = null;
    $body = file_get_contents($url);   // populates $http_response_header locally
    if ($body === false) {
        return false;
    }
    foreach ($http_response_header ?? [] as $h) {
        if (stripos($h, 'Last-Modified:') === 0) {
            $ts = strtotime(trim(substr($h, 14)));
            if ($ts !== false) {
                $lastMod = $ts;
            }
        }
    }
    return $body;
}


function logit($msg, $clear=false){
    if ($clear){
        file_put_contents(__DIR__.'/debug.log', "", LOCK_EX);
    }
    file_put_contents(__DIR__.'/debug.log', $msg.PHP_EOL, FILE_APPEND|LOCK_EX);
}
?>