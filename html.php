<?php
include 'keys.php';

ini_set('display_startup_errors',1);
ini_set('display_errors',1);
//error_reporting(-1);
set_time_limit(0);
error_reporting(E_ALL);
ob_implicit_flush(TRUE);
echo "Starting up...";
//ob_end_flush();



proccessManager();

function proccessManager() {
    rrmdir("../Private/");
    echo "<br>Generating articles";
    $allBBCArticles = BBC();
    echo "<br>All articles have been generated";
    sendNewsPackages($allBBCArticles);
    echo "Emails have been sent";
}

function sendNewsPackages($allBBCArticles) {
    echo "<br>Sending news packages";
    //Customer A wants 4 from every BBC topic
    //Customer B wants 6 from world, technology and uk in that order
    $AInfo = array("email Matousek","email@gmail.com");
    $A = array("world","uk","business","politics","technology","science_and_environment","health","education","entertainment_and_arts","in_pictures");
    $BInfo = array("email Matousek","email@gmail.com");
    $B = array("world","technology","uk");
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
    saveFile("../Private/temp.html", $customisedNewspaper);
    sendEmail("../Private/temp.html");
}

function BBC() {
    $newspaper = "BBC";
    echo "<br>Retrieving articles from the " . $newspaper;
    if (!is_dir("../Private/" . $newspaper)) {
        mkdir("../Private/" . $newspaper);  //create folder
    }
    $websiteURL = "https://bbc.co.uk/news/";
    $topics = array("world","uk","business","politics","technology","science_and_environment","health","education","entertainment_and_arts","in_pictures");
    //$topics = array("world","uk");

    $pattern = "(<a href=\"/news/(.*?)\" class=\"title-link\">)";
    foreach ($topics as $topic) {//Get top articles from each topic
        if (!is_dir("../Private/" . $newspaper . "/" . $topic)) {
            mkdir("../Private/" . $newspaper . "/" . $topic);  //create folder
        }
        $articleLinks = get_web_page($websiteURL . $topic, $pattern);
        $count = 0;
        //Get the actual content for each article link
        foreach ($articleLinks as $link) {
            if (substr($link, 0, 3) !== "av/") {//remove bbc video articles
                if ($count < 3) {
                    echo "<br>" . $link;
                    $articleInfo = getArticle($websiteURL . $link);
                    $articleTitle = $articleInfo[1];
                    $articleContent = $articleInfo[0];
                    $processedArticle = imagesToB64($articleContent);
                    $directory = "../Private/" . $newspaper . "/" . $topic . "/" . $articleTitle . ".html";
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
 

function get_web_page($url,$pattern) {
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
    //Getting all the article links from the page
    //echo " " . $matches[1][0];
    return $matches[1];
}


function getArticle($url) {
    require "keys.php";
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, "https://mercury.postlight.com/parser?url=" . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    
    $headers = array();
    $headers[] = "X-Api-Key: " . $mercuryAPI;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
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

function imagesToB64($string) {
    //Converts all img links to Base64 encodes
    //This allows us to maintain simplicity of generating 1 file
    //With a drawback of (slightly) larger image size
    $out = preg_replace_callback(
    "(src=\"(.*?)\")",
    function($match) {
        static $id = 0;
        $id++;
        $path = $match[1];
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $curl = curl_init($path);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
        $ret_val = curl_exec($curl);
        $b64_image_data =  chunk_split(base64_encode($ret_val));
        curl_close($curl);
        return 'src="data:image/' . $type . ';base64,' . $b64_image_data . '" style="width:100%"';
    },
    $string);
    return $out;
}

function saveFile($dir,$data){
    file_put_contents($dir,$data);
}


function sendEmail($attachment) {
    $to = "email@kindle.com"; 
    $from = "email@matousek.co.uk"; 
    $subject = "News Article HTML"; 
    $message = "Please see attachment";
    
    // a random hash will be necessary to send mixed content
    $separator = md5(time());
    
    // carriage return type (we use a PHP end of line constant)
    $eol = PHP_EOL;
    
    /*
    // attachment name
    
    //$filename = "BBC-News-" . date("h:i:sa") . ".html";
    $filename = basename($attachment).PHP_EOL;//Name of file
    
    // encode data (puts attachment in proper format)
    
    $attachment = chunk_split(base64_encode(file_get_contents($attachment)));
    */
    $path = $attachment;
    // Read the file content
    $filename = "Hello.html";
    $file = $path;
    $file_size = filesize($file);
    $handle = fopen($file, "r");
    $content = fread($handle, $file_size);
    fclose($handle);
    $content = chunk_split(base64_encode($content));
    //echo "<br>" . $content;
    $attachment = $content;
    
    // main header
    $headers  = "From: ".$from.$eol;
    $headers .= "MIME-Version: 1.0".$eol; 
    $headers .= "Content-Type: multipart/mixed; boundary=\"".$separator."\"";
    
    // no more headers after this, we start the body! //
    
    $body = "--".$separator.$eol;
    $body .= "Content-Transfer-Encoding: 7bit".$eol.$eol;
    $body .= "This is a MIME encoded message.".$eol;
    
    // message
    $body .= "--".$separator.$eol;
    $body .= "Content-Type: text/html; charset=\"iso-8859-1\"".$eol;
    $body .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
    $body .= $message.$eol;
    
    // attachment
    $body .= "--".$separator.$eol;
    $body .= "Content-Type: application/octet-stream; name=\"".$filename."\"".$eol; 
    $body .= "Content-Transfer-Encoding: base64".$eol;
    $body .= "Content-Disposition: attachment".$eol.$eol;
    $body .= $attachment.$eol;
    $body .= "--".$separator."--";
    
    // send message
    if (mail($to, $subject, $body, $headers)) {
    echo "<br>Success!";
    } else {
    echo "Failed to send email";
    }
    
} 

function rrmdir($dir) {
    if (is_dir($dir)) {
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
    }
}

?>