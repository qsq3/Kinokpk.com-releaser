<?php
/**
 * Email sender to administration
 * @license GNU GPLv3 http://opensource.org/licenses/gpl-3.0.html
 * @package Kinokpk.com releaser
 * @author ZonD80 <admin@kinokpk.com>
 * @copyright (C) 2008-now, ZonD80, Germany, TorrentsBook.com
 * @link http://dev.kinokpk.com
 */

require "include/bittorrent.php";
dbconn();
loggedinorreturn();

if (!is_valid_id($_GET["id"]))
stderr($tracker_lang['error'], $tracker_lang['invalid_id']);

$id = (int) $_GET["id"];

$res = sql_query("SELECT username, class, email FROM users WHERE id=$id");
$arr = mysql_fetch_assoc($res) or stderr($tracker_lang['error'], "��� ������ ������������.");
$username = $arr["username"];
if ($arr["class"] < UC_MODERATOR)
stderr($tracker_lang['error'], $tracker_lang['access_denied']);

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	$to = $arr["email"];

	$from = substr(trim($_POST["from"]), 0, 80);
	if ($from == "") $from = "��������";

	$from_email = substr(trim($_POST["from_email"]), 0, 80);
	if ($from_email == "") $from_email = $CACHEARRAY['siteemail'];
	if (!strpos($from_email, "@")) stderr($tracker_lang['error'], "�������� e-mail ����� �� ����� �� ������.");

	$from = "$from <$from_email>";

	$subject = substr(trim($_POST["subject"]), 0, 80);
	if ($subject == "") $subject = "(��� ����)";

	$message = trim($_POST["message"]);
	if ($message == "") stderr($tracker_lang['error'], "�� �� ����� ���������!");

	$message = "��������� ���������� �� ������������ ".$CURUSER['username']." � " . date("Y-m-d H:i:s") . " GMT.\n" .
		"��������: ������� �� ��� ������, �� ��������� ��� e-mail �����.\n" .
		"---------------------------------------------------------------------\n\n" .
	$message . "\n\n" .
		"---------------------------------------------------------------------\n{$CACHEARRAY['sitename']}\n";

	$success = sent_mail($to, $CACHEARRAY['sitename'], $CACHEARRAY['siteemail'], $subject, $message);

	if ($success)
	stderr($tracker_lang['success'], "E-mail ������� ���������.");
	else
	stderr($tracker_lang['error'], "������ �� ����� ���� ����������. ����������, ���������� �����.");
}

stdhead("��������� e-mail");
?>