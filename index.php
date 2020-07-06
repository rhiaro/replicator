<?
session_start();
require 'vendor/autoload.php';
$ns = Rhiaro\ns();

$tz = Rhiaro\get_timezone_from_rdf("https://rhiaro.co.uk/tz");
date_default_timezone_set($tz);

$tags = array(
    "beans" => "https://rhiaro.co.uk/tags/beans",
    "bread" => "https://rhiaro.co.uk/tags/bread",
    "cake" => "https://rhiaro.co.uk/tags/cake",
    "coffee" => "https://rhiaro.co.uk/tags/coffee",
    "fruit" => "https://rhiaro.co.uk/tags/fruit",
    "lentils" => "https://rhiaro.co.uk/tags/lentils",
    "pasta" => "https://rhiaro.co.uk/tags/pasta",
    "potato" => "https://rhiaro.co.uk/tags/potato",
    "rice" => "https://rhiaro.co.uk/tags/rice",
    "salad" => "https://rhiaro.co.uk/tags/salad",
    "sandwich" => "https://rhiaro.co.uk/tags/sandwich",
    "tea" => "https://rhiaro.co.uk/tags/tea",
    "vegetables" => "https://rhiaro.co.uk/tags/vegetables",
);

if(isset($_POST['replicated'])){
    if(isset($_POST['endpoint_key'])){
        $_SESSION['key'] = $_POST['endpoint_key'];
    }
    $endpoint = $_POST['endpoint_uri'];
    $result = Rhiaro\form_to_endpoint($_POST);

    if(is_array($result)){
        $errors = $result;
        unset($result);
    }
}
include('templates/index.php');
?>