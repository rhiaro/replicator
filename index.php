<?
session_start();
date_default_timezone_set(file_get_contents("http://rhiaro.co.uk/tz"));
if(isset($_GET['logout'])){ session_unset(); session_destroy(); header("Location: /replicator"); }
if(isset($_GET['reset']) && $_GET['reset'] == "images") { $_SESSION['images'] = set_default_images(); header("Location: /replicator"); }
if(isset($_GET['reset']) && $_GET['reset'] == "feed") { unset($_SESSION['feed']); unset($_SESSION['feed_source']); header("Location: /replicator"); }

include "link-rel-parser.php";

$base = "https://apps.rhiaro.co.uk/replicator";
if(isset($_GET['code'])){
  $auth = auth($_GET['code'], $_GET['state']);
  if($auth !== true){ $errors = $auth; }
  else{
    $response = get_access_token($_GET['code'], $_GET['state']);
    if($response !== true){ $errors = $auth; }
    else {
      header("Location: ".$_GET['state']);
    }
  }
}

// VIP cache
$vips = array("http://rhiaro.co.uk", "http://rhiaro.co.uk/", "http://tigo.rhiaro.co.uk/");

function auth($code, $state, $client_id="https://apps.rhiaro.co.uk/replicator"){
  
  $params = "code=".$code."&redirect_uri=".urlencode($state)."&state=".urlencode($state)."&client_id=".$client_id;
  $ch = curl_init("https://indieauth.com/auth");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded", "Accept: application/json"));
  curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
  //curl_setopt($ch, CURLOPT_HEADERFUNCTION, "dump_headers");
  $response = curl_exec($ch);
  $response = json_decode($response, true);
  $_SESSION['me'] = $response['me'];
  $info = curl_getinfo($ch);
  curl_close($ch);
  
  if(isset($response) && ($response === false || $info['http_code'] != 200)){
    $errors["Login error"] = $info['http_code'];
    if(curl_error($ch)){
      $errors["curl error"] = curl_error($ch);
    }
    return $errors;
  }else{
    return true;
  }
}

function get_access_token($code, $state, $client_id="https://apps.rhiaro.co.uk/replicator"){
  
  $params = "me={$_SESSION['me']}&code=$code&redirect_uri=".urlencode($state)."&state=".urlencode($state)."&client_id=$client_id";
  $token_ep = discover_endpoint($_SESSION['me'], "token_endpoint");
  $ch = curl_init($token_ep);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
  curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
  $response = Array();
  parse_str(curl_exec($ch), $response);
  $info = curl_getinfo($ch);
  curl_close($ch);
  
  if(isset($response) && ($response === false || $info['http_code'] != 200)){
    $errors["Login error"] = $info['http_code'];
    if(curl_error($ch)){
      $errors["curl error"] = curl_error($ch);
    }
    return $errors;
  }else{
    $_SESSION['access_token'] = $response['access_token'];
    return true;
  }
  
}

function discover_endpoint($url, $rel="micropub"){
  if(isset($_SESSION[$rel])){
    return $_SESSION[$rel];
  }else{
    $res = head_http_rels($url);
    $rels = $res['rels'];
    if(!isset($rels[$rel][0])){
      $parsed = json_decode(file_get_contents("https://pin13.net/mf2/?url=".$url), true);
      if(isset($parsed['rels'])){ $rels = $parsed['rels']; }
    }
    if(!isset($rels[$rel][0])){
      // TODO: Try in body
      return "Not found";
    }
    $_SESSION[$rel] = $rels[$rel][0];
    return $rels[$rel][0];
  }
}

function context(){
  return array(
      "@context" => array("as" => "http://www.w3.org/ns/activitystreams#", "blog" => "http://vocab.amy.so/blog#")
    );
}

function form_to_json($post){
  $context = context();
  $data = array_merge($context, $post);
  unset($data['consume']);
  $data["@type"] = array("blog:Consumption");
  $data['as:published'] = $post['year']."-".$post['month']."-".$post['day']."T".$post['time'].$post['zone'];
  unset($data['year']); unset($data['month']); unset($data['day']); unset($data['time']); unset($data['zone']);
  if(isset($post['image'])) $data['as:image'] = array("@id" => $post['image'][0]);
  $json = stripslashes(json_encode($data, JSON_PRETTY_PRINT));
  return $json;
}

function post_to_endpoint($json, $endpoint){
  $ch = curl_init($endpoint);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/activity+json"));
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer ".$_SESSION['access_token']));
  curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
  $response = Array();
  parse_str(curl_exec($ch), $response);
  $info = curl_getinfo($ch);
  curl_close($ch);
  
  return $response;
}

if(isset($_POST['replicated'])){
  if(isset($_SESSION['me'])){
    $endpoint = discover_endpoint($_SESSION['me']);
    $result = post_to_endpoint(form_to_json($_POST), $endpoint);
  }else{
    $errors["Not signed in"] = "You need to sign in to post.";
  }
}

?>
<!doctype html>
<html>
  <head>
    <title>replicator</title>
    <link rel="stylesheet" type="text/css" href="https://apps.rhiaro.co.uk/css/normalize.min.css" />
    <link rel="stylesheet" type="text/css" href="https://apps.rhiaro.co.uk/css/main.css" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style type="text/css">
      pre { max-height: 4em; overflow: auto; }
    </style>
  </head>
  <body>
    <main class="w1of2 center">
      <h1>Replicator</h1>
      
      <?if(isset($errors)):?>
        <div class="fail">
          <?foreach($errors as $key=>$error):?>
            <p><strong><?=$key?>: </strong><?=$error?></p>
          <?endforeach?>
        </div>
      <?endif?>
      
      <?if(isset($result)):?>
        <div>
          <p>The response from you your micropub endpoint:</p>
          <code><?=$endpoint?></code>
          <pre>
            <? var_dump($result); ?>
          </pre>
        </div>
      <?endif?>
      
      <form method="post" role="form" id="consume">
        <p><input type="submit" value="Post" class="neat" name="replicated" /></p>
        <p><label for="name" class="neat">Food</label> <input type="text" name="as:name" id="name" class="neat" /></p>
        <p><label for="tagst" class="neat">Tags</label> <input type="text" name="as:tag" id="tags"class="neat" /></p>
        <p>
          <select name="year" id="year">
            <option value="2016" selected>2016</option>
            <option value="2016">2015</option>
          </select>
          <select name="month" id="month">
            <?for($i=1;$i<=12;$i++):?>
              <option value="<?=date("m", strtotime("2016-$i-01"))?>"<?=(date("n") == $i) ? " selected" : ""?>><?=date("M", strtotime("2016-$i-01"))?></option>
            <?endfor?>
          </select>
          <select name="day" id="day">
            <?for($i=1;$i<=31;$i++):?>
              <option value="<?=date("d", strtotime("2016-01-$i"))?>"<?=(date("j") == $i) ? " selected" : ""?>><?=date("d", strtotime("2016-01-$i"))?></option>
            <?endfor?>
          </select>
          <input type="text" name="time" id="time" value="<?=date("H:i:s")?>" />
          <input type="text" name="zone" id="zone" value="<?=date("P")?>" />
        </p>
      </form>
      
      <div class="color3-bg inner">
        <?if(isset($_SESSION['me'])):?>
          <p class="wee">You are logged in as <strong><?=$_SESSION['me']?></strong> <a href="?logout=1">Logout</a></p>
        <?else:?>
          <form action="https://indieauth.com/auth" method="get" class="inner clearfix">
            <label for="indie_auth_url">Domain:</label>
            <input id="indie_auth_url" type="text" name="me" placeholder="yourdomain.com" />
            <input type="submit" value="signin" />
            <input type="hidden" name="client_id" value="http://rhiaro.co.uk" />
            <input type="hidden" name="redirect_uri" value="<?=$base?>" />
            <input type="hidden" name="state" value="<?=$base?>" />
            <input type="hidden" name="scope" value="post" />
          </form>
        <?endif?>
        
        <h2>Customise</h2>
        <h3>Feed</h3>
        <form method="post" class="inner wee clearfix">
          <p>If you have a public feed of replicator posts, enter the URL here.</p>
          <label for="feed_source">URL of a list of feed:</label>
          <input id="feed_source" name="feed_source" value="<?=isset($_SESSION['feed_source']) ? $_SESSION['feed_source'] : ""?>" />
          <input type="submit" value="Fetch" /> <a href="?reset=feed">Reset</a>
        </form>
        <h3>Post...</h3>
        <form method="post" class="inner wee clearfix">
          <select name="posttype">
            <option value="as2" selected>AS2 JSON</option>
            <option value="mp" disabled>Micropub (form-encoded)</option>
            <option value="mp" disabled>Micropub (JSON)</option>
            <option value="ttl" disabled>Turtle</option>
          </select>
          <input type="submit" value="Save" />
        </form>
      </div>
    </main>
  </body>
</html>