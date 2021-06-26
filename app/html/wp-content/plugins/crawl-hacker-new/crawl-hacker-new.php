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

add_shortcode("crawl_hacker_new", "crawl_hacker_new");

function crawl_hacker_new()
{
    $result = wp_remote_get( "https://hacker-news.firebaseio.com/v0/newstories.json?print=pretty", array(
        "headers" => array(
            "Content-Type" => "application/json",
            "Accept" => "application/json",
            "cache-control" => "no-cache"
        )
    ) );

    if( is_wp_error( $result ) ) {
        return; // Bail early
    }

    $newsArr = json_decode($result["body"], true);

    $output = "";

    $output .= "<table>";
    $output .= "<tr>
            <th>ID</th>
            <th>Title</th>
            <th>Type</th>
            <th>Author</th>
            <th>Score</th>
        </tr>";

    for ($i = 0; $i < 10; $i++) {
        $output .= getNew($newsArr[$i]);
    }

    $output .= "</table>";

    return $output;

    // return print_r($newsArr);
}



function getNew($id)
{
    $result = wp_remote_get( "https://hacker-news.firebaseio.com/v0/item/" . $id . ".json?print=pretty", array(
        "headers" => array(
            "Content-Type" => "application/json",
            "Accept" => "application/json",
            "cache-control" => "no-cache"
        )
    ) );

    if( is_wp_error( $result ) ) {
        return; // Bail early
    }

    $newObj = json_decode($result["body"], true);

    $output =  "<tr>";
    $output .= "<td>{$newObj["id"]}</td>";
    $output .= "<td>";

    if (isset($newObj["url"]))
        $output .= "<a href='{$newObj["url"]}'>{$newObj["title"]}</a>";
    else $output .= $newObj["title"];

    $output .= "<div>Created at: " . date("m/d/Y H:i:s", $newObj["time"]) . "</div></td>";
    $output .= "<td>{$newObj["type"]}</td>";
    $output .= "<td>{$newObj["by"]}</td>";
    $output .= "<td>{$newObj["score"]}</td>";
    $output .= "</tr>";

    return $output;
}
