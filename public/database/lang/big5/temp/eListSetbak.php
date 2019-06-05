<?php
if(!defined('InEmpireBak'))
{
	exit();
}
$onclickword='(�I����V�ƥ��ƾ�)';
$change=(int)$_GET['change'];
if($change==1)
{
	$onclickword='(�I�����)';
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=big5">
<title>�޲z�ƥ��O�s�]�m</title>
<link href="images/css.css" rel="stylesheet" type="text/css">
<script>
function ChangeSet(filename)
{
	var ok=confirm("�T�{�n�ɤJ?");
	if(ok)
	{
		opener.parent.ebakmain.location.href='ChangeTable.php?mydbname=<?=$mydbname?>&savefilename='+filename;
		window.close();
	}
}
</script>
</head>

<body>
<table width="100%" border="0" align="center" cellpadding="3" cellspacing="1">
  <tr> 
    <td>��m�G<a href="ListSetbak.php">�޲z�ƥ��]�m</a>&nbsp;(�s��ؿ��G<b>setsave</b>)</td>
  </tr>
</table>
<br>
<table width="500" border="0" cellpadding="3" cellspacing="1" class="tableborder">
  <tr class="header"> 
    <td width="63%" height="25"> <div align="center">�O�s�]�m���W<?=$onclickword?></div></td>
    <td width="37%"><div align="center">�ާ@</div></td>
  </tr>
  <?php
  while($file=@readdir($hand))
  {
  	if($file!="."&&$file!=".."&&$file!="Index.html"&&is_file("setsave/".$file))
	{
		if($change==1)
		{
			$showfile="<a href='#ebak' onclick=\"javascript:ChangeSet('$file');\" title='$file'>$file</a>";
		}
		else
		{
			$showfile="<a href='phome.php?phome=SetGotoBak&savename=$file' title='$file'>$file</a>";
		}
		//�q�{�]�m
		if($file=='def')
		{
			if(empty($change))
			{
				$showfile=$file;
			}
			$showdel="<b>�q�{�]�m</b>";
		}
		else
		{
			$showdel="<a href=\"phome.php?phome=DoDelSave&mydbname=$mydbname&change=$change&savename=$file\" onclick=\"return confirm('�T�{�n�R���H');\">�R���]�m</a>";
		}
  ?>
  <tr bgcolor="#FFFFFF"> 
    <td height="25"> <div align="left"><img src="images/txt.gif" width="19" height="16">&nbsp; 
        <?=$showfile?> </div></td>
    <td><div align="center">&nbsp;[<?=$showdel?>]</div></td>
  </tr>
  <?
     }
  }
  ?>
  <tr> 
    <td height="25" colspan="2" bgcolor="#FFFFFF"><font color="#666666">(�����G�ƥ��ƾڪ�ɫO�s���ѼƳ]�m�C)</font></td>
  </tr>
</table>
</body>
</html>