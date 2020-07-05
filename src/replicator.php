<?
namespace Rhiaro;

use EasyRdf_Graph;
use EasyRdf_Resource;
use EasyRdf_Namespace;
use EasyRdf_Literal;
use Requests;

// Form input processing

function make_tags($input_array){
    $base = "https://rhiaro.co.uk/tags/";
    $tags_string = $input_array["string"];
    unset($input_array["string"]);
    $tags = explode(",", $tags_string);
    foreach($tags as $tag){
        if(strlen(trim($tag)) > 0){
            $input_array[] = $base.urlencode(trim($tag));
        }
    }
    return $input_array;
}

function make_date($date_parts){
    $date_str = make_date_string($date_parts);
    $date = new EasyRdf_Literal($date_str, null, "xsd:dateTime");
    return $date;
}

function make_date_string($date_parts){
    $date_str = $date_parts["year"]."-".$date_parts["month"]."-".$date_parts["day"]."T".$date_parts["time"].$date_parts["zone"];
    return $date_str;
}

function make_payload($form_request){
    global $ns;
    $g = new EasyRdf_Graph();
    $context = $ns->get("as");
    $options = array("compactArrays" => true);

    $endpoint = $form_request["endpoint_uri"];
    $key = $form_request["endpoint_key"];

    $published_date_parts = [
        "year" => $form_request["year"],
        "month" => $form_request["month"],
        "day" => $form_request["day"],
        "time" => $form_request["time"],
        "zone" => $form_request["zone"],
    ];
    $published_date = make_date($published_date_parts);

    $tags = make_tags($form_request["tags"]);
    $content = trim($form_request["content"]);

    if(!empty($content)){
        $node = $g->newBNode();
        $g->addType($node, "as:Activity");
        $g->addType($node, "asext:Consume");
        $g->addLiteral($node, "as:published", $published_date);
        $g->addLiteral($node, "as:content", $content);
        foreach($tags as $tag){
            $g->addResource($node, "as:tag", $tag);
        }
    }
    echo $g->dump();
}

// Posting

function form_to_endpoint($form_request){
    $endpoint = $form_request["endpoint_uri"];
    $key = $form_request["endpoint_key"];
    $payload = make_payload($form_request);
    if(is_array($payload)){
        // Errors
        return array("errno" => count($payload), "errors" => $payload);
    }else{
        $response = post_to_endpoint($endpoint, $key, $payload);
        return $response;
    }
}

function post_to_endpoint($endpoint, $key, $payload){
    $headers = array("Content-Type" => "application/ld+json", "Authorization" => $key);
    $response = Requests::post($endpoint, $headers, $payload);
    return $response;
}

?>