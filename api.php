<?php 
/**
 *   api.php
 *   Basic API for OpenBH
 *
 *   @author peakinformatik
 *
 *   How to use:
 *    www.yoursite.com/api.php?password=fish&action=getstats&days=7
 *
 *   Possible actions:
 *    getstats (optional parameters: days)
 *    insertlink (optional parameters: link, anchor, maxlink, linkcount)
 *    getpages
 *    getkeywords
 *    pagecount
 */

$config = array(
  'password'=>'fish',    
);

if (isset($_GET['password'])){
  if ($_GET['password'] == $config['password']){
    if (isset($_GET['action'])){
      switch ($_GET['action']){
/***********************************************************************************************************************/
        case 'getstats':
          // Returns the log entries from the last x days
          /*
           * sessid
           * keyword
           * filename
           * remote addr
           * remote hostname
           * time
           * referer
           * useragent
           */

          // Number of days the log goes back. Default is 7
          $days = 7;
          
          if (isset($_GET['days'])){
            if (is_numeric($_GET['days'])){
              $days = $_GET['days'];
            }
          }

          while($days > 0) {
              $file = strtotime("today -$days days");
              $filename = 'data/logs/'.$file.'.txt';
              if(is_readable($filename)){
                $handle = fopen($filename, "r");
                echo fread($handle, filesize($filename));
                fclose($handle);        
              }
              $days = $days - 1;
          }
        break;
/***********************************************************************************************************************/
        case 'insertlink':
          // Inserts link into content, returns number of inserted links
          // GET parameters (optional):
          // link - links that gets inserted 
          // anchor - anchor text for link      
          // maxlinks - max number of links per page
          // linkcount - total number of links to insert

          include('config/config.php');
          include('baselibs/Page.php');

          // Default values
          $maxlinks = 100;
          $linkcount = 1;         

          if (isset($_GET['maxlinks'])){
            if (is_numeric($_GET['maxlinks'])){
              $maxlinks = $_GET['maxlinks'];
            }
          }

          if (isset($_GET['linkcount'])){
            if (is_numeric($_GET['linkcount'])){
              $linkcount = $_GET['linkcount'];
            }
          }

          if (isset($_GET['link'])){
            $link = $_GET['link'];
          }else{
            $link = 'http://www.peakinformatik.com';
          }

          if (isset($_GET['anchor'])){
            $anchor = $_GET['anchor'];
          }else{
            $anchor = 'click here';
          }

          $pages = get_pages();

          $i = 0;
          $errorcount = 0;

          while($i < $linkcount AND $errorcount < 5){
            $keyword = $pages[array_rand($pages)];
            if(insert_link($keyword, $link, $anchor, $maxlinks)){
              $i ++;
              $errorcount = 0;
            }else{
              $errorcount ++;
            } 
          }

          echo $i;
        break;
/***********************************************************************************************************************/
        case 'getpages':
          // Returns pages that have been generated
      
          $d = opendir('data/content/');
          while ($file = readdir($d)){
              if ($file != '.' && $file != '..'){
                echo base64_decode($file).PHP_EOL;                
              }   
          }
          closedir($d);
        break;
/***********************************************************************************************************************/
        case 'getkeywords':
          // Returns all keywords
          $filename = 'config/kw/open.txt';
          $handle = fopen($filename, "r");
          $contents = fread($handle, filesize($filename));
          fclose($handle);

          $keywords = explode(PHP_EOL, $contents);
          foreach($keywords as $keyword){
            if($keyword){
              echo trim($keyword).PHP_EOL;
            }
          }
        break;
/***********************************************************************************************************************/
        case 'pagecount':
          // Number of created pages and number of keywords
          $filename = 'config/kw/open.txt';
          $numofkeywords = count(file($filename));


          $numofpages = 0;
          $d = opendir('data/content/');
          while ($file = readdir($d)){
              if ($file != '.' && $file != '..'){
               $numofpages ++;                
              }   
          }
          closedir($d);

          echo $numofpages.';'.$numofkeywords;
        break;
/***********************************************************************************************************************/
      } 
    }
  }
}

function insert_link($keyword, $link, $anchor, $maxlinks = 10){
  // Inserts link at random position in the text
  // maxlinks = max number of links per page

  // Get content
  $p = Page::GetCache($keyword);

  // Count links
  $linkcount = substr_count($p->content, '<a href="');

  if($maxlinks > $linkcount){

    // Create an array with all the words (links count as a word to prevent breaking the links)
    if(preg_match_all('%(<a[^<]+.*?>|[^\s]+)%', preg_replace('%([\s]\<|\>[\s])%', '$1', $p->content), $matches)) {
        array_shift($matches);
        $words = $matches[0];
    }

    // Insert link at random position
    $linkstring = ' <a href="'.$link.'">'.$anchor.'</a>';
    $rndword = rand(0,count($words));
    $words[$rndword] = $words[$rndword] . $linkstring;

    // Change array back to string
    foreach($words as $word){
      $newcontent = $newcontent . $word .' ';
    }
    $p->content = $newcontent;



    // Save new content
    $path = sprintf('data/content/%s',base64_encode($keyword));
    file_put_contents($path,gzcompress(serialize($p)));

    return true;    
  }else{
    return false;
  }
  

}

function get_pages(){
  $d = opendir('data/content/');
  while ($file = readdir($d)){
    if ($file != '.' && $file != '..' && $file != '.DS_Store'){
      $keywords[]=base64_decode($file);                
    }   
  }
  return $keywords;
}
