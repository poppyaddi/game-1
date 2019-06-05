<?php
if(!defined('InEmpireBak'))
{
	exit();
}
$onclickword='(���ת�򱸷�����)';
$change=(int)$_GET['change'];
if($change==1)
{
	$onclickword='(���ѡ��)';
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gb2312">
<title>�����ݱ�������</title>
<link href="images/css.css" rel="stylesheet" type="text/css">
<script>
function ChangeSet(filename)
{
	var ok=confirm("ȷ��Ҫ����?");
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
    <td>λ�ã�<a href="ListSetbak.php">����������</a>&nbsp;(���Ŀ¼��<b>setsave</b>)</td>
  </tr>
</table>
<br>
<table width="500" border="0" cellpadding="3" cellspacing="1" class="tableborder">
  <tr class="header"> 
    <td width="63%" height="25"> <div align="center">���������ļ���<?=$onclickword?></div></td>
    <td width="37%"><div align="center">����</div></td>
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
		//Ĭ������
		if($file=='def')
		{
			if(empty($change))
			{
				$showfile=$file;
			}
			$showdel="<b>Ĭ������</b>";
		}
		else
		{
			$showdel="<a href=\"phome.php?phome=DoDelSave&mydbname=$mydbname&change=$change&savename=$file\" onclick=\"return confirm('ȷ��Ҫɾ����');\">ɾ������</a>";
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
    <td height="25" colspan="2" bgcolor="#FFFFFF"><font color="#666666">(˵�����������ݱ�ʱ����Ĳ������á�)</font></td>
  </tr>
</table>
</body>
</html>