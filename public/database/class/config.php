<?php
if(!defined('InEmpireBak'))
{
	exit();
}
define('EmpireBakConfig',TRUE);

//Database
$phome_db_dbtype='mysql';
$phome_db_ver='5.0';
$phome_db_server='localhost';
$phome_db_port='';
$phome_db_username='root';
$phome_db_password='sd123456';
$phome_db_dbname='sd';
$baktbpre='';
$phome_db_char='';

//USER
$set_username='admin';
$set_password='ac91ef4cd268bd62657a8bcda6f94d75';
$set_loginauth='';
$set_loginrnd='quyum';
$set_outtime='60';
$set_loginkey='1';
$ebak_set_keytime=60;
$ebak_set_ckuseragent='';

//COOKIE
$phome_cookiedomain='';
$phome_cookiepath='/';
$phome_cookievarpre='hfnvum_';

//LANGUAGE
$langr=ReturnUseEbakLang();
$ebaklang=$langr['lang'];
$ebaklangchar=$langr['langchar'];

//BAK
$bakpath='bdata';
$bakzippath='zip';
$filechmod='1';
$phpsafemod='';
$php_outtime='1000';
$limittype='';
$canlistdb='';
$ebak_set_moredbserver='';
$ebak_set_hidedbs='';
$ebak_set_escapetype='1';

//EBMA
$ebak_ebma_open=1;
$ebak_ebma_path='phpmyadmin';
$ebak_ebma_cklogin=0;

//SYS
$ebak_set_ckrndvar='eygtgcyngpzg';
$ebak_set_ckrndval='7a4032218777c6c0dde53e0502c0550f';
$ebak_set_ckrndvaltwo='5eef53aaba382b9cba101eaf1b105dad';
$ebak_set_ckrndvalthree='39cc0c6f24af8da81b4f3d445794d573';
$ebak_set_ckrndvalfour='4eafe1bbb38f2fe977fd122e9ea8c515';

//------------ SYSTEM ------------
HeaderIeChar();
?>