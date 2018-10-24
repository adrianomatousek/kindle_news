<?php
include '../keys.php';
include 'images_to_b64.php';
include 'send_email.php';
include '../db.php';

$saveDir = "../../Private/";

ini_set('display_startup_errors',1);
ini_set('display_errors',1);
//error_reporting(-1);
set_time_limit(0);
error_reporting(E_ALL);
ob_implicit_flush(TRUE);
echo "Starting up...";
//ob_end_flush();



//jsonTopics();

proccessManager();

//do_sql();

function do_sql() {
    $sql = "SELECT * FROM kindle_sites LIMIT 2";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        // output data of each row
        while($row = $result->fetch_assoc()) {
            echo '<br>' . $row['newspaper'];
            echo '<br>' . htmlspecialchars($row['topics']);
            $json = json_decode($row['topics'], true); //Getting the topics data from json format
            echo '<br>' . $json['topics'][0]['topic'];
        }
    } else {
        echo "0 results";
    }
}

function proccessManager() {
    global $saveDir;
    rrmdir($saveDir);
    echo "<br>Generating articles";
    
    //BBC
    $newspaper = "BBC";
    $websiteURL = "https://www.bbc.co.uk/";
    $topics = array("world","uk","business","politics","technology","science_and_environment","health","education","entertainment_and_arts","in_pictures");
    $pattern = "(<a href=\"/news/(.*?)\" class=\"title-link\">)";
    //$allBBCArticles = retrieveTopArticles($newspaper,$websiteURL,$topics,$pattern);
        
    //Sky
    $newspaper = "Sky";
    $websiteURL = "https://news.sky.com/";
    $topics = array("world","uk","us","business","politics","technology","entertainment");
    $pattern = "(<a href=\"(.*?)\" class=(.*?)grid__link)";
    //$allSkyArticles = retrieveTopArticles($newspaper,$websiteURL,$topics,$pattern);
    
    /* No longer working - 'suspicious' activity detected
    //Bloomberg
    $newspaper = "Bloomberg";
    $websiteURL = "https://www.bloomberg.com/";
    $topics = array("markets/economics","wealth","technology","opinion","politics");
    $pattern = "(<a href=\"(.*?)\" class=\"(.*?)_headline-link\")";
    $allBloombergArticles = retrieveTopArticles($newspaper,$websiteURL,$topics,$pattern);
    */
    
    echo "<br>All articles have been generated";
    
    //sendNewsPackages($allBBCArticles);
    echo "<br>Emails have been sent";
}



function sendNewsPackages($allBBCArticles) {
    global $saveDir;
    echo "<br>Sending news packages";
    //Customer A wants 4 from every BBC topic
    //Customer B wants 6 from world, technology and uk in that order
    /*
    $AInfo = array("Adriano Matousek","adriano.matousek@gmail.com");
    $A = array("world","uk","business","politics","technology","science_and_environment","health","education","entertainment_and_arts","in_pictures");
    $BInfo = array("Adriano Matousek","adriano.matousek@gmail.com");
    $B = array("world","technology","uk");
    */
    $frontPage = "<br><br>
    <h4 style='text-align: center'>Good morning " . $AInfo[0] . "</h4>
    <br><br>
    <h5 style='text-align: center'>" . date("l jS \of F") . "</h5>
    <br>
    <h6 style='text-align: center'>Your bespoke news collection was generated at " . date("h:i A") . "</h6> <mbp:pagebreak/>";
    $contents = "<h3 style='text-align: center'>Contents</h3><br><br>
    <p1>This section will be available soon</p1>
    ";
    $customisedNewspaper = $frontPage . $contents;
    foreach ($A as $topic) {
        foreach ($allBBCArticles[$topic] as $article) {
            $customisedNewspaper .= "<mbp:pagebreak/>" . $article;
        }
    }
    $customisedNewspaper = "<html><body>" . $customisedNewspaper . "</body></html>";
    saveFile($saveDir."/temp.html", $customisedNewspaper);
    sendEmail($saveDir."temp.html");
}

function retrieveTopArticles($newspaper,$websiteURL,$topics,$pattern) {
    global $saveDir;
    
    echo "<br>Retrieving articles from the " . $newspaper;
    if (!is_dir($saveDir . $newspaper)) {
        mkdir($saveDir . $newspaper);  //create folder
    }
    
    foreach ($topics as $topic) {//Get top articles from each topic
        $safeDir = str_replace("/","_",$topic);
        //echo "<br>Safe directory: " . $safeDir;
        //echo "<br>Global dir: " . $saveDir;
        if (!is_dir($saveDir . $newspaper . "/" . $safeDir)) {
            echo "<br>Directory: " . $saveDir . $newspaper . "/" . $safeDir;
            mkdir($saveDir . $newspaper . "/" . $safeDir);  //create folder
        }
        $articleLinks = get_web_page($websiteURL . $topic, $pattern);
        //echo $articleLinks;
        $count = 0;
        //Get the actual content for each article link
        foreach ($articleLinks as $link) {
            if (substr($link, 0, 3) !== "av/" && substr($link, 0, 3) !== "/vi") {//remove video articles
                if ($count < 3) {
                    echo "<br>Link: " . $link;
                    $articleInfo = getArticle($websiteURL . $link);
                    $articleTitle = $articleInfo[1];
                    $articleContent = $articleInfo[0];
                    $processedArticle = imagesToB64($articleContent);
                    $directory = $saveDir . $newspaper . "/" . $topic . "/" . $articleTitle . ".html";
                    saveFile($directory, $processedArticle);
                    ${$topic}[$count] = $processedArticle; 
                    $count = $count + 1;
                    //so each topic[] has an array of articles e.g world[0]
                }
            $allArticles[$topic] = ${$topic};
            //A 2D array with string indexes for topics containing 
            //another array to access each article.
            //e.g $allArticles["world"][0] would contain the first article on the world topic
            }
        }
    }
    return $allArticles;
}
 

function get_web_page($url,$pattern) { //Using front page to retrieve the top articles
    //Preparing curl request
    $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

    $options = array(
        CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
        CURLOPT_POST           =>false,        //set to GET
        CURLOPT_USERAGENT      => $user_agent, //set user agent
        CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
        CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
        CURLOPT_RETURNTRANSFER => true,     // return web page
        CURLOPT_HEADER         => false,    // don't return headers
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING       => "",       // handle all encodings
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
        CURLOPT_TIMEOUT        => 120,      // timeout on response
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 4000); //timeout in seconds
    curl_setopt_array($ch, $options);
    $content = curl_exec($ch);
    $err = curl_errno($ch);
    $errmsg = curl_error($ch);
    $header = curl_getinfo($ch);
    curl_close($ch);
    $header['errno'] = $err;
    $header['errmsg'] = $errmsg;
    $header['content'] = $content;
    $page = (string)$header['content'];
    
    preg_match_all($pattern, $page, $matches); 

    //echo "<br>Complete; match: " . $matches[1][0];
    foreach ($matches[1] as $aLink) {
        echo "<br>____________________________LINK: " . $aLink;
    }
    //Getting all the article links from the page
    return $matches[1];
}


function getArticle($url) {
    require "../keys.php";
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, "https://mercury.postlight.com/parser?url=" . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    
    $headers = array();
    $headers[] = "X-Api-Key: " . $mercuryAPI;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo '<br>Error:' . curl_error($ch);
    }
    curl_close ($ch);
    
    if (isset(json_decode($result)->title)) {
        $rawTitle = json_decode($result)->title;
    } else {
        $rawTitle = "Another article for you " . date("h:i:sa");
    }
    
    if (isset(json_decode($result)->content)) {
    $article = "<h2>" . $rawTitle . "</h2>" . json_decode($result)->content;
    } else {
        return null;
    }
    //echo "<script>console.log(".$result.")</script>";
    $title = str_replace(array('\\','/',':','*','?','"','<','>','|'),' ',$rawTitle);
    return array($article,$title);
}

function saveFile($dir,$data){
    file_put_contents($dir,$data);
}

function jsonTopics(){//Handy for creating new topics
    $topics = array("world","uk","us","business","politics","technology","entertainment");
    /*$json = '{
    "topics": [
            { "topic":"world", "pattern":"(<a href=\"/news/(.*?)\" class=\"title-link\">)"},
            { "topic":"uk", "pattern":"(<a href=\"/news/(.*?)\" class=\"title-link\">)"}
        ]
    }';*/
    echo ' "topics": [';
    foreach($topics as $item){
        echo '{"topic":"';
        echo $item;
        echo addslashes(htmlspecialchars('", "pattern":"(<a href=\"/news/(.*?)\" class=\"title-link\">)"},'));
    }
    echo ']}';
    /*
    $json = json_decode($json,true);
    echo "<br>";
    echo $json["topics"][0]["topic"];
    */
    
    //$json = '"topics": [{"topic":"world", "pattern":"(<a href=\"/news/(.*?)\" class=\"title-link\">)"},{"topic":"uk", "pattern":"(<a href=\"/news/(.*?)\" class=\"title-link\">)"},{"topic":"us", "pattern":"(<a href=\"/news/(.*?)\" class=\"title-link\">)"},{"topic":"business", "pattern":"(<a href=\"/news/(.*?)\" class=\"title-link\">)"},{"topic":"politics", "pattern":"(<a href=\"/news/(.*?)\" class=\"title-link\">)"},{"topic":"technology", "pattern":"(<a href=\"/news/(.*?)\" class=\"title-link\">)"},{"topic":"entertainment","pattern":"(<a href=\"/news/(.*?)\" class=\"title-link\">)"}]}';

$pattern = "(<a href=\"/news/(.*?)\" class=\"title-link\">)";
$pattern = addslashes($pattern);
$json = '{
"topics": [
        { "topic":"world", "pattern":"'.$pattern.'"},
        { "topic":"uk", "pattern":"'.$pattern.'"}
    ]
}';
$json = json_decode($json,true);

echo '<br>New topic: ' . htmlspecialchars(addslashes($json['topics'][1]['pattern']));
//

}


function rrmdir($dir) {
    //echo "<br>Removing dir: " . $dir;
    $folderName = explode("/",explode("../../",$dir)[1])[0];
    //echo "<br> " . $folderName;
    if (is_dir($dir) && $folderName == "Private") {
    $objects = scandir($dir);
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
        if (filetype($dir."/".$object) == "dir") 
           rrmdir($dir."/".$object); 
        else unlink   ($dir."/".$object);
      }
    }
    reset($objects);
    //rmdir($dir);
    } else {
        echo "<br>Error: failed to remove directory";
    }
}

?>