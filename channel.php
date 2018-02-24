<?php
/**
* List all videos from a channel
* echo ytchannel_api('UCwTD5zJbsQGJN75MwbykYNw');
* defualt output = json
*/
function ytchannel_api($ch_id='UCrJhliKNQ8g0qoE_zvL8eVg',$output='json'){

  $replaces = [['|   |',' - '],['| - PyCon 2016|',''],['|&#39;|','\'']];
  $format = $output; // markdown, json, html
  $channel = $ch_id; 
  $base = 'https://www.youtube.com';


  // retrieve
  // https://www.youtube.com/channel/UCrJhliKNQ8g0qoE_zvL8eVg/videos?live_view=500&flow=list&sort=dd&view=0
  $url = $base.'/channel/'.$channel.'/videos?live_view=500&flow=list&sort=dd&view=0';
  $matches = false;
  $next = $content = '';
  do {
    if ($matches) {
      $obj = json_decode(file_get_contents($base.$matches[1]));
      $next = $obj->content_html;
      $next .= $obj->load_more_widget_html;
    }
    else $next = file_get_contents($url);
    if ($next) $content .= $next;
    else break;
  } while (preg_match('|data-uix-load-more-href="([^"]*)"|msiU',$next,$matches));
  preg_match_all('|<span class="video-time([^>]*)>(.*)</span>|msiU',$content,$times);
  $count = preg_match_all('|<h3 class="yt-lockup-title ([^>]*)>(.*)</h3>|msiU',$content,$matches);

  $videos = [];
  for($i=0;$i<$count;$i++) {
    if (!preg_match('|<a([^>]*)>([^<]*)</a>|msi',$matches[0][$i],$link)) continue;
    if (!preg_match('|href="(/watch[^"]*)"|i',$link[1],$href)) continue;
    $href = $base.trim($href[1]);
    $title = trim($link[2]);
    $time = trim(strip_tags($times[2][$i]));
    foreach ($replaces as $replace) {
      $title = preg_replace($replace[0],$replace[1],$title);
    }
    $videos[] = (object) compact('href','title','time');
  }

  // output
  if ($format=='json') {
    $api_output =  json_encode($videos);
  } else if ($format=='html') {
    $api_output =  "<ul>\n";
    foreach ($videos as $video) {
      extract((array)$video);
      $api_output .=  "<li><a href=\"$href\">$title</a> [$time]</li>\n";
    }
    $api_output .= "</ul>\n";
  } else if ($format=='markdown') {
    foreach ($videos as $video) {
      extract((array)$video);
      $api_output = html_entity_decode("- [$title]($href) [$time]\n");
    }
  }

  // return output
  return $api_output;
}
