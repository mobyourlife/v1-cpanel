<?php

/* Conecta-se ao banco de dados. */
function db_conectar()
{
	global $mysql_hostname, $mysql_username, $mysql_password, $mysql_database;
	$db = mysqli_connect($mysql_hostname, $mysql_username, $mysql_password);
	mysqli_select_db($db, $mysql_database);
	return $db;
}

function is_user_registered($fb_uid)
{
	$db = db_conectar();
	
	$sql = sprintf("SELECT 1 FROM myl_accounts WHERE admin_uid = %s;", $fb_uid);
	$res = mysqli_query($db, $sql);
	$count = mysqli_num_rows($res);
	
	mysqli_close($db);
	
	return ($count == 1) ? true : false;
}

function is_subdomain_taken($subdomain)
{
	$db = db_conectar();
	
	$sql = sprintf("SELECT 1 FROM myl_subdomains WHERE subdomain = '%s';", $subdomain);
	$res = mysqli_query($db, $sql);
	$count = mysqli_num_rows($res);
	
	mysqli_close($db);
	
	return ($count == 1) ? true : false;
}

function register_user($admin_uid, $admin_name, $admin_email, $account_id, $access_token, $subdomain)
{
	$db = db_conectar();
	
	$sql = sprintf("INSERT INTO myl_subdomains (page_fbid, subdomain) VALUES (%s, '%s');"
		, $account_id, $subdomain);
	mysqli_query($db, $sql);
	
	if (is_subdomain_taken($subdomain))
	{
		$sql = sprintf("INSERT INTO myl_accounts (admin_uid, admin_name, admin_email, page_fbid, register_date, access_token) VALUES (%s, '%s', '%s', %s, '%s', '%s');"
			, $admin_uid, $admin_name, $admin_email, $account_id, mobdate(), $access_token);
		mysqli_query($db, $sql);
		
		mysqli_query($db, sprintf("INSERT INTO myl_categorias (page_fbid, nome_categoria, nome_seo) VALUES (%s, '%s', '%s');", $account_id, "Fotos", "fotos"));
		mysqli_query($db, sprintf("INSERT INTO myl_categorias (page_fbid, nome_categoria, nome_seo) VALUES (%s, '%s', '%s');", $account_id, "Vídeos", "videos"));
	}
	
	mysqli_close($db);
	
	return is_user_registered($admin_uid);
}

function get_user_subdomain($fb_uid)
{
	$db = db_conectar();
	
	$sql = sprintf("SELECT subdomain FROM myl_subdomains WHERE page_fbid = (SELECT page_fbid FROM myl_accounts WHERE admin_uid = %s);", $fb_uid);
	$res = mysqli_query($db, $sql);
	$subdomain = "";
	
	if (mysqli_num_rows($res) != 0)
	{
		$row = mysqli_fetch_assoc($res);
		$subdomain = $row['subdomain'];
	}
	
	mysqli_close($db);
	
	return $subdomain;
}

function get_user_domain($fb_uid)
{
	$fqdn = sprintf("%s.mobyourlife.com.br", get_user_subdomain($fb_uid));
	return $fqdn;
}

function get_user_fqdn($fb_uid)
{
	$fqdn = sprintf("http://%s", get_user_domain($fb_uid));
	return $fqdn;
}

function get_account_type($fb_uid)
{
	$db = db_conectar();
	
	$sql = sprintf("SELECT page_fbid FROM myl_accounts WHERE admin_uid = %s;", $fb_uid);
	$res = mysqli_query($db, $sql);
	$acctype = "profile";
	
	if (mysqli_num_rows($res) != 0)
	{
		$row = mysqli_fetch_assoc($res);
		if ($row['page_fbid'] != $fb_uid)
		{
			$acctype = "fanpage";
		}
	}
	
	mysqli_close($db);
	
	return $acctype;
}

function get_register_date($fb_uid)
{
	$db = db_conectar();
	
	$sql = sprintf("SELECT register_date FROM myl_accounts WHERE admin_uid = %s;", $fb_uid);
	$res = mysqli_query($db, $sql);
	$regdate = "-";
	
	if (mysqli_num_rows($res) != 0)
	{
		$row = mysqli_fetch_assoc($res);
		$regdate = fromsqldate($row['register_date']);
	}
	
	mysqli_close($db);
	
	return $regdate;
}

function save_user_info($fb_profile)
{
	$fb_uid = $fb_profile->getProperty('id');
	$is_fanpage = ((get_account_type($fb_uid) == "fanpage") ? true : false);
	$page_name = $fb_profile->getProperty('name');

	$db = db_conectar();
	
	$sql = sprintf("INSERT INTO myl_profiles (admin_uid, is_fanpage, page_name) VALUES (%s, %d, '%s') ON DUPLICATE KEY UPDATE page_name = '%s';", $fb_uid, $is_fanpage, $page_name, $page_name);
	mysqli_query($db, $sql);
	
	mysqli_close($db);
}

function get_page_fbid($admin_uid)
{
	$db = db_conectar();
	
	$sql = sprintf("SELECT page_fbid FROM myl_accounts WHERE admin_uid = %s;", $admin_uid);
	$res = mysqli_query($db, $sql);
	$page_fbid = $admin_uid;
	
	if (mysqli_num_rows($res) != 0)
	{
		$row = mysqli_fetch_assoc($res);
		$page_fbid = $row['page_fbid'];
	}
	
	mysqli_close($db);
	
	return $page_fbid;
}

function save_page_info($fb_account)
{
	$db = db_conectar();
	
	$sql = sprintf("UPDATE myl_accounts SET admin_name = '%s', access_token = '%s' WHERE page_fbid = %s;", $fb_account->name, $fb_account->access_token, $fb_account->id);
	mysqli_query($db, $sql);
	
	mysqli_close($db);
}

?>
