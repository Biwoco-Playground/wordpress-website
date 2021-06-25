<?php

/*
Plugin Name: Crawl Hacker New
Plugin URI:
Description: Declares a plugin that will be visible in the
WordPress admin interface
Version: 1.0
Author: Phuc Nguyen
License: GPLv2
*/

add_shortcode( "crawl_hacker_new", "crawl_hacker_new" );


function crawl_hacker_new () {
    $curl = curl_init( 'https://hacker-news.firebaseio.com/v0/newstories.json?print=pretty' );
    curl_setopt($curl, CURLOPT_HTTPGET, true);
    
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Accept: application/json'
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
    
    $result = curl_exec($curl);
    
    curl_close($curl);

    $newsObj = json_decode($result, true);

    echo "<table>";
    echo "<tr>
            <th>ID</th>
            <th>Title</th>
            <th>Type</th>
            <th>Author</th>
            <th>Score</th>
        </tr>";

    for($i = 0; $i<10; $i++){
        getNew($newsObj[$i]);
    }

    echo "</table>";
}

function getNew($id) {
    $curl = curl_init( 'https://hacker-news.firebaseio.com/v0/item/'. $id .'.json?print=pretty' );
    curl_setopt($curl, CURLOPT_HTTPGET, true);
    
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Accept: application/json'
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
    
    $result = curl_exec($curl);
    
    curl_close($curl);

    $newsObj = json_decode($result, true);

    echo "<tr>";
    echo "<td>";
    echo $newsObj["id"];
    echo "</td>"; 
    echo "<td>";

    if(isset($newsObj["url"]))
        echo "<a href='{$newsObj["url"]}'>{$newsObj["title"]}</a>";
    else echo $newsObj["title"];
    echo "<div>Created at: " . date("m/d/Y H:i:s", $newsObj["time"]) . "</div>";
    echo "</td>";    
    echo "<td>";
    echo $newsObj["type"];
    echo "</td>";    
    echo "<td>";
    echo $newsObj["by"];
    echo "</td>";    
    echo "<td>";
    echo $newsObj["score"];
    echo "</td>";      
    echo "</tr>";

}