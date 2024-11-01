<?php
class sfs_admin{

	//Get initial parameters from db
	public function __construct(){
		$this->update_message = '';
		$this->sfsapi = get_sfs_api();
		$this->sfsuid = get_sfs_uid();
		$this->sfsurl = get_sfs_url();
		$this->sfsuser = get_sfs_user();
	}

	//Get photosets for user
	public function get_photosets(){
		$params = array(
			'api_key' => $this->sfsapi,
			'user_id' => $this->sfsuid,
			'method' => 'flickr.photosets.getList',
			'per_page' => '100'
		);
		$response = sfs_api_call($params);

		//Sanity check
		if ($response['stat'] != 'ok'){
			return '<h5>Could not fetch photosets!  ' . $response['message'] . '</h5>';
		}

		//Form the markup to return
		$markup = "<h3>Photosets for $this->sfsuser</h3>";
		if ($response->photosets->total == '0'){
			$markup .= "<p>There does't appear to be any photo sets in your account.</p>";
		}else{
			$markup .= '<div class="sfs-inline"><div class="photoset-header">Photoset Title:</div><div class="photoset-header">Use Shortcode:</div></div>';
			foreach ($response['photosets']['photoset'] as $photoset){
				$markup .= '<div class="sfs-inline"><div class="photoset">' . $photoset['title']['_content'] . '</div><div class="photoset">[simple-flickr set="' . $photoset['id'] . '"]</div></div>';
			}
		}
		return $markup;
	}

	// Set user info based on URL path
	public function set_username($url){
		$params = array(
			'api_key' => $this->sfsapi,
			'method' => 'flickr.urls.lookupUser',
			'url' => $url
		);
		$response = sfs_api_call($params);

		//Make sure our call was sane
		if ($response['stat'] != 'ok'){
			return "Could not get username (" . $response['message'] . ").  Please check the URL path.  You can still use the plugin, but you'll need to look up the photosets on your own.";
		}

		//Update URL in db and object
		if(!$this->update_url($url)){
			return "Could not update the URL in the database (This shouldn't happen!)";
		}else{
			$this->sfsurl = $url;
		}
		
		//Update uid in the database
		$uid = $response['user']['id'];
		if(!$this->update_uid($uid)){
			return "Could not update the user id in the database (This shouldn't happen!)";
		}else{
			$this->sfsuid = $uid;
		}

		//Update username in the database
		$username = $response['user']['username']['_content'];
		if(!$this->update_username($username)){
			return "Could not update the user id in the database (This shouldn't happen!)";
		}else{
			$this->sfsuser = $username;
		}
		return "Successfully updated user info based on URL.";
	}

	//Update the API in the database and object
	public function update_api($sfsapi){
		global $wpdb;
		if(!$wpdb->query($wpdb->prepare("
			INSERT INTO $wpdb->options
			( option_name, option_value )
			VALUES ( 'sfsapi', %s )
			ON DUPLICATE KEY UPDATE
			option_value = VALUES(option_value)",
			$sfsapi
		))){
			$this->update_message = "Unable to update the API key in the database!  (This shouldn't happen)";
		}else{
			$this->sfsapi = $sfsapi;
			$this->update_message = "Successfully updated API key.";
		}
	}

	//Update User ID in the database
	public function update_uid($uid){
                global $wpdb;

                return $wpdb->query($wpdb->prepare("
                        INSERT INTO $wpdb->options
                        ( option_name, option_value )
                        VALUES ( 'sfsuid', %s )
                        ON DUPLICATE KEY UPDATE
                        option_value = VALUES(option_value)",
                        $uid
                ));
	}

	//Update User URL in database
	public function update_url($url){
                global $wpdb;

                return $wpdb->query($wpdb->prepare("
                        INSERT INTO $wpdb->options
                        ( option_name, option_value )
                        VALUES ( 'sfsurl', %s )
                        ON DUPLICATE KEY UPDATE
                        option_value = VALUES(option_value)",
                        $url
                ));
	}

	//Update Username in the database
	public function update_username($username){
		global $wpdb;
		
		return $wpdb->query($wpdb->prepare("
                        INSERT INTO $wpdb->options
                        ( option_name, option_value )
                        VALUES ( 'sfsuser', %s )
                        ON DUPLICATE KEY UPDATE
                        option_value = VALUES(option_value)",
                        $username
                ));
	}

	//Make sure the API key checks out
	public function validate_API($sfsapi){
		$params = array(
			'api_key' => $sfsapi,
			'method' => 'flickr.test.echo',
		);
		$response = sfs_api_call($params);
		if ($response['stat'] == 'ok'){
			return True;
		}else{
			$this->update_message = 'Could not validate the API key: ' . $response['message'];
			return False;
		}
	}
}

$sfsadmin = new sfs_admin;

if (isset($_POST['submit'])){
	$sfsapi = $_POST['sfs-api'];
	if ((!is_null($sfsapi)) and ($sfsapi != $sfsadmin->sfsapi) and ($sfsadmin->validate_API($sfsapi))){
		$sfsadmin->update_api($sfsapi);
	}
	$sfsurl = $_POST['sfs-url'];
	if ((!is_null($sfsurl)) and (!(is_null($sfsadmin->sfsapi))) and ($sfsurl != $sfsadmin->sfsurl)){
		$sfsadmin->update_message .= "<br/>" . $sfsadmin->set_username($sfsurl);
	}
}
?>

<style type="text/css">
div#photosets{
	display:table;
	table-layout: fixed;
}

div.sfs-inline{
	clear:both;
	display:table-row;
	outline: outset thin;
}

div.photoset-header{
	font-weight: bold;
	display:table-cell;
}

div.photoset{
	display:table-cell;
	padding-left: 5px;
	max-width: 30em;
}

label.sfs-admin-label{
	width: 15em;	
	display: inline-block;
}

</style>

<h1 id="sfs-title">Simple Flickr Set</h1>

<h3 id="sfs-menu-info">We just need the API key for your Flickr account.  Not sure what your API key is?  <a href="http://www.flickr.com/services/api/misc.api_keys.html">Get it here</a>.</h3>

<div id="sfs-menu">
	<form id="sfs-options" method="POST">
                <div id="divsfs-url">
                        <label class="sfs-admin-label" for="sfs-url">Link to your Flickr Page:</label>
                        <input type="text" size="30" name="sfs-url" placeholder="http://www.flickr.com/photos/username" value="<?php echo $sfsadmin->sfsurl;?>"/>
                </div>
                <div id="divsfs-api">
                        <label class="sfs-admin-label" for="sfs-api"><a href="http://www.flickr.com/services/api/misc.api_keys.html">Flickr API Key</a>:</label>
                        <input type="text" size="30" name="sfs-api" placeholder="API Key" value="<?php echo $sfsadmin->sfsapi;?>"/>
                </div><br/>
		<button class="button btn-large" name="submit" value="Submit">Update</button>
	</form>
</div>

<div id="apiupdate">
	<p id="apiupdate-message"><?php echo $sfsadmin->update_message;?></p>
</div>

<div id="photosets">
	<?php if ((!is_null($sfsadmin->sfsapi)) and (!is_null($sfsadmin->sfsuid))){ echo $sfsadmin->get_photosets(); }?>
</div>
