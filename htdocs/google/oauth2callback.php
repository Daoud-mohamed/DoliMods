<?php
// Tutorial: http://25labs.com/import-gmail-or-google-contacts-using-google-contacts-data-api-3-0-and-oauth-2-0-in-php/
// Tutorial: https://developers.google.com/google-apps/contacts/v3/


$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res && @file_exists("../../../../main.inc.php")) $res=@include("../../../../main.inc.php");
if (! $res && @file_exists("../../../../../main.inc.php")) $res=@include("../../../../../main.inc.php");
if (! $res && preg_match('/\/(?:custom|nltechno)([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include("../../../dolibarr".$reg[1]."/htdocs/main.inc.php"); // Used on dev env only
if (! $res) die("Include of main fails");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/geturl.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php');
dol_include_once("/google/lib/google.lib.php");


// You must allow Dolibarr to login to

//$client_id='258042696143.apps.googleusercontent.com';
//$client_secret='HdmLOMStzB9MBbAjCr87gz27';
$client_id=$conf->global->GOOGLE_API_CLIENT_ID;
$client_secret=$conf->global->GOOGLE_API_CLIENT_SECRET;

//$redirect_uri='http://localhost/dolibarrnew/custom/google/oauth2callback.php?action=xxx'; // Does not work. Must be exact url
//$redirect_uri='http://localhost/dolibarrnew/custom/google/oauth2callback.php';
$redirect_uri=dol_buildpath('/google/oauth2callback.php', 2);

$jsallowed=preg_replace('/(https*:\/\/[^\/]+\/).*$/','\1',$redirect_uri);

// This is used only if we want to login from this page for test purpose.
$completeoauthurl='https://accounts.google.com/o/oauth2/auth';
$completeoauthurl.='?response_type=code&client_id='.urlencode($conf->global->GOOGLE_API_CLIENT_ID);
$completeoauthurl.='&redirect_uri='.urlencode($redirect_uri);
$completeoauthurl.='&scope='.urlencode('https://www.google.com/m8/feeds https://www.googleapis.com/auth/contacts.readonly');
$completeoauthurl.='&state=dolibarrtokenrequest-oauth2callback';		// To know we are coming from this page
$completeoauthurl.='&access_type=offline';
$completeoauthurl.='&approval_prompt=force';
$completeoauthurl.='&include_granted_scopes=true';
$url=$completeoauthurl;


$code = GETPOST("code");
$state = GETPOST('state');
$scope = GETPOST('scope');


/*
 * Actions
 */

// Ask token (possible only if inside an oauth google session)
if (empty($_SESSION['google_web_token']) || $code)		// We are not into a google session (google_web_token empty) or we come from a redirect of Google auth page
{
	if (! $code)	// If we are not coming from oauth page, we make a redirect to it
	{
		//print 'We are not coming from an oauth page and are not logged into google oauth, so we redirect to it';
		header("Location: ".$url);
		exit;
	}

	// Information received from google are saved into parameter
	// For success: state=security_token&code=....
	// For error: error=access_denied
	//var_dump($_GET);
	dol_syslog('Return from Google oauth with $_GET = '.dol_json_encode($_GET));

	// Forge the url auth part for calling google services
	$fields=array(
		'code'=>  urlencode($code),
		'client_id'=>  urlencode($client_id),
		'client_secret'=>  urlencode($client_secret),
		'redirect_uri'=>  urlencode($redirect_uri),
		'grant_type'=>  urlencode('authorization_code')
	);
	$post = '';
	foreach($fields as $key=>$value)
	{
		$post .= $key.'='.$value.'&';
	}
	$post = rtrim($post,'&');

	$result = getURLContent('https://accounts.google.com/o/oauth2/token', 'POST', $post);
	/*
	$curl = curl_init();
	curl_setopt($curl,CURLOPT_URL,'https://accounts.google.com/o/oauth2/token');
	curl_setopt($curl,CURLOPT_POST,5);
	curl_setopt($curl,CURLOPT_POSTFIELDS,$post);
	curl_setopt($curl,CURLOPT_RETURNTRANSFER,TRUE);
	curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
	$result = curl_exec($curl);
	curl_close($curl);

	dol_syslog("Call 'https://accounts.google.com/o/oauth2/token' and get response ".$result);
	$response =  json_decode($result, true);
	*/
	$response=json_decode($result['content'], true);

	// response should be an array like array('access_token' => , 'token_type' => 'Bearer', 'expires_in' => int 3600)
	if (empty($response['access_token']))
	{

	}
	else
	{
		// The token is the full string into $result['content'] like '{"access_token":"ya29.iQEPBPUAVLXeVq1-QnC6-SHydA9czPX3ySJ5SfYSo5ZIMfFEl5MTs62no8hZp5jUUsm3QVHTrBg7hw","expires_in":3600,"created":1433463453}';
		//var_dump($response);

		// Save token into database
		require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
		$res=dolibarr_set_const($db,'GOOGLE_WEB_TOKEN',trim($result['content']),'chaine',0);
		$_SESSION['google_web_token']=trim($result['content']);
		if (! $res > 0) $error++;

	}

	// Redirect to original page
	if ($state == 'dolibarrtokenrequest-googleadmincontactsync')
	{
		header('Location: '.dol_buildpath('/google/admin/google_contactsync.php',1));
		exit;
	}

	//	$_SESSION['google_web_token']=$response->access_token;
}



// After this, should never be used, except for test purpose
// ---------------------------------------------------------

/*
 * View
 */

$google_web_token = $_SESSION['google_web_token'];

$max_results = 10;

llxHeader();



print '<iframe src="http://www.google.com/calendar/embed?showTitle=0&amp;height=600&amp;wkst=1&amp;bgcolor=%23f4f4f4&amp;src=eldy10%40gmail.com&amp;color=%237A367A&amp;ctz=Europe%2FParis" style=" border-width:0 " width="100%" height="600" frameborder="0" scrolling="no">zob</iframe>';


// Get contacts using oauth
/*
 print 'This page is a test page show information on your Google account using Oauth login. It is not used by module.<br>';
print '<a href="'.$url.'">Click here to authenticate using oauth</a><br><br>';

//var_dump($_GET);
//var_dump($_POST);

print 'We are into an oauth authenticated session oauth_token='.$google_web_token.'<br>';

// Ask API
$url = 'https://www.google.com/m8/feeds/contacts/default/full?max-results='.$max_results.'&oauth_token='.$google_web_token;
$xmlresponse =  curl_file_get_contents($url);
if((strlen(stristr($xmlresponse,'Authorization required'))>0) && (strlen(stristr($xmlresponse,'Error '))>0)) //At times you get Authorization error from Google.
{
echo "<h2>OOPS !! Something went wrong. Please try reloading the page.</h2>";
exit();
}

echo "<h3>Addresses:</h3>";
//var_dump($xmlresponse);

//$xml =  new SimpleXMLElement($xmlresponse);
//$xml->registerXPathNamespace('gd', 'http://schemas.google.com/g/2005');
//$result = $xml->xpath('//gd:email');

//foreach ($result as $title) { echo $title->attributes()->address . "<br>"; }
*/


// Create a contact using oauth
/*$urltocreate='https://www.google.com/m8/feeds/contacts/testapi@testapi.com/full&oauth_token='.$google_web_token;
 $curl = curl_init();
curl_setopt($curl,CURLOPT_URL,$urltocreate);
curl_setopt($curl,CURLOPT_POST,0);
//curl_setopt($curl,CURLOPT_POSTFIELDS,$post);
curl_setopt($curl,CURLOPT_RETURNTRANSFER,TRUE);
curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
$result = curl_exec($curl);
curl_close($curl);
var_dump($result);
*/


llxFooter();

$db->close();