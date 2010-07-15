<?php
/**
 * Poll overview & vote form
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */

require_once "include/bittorrent.php";
dbconn();


$pid = (int) $_GET['id'];

if (isset($_GET['deletevote']) && is_valid_id($_GET['vid']) && (get_user_class() >= UC_MODERATOR)) {
	$vid = (int)$_GET['vid'];

	sql_query("DELETE FROM polls_votes WHERE vid=$vid");
	
	$REL_CACHE->clearGroupCache("block-polls");
	stderr($REL_LANG->say_by_key('success'),"����� ������");
}
loggedinorreturn();

$spbegin = "<div class=\"sp-wrap\"><div class=\"sp-head folded clickable\" style=\"height: 15px;\"><div cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\"><ul><li class=\"bottom\" width=\"50%\"><i>���������� ������������</i></li></ul></div></div><div class=\"sp-body\" style=\"position:absolute;max-width:485px;\">";
$spend = "</div></div>";

if (isset($_GET['vote'])  && ($_SERVER['REQUEST_METHOD'] == 'POST')){
	if ((isset($_POST["vote"]) && !is_valid_id($_POST["vote"])) || !is_valid_id($pid)) 			stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));

	$voteid = (int)$_POST['vote'];

	$pexprow = sql_query("SELECT exp FROM polls WHERE id=$pid");
	list($pexp) = mysql_fetch_array($pexprow);
	if (!is_null($pexp) && ($pexp < time())) stderr($REL_LANG->say_by_key('error'),"���� �������� ������ ��� ������� �����, �� �� ������ ����������");



	$votedrow = sql_query("SELECT sid FROM polls_votes WHERE user=".$CURUSER['id']." AND pid=$pid");
	list($voted) = mysql_fetch_array($votedrow);
	if ($voted) stderr($REL_LANG->say_by_key('error'),"�� ��� ���������� � ���� ������");

	sql_query("INSERT INTO polls_votes (sid,user,pid) VALUES ($voteid,".$CURUSER['id'].",$pid)");

	$REL_CACHE->clearGroupCache("block-polls");
	safe_redirect($REL_SEO->make_link('polloverview','id',$pid));

}

if (!is_valid_id($pid)) 			stderr($REL_LANG->say_by_key('error'), $REL_LANG->say_by_key('invalid_id'));


$id = $pid;
$poll = sql_query("SELECT polls.*, polls_structure.value, polls_structure.id AS sid,polls_votes.vid,polls_votes.user,users.username,users.class,(SELECT SUM(1) FROM pollcomments WHERE poll = $id) AS numcomm FROM polls LEFT JOIN polls_structure ON polls.id = polls_structure.pollid LEFT JOIN polls_votes ON polls_votes.sid=polls_structure.id LEFT JOIN users ON users.id=polls_votes.user WHERE polls.id = $id ORDER BY sid ASC");
$pquestion = array();
$pstart = array();
$pexp = array();
$public = array();
$sidvalues = array();
$votes = array();
$sids = array();
$votesres = array();
$sidcount = array();
$sidvals = array();
$votecount = array();
$usercode = array();
$comments = array();

while ($pollarray = mysql_fetch_array($poll)) {
	$pquestion[] = $pollarray['question'];
	$pstart[] = $pollarray['start'];
	$pexp[] = $pollarray['exp'];
	$public[] = $pollarray['public'];
	$comments[] = $pollarray['numcomm'];
	$sidvalues[$pollarray['sid']] = $pollarray['value'];
	$votes[] = array($pollarray['sid'] => array('vid'=>$pollarray['vid'],'userid'=>$pollarray['user'],'username'=>$pollarray['username'],'userclass'=>$pollarray['class']));
	$sids[] = $pollarray['sid'];
}
$pstart = @array_unique($pstart);
$pstart = $pstart[0];
if (!$pstart) stderr($REL_LANG->say_by_key('error'), "������ ������ �� ����������");
$pexp = @array_unique($pexp);
$pexp = $pexp[0];
$pquestion = @array_unique($pquestion);
$pquestion = $pquestion[0];
$public = @array_unique($public);
$public = $public[0];
$comments = @array_unique($comments);
$comments = $comments[0];

$sids = @array_unique($sids);
sort($sids);
reset($sids);



stdhead("����� ������");
print '<div border="1" id="polls" style="width: 937px;">
		<ul class="polls_title">
			<li style="margin:0px;"><h1 style="margin:0px; text-align: center;">����� � '.$id.'	</h1><h4 style="margin-bottom: 10px;text-align:center;">������: '.mkprettytime($pstart).(!is_null($pexp)?(($pexp > time())?", �������������: ".mkprettytime($pexp):", <font color=\"red\">��������</font>: ".mkprettytime($pexp)):'').'</h4></li>
		</ul>
		<ul class="polls_title_q">
			<li align="center" class="colheadli" colspan="2"><h3 style="margin-top: 7px;margin-bottom:0;">'.$pquestion.'</h3>'.((get_user_class() >= UC_ADMINISTRATOR)?" <span style=\"margin-left: 335px;\">[<a href=\"".$REL_SEO->make_link('pollsadmin','action','add')."\">������� �����</a>] [<a href=\"".$REL_SEO->make_link('pollsadmin','action','edit','id',$id)."\">�������������</a>] [<a onClick=\"return confirm('�� �������?')\" href=\"".$REL_SEO->make_link('pollsadmin','action','delete','id',$id)."\">�������</a>]":"<span>").'</li>
		</ul>';

foreach ($sids as $sid)
$votesres[$sid] = array();

$voted=0;

foreach($votes as $votetemp)
foreach ($votetemp as $sid => $value)
array_push($votesres[$sid],$value);




foreach ($votesres as $votedrow => $votes) {

	$sidcount[] = $votedrow;
	$sidvals[] = $sidvalues[$votedrow];
	$votecount[$votedrow] = 0;
	$usercode[$votedrow] = '';

	foreach($votes as $vote) {
		//     print $votedrow."<hr />";
		//   print_r ($vote);
		$vid=$vote['vid'];
		$userid=$vote['userid'];
		$user['username']=$vote['username'];
		$user['class']=$vote['userclass'];

		//      print($vote['vid'].$vote['username'].$vote['userclass'].$vote['userid'].",");
		if ($vote['userid'] == $CURUSER['id']) $voted = $votedrow;
		if (!is_null($vid)) $votecount[$votedrow]++;

		if ((($public) || (get_user_class() >= UC_MODERATOR)) && !is_null($vid))
		$usercode[$votedrow] .= "<a href=\"".$REL_SEO->make_link('userdetails','id',$userid,'username',translit($user['username']))."\">".get_user_class_color($user['class'],$user['username'])."</a>".((get_user_class() >= UC_MODERATOR)?" [<a onClick=\"return confirm('������� ���� �����?')\" href=\"".$REL_SEO->make_link('polloverview','deletevote','','vid',$vid)."\">D</a>] ":" ");

		if (($votecount[$votedrow]) >= $maxvotes) $maxvotes = $votecount[$votedrow];

	}
}        $tvotes = array_sum($votecount);

@$percentpervote = 50/$maxvotes;
if (!$percentpervote) $percentpervote=0;

foreach ($sidcount as $sidkey => $vsid){
	@$percent = round($votecount[$vsid]*100/($tvotes));

	if (!$percent) $percent = 0;
	// print("<ul><li class=\"polls_l_div\">");
	if ($vsid == $voted)
	print("<ul><dt class=\"polls_right\"><b>".$sidvals[$sidkey]." - ��� �����</b>");
	elseif (((!is_null($pexp) && ($pexp > time())) || is_null($pexp)) && !$voted) print "<form name=\"voteform\" method=\"post\" action=\"".$REL_SEO->make_link('polloverview','vote','','id',$id)."\"><ul><dt class=\"polls_right\">
  <input type=\"radio\" name=\"vote\" value=\"$vsid\">
  <input type=\"hidden\" name=\"type\" value=\"$ptype\">".$sidvals[$sidkey];

	else print"<ul><dt class=\"polls_right\">".$sidvals[$sidkey];
	print"</dt><dt class=\"polls_left\"><img src=\"./themes/$ss_uri/images/bar_left.gif\"><img src=\"./themes/$ss_uri/images/bar.gif\" height=\"12\" width=\"".round($percentpervote*$votecount[$vsid])."%\"><img src=\"./themes/$ss_uri/images/bar_right.gif\">&nbsp;&nbsp;$percent%, �������:  ".$votecount[$vsid]."<br />".((!$usercode[$vsid])?"����� ��������� ��� ����� �� ���������":$spbegin.$usercode[$vsid].$spend)."</dt></ul>";
}
if (((!is_null($pexp) && ($pexp > time())) || is_null($pexp)) && !$voted) $novote=true;
if ($novote) print"<ul><li><input type=\"submit\" class=\"button\" value=\"���������� �� ���� �������!\" style=\"margin-top: 2px;\"/></li>";
elseif (!is_null($pexp) && ($pexp < time())) print'<ul><li><span style="color:red;">����� ������</span></li>';
elseif ($voted) print'<ul><li><span style="color: red; float: left; padding-right: 5px;">�� ��� ���������� � ���� ������</span></li>';
print'<li align="center">����� �������: '.$tvotes.', ������������: '.$comments.' [<a href="'.$REL_SEO->make_link('polloverview','id',$id).'"><b>���������</b></a>] [<a href="'.$REL_SEO->make_link('polloverview','id',$id).'#comments"><b>��������������</b></a>] [<a href="'.$REL_SEO->make_link('pollsarchive').'"><b>����� �������</b></a>]</li></ul>'.($novote?'</form>':'');

print ('</div>');







// POLLCOMMENTS START

$subres = sql_query("SELECT SUM(1) FROM pollcomments WHERE poll = ".$pid);
$subrow = mysql_fetch_array($subres);
$count = $subrow[0];

$limited = 10;

if (!$count) {

	print("<table style=\"margin-top: 2px;\" cellpadding=\"5\" width=\"100%\">");
	print("<tr><td class=colhead align=\"left\" colspan=\"2\">");
	print("<div style=\"float: left; width: auto;\" align=\"left\"> :: ������ ������������ � ������</div>");
	print("<div align=\"right\"><a href=\"".$REL_SEO->make_link('polloverview','id',$pid)."#comments\" class=altlink_white>{$REL_LANG->say_by_key('add_comment')}</a></div>");
	print("</td></tr><tr><td align=\"center\">");
	print("������������ ���. <a href=\"".$REL_SEO->make_link('polloverview','id',$pid)."#comments\">������� ��������?</a>");
	print("</td></tr></table><br />");

}
else {
	list($pagertop, $pagerbottom, $limit) = pager($limited, $count, $REL_SEO->make_link('polloverview','id',$pid)."&", array(lastpagedefault => 1));

	$subres = sql_query("SELECT pc.id, pc.ip, pc.ratingsum, pc.text, pc.user, pc.added, pc.editedby, pc.editedat, u.avatar, u.warned, ".
                  "u.username, u.title, u.class, u.donor, u.enabled, u.ratingsum AS urating, u.gender, sessions.time AS last_access, e.username AS editedbyname FROM pollcomments AS pc LEFT JOIN users AS u ON pc.user = u.id LEFT JOIN sessions ON pc.user=sessions.uid LEFT JOIN users AS e ON pc.editedby = e.id WHERE poll = " .
                  "".$id." GROUP BY pc.id ORDER BY pc.id $limit") or sqlerr(__FILE__, __LINE__);
	$allrows = array();

	while ($subrow = mysql_fetch_array($subres)) {
		$subrow['subject'] = $pquestion;
		$subrow['link'] = $REL_SEO->make_link('polloverview','id',$id)."#comm{$subrow['id']}";
		$allrows[] = $subrow;
	}




	print("<table class=main cellspacing=\"0\" cellPadding=\"5\" width=\"100%\" >");
	print("<tr><td class=\"colhead\" align=\"center\" >");
	print("<div style=\"float: left; width: auto;\" align=\"left\"> :: ������ ������������</div>");
	print("<div align=\"right\"><a href=\"".$REL_SEO->make_link('polloverview','id',$pid)."#comments\" class=altlink_white>�������� �����������</a></div>");
	print("</td></tr>");

	print("<tr><td>");
	print($pagertop);
	print("</td></tr>");
	print("<tr><td>");
	commenttable($allrows,"pollcomment");
	print("</td></tr>");
	print("<tr><td>");
	print($pagerbottom);
	print("</td></tr>");
	print("</table>");
}



print("<table style=\"margin-top: 2px;\" cellpadding=\"5\" width=\"100%\">");
print("<tr><td class=colhead align=\"left\" colspan=\"2\">  <a name=comments>&nbsp;</a><b>:: �������� ����������� � ������ | ".is_i_notified($id,'pollcomments')."</b></td></tr>");
print("<tr><td width=\"100%\" align=\"center\" >");
//print("���� ���: ");
//print("".$CURUSER['username']."<p>");
print ( "<form name=comment method=\"post\" action=\"".$REL_SEO->make_link('pollcomment','action','add')."\">" );
print ( "<table width=\"100%\"><tr><td align=\"center\">" . textbbcode ( "text") . "</td></tr>" );

print ( "<tr><td  align=\"center\">" );
print ( "<input type=\"hidden\" name=\"pid\" value=\"$id\"/>" );
print ( "<input type=\"submit\" value=\"���������� �����������\" />" );
print ( "</td></tr></table></form>" );
print('</table>');
stdfoot();

?>
